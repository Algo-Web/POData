<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\CynicDeserialiser;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\IncomingRequest;
use POData\OperationContext\Web\WebOperationContext as OperationContextAdapter;
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

        $request = $this->setUpRequest('PUT');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $model               = new Customer2();
        $model->CustomerID   = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country      = 'STRAYA';
        $model->Rating       = 11;

        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('updateResource')->andReturn($model)->once();

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
        $objectResult->id              = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
    }

    public function testCreateSimpleOrderModelAndAddReferenceToExistingCustomer()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Orders');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $order            = new Order2();
        $order->OrderID   = 1;
        $order->ShipName  = 'Ship';
        $order->ItemCount = 11;
        $order->Price     = 42;

        $customer               = new Customer2();
        $customer->CustomerID   = 1;
        $customer->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $customer->CustomerName = 'CustomerName';
        $customer->Country      = 'STRAYA';
        $customer->Rating       = 11;

        $customerUrl = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($order)->once();
        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($customer)->atLeast(1); //TODO: this used to have a once, is that right?
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->atLeast(1); //TODO: this used to have a once, is that right?

        $propContent             = new ODataPropertyContent(
            [
                'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', null),
                'OrderDate' => new ODataProperty('OrderDate', 'Edm.DateTime', null),
                'DeliveryDate' => new ODataProperty('DeliveryDate', 'Edm.DateTime', null),
                'ShipName' => new ODataProperty('ShipName', 'Edm.String', 'Ship'),
                'ItemCount' => new ODataProperty('ItemCount', 'Edm.Int32', 11),
                'QualityRate' => new ODataProperty('QualityRate', 'Edm.Int32', null),
                'Price' => new ODataProperty('Price', 'Edm.Double', 42)
            ]
        );

        // hook up to existing customer, and not hooking up to any order details
        $links                    = [new ODataLink(), new ODataLink()];
        $links[0]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer');
        $links[0]->setTitle('Customer');
        $links[0]->setType('application/atom+xml;type=entry');
        $links[0]->setUrl('Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')');
        $links[0]->setIsCollection(false);
        $links[0]->setIsExpanded(true);
        $links[0]->setExpandedResult(null);
        $links[1]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details');
        $links[1]->setTitle('Order_Details');
        $links[1]->setType('application/atom+xml;type=feed');
        $links[1]->setUrl(null);
        $links[1]->setExpandedResult(null);

        $objectResult                  = new ODataEntry();
        $objectResult->setTitle(new ODataTitle('Order'));
        $objectResult->setType(new ODataCategory('Order'));
        $objectResult->editLink        = new ODataLink('edit', 'Order', null, 'Orders(OrderID=1)');
        $objectResult->setPropertyContent($propContent);
        $objectResult->setLinks($links);
        $objectResult->setResourceSetName('Orders');

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertTrue(!($objectResult->links[0]->getUrl() instanceof KeyDescriptor));
    }

    public function testCreateSimpleOrderModelAndAddReferenceToNewCustomer()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Orders');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $order            = new Order2();
        $order->OrderID   = 1;
        $order->ShipName  = 'Ship';
        $order->ItemCount = 11;
        $order->Price     = 42;

        $customer               = new Customer2();
        $customer->CustomerID   = 1;
        $customer->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $customer->CustomerName = 'CustomerName';
        $customer->Country      = 'STRAYA';
        $customer->Rating       = 11;

        $customerUrl = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                       . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($order, $customer)->twice();
        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($customer)->never();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->once();

        $linkPropContent             = new ODataPropertyContent(
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


        $linkResult                  = new ODataEntry();
        $linkResult->title           = new ODataTitle('Customer');
        $linkResult->type            = new ODataCategory('Customer');
        $linkResult->resourceSetName = 'Customers';
        $linkResult->propertyContent = $linkPropContent;

        $propContent             = new ODataPropertyContent(
            [
                'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', null),
                'OrderDate' => new ODataProperty('OrderDate', 'Edm.DateTime', null),
                'DeliveryDate' => new ODataProperty('DeliveryDate', 'Edm.DateTime', null),
                'ShipName' => new ODataProperty('ShipName', 'Edm.String', 'Ship'),
                'ItemCount' => new ODataProperty('ItemCount', 'Edm.Int32', 11),
                'QualityRate' => new ODataProperty('QualityRate', 'Edm.Int32', null),
                'Price' => new ODataProperty('Price', 'Edm.Double', 42)
            ]
        );

        // hook up to existing customer, and not hooking up to any order details
        $links                    = [new ODataLink(), new ODataLink()];
        $links[0]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer');
        $links[0]->setTitle('Customer');
        $links[0]->setType('application/atom+xml;type=entry');
        $links[0]->setIsCollection(true);
        $links[0]->setIsExpanded(true);
        $links[0]->setExpandedResult(new ODataExpandedResult($linkResult));
        $links[1]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details');
        $links[1]->setTitle('Order_Details');
        $links[1]->setType('application/atom+xml;type=feed');
        $links[1]->setUrl(null);
        $links[1]->setExpandedResult(null);

        $objectResult                  = new ODataEntry();
        $objectResult->title           = new ODataTitle('Order');
        $objectResult->type            = new ODataCategory('Order');
        $objectResult->editLink        = new ODataLink('edit', 'Order', null, 'Orders(OrderID=1)');
        $objectResult->propertyContent = $propContent;
        $objectResult->links           = $links;
        $objectResult->resourceSetName = 'Orders';

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertTrue(!($objectResult->links[0]->getUrl() instanceof KeyDescriptor));
        $this->assertTrue($objectResult->links[0]->getExpandedResult()->getData()->id instanceof KeyDescriptor);
    }

    public function testUpdateSimpleCustomerModelAndAddNewOrderModel()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest('PUT');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $order            = new Order2();
        $order->OrderID   = 1;
        $order->ShipName  = 'Ship';
        $order->ItemCount = 11;
        $order->Price     = 42;

        $customer               = new Customer2();
        $customer->CustomerID   = 1;
        $customer->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $customer->CustomerName = 'CustomerName';
        $customer->Country      = 'STRAYA';
        $customer->Rating       = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($customer)->once();
        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($customer)->once();
        $prov->shouldReceive('updateResource')->andReturn(null)->once();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->once();

        $linkPropContent             = new ODataPropertyContent(
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

        $linkResult                  = new ODataEntry();
        $linkResult->setTitle(new ODataTitle('Customer'));
        $linkResult->setType(new ODataCategory('Customer'));
        $linkResult->setResourceSetName('Customers');
        $linkResult->setPropertyContent($linkPropContent);

        $propContent             = new ODataPropertyContent(
            [
                'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', 1),
                'OrderDate' => new ODataProperty('OrderDate', 'Edm.DateTime', null),
                'DeliveryDate' => new ODataProperty('DeliveryDate', 'Edm.DateTime', null),
                'ShipName' => new ODataProperty('ShipName', 'Edm.String', 'Ship'),
                'ItemCount' => new ODataProperty('ItemCount', 'Edm.Int32', 11),
                'QualityRate' => new ODataProperty('QualityRate', 'Edm.Int32', null),
                'Price' => new ODataProperty('Price', 'Edm.Double', 42)
            ]
        );

        $links                    = [new ODataLink(), new ODataLink()];
        $links[0]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer');
        $links[0]->setTitle('Customer');
        $links[0]->setType('application/atom+xml;type=entry');
        $links[0]->setIsCollection(true);
        $links[0]->setIsExpanded(true);
        $links[0]->setExpandedResult(new ODataExpandedResult($linkResult));
        $links[1]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details');
        $links[1]->setTitle('Order_Details');
        $links[1]->setType('application/atom+xml;type=feed');
        $links[1]->setUrl(null);
        $links[1]->setExpandedResult(null);

        $objectResult = new ODataEntry(
            'http://localhost/odata.svc/Orders(OrderID=1)',
            null,
            new ODataTitle('Order'),
            new ODataLink('edit', 'Order', null, 'Orders(OrderID=1)'),
            new ODataCategory('Order'),
            $propContent,
            [],
            null,
            $links,
            null,
            null,
            'Orders'
        );


        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertTrue(!($objectResult->links[0]->getUrl() instanceof KeyDescriptor));
        $this->assertTrue($objectResult->links[0]->getExpandedResult()->getData()->id instanceof KeyDescriptor);
    }

    public function testUpdateSimpleCustomerModelAndUpdateAndAttachOrderModel()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest('PUT');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $order            = new Order2();
        $order->OrderID   = 1;
        $order->ShipName  = 'Ship';
        $order->ItemCount = 11;
        $order->Price     = 42;

        $customer               = new Customer2();
        $customer->CustomerID   = 1;
        $customer->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $customer->CustomerName = 'CustomerName';
        $customer->Country      = 'STRAYA';
        $customer->Rating       = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn(null)->never();
        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($order, $customer)->twice();
        $prov->shouldReceive('updateResource')->andReturn($order, $customer)->twice();
        $prov->shouldReceive('hookSingleModel')->andReturn(null)->once();

        $linkPropContent             = new ODataPropertyContent(
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

        $linkResult                  = new ODataEntry();
        $linkResult->title           = new ODataTitle('Customer');
        $linkResult->type            = new ODataCategory('Customer');
        $linkResult->resourceSetName = 'Customers';
        $linkResult->propertyContent = $linkPropContent;
        $linkResult->id              = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $propContent             = new ODataPropertyContent(
            [
                'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', 1),
                'OrderDate' => new ODataProperty('OrderDate', 'Edm.DateTime', null),
                'DeliveryDate' => new ODataProperty('DeliveryDate', 'Edm.DateTime', null),
                'ShipName' => new ODataProperty('ShipName', 'Edm.String', 'Ship'),
                'ItemCount' => new ODataProperty('ItemCount', 'Edm.Int32', 11),
                'QualityRate' => new ODataProperty('QualityRate', 'Edm.Int32', null),
                'Price' => new ODataProperty('Price', 'Edm.Double', 42)
            ]
        );

        $links                    = [new ODataLink(), new ODataLink()];
        $links[0]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer');
        $links[0]->setTitle('Customer');
        $links[0]->setType('application/atom+xml;type=entry');
        $links[0]->setIsCollection(true);
        $links[0]->setIsExpanded(true);
        $links[0]->setUrl('Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')');
        $links[0]->setExpandedResult(new ODataExpandedResult($linkResult));
        $links[1]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details');
        $links[1]->setTitle('Order_Details');
        $links[1]->setType('application/atom+xml;type=feed');
        $links[1]->setUrl(null);
        $links[1]->setExpandedResult(null);

        $objectResult                  = new ODataEntry();
        $objectResult->id              = 'http://localhost/odata.svc/Orders(OrderID=1)';
        $objectResult->title           = new ODataTitle('Order');
        $objectResult->type            = new ODataCategory('Order');
        $objectResult->editLink        = new ODataLink('edit', 'Order', null, 'Orders(OrderID=1)');
        $objectResult->propertyContent = $propContent;
        $objectResult->links           = $links;
        $objectResult->resourceSetName = 'Orders';

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
        $this->assertTrue(!($objectResult->links[0]->getUrl() instanceof KeyDescriptor));
        $this->assertTrue($objectResult->links[0]->getExpandedResult()->getData()->id instanceof KeyDescriptor);
    }

    /**
     * @param $request
     * @return array
     */
    private function setUpDataServiceDeps($request)
    {
        $op   = new OperationContextAdapter($request);
        $host = new ServiceHost($op);

        $meta = NorthWindMetadata::Create();
        $prov = m::mock(ProvidersWrapper::class);

        return [$host, $meta, $prov];
    }
}
