<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
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

class SerialiserWriteElementsTest extends SerialiserTestBase
{
    public function testCompareWriteMultipleModelsNoPageOverrun()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $model2 = new Customer2();
        $model2->CustomerID = 2;
        $model2->CustomerGuid = '223e4567-e89b-12d3-a456-426655440000';

        $models = [$model, $model2];

        $results = [new QueryResult(), new QueryResult()];
        $results[0]->results = $models[0];
        $results[1]->results = $models[1];

        $collection = new QueryResult();
        $collection->results = $results;

        $content1 = $this->generateCustomerProperties();
        $content2 = $this->generateCustomerProperties();

        $content1->properties['CustomerID']->value = '1';
        $content1->properties['CustomerGuid']->value = '123e4567-e89b-12d3-a456-426655440000';

        $content2->properties['CustomerID']->value = '2';
        $content2->properties['CustomerGuid']->value = '223e4567-e89b-12d3-a456-426655440000';

        $linkContent = [$content1, $content2];

        $links = [new ODataLink(), new ODataLink()];
        $links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
        $links[0]->title = 'Orders';
        $links[0]->type = 'application/atom+xml;type=feed';
        $links[0]->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $links[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
        $links[1]->title = 'Orders';
        $links[1]->type = 'application/atom+xml;type=feed';
        $links[1]->url = 'Customers(CustomerID=\'2\',CustomerGuid=guid\'223e4567-e89b-12d3-a456-426655440000\')/Orders';

        $entry = [new ODataEntry(), new ODataEntry()];
        $entry[0]->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                        .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry[0]->title = new ODataTitle('Customer');
        $entry[0]->type = new ODataCategory('Customer');
        $entry[0]->editLink = new ODataLink();
        $entry[0]->editLink->url = 'Customers(CustomerID=\'1\','
                                   .'CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry[0]->editLink->name = 'edit';
        $entry[0]->editLink->title = 'Customer';
        $entry[0]->links[] = $links[0];
        $entry[0]->propertyContent = $linkContent[0];
        $entry[0]->resourceSetName = 'Customers';
        $entry[0]->updated = '2017-01-01T00:00:00+00:00';
        $entry[1]->id = 'http://localhost/odata.svc/Customers(CustomerID=\'2\',CustomerGuid'
                        .'=guid\'223e4567-e89b-12d3-a456-426655440000\')';
        $entry[1]->title = new ODataTitle('Customer');
        $entry[1]->type = new ODataCategory('Customer');
        $entry[1]->editLink = new ODataLink();
        $entry[1]->editLink->url = 'Customers(CustomerID=\'2\','
                                   .'CustomerGuid=guid\'223e4567-e89b-12d3-a456-426655440000\')';
        $entry[1]->editLink->name = 'edit';
        $entry[1]->editLink->title = 'Customer';
        $entry[1]->links[] = $links[1];
        $entry[1]->propertyContent = $linkContent[1];
        $entry[1]->resourceSetName = 'Customers';
        $entry[1]->updated = '2017-01-01T00:00:00+00:00';

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Customers';
        $selfLink->url = 'Customers';

        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Customers';
        $objectResult->title = new ODataTitle('Customers');
        $objectResult->selfLink = $selfLink;
        $objectResult->entries = $entry;
        $objectResult->updated = '2017-01-01T00:00:00+00:00';

        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteMultipleModelsHasPageOverrun()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host, 10);

        $results = new QueryResult();
        $results->hasMore = true;
        $results->results = [];
        for ($i = 1; $i < 301; $i++) {
            $model = new Customer2();
            $model->CustomerID = $i;
            $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
            $res = new QueryResult();
            $res->results = $model;
            $results->results[] = (0 == $i % 2) ? $res : $model;
        }

        $entries = [];
        for ($i = 1; $i < 301; $i++) {
            $editStub = 'Customers(CustomerID=\''.$i.'\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';

            $link = new ODataLink();
            $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
            $link->title = 'Orders';
            $link->type = 'application/atom+xml;type=feed';
            $link->url = $editStub.'/Orders';

            $cand = new ODataEntry();
            $cand->id = 'http://localhost/odata.svc/'.$editStub;
            $cand->editLink = new ODataLink();
            $cand->editLink->url = $editStub;
            $cand->editLink->name = 'edit';
            $cand->editLink->title = 'Customer';
            $cand->title = new ODataTitle('Customer');
            $cand->type = new ODataCategory('Customer');
            $cand->propertyContent = $this->generateCustomerProperties();
            $cand->propertyContent->properties['CustomerID']->value = strval($i);
            $cand->propertyContent->properties['CustomerGuid']->value = '123e4567-e89b-12d3-a456-426655440000';
            $cand->links = [$link];
            $cand->resourceSetName = 'Customers';
            $cand->updated = '2017-01-01T00:00:00+00:00';

            $entries[] = $cand;
        }

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Customers';
        $selfLink->url = 'Customers';

        $nextLink = new ODataLink();
        $nextLink->name = 'next';
        $nextLink->url = 'http://localhost/odata.svc/Customers?$skiptoken=\'300\''
                         .', guid\'123e4567-e89b-12d3-a456-426655440000\'';


        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Customers';
        $objectResult->title = new ODataTitle('Customers');
        $objectResult->selfLink = $selfLink;
        $objectResult->nextPageLink = $nextLink;
        $objectResult->entries = $entries;
        $objectResult->updated = '2017-01-01T00:00:00+00:00';

        $ironicResult = $ironic->writeTopLevelElements($results);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteModelsOnManyEndOfRelation()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)/Order_Details');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)/Order_Details');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $deet1 = new OrderDetails2();
        $deet1->OrderID = 1;
        $deet1->ProductID = 1;
        $deet2 = new OrderDetails2();
        $deet2->OrderID = 1;
        $deet2->ProductID = 2;

        $collection = new QueryResult();
        $collection->results = [$deet1, $deet2];

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Order_Details';
        $selfLink->url = 'Order_Details';

        $links = [];
        $links[] = [new ODataLink(), new ODataLink()];
        $links[] = [new ODataLink(), new ODataLink()];
        $links[0][0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $links[0][1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product';
        $links[1][0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $links[1][1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product';
        $links[0][0]->title = 'Order';
        $links[0][1]->title = 'Product';
        $links[1][0]->title = 'Order';
        $links[1][1]->title = 'Product';
        $links[0][0]->type = 'application/atom+xml;type=entry';
        $links[0][1]->type = 'application/atom+xml;type=entry';
        $links[1][0]->type = 'application/atom+xml;type=entry';
        $links[1][1]->type = 'application/atom+xml;type=entry';
        $links[0][0]->url = 'Order_Details(ProductID=1,OrderID=1)/Order';
        $links[0][1]->url = 'Order_Details(ProductID=1,OrderID=1)/Product';
        $links[1][0]->url = 'Order_Details(ProductID=2,OrderID=1)/Order';
        $links[1][1]->url = 'Order_Details(ProductID=2,OrderID=1)/Product';

        $entries = [new ODataEntry(), new ODataEntry()];
        $entries[0]->id = 'http://localhost/odata.svc/Order_Details(ProductID=1,OrderID=1)';
        $entries[0]->title = new ODataTitle('Order_Details');
        $entries[0]->editLink = new ODataLink();
        $entries[0]->editLink->url = 'Order_Details(ProductID=1,OrderID=1)';
        $entries[0]->editLink->title = 'Order_Details';
        $entries[0]->editLink->name = 'edit';
        $entries[0]->type = new ODataCategory('Order_Details');
        $entries[0]->propertyContent = $this->generateOrderDetailsProperties();
        $entries[0]->propertyContent->properties['ProductID']->value = '1';
        $entries[0]->propertyContent->properties['OrderID']->value = '1';
        $entries[0]->links = $links[0];
        $entries[0]->resourceSetName = 'Order_Details';
        $entries[0]->updated = '2017-01-01T00:00:00+00:00';
        $entries[1]->id = 'http://localhost/odata.svc/Order_Details(ProductID=2,OrderID=1)';
        $entries[1]->title = new ODataTitle('Order_Details');
        $entries[1]->editLink = new ODataLink();
        $entries[1]->editLink->url = 'Order_Details(ProductID=2,OrderID=1)';
        $entries[1]->editLink->title = 'Order_Details';
        $entries[1]->editLink->name = 'edit';
        $entries[1]->type = new ODataCategory('Order_Details');
        $entries[1]->propertyContent = $this->generateOrderDetailsProperties();
        $entries[1]->propertyContent->properties['ProductID']->value = '2';
        $entries[1]->propertyContent->properties['OrderID']->value = '1';
        $entries[1]->links = $links[1];
        $entries[1]->resourceSetName = 'Order_Details';
        $entries[1]->updated = '2017-01-01T00:00:00+00:00';

        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Orders(OrderID=1)/Order_Details';
        $objectResult->title = new ODataTitle('Order_Details');
        $objectResult->selfLink = $selfLink;
        $objectResult->entries = $entries;
        $objectResult->updated = '2017-01-01T00:00:00+00:00';

        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteTopLevelElementsAllExpanded()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers?$expand=Orders');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers?$expand=Orders');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $order = new Order2();
        $order->OrderID = 1;
        $order->ProductID = 42;

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->Orders = [$order];

        $results = [new QueryResult(), new QueryResult()];
        $results[0]->results = $model;
        $results[1]->results = $model;

        $collection = new QueryResult();
        $collection->results = $results;

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Orders');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true, true, true, false);
        $node->shouldReceive('canSelectAllProperties')->andReturn(true);
        $node->shouldReceive('findNode')->andReturn($expandNode);

        $ironic->getRequest()->setRootProjectionNode($node);

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Customers';
        $selfLink->url = 'Customers';

        $subLinks = [new ODataLink(), new ODataLink()];
        $subLinks[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer';
        $subLinks[0]->title = 'Customer';
        $subLinks[0]->type = 'application/atom+xml;type=entry';
        $subLinks[0]->url = 'Orders(OrderID=1)/Customer';
        $subLinks[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details';
        $subLinks[1]->title = 'Order_Details';
        $subLinks[1]->type = 'application/atom+xml;type=feed';
        $subLinks[1]->url = 'Orders(OrderID=1)/Order_Details';

        $subEntry = new ODataEntry();
        $subEntry->id = 'http://localhost/odata.svc/Orders(OrderID=1)';
        $subEntry->title = new ODataTitle('Order');
        $subEntry->editLink = new ODataLink();
        $subEntry->editLink->url = 'Orders(OrderID=1)';
        $subEntry->editLink->name = 'edit';
        $subEntry->editLink->title = 'Order';
        $subEntry->type = new ODataCategory('Order');
        $subEntry->resourceSetName = 'Orders';
        $subEntry->propertyContent = $this->generateOrderProperties();
        $subEntry->propertyContent->properties['OrderID']->value = '1';
        $subEntry->links = $subLinks;
        $subEntry->updated = '2017-01-01T00:00:00+00:00';

        $subSelf = new ODataLink();
        $subSelf->name = 'self';
        $subSelf->title = 'Orders';
        $subSelf->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';

        $subFeed = new ODataFeed();
        $subFeed->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                       .'=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $subFeed->title = new ODataTitle('Orders');
        $subFeed->selfLink = $subSelf;
        $subFeed->entries = [$subEntry];
        $subFeed->updated = '2017-01-01T00:00:00+00:00';

        $link = new ODataLink();
        $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
        $link->title = 'Orders';
        $link->type = 'application/atom+xml;type=feed';
        $link->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $link->isCollection = true;
        $link->isExpanded = true;
        $link->expandedResult = $subFeed;

        $entry = new ODataEntry();
        $entry->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                     .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry->title = new ODataTitle('Customer');
        $entry->editLink = new ODataLink();
        $entry->editLink->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry->editLink->name = 'edit';
        $entry->editLink->title = 'Customer';
        $entry->type = new ODataCategory('Customer');
        $entry->resourceSetName = 'Customers';
        $entry->propertyContent = $this->generateCustomerProperties();
        $entry->propertyContent->properties['CustomerID']->value = '1';
        $entry->propertyContent->properties['CustomerGuid']->value = '123e4567-e89b-12d3-a456-426655440000';
        $entry->links = [$link];
        $entry->updated = '2017-01-01T00:00:00+00:00';

        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Customers';
        $objectResult->title = new ODataTitle('Customers');
        $objectResult->selfLink = $selfLink;
        $objectResult->entries = [$entry, $entry];
        $objectResult->updated = '2017-01-01T00:00:00+00:00';

        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteNullTopLevelElements()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $collection = new QueryResult();
        $collection->results = null;

        $models = null;
        $expected = 'assert(): !is_array($entryObjects->results) failed';
        $expectedExceptionClass = \PHPUnit_Framework_Error_Warning::class;
        $actual = null;
        $actualExceptionClass = null;

        try {
            $ironic->writeTopLevelElements($collection);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteElementsWithMediaLinks()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Employees');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Employees');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $mod1 = new Employee2();
        $mod1->EmployeeID = 1;
        $mod1->TumbNail_48X48 = 'Bork bork bork!';
        $mod2 = new Employee2();
        $mod2->EmployeeID = 2;
        $mod2->TumbNail_48X48 = 'Bork bork bork!';

        $collection = new QueryResult();
        $collection->results = [$mod1, $mod2];

        $mediaLink = [
            new ODataMediaLink('Employee', '/$value', 'Employees(EmployeeID=\'1\')/$value', '*/*', '', 'edit-media'),
            new ODataMediaLink('Employee', '/$value', 'Employees(EmployeeID=\'2\')/$value', '*/*', '', 'edit-media')
        ];

        $mediaLinks = [
            new ODataMediaLink(
                'TumbNail_48X48',
                'Employees(EmployeeID=\'1\')/TumbNail_48X48',
                'Employees(EmployeeID=\'1\')/TumbNail_48X48',
                'application/octet-stream',
                ''
            ),
            new ODataMediaLink(
                'TumbNail_48X48',
                'Employees(EmployeeID=\'2\')/TumbNail_48X48',
                'Employees(EmployeeID=\'2\')/TumbNail_48X48',
                'application/octet-stream',
                ''
            )
        ];

        $links = [];
        $links[] = [new ODataLink(), new ODataLink()];
        $links[] = [new ODataLink(), new ODataLink()];
        $links[0][0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager';
        $links[0][1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates';
        $links[1][0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager';
        $links[1][1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates';
        $links[0][0]->title = 'Manager';
        $links[0][1]->title = 'Subordinates';
        $links[1][0]->title = 'Manager';
        $links[1][1]->title = 'Subordinates';
        $links[0][0]->type = 'application/atom+xml;type=entry';
        $links[0][1]->type = 'application/atom+xml;type=feed';
        $links[1][0]->type = 'application/atom+xml;type=entry';
        $links[1][1]->type = 'application/atom+xml;type=feed';
        $links[0][0]->url = 'Employees(EmployeeID=\'1\')/Manager';
        $links[0][1]->url = 'Employees(EmployeeID=\'1\')/Subordinates';
        $links[1][0]->url = 'Employees(EmployeeID=\'2\')/Manager';
        $links[1][1]->url = 'Employees(EmployeeID=\'2\')/Subordinates';

        $prop1 = $this->generateEmployeeProperties();
        $prop2 = $this->generateEmployeeProperties();

        $prop1->properties['EmployeeID']->value = '1';
        $prop2->properties['EmployeeID']->value = '2';

        $entries = [new ODataEntry(), new ODataEntry];
        $entries[0]->id = 'http://localhost/odata.svc/Employees(EmployeeID=\'1\')';
        $entries[0]->title = new ODataTitle('Employee');
        $entries[0]->editLink = new ODataLink();
        $entries[0]->editLink->url = 'Employees(EmployeeID=\'1\')';
        $entries[0]->editLink->name = 'edit';
        $entries[0]->editLink->title = 'Employee';
        $entries[0]->type = new ODataCategory('Employee');
        $entries[0]->isMediaLinkEntry = true;
        $entries[0]->mediaLink = $mediaLink[0];
        $entries[0]->mediaLinks[] = $mediaLinks[0];
        $entries[0]->links = $links[0];
        $entries[0]->propertyContent = $prop1;
        $entries[0]->resourceSetName = 'Employees';
        $entries[0]->updated = '2017-01-01T00:00:00+00:00';
        $entries[1]->id = 'http://localhost/odata.svc/Employees(EmployeeID=\'2\')';
        $entries[1]->title = new ODataTitle('Employee');
        $entries[1]->editLink = new ODataLink();
        $entries[1]->editLink->url = 'Employees(EmployeeID=\'2\')';
        $entries[1]->editLink->name = 'edit';
        $entries[1]->editLink->title = 'Employee';
        $entries[1]->type = new ODataCategory('Employee');
        $entries[1]->isMediaLinkEntry = true;
        $entries[1]->mediaLink = $mediaLink[1];
        $entries[1]->mediaLinks[] = $mediaLinks[1];
        $entries[1]->links = $links[1];
        $entries[1]->propertyContent = $prop2;
        $entries[1]->resourceSetName = 'Employees';
        $entries[1]->updated = '2017-01-01T00:00:00+00:00';

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Employees';
        $selfLink->url = 'Employees';

        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Employees';
        $objectResult->title = new ODataTitle('Employees');
        $objectResult->selfLink = $selfLink;
        $objectResult->entries = $entries;
        $objectResult->updated = '2017-01-01T00:00:00+00:00';

        $ironicResult = $ironic->writeTopLevelElements($collection);

        // zero out etag values
        $ironicResult->entries[0]->mediaLink->eTag = '';
        $ironicResult->entries[1]->mediaLink->eTag = '';
        $ironicResult->entries[0]->mediaLinks[0]->eTag = '';
        $ironicResult->entries[1]->mediaLinks[0]->eTag = '';

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
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
     * @param  mixed $pageSize
     * @return array
     */
    private function setUpSerialisers($query, $meta, $host, $pageSize = 200)
    {
        // default data service
        $service = new TestDataService($query, $meta, $host);
        $service->maxPageSize = $pageSize;
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());
        return [$object, $ironic];
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateEmployeeProperties()
    {
        $prop1 = new ODataPropertyContent();
        $prop1->properties = [
            'EmployeeID' => new ODataProperty(),
            'FirstName' => new ODataProperty(),
            'LastName' => new ODataProperty(),
            'ReportsTo' => new ODataProperty(),
            'Emails' => new ODataProperty()
        ];
        $prop1->properties['EmployeeID']->name = 'EmployeeID';
        $prop1->properties['EmployeeID']->typeName = 'Edm.String';
        $prop1->properties['FirstName']->name = 'FirstName';
        $prop1->properties['FirstName']->typeName = 'Edm.String';
        $prop1->properties['LastName']->name = 'LastName';
        $prop1->properties['LastName']->typeName = 'Edm.String';
        $prop1->properties['ReportsTo']->name = 'ReportsTo';
        $prop1->properties['ReportsTo']->typeName = 'Edm.Int32';
        $prop1->properties['Emails']->name = 'Emails';
        $prop1->properties['Emails']->typeName = 'Collection(Edm.String)';
        return $prop1;
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateCustomerProperties()
    {
        $content1 = new ODataPropertyContent();
        $content1->properties = [
            'CustomerID' => new ODataProperty(),
            'CustomerGuid' => new ODataProperty(),
            'CustomerName' => new ODataProperty(),
            'Country' => new ODataProperty(),
            'Rating' => new ODataProperty(),
            'Photo' => new ODataProperty(),
            'Address' => new ODataProperty()
        ];
        $content1->properties['CustomerID']->name = 'CustomerID';
        $content1->properties['CustomerID']->typeName = 'Edm.String';
        $content1->properties['CustomerGuid']->name = 'CustomerGuid';
        $content1->properties['CustomerGuid']->typeName = 'Edm.Guid';
        $content1->properties['CustomerName']->name = 'CustomerName';
        $content1->properties['CustomerName']->typeName = 'Edm.String';
        $content1->properties['Country']->name = 'Country';
        $content1->properties['Country']->typeName = 'Edm.String';
        $content1->properties['Rating']->name = 'Rating';
        $content1->properties['Rating']->typeName = 'Edm.Int32';
        $content1->properties['Photo']->name = 'Photo';
        $content1->properties['Photo']->typeName = 'Edm.Binary';
        $content1->properties['Address']->name = 'Address';
        $content1->properties['Address']->typeName = 'Address';
        return $content1;
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateOrderDetailsProperties()
    {
        $content1 = new ODataPropertyContent();
        $content1->properties = [
            'ProductID' => new ODataProperty(),
            'OrderID' => new ODataProperty(),
            'UnitPrice' => new ODataProperty(),
            'Quantity' => new ODataProperty(),
            'Discount' => new ODataProperty()
        ];
        $content1->properties['ProductID']->name = 'ProductID';
        $content1->properties['ProductID']->typeName = 'Edm.Int32';
        $content1->properties['OrderID']->name = 'OrderID';
        $content1->properties['OrderID']->typeName = 'Edm.Int32';
        $content1->properties['UnitPrice']->name = 'UnitPrice';
        $content1->properties['UnitPrice']->typeName = 'Edm.Decimal';
        $content1->properties['Quantity']->name = 'Quantity';
        $content1->properties['Quantity']->typeName = 'Edm.Int16';
        $content1->properties['Discount']->name = 'Discount';
        $content1->properties['Discount']->typeName = 'Edm.Single';
        return $content1;
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateOrderProperties()
    {
        $content1 = new ODataPropertyContent();
        $content1->properties = [
            'OrderID' => new ODataProperty(),
            'OrderDate' => new ODataProperty(),
            'DeliveryDate' => new ODataProperty(),
            'ShipName' => new ODataProperty(),
            'ItemCount' => new ODataProperty(),
            'QualityRate' => new ODataProperty(),
            'Price' => new ODataProperty()
        ];
        $content1->properties['OrderID']->name = 'OrderID';
        $content1->properties['OrderID']->typeName = 'Edm.Int32';
        $content1->properties['OrderDate']->name = 'OrderDate';
        $content1->properties['OrderDate']->typeName = 'Edm.DateTime';
        $content1->properties['DeliveryDate']->name = 'DeliveryDate';
        $content1->properties['DeliveryDate']->typeName = 'Edm.DateTime';
        $content1->properties['ShipName']->name = 'ShipName';
        $content1->properties['ShipName']->typeName = 'Edm.String';
        $content1->properties['ItemCount']->name = 'ItemCount';
        $content1->properties['ItemCount']->typeName = 'Edm.Int32';
        $content1->properties['QualityRate']->name = 'QualityRate';
        $content1->properties['QualityRate']->typeName = 'Edm.Int32';
        $content1->properties['Price']->name = 'Price';
        $content1->properties['Price']->typeName = 'Edm.Double';

        return $content1;
    }
}
