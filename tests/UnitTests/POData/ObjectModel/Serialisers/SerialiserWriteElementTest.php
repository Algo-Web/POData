<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use Mockery as m;
use POData\Common\ODataException;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
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
        $propContent->properties = [new ODataProperty(), new ODataProperty(), new ODataProperty(), new ODataProperty(),
            new ODataProperty(), new ODataProperty(), new ODataProperty()];
        $propContent->properties[0]->name = 'CustomerID';
        $propContent->properties[0]->typeName = 'Edm.String';
        $propContent->properties[0]->value = '1';
        $propContent->properties[1]->name = 'CustomerGuid';
        $propContent->properties[1]->typeName = 'Edm.Guid';
        $propContent->properties[1]->value = '123e4567-e89b-12d3-a456-426655440000';
        $propContent->properties[2]->name = 'CustomerName';
        $propContent->properties[2]->typeName = 'Edm.String';
        $propContent->properties[3]->name = 'Country';
        $propContent->properties[3]->typeName = 'Edm.String';
        $propContent->properties[4]->name = 'Rating';
        $propContent->properties[4]->typeName = 'Edm.Int32';
        $propContent->properties[5]->name = 'Photo';
        $propContent->properties[5]->typeName = 'Edm.Binary';
        $propContent->properties[6]->name = 'Address';
        $propContent->properties[6]->typeName = 'Address';

        $objectResult = new ODataEntry();
        $objectResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $objectResult->title = 'Customer';
        $objectResult->type = 'Customer';
        $objectResult->editLink = 'Customers(CustomerID=\'1\',CustomerGuid'
                                  .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $objectResult->propertyContent = $propContent;
        $objectResult->links[] = $link;
        $objectResult->resourceSetName = 'Customers';
        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
        $numProperties = count($objectResult->propertyContent->properties);
        for ($i = 0; $i < $numProperties; $i++) {
            $propName = $objectResult->propertyContent->properties[$i]->name;
            $objectVal = $objectResult->propertyContent->properties[$i]->value;
            $ironicVal = $ironicResult->propertyContent->properties[$i]->value;
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
        $propContent->properties = [new ODataProperty(), new ODataProperty(), new ODataProperty(), new ODataProperty(),
            new ODataProperty(), new ODataProperty(), new ODataProperty()];
        $propContent->properties[0]->name = 'OrderID';
        $propContent->properties[0]->typeName = 'Edm.Int32';
        $propContent->properties[0]->value = '1';
        $propContent->properties[1]->name = 'OrderDate';
        $propContent->properties[1]->typeName = 'Edm.DateTime';
        $propContent->properties[2]->name = 'DeliveryDate';
        $propContent->properties[2]->typeName = 'Edm.DateTime';
        $propContent->properties[3]->name = 'ShipName';
        $propContent->properties[3]->typeName = 'Edm.String';
        $propContent->properties[4]->name = 'ItemCount';
        $propContent->properties[4]->typeName = 'Edm.Int32';
        $propContent->properties[5]->name = 'QualityRate';
        $propContent->properties[5]->typeName = 'Edm.Int32';
        $propContent->properties[6]->name = 'Price';
        $propContent->properties[6]->typeName = 'Edm.Double';

        $addressContent = new ODataPropertyContent();
        $addressContent->properties = [new ODataProperty(), new ODataProperty(), new ODataProperty(),
            new ODataProperty(), new ODataProperty(), new ODataProperty()];
        $addressContent->properties[0]->name = 'HouseNumber';
        $addressContent->properties[0]->typeName = 'Edm.String';
        $addressContent->properties[0]->value = '1';
        $addressContent->properties[1]->name = 'LineNumber';
        $addressContent->properties[1]->typeName = 'Edm.Int32';
        $addressContent->properties[2]->name = 'LineNumber2';
        $addressContent->properties[2]->typeName = 'Edm.Int32';
        $addressContent->properties[3]->name = 'StreetName';
        $addressContent->properties[3]->typeName = 'Edm.String';
        $addressContent->properties[4]->name = 'IsValid';
        $addressContent->properties[4]->typeName = 'Edm.Boolean';
        $addressContent->properties[5]->name = 'Address2';
        $addressContent->properties[5]->typeName = 'Address2';

        $linkPropContent = new ODataPropertyContent();
        $linkPropContent->properties = [new ODataProperty(), new ODataProperty(), new ODataProperty(),
            new ODataProperty(), new ODataProperty(), new ODataProperty(), new ODataProperty()];
        $linkPropContent->properties[0]->name = 'CustomerID';
        $linkPropContent->properties[0]->typeName = 'Edm.String';
        $linkPropContent->properties[0]->value = '1';
        $linkPropContent->properties[1]->name = 'CustomerGuid';
        $linkPropContent->properties[1]->typeName = 'Edm.Guid';
        $linkPropContent->properties[1]->value = '123e4567-e89b-12d3-a456-426655440000';
        $linkPropContent->properties[2]->name = 'CustomerName';
        $linkPropContent->properties[2]->typeName = 'Edm.String';
        $linkPropContent->properties[3]->name = 'Country';
        $linkPropContent->properties[3]->typeName = 'Edm.String';
        $linkPropContent->properties[4]->name = 'Rating';
        $linkPropContent->properties[4]->typeName = 'Edm.Int32';
        $linkPropContent->properties[5]->name = 'Photo';
        $linkPropContent->properties[5]->typeName = 'Edm.Binary';
        $linkPropContent->properties[6]->name = 'Address';
        $linkPropContent->properties[6]->typeName = 'Address';
        $linkPropContent->properties[6]->value = $addressContent;
        $linkResult = new ODataEntry();
        $linkResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                          .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $linkResult->title = 'Customer';
        $linkResult->editLink = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $linkResult->type = 'Customer';
        $linkResult->links = [new ODataLink()];
        $linkResult->links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
        $linkResult->links[0]->title = 'Orders';
        $linkResult->links[0]->type = 'application/atom+xml;type=feed';
        $linkResult->links[0]->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567'
                                     .'-e89b-12d3-a456-426655440000\')/Orders';
        $linkResult->resourceSetName = 'Customers';
        $linkResult->propertyContent = $linkPropContent;

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
        $objectResult->title = 'Order';
        $objectResult->type = 'Order';
        $objectResult->editLink = 'Orders(OrderID=1)';
        $objectResult->propertyContent = $propContent;
        $objectResult->links = $links;
        $objectResult->resourceSetName = 'Orders';
        $ironicResult = $ironic->writeTopLevelElement($result);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteWithNonNullBagProperty()
    {
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
        $propContent->properties = [new ODataProperty(), new ODataProperty(), new ODataProperty(),
            new ODataProperty(), new ODataProperty()];
        $propContent->properties[0]->name = 'EmployeeID';
        $propContent->properties[0]->typeName = 'Edm.String';
        $propContent->properties[0]->value = 'Cave Johnson';
        $propContent->properties[1]->name = 'FirstName';
        $propContent->properties[1]->typeName = 'Edm.String';
        $propContent->properties[2]->name = 'LastName';
        $propContent->properties[2]->typeName = 'Edm.String';
        $propContent->properties[3]->name = 'ReportsTo';
        $propContent->properties[3]->typeName = 'Edm.Int32';
        $propContent->properties[4]->name = 'Emails';
        $propContent->properties[4]->typeName = 'Collection(Edm.String)';
        $propContent->properties[4]->value = $emailBag;


        $mediaLink = new ODataMediaLink(
            'Employee',
            '/$value',
            'Employees(EmployeeID=\'Cave+Johnson\')/$value',
            '*/*',
            ''
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
        $objectResult->title = 'Employee';
        $objectResult->type = 'Employee';
        $objectResult->editLink = 'Employees(EmployeeID=\'Cave+Johnson\')';
        $objectResult->isMediaLinkEntry = true;
        $objectResult->mediaLink = $mediaLink;
        $objectResult->mediaLinks[] = $mediaArray;
        $objectResult->propertyContent = $propContent;
        $objectResult->links = $links;
        $objectResult->resourceSetName = 'Employees';
        $ironicResult = $ironic->writeTopLevelElement($result);

        // zero out etag values
        $ironicResult->mediaLink->eTag = '';
        $ironicResult->mediaLinks[0]->eTag = '';

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testExpandEmployeeAttachedManagerWithAllProperties()
    {
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
            ''
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
        $propContent->properties[] = $contentProp1;
        $propContent->properties[] = $contentProp2;
        $propContent->properties[] = $contentProp3;
        $propContent->properties[] = $contentProp4;
        $propContent->properties[] = $contentProp5;

        $managerMedia1 = new ODataMediaLink(
            'Employee',
            '/$value',
            'Employees(EmployeeID=\'Cave+Johnson\')/$value',
            '*/*',
            ''
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
        $manager->title = 'Employee';
        $manager->editLink = 'Employees(EmployeeID=\'Cave+Johnson\')';
        $manager->mediaLink = $managerMedia1;
        $manager->mediaLinks = [$managerMedia2];
        $manager->propertyContent = $propContent;
        $manager->type = 'Employee';
        $manager->isMediaLinkEntry = true;
        $manager->links = [$managerLink1, $managerLink2];
        $manager->resourceSetName = 'Employees';

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
        $objContent->properties[] = $objContentProperty;

        $objectResult = new ODataEntry();
        $objectResult->id = 'http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')';
        $objectResult->title = 'Employee';
        $objectResult->editLink = 'Employees(EmployeeID=\'Bruce\')';
        $objectResult->type = 'Employee';
        $objectResult->propertyContent = $objContent;
        $objectResult->isMediaLinkEntry = true;
        $objectResult->mediaLink = $media1;
        $objectResult->mediaLinks = [$media2];
        $objectResult->links[] = $link;
        $objectResult->resourceSetName = 'Employees';
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
