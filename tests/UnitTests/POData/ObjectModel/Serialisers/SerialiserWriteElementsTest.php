<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use POData\ObjectModel\ObjectModelSerializer;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use Mockery as m;
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
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
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

        $objectResult = $object->writeTopLevelElements($collection);
        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteMultipleModelsHasPageOverrun()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

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

        $objectResult = $object->writeTopLevelElements($results);
        $ironicResult = $ironic->writeTopLevelElements($results);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteModelsOnManyEndOfRelation()
    {
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

        $objectResult = $object->writeTopLevelElements($collection);
        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteTopLevelElementsAllExpanded()
    {
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

        $object->getRequest()->setRootProjectionNode($node);
        $ironic->getRequest()->setRootProjectionNode($node);

        $objectResult = $object->writeTopLevelElements($collection);
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
        $expected = null;
        $expectedExceptionClass = null;
        $actual = null;
        $actualExceptionClass = null;

        try {
            $object->writeTopLevelElements($collection);
        } catch (\Exception $e) {
            $expectedExceptionClass = get_class($e);
            $expected = $e->getMessage();
        }
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

        $objectResult = $object->writeTopLevelElements($collection);
        $ironicResult = $ironic->writeTopLevelElements($collection);

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
}
