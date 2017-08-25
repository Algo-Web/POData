<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\CynicDeserialiser;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;

class ObjectDeserialiserCreateTest extends SerialiserTestBase
{
    public function testCreateSimpleCustomerModel()
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

        $cereal = new CynicDeserialiser($meta, $prov);

        $result = $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertTrue($result instanceof Customer2);
        $this->assertEquals(1, $result->CustomerID);
    }

    public function testUpdateSimpleCustomerModel()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('PUT');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country = 'STRAYA';
        $model->Rating = 11;

        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('updateResource')->andReturn($model)->once();

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
        $objectResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
    }

    public function testCreateSimpleOrderModelAndAddReferenceToExistingCustomer()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Orders');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $order = new Order2();
        $order->OrderID = 1;
        $order->ShipName = 'Ship';
        $order->ItemCount = 11;
        $order->Price = 42;

        $customer = new Customer2();
        $customer->CustomerID = 1;
        $customer->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $customer->CustomerName = 'CustomerName';
        $customer->Country = 'STRAYA';
        $customer->Rating = 11;

        $customerUrl = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($order)->once();
        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($customer)->once();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->once();

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['OrderID' => new ODataProperty(), 'OrderDate' => new ODataProperty(),
            'DeliveryDate' => new ODataProperty(), 'ShipName' => new ODataProperty(),
            'ItemCount' => new ODataProperty(), 'QualityRate' => new ODataProperty(), 'Price' => new ODataProperty()];
        $propContent->properties['OrderID']->name = 'OrderID';
        $propContent->properties['OrderID']->typeName = 'Edm.Int32';
        $propContent->properties['OrderDate']->name = 'OrderDate';
        $propContent->properties['OrderDate']->typeName = 'Edm.DateTime';
        $propContent->properties['DeliveryDate']->name = 'DeliveryDate';
        $propContent->properties['DeliveryDate']->typeName = 'Edm.DateTime';
        $propContent->properties['ShipName']->name = 'ShipName';
        $propContent->properties['ShipName']->typeName = 'Edm.String';
        $propContent->properties['ShipName']->value = 'Ship';
        $propContent->properties['ItemCount']->name = 'ItemCount';
        $propContent->properties['ItemCount']->typeName = 'Edm.Int32';
        $propContent->properties['ItemCount']->value = 11;
        $propContent->properties['QualityRate']->name = 'QualityRate';
        $propContent->properties['QualityRate']->typeName = 'Edm.Int32';
        $propContent->properties['Price']->name = 'Price';
        $propContent->properties['Price']->typeName = 'Edm.Double';
        $propContent->properties['Price']->value = 42;

        // hook up to existing customer, and not hooking up to any order details
        $links = [new ODataLink(), new ODataLink()];
        $links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer';
        $links[0]->title = 'Customer';
        $links[0]->type = 'application/atom+xml;type=entry';
        $links[0]->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $links[0]->isCollection = false;
        $links[0]->isExpanded = true;
        $links[0]->expandedResult = null;
        $links[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details';
        $links[1]->title = 'Order_Details';
        $links[1]->type = 'application/atom+xml;type=feed';
        $links[1]->url = null;
        $links[1]->expandedResult = null;

        $objectResult = new ODataEntry();
        $objectResult->title = new ODataTitle('Order');
        $objectResult->type = new ODataCategory('Order');
        $objectResult->editLink = new ODataLink();
        $objectResult->editLink->url = 'Orders(OrderID=1)';
        $objectResult->editLink->name = 'edit';
        $objectResult->editLink->title = 'Order';
        $objectResult->propertyContent = $propContent;
        $objectResult->links = $links;
        $objectResult->resourceSetName = 'Orders';

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertTrue($objectResult->links[0]->url instanceof KeyDescriptor);
    }

    public function testCreateSimpleOrderModelAndAddReferenceToNewCustomer()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Orders');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $order = new Order2();
        $order->OrderID = 1;
        $order->ShipName = 'Ship';
        $order->ItemCount = 11;
        $order->Price = 42;

        $customer = new Customer2();
        $customer->CustomerID = 1;
        $customer->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $customer->CustomerName = 'CustomerName';
        $customer->Country = 'STRAYA';
        $customer->Rating = 11;

        $customerUrl = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                       .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($order, $customer)->twice();
        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($customer)->never();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->once();

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
        $linkPropContent->properties['Country']->value = 'STRAYA';
        $linkPropContent->properties['Rating']->name = 'Rating';
        $linkPropContent->properties['Rating']->typeName = 'Edm.Int32';
        $linkPropContent->properties['Rating']->value = 11;
        $linkPropContent->properties['Photo']->name = 'Photo';
        $linkPropContent->properties['Photo']->typeName = 'Edm.Binary';
        $linkPropContent->properties['Address']->name = 'Address';
        $linkPropContent->properties['Address']->typeName = 'Address';

        $linkResult = new ODataEntry();
        $linkResult->title = new ODataTitle('Customer');
        $linkResult->type = new ODataCategory('Customer');
        $linkResult->resourceSetName = 'Customers';
        $linkResult->propertyContent = $linkPropContent;

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['OrderID' => new ODataProperty(), 'OrderDate' => new ODataProperty(),
            'DeliveryDate' => new ODataProperty(), 'ShipName' => new ODataProperty(),
            'ItemCount' => new ODataProperty(), 'QualityRate' => new ODataProperty(), 'Price' => new ODataProperty()];
        $propContent->properties['OrderID']->name = 'OrderID';
        $propContent->properties['OrderID']->typeName = 'Edm.Int32';
        $propContent->properties['OrderDate']->name = 'OrderDate';
        $propContent->properties['OrderDate']->typeName = 'Edm.DateTime';
        $propContent->properties['DeliveryDate']->name = 'DeliveryDate';
        $propContent->properties['DeliveryDate']->typeName = 'Edm.DateTime';
        $propContent->properties['ShipName']->name = 'ShipName';
        $propContent->properties['ShipName']->typeName = 'Edm.String';
        $propContent->properties['ShipName']->value = 'Ship';
        $propContent->properties['ItemCount']->name = 'ItemCount';
        $propContent->properties['ItemCount']->typeName = 'Edm.Int32';
        $propContent->properties['ItemCount']->value = 11;
        $propContent->properties['QualityRate']->name = 'QualityRate';
        $propContent->properties['QualityRate']->typeName = 'Edm.Int32';
        $propContent->properties['Price']->name = 'Price';
        $propContent->properties['Price']->typeName = 'Edm.Double';
        $propContent->properties['Price']->value = 42;

        // hook up to existing customer, and not hooking up to any order details
        $links = [new ODataLink(), new ODataLink()];
        $links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer';
        $links[0]->title = 'Customer';
        $links[0]->type = 'application/atom+xml;type=entry';
        $links[0]->isCollection = true;
        $links[0]->isExpanded = true;
        $links[0]->expandedResult = $linkResult;
        $links[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details';
        $links[1]->title = 'Order_Details';
        $links[1]->type = 'application/atom+xml;type=feed';
        $links[1]->url = null;
        $links[1]->expandedResult = null;

        $objectResult = new ODataEntry();
        $objectResult->title = new ODataTitle('Order');
        $objectResult->type = new ODataCategory('Order');
        $objectResult->editLink = new ODataLink();
        $objectResult->editLink->url = 'Orders(OrderID=1)';
        $objectResult->editLink->name = 'edit';
        $objectResult->editLink->title = 'Order';
        $objectResult->propertyContent = $propContent;
        $objectResult->links = $links;
        $objectResult->resourceSetName = 'Orders';

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertTrue($objectResult->links[0]->url instanceof KeyDescriptor);
        $this->assertTrue($objectResult->links[0]->expandedResult->id instanceof KeyDescriptor);
    }

    public function testUpdateSimpleCustomerModelAndAddNewOrderModel()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('PUT');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $order = new Order2();
        $order->OrderID = 1;
        $order->ShipName = 'Ship';
        $order->ItemCount = 11;
        $order->Price = 42;

        $customer = new Customer2();
        $customer->CustomerID = 1;
        $customer->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $customer->CustomerName = 'CustomerName';
        $customer->Country = 'STRAYA';
        $customer->Rating = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($customer)->once();
        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($customer)->once();
        $prov->shouldReceive('updateResource')->andReturn(null)->once();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->once();

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
        $linkPropContent->properties['Country']->value = 'STRAYA';
        $linkPropContent->properties['Rating']->name = 'Rating';
        $linkPropContent->properties['Rating']->typeName = 'Edm.Int32';
        $linkPropContent->properties['Rating']->value = 11;
        $linkPropContent->properties['Photo']->name = 'Photo';
        $linkPropContent->properties['Photo']->typeName = 'Edm.Binary';
        $linkPropContent->properties['Address']->name = 'Address';
        $linkPropContent->properties['Address']->typeName = 'Address';

        $linkResult = new ODataEntry();
        $linkResult->title = new ODataTitle('Customer');
        $linkResult->type = new ODataCategory('Customer');
        $linkResult->resourceSetName = 'Customers';
        $linkResult->propertyContent = $linkPropContent;

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['OrderID' => new ODataProperty(), 'OrderDate' => new ODataProperty(),
            'DeliveryDate' => new ODataProperty(), 'ShipName' => new ODataProperty(),
            'ItemCount' => new ODataProperty(), 'QualityRate' => new ODataProperty(), 'Price' => new ODataProperty()];
        $propContent->properties['OrderID']->name = 'OrderID';
        $propContent->properties['OrderID']->typeName = 'Edm.Int32';
        $propContent->properties['OrderID']->value = 1;
        $propContent->properties['OrderDate']->name = 'OrderDate';
        $propContent->properties['OrderDate']->typeName = 'Edm.DateTime';
        $propContent->properties['DeliveryDate']->name = 'DeliveryDate';
        $propContent->properties['DeliveryDate']->typeName = 'Edm.DateTime';
        $propContent->properties['ShipName']->name = 'ShipName';
        $propContent->properties['ShipName']->typeName = 'Edm.String';
        $propContent->properties['ShipName']->value = 'Ship';
        $propContent->properties['ItemCount']->name = 'ItemCount';
        $propContent->properties['ItemCount']->typeName = 'Edm.Int32';
        $propContent->properties['ItemCount']->value = 11;
        $propContent->properties['QualityRate']->name = 'QualityRate';
        $propContent->properties['QualityRate']->typeName = 'Edm.Int32';
        $propContent->properties['Price']->name = 'Price';
        $propContent->properties['Price']->typeName = 'Edm.Double';
        $propContent->properties['Price']->value = 42;

        $links = [new ODataLink(), new ODataLink()];
        $links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer';
        $links[0]->title = 'Customer';
        $links[0]->type = 'application/atom+xml;type=entry';
        $links[0]->isCollection = true;
        $links[0]->isExpanded = true;
        $links[0]->expandedResult = $linkResult;
        $links[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details';
        $links[1]->title = 'Order_Details';
        $links[1]->type = 'application/atom+xml;type=feed';
        $links[1]->url = null;
        $links[1]->expandedResult = null;

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

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertTrue($objectResult->links[0]->url instanceof KeyDescriptor);
        $this->assertTrue($objectResult->links[0]->expandedResult->id instanceof KeyDescriptor);
    }

    public function testUpdateSimpleCustomerModelAndUpdateAndAttachOrderModel()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('PUT');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $order = new Order2();
        $order->OrderID = 1;
        $order->ShipName = 'Ship';
        $order->ItemCount = 11;
        $order->Price = 42;

        $customer = new Customer2();
        $customer->CustomerID = 1;
        $customer->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $customer->CustomerName = 'CustomerName';
        $customer->Country = 'STRAYA';
        $customer->Rating = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn(null)->never();
        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($order, $customer)->twice();
        $prov->shouldReceive('updateResource')->andReturn($order, $customer)->twice();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->once();

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
        $linkPropContent->properties['Country']->value = 'STRAYA';
        $linkPropContent->properties['Rating']->name = 'Rating';
        $linkPropContent->properties['Rating']->typeName = 'Edm.Int32';
        $linkPropContent->properties['Rating']->value = 11;
        $linkPropContent->properties['Photo']->name = 'Photo';
        $linkPropContent->properties['Photo']->typeName = 'Edm.Binary';
        $linkPropContent->properties['Address']->name = 'Address';
        $linkPropContent->properties['Address']->typeName = 'Address';

        $linkResult = new ODataEntry();
        $linkResult->title = new ODataTitle('Customer');
        $linkResult->type = new ODataCategory('Customer');
        $linkResult->resourceSetName = 'Customers';
        $linkResult->propertyContent = $linkPropContent;
        $linkResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['OrderID' => new ODataProperty(), 'OrderDate' => new ODataProperty(),
            'DeliveryDate' => new ODataProperty(), 'ShipName' => new ODataProperty(),
            'ItemCount' => new ODataProperty(), 'QualityRate' => new ODataProperty(), 'Price' => new ODataProperty()];
        $propContent->properties['OrderID']->name = 'OrderID';
        $propContent->properties['OrderID']->typeName = 'Edm.Int32';
        $propContent->properties['OrderID']->value = 1;
        $propContent->properties['OrderDate']->name = 'OrderDate';
        $propContent->properties['OrderDate']->typeName = 'Edm.DateTime';
        $propContent->properties['DeliveryDate']->name = 'DeliveryDate';
        $propContent->properties['DeliveryDate']->typeName = 'Edm.DateTime';
        $propContent->properties['ShipName']->name = 'ShipName';
        $propContent->properties['ShipName']->typeName = 'Edm.String';
        $propContent->properties['ShipName']->value = 'Ship';
        $propContent->properties['ItemCount']->name = 'ItemCount';
        $propContent->properties['ItemCount']->typeName = 'Edm.Int32';
        $propContent->properties['ItemCount']->value = 11;
        $propContent->properties['QualityRate']->name = 'QualityRate';
        $propContent->properties['QualityRate']->typeName = 'Edm.Int32';
        $propContent->properties['Price']->name = 'Price';
        $propContent->properties['Price']->typeName = 'Edm.Double';
        $propContent->properties['Price']->value = 42;

        $links = [new ODataLink(), new ODataLink()];
        $links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer';
        $links[0]->title = 'Customer';
        $links[0]->type = 'application/atom+xml;type=entry';
        $links[0]->isCollection = true;
        $links[0]->isExpanded = true;
        $links[0]->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $links[0]->expandedResult = $linkResult;
        $links[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details';
        $links[1]->title = 'Order_Details';
        $links[1]->type = 'application/atom+xml;type=feed';
        $links[1]->url = null;
        $links[1]->expandedResult = null;

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

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertTrue($objectResult->links[0]->url instanceof KeyDescriptor);
        $this->assertTrue($objectResult->links[0]->expandedResult->id instanceof KeyDescriptor);
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
        $prov = m::mock(ProvidersWrapper::class);

        return [$host, $meta, $prov];
    }
}
