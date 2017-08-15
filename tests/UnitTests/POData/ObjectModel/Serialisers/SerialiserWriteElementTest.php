<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\Common\ODataException;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use Symfony\Component\HttpFoundation\ParameterBag;
use UnitTests\POData\Facets\NorthWind1\Address4;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\Employee2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;

class SerialiserWriteElementTest extends SerialiserTestBase
{
    public function testCompareWriteSingleModelWithPropertiesNulled()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $result = new QueryResult();
        $result->results = $model;

        $link = new ODataLink();
        $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
        $link->type = 'application/atom+xml;type=feed';
        $link->title = 'Orders';
        $link->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['CustomerID' => new ODataProperty(), 'CustomerGuid' => new ODataProperty(),
            'CustomerName' => new ODataProperty(), 'Country' => new ODataProperty(), 'Rating' => new ODataProperty(),
            'Photo' => new ODataProperty(), 'Address' => new ODataProperty()];
        $propContent->properties['CustomerID']->name = 'CustomerID';
        $propContent->properties['CustomerID']->typeName = 'Edm.String';
        $propContent->properties['CustomerID']->value = '1';
        $propContent->properties['CustomerGuid']->name = 'CustomerGuid';
        $propContent->properties['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent->properties['CustomerGuid']->value = '123e4567-e89b-12d3-a456-426655440000';
        $propContent->properties['CustomerName']->name = 'CustomerName';
        $propContent->properties['CustomerName']->typeName = 'Edm.String';
        $propContent->properties['Country']->name = 'Country';
        $propContent->properties['Country']->typeName = 'Edm.String';
        $propContent->properties['Rating']->name = 'Rating';
        $propContent->properties['Rating']->typeName = 'Edm.Int32';
        $propContent->properties['Photo']->name = 'Photo';
        $propContent->properties['Photo']->typeName = 'Edm.Binary';
        $propContent->properties['Address']->name = 'Address';
        $propContent->properties['Address']->typeName = 'Address';

        $objectResult = new ODataEntry();
        $objectResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $objectResult->title = new ODataTitle('Customer');
        $objectResult->type = new ODataCategory('Customer');
        $objectResult->editLink = new ODataLink();
        $objectResult->editLink->url = 'Customers(CustomerID=\'1\',CustomerGuid'
                                       .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $objectResult->editLink->name = 'edit';
        $objectResult->editLink->title = 'Customer';
        $objectResult->propertyContent = $propContent;
        $objectResult->links[] = $link;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->updated = '2017-01-01T00:00:00+00:00';

        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
        $numProperties = count($objectResult->propertyContent->properties);
        $keys = array_keys($objectResult->propertyContent->properties);
        for ($i = 0; $i < $numProperties; $i++) {
            $propName = $objectResult->propertyContent->properties[$keys[$i]]->name;
            $objectVal = $objectResult->propertyContent->properties[$keys[$i]]->value;
            $ironicVal = $ironicResult->propertyContent->properties[$keys[$i]]->value;
            $this->assertEquals(
                isset($objectVal),
                isset($ironicVal),
                'Values for' . $propName . 'differently null.  '.$i
            );
            $this->assertEquals(
                is_string($objectVal),
                is_string($ironicVal),
                'Values for '. $propName .'not identical'
            );
        }
    }

    public function testExpandOrderAttachedCustomer()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)?$expand=Customer');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)?$expand=Customer');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Customer');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('canSelectAllProperties')->andReturn(true);
        $node->shouldReceive('findNode')->andReturn($expandNode);

        $ironic->getRequest()->setRootProjectionNode($node);

        $address = new Address4();
        $address->IsPrimary = true;
        $address->HouseNumber = 1;
        $address->IsValid = null;

        $cust = new Customer2();
        $cust->CustomerID = 1;
        $cust->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $cust->Address = $address;

        $order = new Order2();
        $order->OrderID = 1;
        $order->Customer = $cust;

        $result = new QueryResult();
        $result->results = $order;

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['OrderID' => new ODataProperty(), 'OrderDate' => new ODataProperty(),
            'DeliveryDate' => new ODataProperty(), 'ShipName' => new ODataProperty(),
            'ItemCount' => new ODataProperty(), 'QualityRate' => new ODataProperty(), 'Price' => new ODataProperty()];
        $propContent->properties['OrderID']->name = 'OrderID';
        $propContent->properties['OrderID']->typeName = 'Edm.Int32';
        $propContent->properties['OrderID']->value = '1';
        $propContent->properties['OrderDate']->name = 'OrderDate';
        $propContent->properties['OrderDate']->typeName = 'Edm.DateTime';
        $propContent->properties['DeliveryDate']->name = 'DeliveryDate';
        $propContent->properties['DeliveryDate']->typeName = 'Edm.DateTime';
        $propContent->properties['ShipName']->name = 'ShipName';
        $propContent->properties['ShipName']->typeName = 'Edm.String';
        $propContent->properties['ItemCount']->name = 'ItemCount';
        $propContent->properties['ItemCount']->typeName = 'Edm.Int32';
        $propContent->properties['QualityRate']->name = 'QualityRate';
        $propContent->properties['QualityRate']->typeName = 'Edm.Int32';
        $propContent->properties['Price']->name = 'Price';
        $propContent->properties['Price']->typeName = 'Edm.Double';

        $addressContent = new ODataPropertyContent();
        $addressContent->properties = ['HouseNumber' => new ODataProperty(), 'LineNumber' => new ODataProperty(),
            'LineNumber2' => new ODataProperty(), 'StreetName' => new ODataProperty(), 'IsValid' => new ODataProperty(),
            'Address2' => new ODataProperty()];
        $addressContent->properties['HouseNumber']->name = 'HouseNumber';
        $addressContent->properties['HouseNumber']->typeName = 'Edm.String';
        $addressContent->properties['HouseNumber']->value = '1';
        $addressContent->properties['LineNumber']->name = 'LineNumber';
        $addressContent->properties['LineNumber']->typeName = 'Edm.Int32';
        $addressContent->properties['LineNumber2']->name = 'LineNumber2';
        $addressContent->properties['LineNumber2']->typeName = 'Edm.Int32';
        $addressContent->properties['StreetName']->name = 'StreetName';
        $addressContent->properties['StreetName']->typeName = 'Edm.String';
        $addressContent->properties['IsValid']->name = 'IsValid';
        $addressContent->properties['IsValid']->typeName = 'Edm.Boolean';
        $addressContent->properties['Address2']->name = 'Address2';
        $addressContent->properties['Address2']->typeName = 'Address2';

        $linkPropContent = new ODataPropertyContent();
        $linkPropContent->properties = ['CustomerID' => new ODataProperty(), 'CustomerGuid' => new ODataProperty(),
            'CustomerName' => new ODataProperty(), 'Country' => new ODataProperty(), 'Rating' => new ODataProperty(),
            'Photo' => new ODataProperty(), 'Address' => new ODataProperty()];
        $linkPropContent->properties['CustomerID']->name = 'CustomerID';
        $linkPropContent->properties['CustomerID']->typeName = 'Edm.String';
        $linkPropContent->properties['CustomerID']->value = '1';
        $linkPropContent->properties['CustomerGuid']->name = 'CustomerGuid';
        $linkPropContent->properties['CustomerGuid']->typeName = 'Edm.Guid';
        $linkPropContent->properties['CustomerGuid']->value = '123e4567-e89b-12d3-a456-426655440000';
        $linkPropContent->properties['CustomerName']->name = 'CustomerName';
        $linkPropContent->properties['CustomerName']->typeName = 'Edm.String';
        $linkPropContent->properties['Country']->name = 'Country';
        $linkPropContent->properties['Country']->typeName = 'Edm.String';
        $linkPropContent->properties['Rating']->name = 'Rating';
        $linkPropContent->properties['Rating']->typeName = 'Edm.Int32';
        $linkPropContent->properties['Photo']->name = 'Photo';
        $linkPropContent->properties['Photo']->typeName = 'Edm.Binary';
        $linkPropContent->properties['Address']->name = 'Address';
        $linkPropContent->properties['Address']->typeName = 'Address';
        $linkPropContent->properties['Address']->value = $addressContent;

        $linkResult = new ODataEntry();
        $linkResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                          .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $linkResult->title = new ODataTitle('Customer');
        $linkResult->editLink = new ODataLink();
        $linkResult->editLink->url = 'Customers(CustomerID=\'1\','
                                     .'CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $linkResult->editLink->name = 'edit';
        $linkResult->editLink->title = 'Customer';
        $linkResult->type = new ODataCategory('Customer');
        $linkResult->links = [new ODataLink()];
        $linkResult->links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
        $linkResult->links[0]->title = 'Orders';
        $linkResult->links[0]->type = 'application/atom+xml;type=feed';
        $linkResult->links[0]->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567'
                                     .'-e89b-12d3-a456-426655440000\')/Orders';
        $linkResult->resourceSetName = 'Customers';
        $linkResult->propertyContent = $linkPropContent;
        $linkResult->updated = '2017-01-01T00:00:00+00:00';

        $links = [new ODataLink(), new ODataLink()];
        $links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer';
        $links[0]->title = 'Customer';
        $links[0]->type = 'application/atom+xml;type=entry';
        $links[0]->url = 'Orders(OrderID=1)/Customer';
        $links[0]->isCollection = false;
        $links[0]->isExpanded = true;
        $links[0]->expandedResult = $linkResult;
        $links[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details';
        $links[1]->title = 'Order_Details';
        $links[1]->type = 'application/atom+xml;type=feed';
        $links[1]->url = 'Orders(OrderID=1)/Order_Details';

        $objectResult = new ODataEntry();
        $objectResult->id = 'http://localhost/odata.svc/Orders(OrderID=1)';
        $objectResult->title = new ODataTitle('Order');
        $objectResult->type = new ODataCategory('Order');
        $objectResult->editLink = new ODataLink();
        $objectResult->editLink->url = 'Orders(OrderID=1)';
        $objectResult->editLink->name = 'edit';
        $objectResult->editLink->title = 'Order';
        $objectResult->propertyContent = $propContent;
        $objectResult->links = $links;
        $objectResult->resourceSetName = 'Orders';
        $objectResult->updated = '2017-01-01T00:00:00+00:00';

        $ironicResult = $ironic->writeTopLevelElement($result);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteWithNonNullBagProperty()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/Employees(EmployeeID=\'Bruce\')');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $caveJohnson = new Employee2();
        $caveJohnson->EmployeeID = 'Cave Johnson';
        $caveJohnson->Emails = ['foo', 'bar'];
        $caveJohnson->TumbNail_48X48 = 'foobar';

        $result = new QueryResult();
        $result->results = $caveJohnson;

        $emailBag = new ODataBagContent();
        $emailBag->propertyContents = ['foo', 'bar'];

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['EmployeeID' => new ODataProperty(), 'FirstName' => new ODataProperty(),
            'LastName' => new ODataProperty(), 'ReportsTo' => new ODataProperty(), 'Emails' => new ODataProperty()];
        $propContent->properties['EmployeeID']->name = 'EmployeeID';
        $propContent->properties['EmployeeID']->typeName = 'Edm.String';
        $propContent->properties['EmployeeID']->value = 'Cave Johnson';
        $propContent->properties['FirstName']->name = 'FirstName';
        $propContent->properties['FirstName']->typeName = 'Edm.String';
        $propContent->properties['LastName']->name = 'LastName';
        $propContent->properties['LastName']->typeName = 'Edm.String';
        $propContent->properties['ReportsTo']->name = 'ReportsTo';
        $propContent->properties['ReportsTo']->typeName = 'Edm.Int32';
        $propContent->properties['Emails']->name = 'Emails';
        $propContent->properties['Emails']->typeName = 'Collection(Edm.String)';
        $propContent->properties['Emails']->value = $emailBag;

        $mediaLink = new ODataMediaLink(
            'Employee',
            '/$value',
            'Employees(EmployeeID=\'Cave+Johnson\')/$value',
            '*/*',
            '',
            'edit-media'
        );

        $mediaArray = new ODataMediaLink(
            'TumbNail_48X48',
            'Employees(EmployeeID=\'Cave+Johnson\')/TumbNail_48X48',
            'Employees(EmployeeID=\'Cave+Johnson\')/TumbNail_48X48',
            'application/octet-stream',
            ''
        );

        $links = [new ODataLink(), new ODataLink()];
        $links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager';
        $links[0]->title = 'Manager';
        $links[0]->type = 'application/atom+xml;type=entry';
        $links[0]->url = 'Employees(EmployeeID=\'Cave+Johnson\')/Manager';
        $links[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates';
        $links[1]->title = 'Subordinates';
        $links[1]->type = 'application/atom+xml;type=feed';
        $links[1]->url = 'Employees(EmployeeID=\'Cave+Johnson\')/Subordinates';

        $objectResult = new ODataEntry();
        $objectResult->id = 'http://localhost/odata.svc/Employees(EmployeeID=\'Cave+Johnson\')';
        $objectResult->title = new ODataTitle('Employee');
        $objectResult->type = new ODataCategory('Employee');
        $objectResult->editLink = new ODataLink();
        $objectResult->editLink->url = 'Employees(EmployeeID=\'Cave+Johnson\')';
        $objectResult->editLink->name = 'edit';
        $objectResult->editLink->title = 'Employee';
        $objectResult->isMediaLinkEntry = true;
        $objectResult->mediaLink = $mediaLink;
        $objectResult->mediaLinks[] = $mediaArray;
        $objectResult->propertyContent = $propContent;
        $objectResult->links = $links;
        $objectResult->resourceSetName = 'Employees';
        $objectResult->updated = '2017-01-01T00:00:00+00:00';

        $ironicResult = $ironic->writeTopLevelElement($result);

        // zero out etag values
        $ironicResult->mediaLink->eTag = '';
        $ironicResult->mediaLinks[0]->eTag = '';

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testExpandEmployeeAttachedManagerWithAllProperties()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/Employees(EmployeeID=\'Bruce\')?$expand=Manager');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')?$expand=Manager');
        $request->request = new ParameterBag([ '$expand' => 'Manager']);

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $iType = new StringType();

        $rType = m::mock(ResourcePrimitiveType::class);
        $rType->shouldReceive('getFullName')->andReturn('Edm.String');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getResourceType')->andReturn($rType);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(true, false);

        $mailNode = m::mock(ExpandedProjectionNode::class);
        $mailNode->shouldReceive('getResourceProperty')->andReturn($rProp);
        $mailNode->shouldReceive('getPropertyName')->andReturn('Emails');
        $mailNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $mailNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $mailNode->shouldReceive('findNode')->andReturn(null);

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('getResourceProperty')->andReturn($rProp);
        $expandNode->shouldReceive('getPropertyName')->andReturn('Manager');
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(true);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Customer');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true, true, true, true);
        $node->shouldReceive('canSelectAllProperties')->andReturn(false);
        $node->shouldReceive('findNode')->andReturn($expandNode);
        $node->shouldReceive('getChildNodes')->andReturn([$expandNode, $mailNode])->atLeast(1);

        $ironic->getRequest()->setRootProjectionNode($node);

        $caveJohnson = new Employee2();
        $caveJohnson->EmployeeID = 'Cave Johnson';
        $caveJohnson->Emails = ['foo', 'bar'];
        $caveJohnson->TumbNail_48X48 = 'foobar';

        $emp = new Employee2();
        $emp->Manager = $caveJohnson;
        $emp->EmployeeID = 'Bruce';
        $emp->TumbNail_48X48 = 'foobar';
        $emp->Emails = null;

        $result = new QueryResult();
        $result->results = $emp;

        $media1 = new ODataMediaLink(
            'Employee',
            '/$value',
            'Employees(EmployeeID=\'Bruce\')/$value',
            '*/*',
            '',
            'edit-media'
        );
        $media2 = new ODataMediaLink(
            'TumbNail_48X48',
            'Employees(EmployeeID=\'Bruce\')/TumbNail_48X48',
            'Employees(EmployeeID=\'Bruce\')/TumbNail_48X48',
            'application/octet-stream',
            ''
        );

        $contentProp1 = new ODataProperty();
        $contentProp1->name = 'EmployeeID';
        $contentProp1->typeName = 'Edm.String';
        $contentProp1->value = 'Cave Johnson';
        $contentProp2 = new ODataProperty();
        $contentProp2->name = 'FirstName';
        $contentProp2->typeName = 'Edm.String';
        $contentProp3 = new ODataProperty();
        $contentProp3->name = 'LastName';
        $contentProp3->typeName = 'Edm.String';
        $contentProp4 = new ODataProperty();
        $contentProp4->name = 'ReportsTo';
        $contentProp4->typeName = 'Edm.Int32';
        $contentProp5 = new ODataProperty();
        $contentProp5->name = 'Emails';
        $contentProp5->typeName = 'Collection(Edm.String)';
        $contentProp5->value = new ODataBagContent();
        $contentProp5->value->propertyContents = ['foo', 'bar'];

        $propContent = new ODataPropertyContent();
        $propContent->properties['EmployeeID'] = $contentProp1;
        $propContent->properties['FirstName'] = $contentProp2;
        $propContent->properties['LastName'] = $contentProp3;
        $propContent->properties['ReportsTo'] = $contentProp4;
        $propContent->properties['Emails'] = $contentProp5;

        $managerMedia1 = new ODataMediaLink(
            'Employee',
            '/$value',
            'Employees(EmployeeID=\'Cave+Johnson\')/$value',
            '*/*',
            '',
            'edit-media'
        );
        $managerMedia2 = new ODataMediaLink(
            'TumbNail_48X48',
            'Employees(EmployeeID=\'Cave+Johnson\')/TumbNail_48X48',
            'Employees(EmployeeID=\'Cave+Johnson\')/TumbNail_48X48',
            'application/octet-stream',
            ''
        );

        $managerLink1 = new ODataLink();
        $managerLink1->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager';
        $managerLink1->title = 'Manager';
        $managerLink1->type = 'application/atom+xml;type=entry';
        $managerLink1->url = 'Employees(EmployeeID=\'Cave+Johnson\')/Manager';
        $managerLink2 = new ODataLink();
        $managerLink2->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates';
        $managerLink2->title = 'Subordinates';
        $managerLink2->type = 'application/atom+xml;type=feed';
        $managerLink2->url = 'Employees(EmployeeID=\'Cave+Johnson\')/Subordinates';

        $manager = new ODataEntry();
        $manager->id = 'http://localhost/odata.svc/Employees(EmployeeID=\'Cave+Johnson\')';
        $manager->title = new ODataTitle('Employee');
        $manager->editLink = new ODataLink();
        $manager->editLink->url = 'Employees(EmployeeID=\'Cave+Johnson\')';
        $manager->editLink->name = 'edit';
        $manager->editLink->title = 'Employee';
        $manager->mediaLink = $managerMedia1;
        $manager->mediaLinks = [$managerMedia2];
        $manager->propertyContent = $propContent;
        $manager->type = new ODataCategory('Employee');
        $manager->isMediaLinkEntry = true;
        $manager->links = [$managerLink1, $managerLink2];
        $manager->resourceSetName = 'Employees';
        $manager->updated = '2017-01-01T00:00:00+00:00';

        $link = new ODataLink();
        $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager';
        $link->title = 'Manager';
        $link->type = 'application/atom+xml;type=entry';
        $link->url = 'Employees(EmployeeID=\'Bruce\')/Manager';
        $link->isCollection = false;
        $link->isExpanded = true;
        $link->expandedResult = $manager;

        $objContentProperty = new ODataProperty();
        $objContentProperty->name = 'Emails';
        $objContentProperty->typeName = 'Collection(Edm.String)';

        $objContent = new ODataPropertyContent();
        $objContent->properties['Emails'] = $objContentProperty;

        $objectResult = new ODataEntry();
        $objectResult->id = 'http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')';
        $objectResult->title = new ODataTitle('Employee');
        $objectResult->editLink = new ODataLink();
        $objectResult->editLink->url = 'Employees(EmployeeID=\'Bruce\')';
        $objectResult->editLink->name = 'edit';
        $objectResult->editLink->title = 'Employee';
        $objectResult->type = new ODataCategory('Employee');
        $objectResult->propertyContent = $objContent;
        $objectResult->isMediaLinkEntry = true;
        $objectResult->mediaLink = $media1;
        $objectResult->mediaLinks = [$media2];
        $objectResult->links[] = $link;
        $objectResult->resourceSetName = 'Employees';
        $objectResult->updated = '2017-01-01T00:00:00+00:00';

        $ironicResult = $ironic->writeTopLevelElement($result);

        // flatten, remove and zero out etags - haven't yet figured out how to freeze etag generation
        $ironicResult->mediaLinks[0]->eTag = '';
        $ironicResult->mediaLink->eTag = '';
        $ironicResult->links[0]->expandedResult->mediaLinks[0]->eTag = '';
        $ironicResult->links[0]->expandedResult->mediaLink->eTag = '';

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteSingleModelWithKeyPropertiesNulled()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();

        $result = new QueryResult();
        $result->results = $model;

        $expected = 'The serialized resource of type Customer has a null value in key member \'CustomerID\'. Null'
                    .' values are not supported in key members.';
        $expectedExceptionClass = ODataException::class;
        $actual = null;
        $actualExceptionClass = null;

        try {
            $ironic->writeTopLevelElement($result);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param $request
     * @return array
     */
    private function setUpDataServiceDeps($request)
    {
        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);

        $meta = NorthWindMetadata::Create();
        $query = m::mock(IQueryProvider::class);

        return [$host, $meta, $query];
    }

    /**
     * @param $query
     * @param $meta
     * @param $host
     * @return array
     */
    private function setUpSerialisers($query, $meta, $host)
    {
        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $ironic = new IronicSerialiser($service, $processor->getRequest());
        return $ironic;
    }
}
