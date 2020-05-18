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
                'CustomerID' => new ODataProperty('CustomerID', 'Edm.String', '1'),
                'CustomerGuid' => new ODataProperty('CustomerGuid', 'Edm.Guid', '123e4567-e89b-12d3-a456-426655440000'),
                'CustomerName' => new ODataProperty('CustomerName', 'Edm.String', null),
                'Country' => new ODataProperty('Country', 'Edm.String', 'STRAYA'),
                'Rating' => new ODataProperty('Rating', 'Edm.Int32', 11),
                'Photo' => new ODataProperty('Photo', 'Edm.Binary', null),
                'Address' => new ODataProperty('Address', 'Address', null)
            ]
        );


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
        $feed->setEntries([$feed1, $feed1, $feed2]);

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
                'CustomerID' => new ODataProperty('CustomerID', 'Edm.String', '1'),
                'CustomerGuid' => new ODataProperty('CustomerGuid', 'Edm.Guid', '123e4567-e89b-12d3-a456-426655440000'),
                'CustomerName' => new ODataProperty('CustomerName', 'Edm.String', 'CustomerName'),
                'Country' => new ODataProperty('Country', 'Edm.String', 'STRAYA'),
                'Rating' => new ODataProperty('Rating', 'Edm.Int32', 11),
                'Photo' => new ODataProperty('Photo', 'Edm.Binary', null),
                'Address' => new ODataProperty('Address', 'Address', null)
            ]
        );


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
                'ProductID' => new ODataProperty('ProductID', 'Edm.Int32', 1),
                'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', 1),
                'UnitPrice' => new ODataProperty('UnitPrice', 'Edm.Single', 42),
                'Quantity' => new ODataProperty('Quantity', 'Edm.Int16', 1),
                'Discount' => new ODataProperty('Discount', 'Edm.Single', 0)
            ]
        );

        $deet                  = new ODataEntry();
        $deet->resourceSetName = 'Order_Details';
        $deet->title           = new ODataTitle('Order_Details');
        $deet->type            = new ODataCategory('Order_Details');
        $deet->propertyContent = $deetContent;

        $orderFeed          = new ODataFeed();
        $orderFeed->addEntry($deet);

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
                'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', '1'),
                'OrderDate' => new ODataProperty('OrderDate', 'Edm.DateTime', null),
                'DeliveryDate' => new ODataProperty('DeliveryDate', 'Edm.DateTime', null),
                'ShipName' => new ODataProperty('ShipName', 'Edm.String', 'Ship'),
                'ItemCount' => new ODataProperty('ItemCount', 'Edm.Int32', 11),
                'QualityRate' => new ODataProperty('QualityRate', 'Edm.Int32', null),
                'Price' => new ODataProperty('Price','Edm.Double', 42)
            ]
        );

        $order                  = new ODataEntry();
        $order->resourceSetName = 'Orders';
        $order->title           = new ODataTitle('Order');
        $order->type            = new ODataCategory('Order');
        $order->propertyContent = $linkContent;
        $order->links           = [$orderLink];

        $feed          = new ODataFeed();
        $feed->addEntry($order);

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
                'CustomerID' => new ODataProperty('CustomerID', 'Edm.String', '1'),
                'CustomerGuid' => new ODataProperty('CustomerGuid', 'Edm.Guid', '123e4567-e89b-12d3-a456-426655440000'),
                'CustomerName' => new ODataProperty('CustomerName', 'Edm.String', 'CustomerName'),
                'Country' => new ODataProperty('Country', 'Edm.String', 'STRAYA'),
                'Rating' => new ODataProperty('Rating', 'Edm.Int32', 11),
                'Photo' => new ODataProperty('Photo', 'Edm.Binary', null),
                'Address' => new ODataProperty('Address', 'Address', null)
            ]
        );

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
                'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', '1'),
                'OrderDate' => new ODataProperty('OrderDate', 'Edm.DateTime', null),
                'DeliveryDate' => new ODataProperty('DeliveryDate', 'Edm.DateTime', null),
                'ShipName' => new ODataProperty('ShipName', 'Edm.String', 'Ship'),
                'ItemCount' => new ODataProperty('ItemCount', 'Edm.Int32', 11),
                'QualityRate' => new ODataProperty('QualityRate', 'Edm.Int32', null),
                'Price' => new ODataProperty('Price','Edm.Double', 42)
            ]
        );

        $order                  = new ODataEntry();
        $order->resourceSetName = 'Orders';
        $order->title           = new ODataTitle('Order');
        $order->type            = new ODataCategory('Order');
        $order->propertyContent = $linkContent;
        $order->links           = [$orderLink];
        $order->id              = 'http://localhost/odata.svc/Orders(OrderID=1)';

        $feed          = new ODataFeed();
        $feed->addEntry($order);

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
                'CustomerID' => new ODataProperty('CustomerID', 'Edm.String', '1'),
                'CustomerGuid' => new ODataProperty('CustomerGuid', 'Edm.Guid', '123e4567-e89b-12d3-a456-426655440000'),
                'CustomerName' => new ODataProperty('CustomerName', 'Edm.String', 'CustomerName'),
                'Country' => new ODataProperty('Country', 'Edm.String', 'STRAYA'),
                'Rating' => new ODataProperty('Rating', 'Edm.Int32', 11),
                'Photo' => new ODataProperty('Photo', 'Edm.Binary', null),
                'Address' => new ODataProperty('Address', 'Address', null)
            ]
        );

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
