<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use UnitTests\POData\TestCase;
use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\CynicDeserialiser;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
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
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\Employee2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;
use UnitTests\POData\Facets\NorthWind1\OrderDetails2;
use UnitTests\POData\ObjectModel\reusableEntityClass1;

class ObjectDeserialiserFeedTest extends SerialiserTestBase
{
    public function testCreateSingleElementWithEmptyFeed()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country = 'STRAYA';
        $model->Rating = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('createBulkResourceforResourceSet')->andReturn(null)->never();

        $feed = new ODataFeed();

        $link = new ODataLink();
        $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $link->title = 'Order';
        $link->type = 'application/atom+xml;type=feed';
        $link->url = null;
        $link->isCollection = false;
        $link->isExpanded = true;
        $link->expandedResult = $feed;

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
        $propContent->properties['CustomerName']->value = 'CustomerName';
        $propContent->properties['Country']->name = 'Country';
        $propContent->properties['Country']->typeName = 'Edm.String';
        $propContent->properties['Country']->value = 'STRAYA';
        $propContent->properties['Rating']->name = 'Rating';
        $propContent->properties['Rating']->typeName = 'Edm.Int32';
        $propContent->properties['Rating']->value = 11;
        $propContent->properties['Photo']->name = 'Photo';
        $propContent->properties['Photo']->typeName = 'Edm.Binary';
        $propContent->properties['Address']->name = 'Address';
        $propContent->properties['Address']->typeName = 'Address';

        $objectResult = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type = new ODataCategory('Customer');
        $objectResult->links = [$link];

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertNull($objectResult->links[0]->url);
    }

    public function testResourceSetMismatch()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country = 'STRAYA';
        $model->Rating = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('createBulkResourceforResourceSet')->andReturn(null)->never();

        $feed1 = new ODataEntry();
        $feed1->resourceSetName = 'Orders';
        $feed2 = new ODataEntry();
        $feed2->resourceSetName = 'Customers';

        $feed = new ODataFeed();
        $feed->entries = [$feed1, $feed1, $feed2];

        $link = new ODataLink();
        $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $link->title = 'Order';
        $link->type = 'application/atom+xml;type=feed';
        $link->url = null;
        $link->isCollection = false;
        $link->isExpanded = true;
        $link->expandedResult = $feed;

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
        $propContent->properties['CustomerName']->value = 'CustomerName';
        $propContent->properties['Country']->name = 'Country';
        $propContent->properties['Country']->typeName = 'Edm.String';
        $propContent->properties['Country']->value = 'STRAYA';
        $propContent->properties['Rating']->name = 'Rating';
        $propContent->properties['Rating']->typeName = 'Edm.Int32';
        $propContent->properties['Rating']->value = 11;
        $propContent->properties['Photo']->name = 'Photo';
        $propContent->properties['Photo']->typeName = 'Edm.Binary';
        $propContent->properties['Address']->name = 'Address';
        $propContent->properties['Address']->typeName = 'Address';

        $objectResult = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type = new ODataCategory('Customer');
        $objectResult->links = [$link];

        $cereal = new CynicDeserialiser($meta, $prov);

        $expected = 'All entries in given feed must have same resource set';
        $actual = null;

        try {
            $cereal->processPayload($objectResult);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateAndCreateFeedAssociatedWithNonEmptyGrandchild()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $orderDeet = new OrderDetails2();
        $orderDeet->Discount = 0;
        $orderDeet->UnitPrice = 42;
        $orderDeet->Quantity = 1;
        $orderDeet->ProductID = 42;
        $orderDeet->OrderID = 1;

        $orderModel = new Order2();
        $orderModel->OrderID = 1;
        $orderModel->ShipName = 'Ship';
        $orderModel->ItemCount = 11;
        $orderModel->Price = 42;

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country = 'STRAYA';
        $model->Rating = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('createBulkResourceforResourceSet')->andReturn([$orderModel], [$orderDeet])->twice();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->twice();

        $deetContent = new ODataPropertyContent();
        $deetContent->properties = ['ProductID' => new ODataProperty(), 'OrderID' => new ODataProperty(),
            'UnitPrice' => new ODataProperty(), 'Quantity' => new ODataProperty(), 'Discount' => new ODataProperty()];
        $deetContent->properties['UnitPrice']->name = 'UnitPrice';
        $deetContent->properties['UnitPrice']->typeName = 'Edm.Single';
        $deetContent->properties['UnitPrice']->value = 42;
        $deetContent->properties['Quantity']->name = 'UnitPrice';
        $deetContent->properties['Quantity']->typeName = 'Edm.Int16';
        $deetContent->properties['Quantity']->value = 1;
        $deetContent->properties['Discount']->name = 'Discount';
        $deetContent->properties['Discount']->typeName = 'Edm.Single';
        $deetContent->properties['Discount']->value = 0;
        $deetContent->properties['OrderID']->name = 'OrderID';
        $deetContent->properties['OrderID']->typeName = 'Edm.Int32';
        $deetContent->properties['OrderID']->value = 1;
        $deetContent->properties['ProductID']->name = 'ProductID';
        $deetContent->properties['ProductID']->typeName = 'Edm.Int32';
        $deetContent->properties['ProductID']->value = 1;

        $deet = new ODataEntry();
        $deet->resourceSetName = 'Order_Details';
        $deet->title = new ODataTitle('Order_Details');
        $deet->type = new ODataCategory('Order_Details');
        $deet->propertyContent = $deetContent;

        $orderFeed = new ODataFeed();
        $orderFeed->entries = [$deet];

        $orderLink = new ODataLink();
        $orderLink->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $orderLink->title = 'Order';
        $orderLink->type = 'application/atom+xml;type=feed';
        $orderLink->url = null;
        $orderLink->isCollection = true;
        $orderLink->isExpanded = true;
        $orderLink->expandedResult = $orderFeed;

        $linkContent = new ODataPropertyContent();
        $linkContent->properties = ['OrderID' => new ODataProperty(), 'OrderDate' => new ODataProperty(),
            'DeliveryDate' => new ODataProperty(), 'ShipName' => new ODataProperty(),
            'ItemCount' => new ODataProperty(), 'QualityRate' => new ODataProperty(), 'Price' => new ODataProperty()];
        $linkContent->properties['OrderID']->name = 'OrderID';
        $linkContent->properties['OrderID']->typeName = 'Edm.Int32';
        $linkContent->properties['OrderID']->value = '1';
        $linkContent->properties['OrderDate']->name = 'OrderDate';
        $linkContent->properties['OrderDate']->typeName = 'Edm.DateTime';
        $linkContent->properties['DeliveryDate']->name = 'DeliveryDate';
        $linkContent->properties['DeliveryDate']->typeName = 'Edm.DateTime';
        $linkContent->properties['ShipName']->name = 'ShipName';
        $linkContent->properties['ShipName']->typeName = 'Edm.String';
        $linkContent->properties['ShipName']->value = 'Ship';
        $linkContent->properties['ItemCount']->name = 'ItemCount';
        $linkContent->properties['ItemCount']->typeName = 'Edm.Int32';
        $linkContent->properties['ItemCount']->value = 11;
        $linkContent->properties['QualityRate']->name = 'QualityRate';
        $linkContent->properties['QualityRate']->typeName = 'Edm.Int32';
        $linkContent->properties['Price']->name = 'Price';
        $linkContent->properties['Price']->typeName = 'Edm.Double';
        $linkContent->properties['Price']->value = 42;

        $order = new ODataEntry();
        $order->resourceSetName = 'Orders';
        $order->title = new ODataTitle('Order');
        $order->type = new ODataCategory('Order');
        $order->propertyContent = $linkContent;
        $order->links = [$orderLink];

        $feed = new ODataFeed();
        $feed->entries = [$order];

        $link = new ODataLink();
        $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $link->title = 'Order';
        $link->type = 'application/atom+xml;type=feed';
        $link->url = null;
        $link->isCollection = false;
        $link->isExpanded = true;
        $link->expandedResult = $feed;

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
        $propContent->properties['CustomerName']->value = 'CustomerName';
        $propContent->properties['Country']->name = 'Country';
        $propContent->properties['Country']->typeName = 'Edm.String';
        $propContent->properties['Country']->value = 'STRAYA';
        $propContent->properties['Rating']->name = 'Rating';
        $propContent->properties['Rating']->typeName = 'Edm.Int32';
        $propContent->properties['Rating']->value = 11;
        $propContent->properties['Photo']->name = 'Photo';
        $propContent->properties['Photo']->typeName = 'Edm.Binary';
        $propContent->properties['Address']->name = 'Address';
        $propContent->properties['Address']->typeName = 'Address';

        $objectResult = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type = new ODataCategory('Customer');
        $objectResult->links = [$link];

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertNull($objectResult->links[0]->url);
        $this->assertTrue($objectResult->links[0]->expandedResult->entries[0]->id instanceof KeyDescriptor);
    }

    public function testUpdateAndUpdateFeedAssociatedWithEmptyGrandchild()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $orderModel = new Order2();
        $orderModel->OrderID = 1;
        $orderModel->ShipName = 'Ship';
        $orderModel->ItemCount = 11;
        $orderModel->Price = 42;

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country = 'STRAYA';
        $model->Rating = 11;

        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('updateResource')->andReturn($model)->once();
        $prov->shouldReceive('updateBulkResource')->andReturn([$orderModel])->once();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->once();

        $orderFeed = new ODataFeed();
        $orderFeed->entries = [];

        $orderLink = new ODataLink();
        $orderLink->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $orderLink->title = 'Order';
        $orderLink->type = 'application/atom+xml;type=feed';
        $orderLink->url = null;
        $orderLink->isCollection = true;
        $orderLink->isExpanded = true;
        $orderLink->expandedResult = $orderFeed;

        $linkContent = new ODataPropertyContent();
        $linkContent->properties = ['OrderID' => new ODataProperty(), 'OrderDate' => new ODataProperty(),
            'DeliveryDate' => new ODataProperty(), 'ShipName' => new ODataProperty(),
            'ItemCount' => new ODataProperty(), 'QualityRate' => new ODataProperty(), 'Price' => new ODataProperty()];
        $linkContent->properties['OrderID']->name = 'OrderID';
        $linkContent->properties['OrderID']->typeName = 'Edm.Int32';
        $linkContent->properties['OrderID']->value = '1';
        $linkContent->properties['OrderDate']->name = 'OrderDate';
        $linkContent->properties['OrderDate']->typeName = 'Edm.DateTime';
        $linkContent->properties['DeliveryDate']->name = 'DeliveryDate';
        $linkContent->properties['DeliveryDate']->typeName = 'Edm.DateTime';
        $linkContent->properties['ShipName']->name = 'ShipName';
        $linkContent->properties['ShipName']->typeName = 'Edm.String';
        $linkContent->properties['ShipName']->value = 'Ship';
        $linkContent->properties['ItemCount']->name = 'ItemCount';
        $linkContent->properties['ItemCount']->typeName = 'Edm.Int32';
        $linkContent->properties['ItemCount']->value = 11;
        $linkContent->properties['QualityRate']->name = 'QualityRate';
        $linkContent->properties['QualityRate']->typeName = 'Edm.Int32';
        $linkContent->properties['Price']->name = 'Price';
        $linkContent->properties['Price']->typeName = 'Edm.Double';
        $linkContent->properties['Price']->value = 42;

        $order = new ODataEntry();
        $order->resourceSetName = 'Orders';
        $order->title = new ODataTitle('Order');
        $order->type = new ODataCategory('Order');
        $order->propertyContent = $linkContent;
        $order->links = [$orderLink];
        $order->id = 'http://localhost/odata.svc/Orders(OrderID=1)';

        $feed = new ODataFeed();
        $feed->entries = [$order];

        $link = new ODataLink();
        $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $link->title = 'Order';
        $link->type = 'application/atom+xml;type=feed';
        $link->url = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                     .'=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $link->isCollection = false;
        $link->isExpanded = true;
        $link->expandedResult = $feed;

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
        $propContent->properties['CustomerName']->value = 'CustomerName';
        $propContent->properties['Country']->name = 'Country';
        $propContent->properties['Country']->typeName = 'Edm.String';
        $propContent->properties['Country']->value = 'STRAYA';
        $propContent->properties['Rating']->name = 'Rating';
        $propContent->properties['Rating']->typeName = 'Edm.Int32';
        $propContent->properties['Rating']->value = 11;
        $propContent->properties['Photo']->name = 'Photo';
        $propContent->properties['Photo']->typeName = 'Edm.Binary';
        $propContent->properties['Address']->name = 'Address';
        $propContent->properties['Address']->typeName = 'Address';

        $objectResult = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type = new ODataCategory('Customer');
        $objectResult->links = [$link];
        $objectResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertNotNull($objectResult->links[0]->url);
        $this->assertTrue($objectResult->links[0]->expandedResult->entries[0]->id instanceof KeyDescriptor);
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
        $query = m::mock(ProvidersWrapper::class);

        return [$host, $meta, $query];
    }
}
