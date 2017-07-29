<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use POData\ObjectModel\ObjectModelSerializer;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use Mockery as m;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;

class SerialiserWriteUrlTest extends SerialiserTestBase
{
    public function testWriteUrlForBasicModel()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();
        $model->CustomerID = 2;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $result = new QueryResult();
        $result->results = $model;

        $objectResult = $object->writeUrlElement($result);
        $ironicResult = $ironic->writeUrlElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteUrlForBasicModelAsCollectionWithCount()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();
        $model->CustomerID = 2;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $result = new QueryResult();
        $result->results = $model;

        $collection = new QueryResult();
        $collection->results = [$result];

        $objectResult = $object->writeUrlElements($collection);
        $ironicResult = $ironic->writeUrlElements($collection);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteUrlForBasicModelAsCollectionWithCountAndPageOverrun()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();
        $model->CustomerID = 2;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $result = new QueryResult();
        $result->results = $model;

        $collection = new QueryResult();
        $collection->results = [$result];
        $collection->hasMore = true;

        $objectResult = $object->writeUrlElements($collection);
        $ironicResult = $ironic->writeUrlElements($collection);
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
    private function setUpSerialisers($query, $meta, $host)
    {
        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());
        return array($object, $ironic);
    }
}
