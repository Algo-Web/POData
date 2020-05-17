<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\CynicDeserialiser;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ObjectModelSerializer;
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
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\Employee2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;
use UnitTests\POData\Facets\NorthWind1\OrderDetails2;
use UnitTests\POData\ObjectModel\reusableEntityClass1;
use UnitTests\POData\TestCase;

class ObjectDeserialiserFeedTest extends SerialiserTestBase
{
    public function testCreateSingleElementWithEmptyFeed()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $model               = new Customer2();
        $model->CustomerID   = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country      = 'STRAYA';
        $model->Rating       = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('createBulkResourceforResourceSet')->andReturn(null)->never();

        $feed = new ODataFeed();

        $link                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order',
            'Order',
            'application/atom+xml;type=feed',
            null,
            false,
            new ODataExpandedResult($feed),
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
        $propContent['CustomerID']->name       = 'CustomerID';
        $propContent['CustomerID']->typeName   = 'Edm.String';
        $propContent['CustomerID']->value      = '1';
        $propContent['CustomerGuid']->name     = 'CustomerGuid';
        $propContent['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent['CustomerGuid']->value    = '123e4567-e89b-12d3-a456-426655440000';
        $propContent['CustomerName']->name     = 'CustomerName';
        $propContent['CustomerName']->typeName = 'Edm.String';
        $propContent['CustomerName']->value    = 'CustomerName';
        $propContent['Country']->name          = 'Country';
        $propContent['Country']->typeName      = 'Edm.String';
        $propContent['Country']->value         = 'STRAYA';
        $propContent['Rating']->name           = 'Rating';
        $propContent['Rating']->typeName       = 'Edm.Int32';
        $propContent['Rating']->value          = 11;
        $propContent['Photo']->name            = 'Photo';
        $propContent['Photo']->typeName        = 'Edm.Binary';
        $propContent['Address']->name          = 'Address';
        $propContent['Address']->typeName      = 'Address';

        $objectResult                  = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type            = new ODataCategory('Customer');
        $objectResult->links           = [$link];

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertNull($objectResult->links[0]->getUrl());
    }

    public function testResourceSetMismatch()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $model               = new Customer2();
        $model->CustomerID   = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country      = 'STRAYA';
        $model->Rating       = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('createBulkResourceforResourceSet')->andReturn(null)->never();

        $feed1                  = new ODataEntry();
        $feed1->resourceSetName = 'Orders';
        $feed2                  = new ODataEntry();
        $feed2->resourceSetName = 'Customers';

        $feed          = new ODataFeed();
        $feed->entries = [$feed1, $feed1, $feed2];

        $link                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order',
            'Order',
            'application/atom+xml;type=feed',
            null,
            false,
            new ODataExpandedResult($feed),
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
        $propContent['CustomerID']->name       = 'CustomerID';
        $propContent['CustomerID']->typeName   = 'Edm.String';
        $propContent['CustomerID']->value      = '1';
        $propContent['CustomerGuid']->name     = 'CustomerGuid';
        $propContent['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent['CustomerGuid']->value    = '123e4567-e89b-12d3-a456-426655440000';
        $propContent['CustomerName']->name     = 'CustomerName';
        $propContent['CustomerName']->typeName = 'Edm.String';
        $propContent['CustomerName']->value    = 'CustomerName';
        $propContent['Country']->name          = 'Country';
        $propContent['Country']->typeName      = 'Edm.String';
        $propContent['Country']->value         = 'STRAYA';
        $propContent['Rating']->name           = 'Rating';
        $propContent['Rating']->typeName       = 'Edm.Int32';
        $propContent['Rating']->value          = 11;
        $propContent['Photo']->name            = 'Photo';
        $propContent['Photo']->typeName        = 'Edm.Binary';
        $propContent['Address']->name          = 'Address';
        $propContent['Address']->typeName      = 'Address';

        $objectResult                  = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type            = new ODataCategory('Customer');
        $objectResult->links           = [$link];

        $cereal = new CynicDeserialiser($meta, $prov);

        $expected = 'All entries in given feed must have same resource set';
        $actual   = null;

        try {
            $cereal->processPayload($objectResult);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testCreateAndCreateFeedAssociatedWithNonEmptyGrandchild()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $orderDeet            = new OrderDetails2();
        $orderDeet->Discount  = 0;
        $orderDeet->UnitPrice = 42;
        $orderDeet->Quantity  = 1;
        $orderDeet->ProductID = 42;
        $orderDeet->OrderID   = 1;

        $orderModel            = new Order2();
        $orderModel->OrderID   = 1;
        $orderModel->ShipName  = 'Ship';
        $orderModel->ItemCount = 11;
        $orderModel->Price     = 42;

        $model               = new Customer2();
        $model->CustomerID   = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country      = 'STRAYA';
        $model->Rating       = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('createBulkResourceforResourceSet')->andReturn([$orderModel], [$orderDeet])->twice();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->twice();

        $deetContent             = new ODataPropertyContent(
            [
                'ProductID' => new ODataProperty(),
                'OrderID' => new ODataProperty(),
                'UnitPrice' => new ODataProperty(),
                'Quantity' => new ODataProperty(),
                'Discount' => new ODataProperty()
            ]
        );
        $deetContent['UnitPrice']->name     = 'UnitPrice';
        $deetContent['UnitPrice']->typeName = 'Edm.Single';
        $deetContent['UnitPrice']->value    = 42;
        $deetContent['Quantity']->name      = 'UnitPrice';
        $deetContent['Quantity']->typeName  = 'Edm.Int16';
        $deetContent['Quantity']->value     = 1;
        $deetContent['Discount']->name      = 'Discount';
        $deetContent['Discount']->typeName  = 'Edm.Single';
        $deetContent['Discount']->value     = 0;
        $deetContent['OrderID']->name       = 'OrderID';
        $deetContent['OrderID']->typeName   = 'Edm.Int32';
        $deetContent['OrderID']->value      = 1;
        $deetContent['ProductID']->name     = 'ProductID';
        $deetContent['ProductID']->typeName = 'Edm.Int32';
        $deetContent['ProductID']->value    = 1;

        $deet                  = new ODataEntry();
        $deet->resourceSetName = 'Order_Details';
        $deet->title           = new ODataTitle('Order_Details');
        $deet->type            = new ODataCategory('Order_Details');
        $deet->propertyContent = $deetContent;

        $orderFeed          = new ODataFeed();
        $orderFeed->entries = [$deet];

        $orderLink                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order',
            'Order',
            'application/atom+xml;type=feed',
            null,
            true,
            new ODataExpandedResult($orderFeed),
            true
        );

        $linkContent             = new ODataPropertyContent(
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
        $linkContent['OrderID']->name          = 'OrderID';
        $linkContent['OrderID']->typeName      = 'Edm.Int32';
        $linkContent['OrderID']->value         = '1';
        $linkContent['OrderDate']->name        = 'OrderDate';
        $linkContent['OrderDate']->typeName    = 'Edm.DateTime';
        $linkContent['DeliveryDate']->name     = 'DeliveryDate';
        $linkContent['DeliveryDate']->typeName = 'Edm.DateTime';
        $linkContent['ShipName']->name         = 'ShipName';
        $linkContent['ShipName']->typeName     = 'Edm.String';
        $linkContent['ShipName']->value        = 'Ship';
        $linkContent['ItemCount']->name        = 'ItemCount';
        $linkContent['ItemCount']->typeName    = 'Edm.Int32';
        $linkContent['ItemCount']->value       = 11;
        $linkContent['QualityRate']->name      = 'QualityRate';
        $linkContent['QualityRate']->typeName  = 'Edm.Int32';
        $linkContent['Price']->name            = 'Price';
        $linkContent['Price']->typeName        = 'Edm.Double';
        $linkContent['Price']->value           = 42;

        $order                  = new ODataEntry();
        $order->resourceSetName = 'Orders';
        $order->title           = new ODataTitle('Order');
        $order->type            = new ODataCategory('Order');
        $order->propertyContent = $linkContent;
        $order->links           = [$orderLink];

        $feed          = new ODataFeed();
        $feed->entries = [$order];

        $link                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order',
            'Order',
            'application/atom+xml;type=feed',
            null,
            false,
            new ODataExpandedResult($feed),
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
        $propContent['CustomerID']->name       = 'CustomerID';
        $propContent['CustomerID']->typeName   = 'Edm.String';
        $propContent['CustomerID']->value      = '1';
        $propContent['CustomerGuid']->name     = 'CustomerGuid';
        $propContent['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent['CustomerGuid']->value    = '123e4567-e89b-12d3-a456-426655440000';
        $propContent['CustomerName']->name     = 'CustomerName';
        $propContent['CustomerName']->typeName = 'Edm.String';
        $propContent['CustomerName']->value    = 'CustomerName';
        $propContent['Country']->name          = 'Country';
        $propContent['Country']->typeName      = 'Edm.String';
        $propContent['Country']->value         = 'STRAYA';
        $propContent['Rating']->name           = 'Rating';
        $propContent['Rating']->typeName       = 'Edm.Int32';
        $propContent['Rating']->value          = 11;
        $propContent['Photo']->name            = 'Photo';
        $propContent['Photo']->typeName        = 'Edm.Binary';
        $propContent['Address']->name          = 'Address';
        $propContent['Address']->typeName      = 'Address';

        $objectResult                  = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type            = new ODataCategory('Customer');
        $objectResult->links           = [$link];

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertNull($objectResult->links[0]->getUrl());
        $this->assertTrue($objectResult->links[0]->getExpandedResult()->getFeed()->entries[0]->id instanceof KeyDescriptor);
    }

    public function testUpdateAndUpdateFeedAssociatedWithEmptyGrandchild()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $orderModel            = new Order2();
        $orderModel->OrderID   = 1;
        $orderModel->ShipName  = 'Ship';
        $orderModel->ItemCount = 11;
        $orderModel->Price     = 42;

        $model               = new Customer2();
        $model->CustomerID   = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country      = 'STRAYA';
        $model->Rating       = 11;

        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('updateResource')->andReturn($model)->once();
        $prov->shouldReceive('updateBulkResource')->andReturn([$orderModel])->once();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->once();

        $orderFeed          = new ODataFeed();
        $orderFeed->entries = [];

        $orderLink                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order',
            'Order',
            'application/atom+xml;type=feed',
            null,
            true,
            new ODataExpandedResult($orderFeed),
            true
        );

        $linkContent             = new ODataPropertyContent(
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
        $linkContent['OrderID']->name          = 'OrderID';
        $linkContent['OrderID']->typeName      = 'Edm.Int32';
        $linkContent['OrderID']->value         = '1';
        $linkContent['OrderDate']->name        = 'OrderDate';
        $linkContent['OrderDate']->typeName    = 'Edm.DateTime';
        $linkContent['DeliveryDate']->name     = 'DeliveryDate';
        $linkContent['DeliveryDate']->typeName = 'Edm.DateTime';
        $linkContent['ShipName']->name         = 'ShipName';
        $linkContent['ShipName']->typeName     = 'Edm.String';
        $linkContent['ShipName']->value        = 'Ship';
        $linkContent['ItemCount']->name        = 'ItemCount';
        $linkContent['ItemCount']->typeName    = 'Edm.Int32';
        $linkContent['ItemCount']->value       = 11;
        $linkContent['QualityRate']->name      = 'QualityRate';
        $linkContent['QualityRate']->typeName  = 'Edm.Int32';
        $linkContent['Price']->name            = 'Price';
        $linkContent['Price']->typeName        = 'Edm.Double';
        $linkContent['Price']->value           = 42;

        $order                  = new ODataEntry();
        $order->resourceSetName = 'Orders';
        $order->title           = new ODataTitle('Order');
        $order->type            = new ODataCategory('Order');
        $order->propertyContent = $linkContent;
        $order->links           = [$orderLink];
        $order->id              = 'http://localhost/odata.svc/Orders(OrderID=1)';

        $feed          = new ODataFeed();
        $feed->entries = [$order];

        $link        = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order',
            'Order',
            'application/atom+xml;type=feed',
            'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders',
            false,
            new ODataExpandedResult($feed),
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
        $propContent['CustomerID']->name       = 'CustomerID';
        $propContent['CustomerID']->typeName   = 'Edm.String';
        $propContent['CustomerID']->value      = '1';
        $propContent['CustomerGuid']->name     = 'CustomerGuid';
        $propContent['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent['CustomerGuid']->value    = '123e4567-e89b-12d3-a456-426655440000';
        $propContent['CustomerName']->name     = 'CustomerName';
        $propContent['CustomerName']->typeName = 'Edm.String';
        $propContent['CustomerName']->value    = 'CustomerName';
        $propContent['Country']->name          = 'Country';
        $propContent['Country']->typeName      = 'Edm.String';
        $propContent['Country']->value         = 'STRAYA';
        $propContent['Rating']->name           = 'Rating';
        $propContent['Rating']->typeName       = 'Edm.Int32';
        $propContent['Rating']->value          = 11;
        $propContent['Photo']->name            = 'Photo';
        $propContent['Photo']->typeName        = 'Edm.Binary';
        $propContent['Address']->name          = 'Address';
        $propContent['Address']->typeName      = 'Address';

        $objectResult                  = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type            = new ODataCategory('Customer');
        $objectResult->links           = [$link];
        $objectResult->id              = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertNotNull($objectResult->links[0]->getUrl());
        $this->assertTrue($objectResult->links[0]->getExpandedResult()->getFeed()->entries[0]->id instanceof KeyDescriptor);
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
        $query = m::mock(ProvidersWrapper::class);

        return [$host, $meta, $query];
    }
}
