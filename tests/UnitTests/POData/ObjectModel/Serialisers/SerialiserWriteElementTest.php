<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\Common\ODataException;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\IncomingRequest;
use POData\OperationContext\Web\WebOperationContext as OperationContextAdapter;
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
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $model               = new Customer2();
        $model->CustomerID   = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $result          = new QueryResult();
        $result->results = $model;

        $link               = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders',
            'Orders',
            'application/atom+xml;type=feed',
            'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders',
            true
        );

        $propContent             = new ODataPropertyContent(
            [
                'CustomerID' => new ODataProperty(),
                'CustomerGuid' => new ODataProperty(),
                'CustomerName' => new ODataProperty(),
                'Country' => new ODataProperty(),
                'Rating' => new ODataProperty(),
                'Photo' => new ODataProperty(),
                'Address' => new ODataProperty()
            ]
        );
        $propContent->properties['CustomerID']->name       = 'CustomerID';
        $propContent->properties['CustomerID']->typeName   = 'Edm.String';
        $propContent->properties['CustomerID']->value      = '1';
        $propContent->properties['CustomerGuid']->name     = 'CustomerGuid';
        $propContent->properties['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent->properties['CustomerGuid']->value    = '123e4567-e89b-12d3-a456-426655440000';
        $propContent->properties['CustomerName']->name     = 'CustomerName';
        $propContent->properties['CustomerName']->typeName = 'Edm.String';
        $propContent->properties['Country']->name          = 'Country';
        $propContent->properties['Country']->typeName      = 'Edm.String';
        $propContent->properties['Rating']->name           = 'Rating';
        $propContent->properties['Rating']->typeName       = 'Edm.Int32';
        $propContent->properties['Photo']->name            = 'Photo';
        $propContent->properties['Photo']->typeName        = 'Edm.Binary';
        $propContent->properties['Address']->name          = 'Address';
        $propContent->properties['Address']->typeName      = 'Address';

        $objectResult     = new ODataEntry();
        $objectResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $objectResult->title         = new ODataTitle('Customer');
        $objectResult->type          = new ODataCategory('NorthWind.Customer');
        $objectResult->editLink      = new ODataLink('edit', 'Customer', null, 'Customers(CustomerID=\'1\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')');
        $objectResult->propertyContent = $propContent;
        $objectResult->links[]         = $link;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->updated         = '2017-01-01T00:00:00+00:00';
        $objectResult->baseURI         = 'http://localhost/odata.svc/';

        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
        $numProperties = count($objectResult->propertyContent->properties);
        $keys          = array_keys($objectResult->propertyContent->properties);
        for ($i = 0; $i < $numProperties; $i++) {
            $propName  = $objectResult->propertyContent->properties[$keys[$i]]->name;
            $objectVal = $objectResult->propertyContent->properties[$keys[$i]]->value;
            $ironicVal = $ironicResult->propertyContent->properties[$keys[$i]]->value;
            $this->assertEquals(
                isset($objectVal),
                isset($ironicVal),
                'Values for' . $propName . 'differently null.  ' . $i
            );
            $this->assertEquals(
                is_string($objectVal),
                is_string($ironicVal),
                'Values for ' . $propName . 'not identical'
            );
        }
    }

    public function testExpandOrderAttachedCustomer()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)?$expand=Customer');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)?$expand=Customer');

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

        $address              = new Address4();
        $address->IsPrimary   = true;
        $address->HouseNumber = 1;
        $address->IsValid     = null;

        $cust               = new Customer2();
        $cust->CustomerID   = 1;
        $cust->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $cust->Address      = $address;

        $order           = new Order2();
        $order->OrderID  = 1;
        $order->Customer = $cust;

        $result          = new QueryResult();
        $result->results = $order;

        $propContent             = new ODataPropertyContent(
            [
                'OrderID' => new ODataProperty(),
                'OrderDate' => new ODataProperty(),
                'DeliveryDate' => new ODataProperty(),
                'ShipName' => new ODataProperty(),
                'ItemCount' => new ODataProperty(),
                'QualityRate' => new ODataProperty(),
                'Price' => new ODataProperty()
            ]
        );
        $propContent->properties['OrderID']->name          = 'OrderID';
        $propContent->properties['OrderID']->typeName      = 'Edm.Int32';
        $propContent->properties['OrderID']->value         = '1';
        $propContent->properties['OrderDate']->name        = 'OrderDate';
        $propContent->properties['OrderDate']->typeName    = 'Edm.DateTime';
        $propContent->properties['DeliveryDate']->name     = 'DeliveryDate';
        $propContent->properties['DeliveryDate']->typeName = 'Edm.DateTime';
        $propContent->properties['ShipName']->name         = 'ShipName';
        $propContent->properties['ShipName']->typeName     = 'Edm.String';
        $propContent->properties['ItemCount']->name        = 'ItemCount';
        $propContent->properties['ItemCount']->typeName    = 'Edm.Int32';
        $propContent->properties['QualityRate']->name      = 'QualityRate';
        $propContent->properties['QualityRate']->typeName  = 'Edm.Int32';
        $propContent->properties['Price']->name            = 'Price';
        $propContent->properties['Price']->typeName        = 'Edm.Double';

        $addressContent             = new ODataPropertyContent(
            [
                'HouseNumber' => new ODataProperty(),
                'LineNumber' => new ODataProperty(),
                'LineNumber2' => new ODataProperty(),
                'StreetName' => new ODataProperty(),
                'IsValid' => new ODataProperty(),
                'Address2' => new ODataProperty()
            ]
        );
        $addressContent->properties['HouseNumber']->name     = 'HouseNumber';
        $addressContent->properties['HouseNumber']->typeName = 'Edm.String';
        $addressContent->properties['HouseNumber']->value    = '1';
        $addressContent->properties['LineNumber']->name      = 'LineNumber';
        $addressContent->properties['LineNumber']->typeName  = 'Edm.Int32';
        $addressContent->properties['LineNumber2']->name     = 'LineNumber2';
        $addressContent->properties['LineNumber2']->typeName = 'Edm.Int32';
        $addressContent->properties['StreetName']->name      = 'StreetName';
        $addressContent->properties['StreetName']->typeName  = 'Edm.String';
        $addressContent->properties['IsValid']->name         = 'IsValid';
        $addressContent->properties['IsValid']->typeName     = 'Edm.Boolean';
        $addressContent->properties['Address2']->name        = 'Address2';
        $addressContent->properties['Address2']->typeName    = 'Address2';

        $linkPropContent             = new ODataPropertyContent();
        $linkPropContent->properties = ['CustomerID' => new ODataProperty(), 'CustomerGuid' => new ODataProperty(),
            'CustomerName' => new ODataProperty(), 'Country' => new ODataProperty(), 'Rating' => new ODataProperty(),
            'Photo' => new ODataProperty(), 'Address' => new ODataProperty()];
        $linkPropContent->properties['CustomerID']->name       = 'CustomerID';
        $linkPropContent->properties['CustomerID']->typeName   = 'Edm.String';
        $linkPropContent->properties['CustomerID']->value      = '1';
        $linkPropContent->properties['CustomerGuid']->name     = 'CustomerGuid';
        $linkPropContent->properties['CustomerGuid']->typeName = 'Edm.Guid';
        $linkPropContent->properties['CustomerGuid']->value    = '123e4567-e89b-12d3-a456-426655440000';
        $linkPropContent->properties['CustomerName']->name     = 'CustomerName';
        $linkPropContent->properties['CustomerName']->typeName = 'Edm.String';
        $linkPropContent->properties['Country']->name          = 'Country';
        $linkPropContent->properties['Country']->typeName      = 'Edm.String';
        $linkPropContent->properties['Rating']->name           = 'Rating';
        $linkPropContent->properties['Rating']->typeName       = 'Edm.Int32';
        $linkPropContent->properties['Photo']->name            = 'Photo';
        $linkPropContent->properties['Photo']->typeName        = 'Edm.Binary';
        $linkPropContent->properties['Address']->name          = 'Address';
        $linkPropContent->properties['Address']->typeName      = 'Address';
        $linkPropContent->properties['Address']->value         = $addressContent;

        $linkRawResult     = new ODataFeed();
        $linkRawResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\','
                             . 'CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $linkRawResult->title           = new ODataTitle('Orders');
        $linkRawResult->setSelfLink(new ODataLink('self', 'Orders', null, 'Customers(CustomerID=\'1\','
            . 'CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders'));


        $linkResult     = new ODataEntry();
        $linkResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                          . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $linkResult->title         = new ODataTitle('Customer');
        $linkResult->editLink      = new ODataLink('edit', 'Customer', null, 'Customers(CustomerID=\'1\','
            . 'CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')');
        $linkResult->type            = new ODataCategory('NorthWind.Customer');
        $linkResult->links           = [
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders',
                'Orders',
                'application/atom+xml;type=feed',
                'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567'
                . '-e89b-12d3-a456-426655440000\')/Orders',
                true,
                new ODataExpandedResult($linkRawResult),
                true
            )
        ];
        $linkResult->resourceSetName          = 'Customers';
        $linkResult->propertyContent          = $linkPropContent;
        $linkResult->updated                  = '2017-01-01T00:00:00+00:00';

        $linkFeedResult                  = new ODataFeed();
        $linkFeedResult->id              = 'http://localhost/odata.svc/Orders(OrderID=1)/Order_Details';
        $linkFeedResult->title           = new ODataTitle('Order_Details');
        $linkFeedResult->setSelfLink(new ODataLink('self', 'Order_Details', null, 'Orders(OrderID=1)/Order_Details'));

        $links                    = [
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer',
                'Customer',
                'application/atom+xml;type=entry',
                'Orders(OrderID=1)/Customer',
                false,
                new ODataExpandedResult($linkResult),
                true
            ),
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details',
                'Order_Details',
                'application/atom+xml;type=feed',
                'Orders(OrderID=1)/Order_Details',
                true,
                new ODataExpandedResult($linkFeedResult),
                true
            )
        ];

        $objectResult                  = new ODataEntry();
        $objectResult->id              = 'http://localhost/odata.svc/Orders(OrderID=1)';
        $objectResult->title           = new ODataTitle('Order');
        $objectResult->type            = new ODataCategory('NorthWind.Order');
        $objectResult->editLink        = new ODataLink('edit', 'Order', null, 'Orders(OrderID=1)');
        $objectResult->propertyContent = $propContent;
        $objectResult->links           = $links;
        $objectResult->resourceSetName = 'Orders';
        $objectResult->updated         = '2017-01-01T00:00:00+00:00';
        $objectResult->baseURI         = 'http://localhost/odata.svc/';

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
        $request->shouldReceive('getRawUrl')
            ->andReturn('http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $caveJohnson                 = new Employee2();
        $caveJohnson->EmployeeID     = 'Cave Johnson';
        $caveJohnson->Emails         = ['foo', 'bar'];
        $caveJohnson->TumbNail_48X48 = 'foobar';

        $result          = new QueryResult();
        $result->results = $caveJohnson;

        $emailBag                   = new ODataBagContent();
        $emailBag->setPropertyContents(['foo', 'bar']);

        $propContent             = new ODataPropertyContent(
            [
                'EmployeeID' => new ODataProperty(),
                'FirstName' => new ODataProperty(),
                'LastName' => new ODataProperty(),
                'ReportsTo' => new ODataProperty(),
                'Emails' => new ODataProperty()
            ]
        );
        $propContent->properties['EmployeeID']->name     = 'EmployeeID';
        $propContent->properties['EmployeeID']->typeName = 'Edm.String';
        $propContent->properties['EmployeeID']->value    = 'Cave Johnson';
        $propContent->properties['FirstName']->name      = 'FirstName';
        $propContent->properties['FirstName']->typeName  = 'Edm.String';
        $propContent->properties['LastName']->name       = 'LastName';
        $propContent->properties['LastName']->typeName   = 'Edm.String';
        $propContent->properties['ReportsTo']->name      = 'ReportsTo';
        $propContent->properties['ReportsTo']->typeName  = 'Edm.Int32';
        $propContent->properties['Emails']->name         = 'Emails';
        $propContent->properties['Emails']->typeName     = 'Collection(Edm.String)';
        $propContent->properties['Emails']->value        = $emailBag;

        $mediaLink = new ODataMediaLink(
            'NorthWind.Employee',
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

        $links                  = [
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager',
                'Manager',
                'application/atom+xml;type=entry',
                'Employees(EmployeeID=\'Cave+Johnson\')/Manager'
            ),
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates',
                'Subordinates',
                'application/atom+xml;type=feed',
                'Employees(EmployeeID=\'Cave+Johnson\')/Subordinates',
                true,
                null,
                false
            )
        ];

        $objectResult                   = new ODataEntry();
        $objectResult->id               = 'http://localhost/odata.svc/Employees(EmployeeID=\'Cave+Johnson\')';
        $objectResult->title            = new ODataTitle('Employee');
        $objectResult->type             = new ODataCategory('NorthWind.Employee');
        $objectResult->editLink         = new ODataLink('edit', 'Employee', null, 'Employees(EmployeeID=\'Cave+Johnson\')');
        $objectResult->isMediaLinkEntry = true;
        $objectResult->mediaLink        = $mediaLink;
        $objectResult->mediaLinks[]     = $mediaArray;
        $objectResult->propertyContent  = $propContent;
        $objectResult->links            = $links;
        $objectResult->resourceSetName  = 'Employees';
        $objectResult->updated          = '2017-01-01T00:00:00+00:00';
        $objectResult->baseURI          = 'http://localhost/odata.svc/';

        $ironicResult = $ironic->writeTopLevelElement($result);

        // zero out etag values
        $ironicResult->mediaLink->eTag     = '';
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
        $request->shouldReceive('getRawUrl')
            ->andReturn('http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')?$expand=Manager');

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
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG()])->andReturn(true, false);

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
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Customer');
        $node->shouldReceive('isExpansionSpecified')->withArgs(['Subordinates'])->andReturn(false)->times(0);
        $node->shouldReceive('isExpansionSpecified')->withArgs(['Manager'])->andReturn(true)->times(0);
        $node->shouldReceive('canSelectAllProperties')->andReturn(false);
        $node->shouldReceive('findNode')->andReturn($expandNode, $expandNode, null);
        $node->shouldReceive('getChildNodes')->andReturn([$expandNode, $mailNode])->times(1);

        $ironic->getRequest()->setRootProjectionNode($node);

        $caveJohnson                 = new Employee2();
        $caveJohnson->EmployeeID     = 'Cave Johnson';
        $caveJohnson->Emails         = ['foo', 'bar'];
        $caveJohnson->TumbNail_48X48 = 'foobar';

        $emp                 = new Employee2();
        $emp->Manager        = $caveJohnson;
        $emp->EmployeeID     = 'Bruce';
        $emp->TumbNail_48X48 = 'foobar';
        $emp->Emails         = null;

        $result          = new QueryResult();
        $result->results = $emp;

        $media1 = new ODataMediaLink(
            'NorthWind.Employee',
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

        $contentProp1                          = new ODataProperty();
        $contentProp1->name                    = 'EmployeeID';
        $contentProp1->typeName                = 'Edm.String';
        $contentProp1->value                   = 'Cave Johnson';
        $contentProp2                          = new ODataProperty();
        $contentProp2->name                    = 'FirstName';
        $contentProp2->typeName                = 'Edm.String';
        $contentProp3                          = new ODataProperty();
        $contentProp3->name                    = 'LastName';
        $contentProp3->typeName                = 'Edm.String';
        $contentProp4                          = new ODataProperty();
        $contentProp4->name                    = 'ReportsTo';
        $contentProp4->typeName                = 'Edm.Int32';
        $contentProp5                          = new ODataProperty();
        $contentProp5->name                    = 'Emails';
        $contentProp5->typeName                = 'Collection(Edm.String)';
        $contentProp5->value                   = new ODataBagContent();
        $contentProp5->value->setPropertyContents(['foo', 'bar']);

        $propContent                           = new ODataPropertyContent(
            [
                'EmployeeID' => $contentProp1,
                'FirstName'  => $contentProp2,
                'LastName'   => $contentProp3,
                'ReportsTo'  => $contentProp4,
                'Emails'     => $contentProp5
            ]
        );

        $managerMedia1 = new ODataMediaLink(
            'NorthWind.Employee',
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

        $managerResult                  = new ODataEntry();
        $managerResult->resourceSetName = 'Employee';

        $managerLink1                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager',
            'Manager',
            'application/atom+xml;type=entry',
            'Employees(EmployeeID=\'Cave+Johnson\')/Manager',
            false
        );
        $managerLink1->setIsExpanded(true);
        $managerLink1->setExpandedResult(new ODataExpandedResult($managerResult));
        $managerLink2                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates',
            'Subordinates',
            'application/atom+xml;type=feed',
            'Employees(EmployeeID=\'Cave+Johnson\')/Subordinates',
            true
        );
        $managerLink2->setIsExpanded(false);


        $manager                   = new ODataEntry();
        $manager->id               = 'http://localhost/odata.svc/Employees(EmployeeID=\'Cave+Johnson\')';
        $manager->title            = new ODataTitle('Employee');
        $manager->editLink         = new ODataLink('edit', 'Employee', null, 'Employees(EmployeeID=\'Cave+Johnson\')');
        $manager->mediaLink        = $managerMedia1;
        $manager->mediaLinks       = [$managerMedia2];
        $manager->propertyContent  = $propContent;
        $manager->type             = new ODataCategory('NorthWind.Employee');
        $manager->isMediaLinkEntry = true;
        $manager->links            = [$managerLink1, $managerLink2];
        $manager->resourceSetName  = 'Employees';
        $manager->updated          = '2017-01-01T00:00:00+00:00';

        $link                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager',
            'Manager',
            'application/atom+xml;type=entry',
            'Employees(EmployeeID=\'Bruce\')/Manager',
            false,
            new ODataExpandedResult($manager),
            true
        );

        $objContentProperty           = new ODataProperty();
        $objContentProperty->name     = 'Emails';
        $objContentProperty->typeName = 'Collection(Edm.String)';

        $objContent                       = new ODataPropertyContent(
            [
                'Emails' => $objContentProperty
            ]
        );

        $objectResult                   = new ODataEntry();
        $objectResult->id               = 'http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')';
        $objectResult->title            = new ODataTitle('Employee');
        $objectResult->editLink         = new ODataLink('edit', 'Employee', null, 'Employees(EmployeeID=\'Bruce\')');
        $objectResult->type             = new ODataCategory('NorthWind.Employee');
        $objectResult->propertyContent  = $objContent;
        $objectResult->isMediaLinkEntry = true;
        $objectResult->mediaLink        = $media1;
        $objectResult->mediaLinks       = [$media2];
        $objectResult->links[]          = $link;
        $objectResult->resourceSetName  = 'Employees';
        $objectResult->updated          = '2017-01-01T00:00:00+00:00';
        $objectResult->baseURI          = 'http://localhost/odata.svc/';

        $ironicResult = $ironic->writeTopLevelElement($result);

        // flatten, remove and zero out etags - haven't yet figured out how to freeze etag generation
        $ironicResult->mediaLinks[0]->eTag                                            = '';
        $ironicResult->mediaLink->eTag                                                = '';
        $ironicResult->links[0]->getExpandedResult()->getEntry()->mediaLinks[0]->eTag = '';
        $ironicResult->links[0]->getExpandedResult()->getEntry()->mediaLink->eTag     = '';

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteSingleModelWithKeyPropertiesNulled()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();

        $result          = new QueryResult();
        $result->results = $model;

        $expected = 'The serialized resource of type Customer has a null value in key member \'CustomerID\'. Null'
                    . ' values are not supported in key members.';
        $expectedExceptionClass = ODataException::class;
        $actual                 = null;
        $actualExceptionClass   = null;

        try {
            $ironic->writeTopLevelElement($result);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual               = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param $request
     * @return array
     */
    private function setUpDataServiceDeps($request)
    {
        $op   = new OperationContextAdapter($request);
        $host = new ServiceHost($op);

        $meta  = NorthWindMetadata::Create();
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
        $service                            = new TestDataService($query, $meta, $host);
        $processor                          = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $ironic = new IronicSerialiser($service, $processor->getRequest());
        return $ironic;
    }
}
