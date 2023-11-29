<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Mockery as m;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataNextPageLink;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\IncomingRequest;
use POData\OperationContext\Web\WebOperationContext as OperationContextAdapter;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\IReadQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;

class SerialiserWriteUrlTest extends SerialiserTestBase
{
    public function testWriteUrlForBasicModel()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);
        $ironic                    = $this->setUpSerialisers($query, $meta, $host);

        $model               = new Customer2();
        $model->CustomerID   = 2;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $result          = new QueryResult();
        $result->results = $model;

        $objectResult      = new ODataURL('http://localhost/odata.svc/Customers(CustomerID=\'2\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')');
        $ironicResult = $ironic->writeUrlElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteUrlForBasicModelAsCollectionWithCount()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host, 2);

        $model               = new Customer2();
        $model->CustomerID   = 2;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $result          = new QueryResult();
        $result->results = $model;

        $collection          = new QueryResult();
        $collection->results = [$result, $model];

        $url      = new ODataURL('http://localhost/odata.svc/Customers(CustomerID=\'2\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')');

        $objectResult         = new ODataURLCollection(
            [$url,$url],
            null,
            2
        );
        $ironicResult         = $ironic->writeUrlElements($collection);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteUrlForBasicModelAsCollectionWithCountAndPageOverrun()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $model               = new Customer2();
        $model->CustomerID   = 2;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $result          = new QueryResult();
        $result->results = $model;

        $collection          = new QueryResult();
        $collection->results = [$result];
        $collection->hasMore = true;

        $url      = new ODataURL('http://localhost/odata.svc/Customers(CustomerID=\'2\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')');

        $nextLink       = new ODataNextPageLink('');
        $nextLink->setName('next');
        $nextLink->setUrl('http://localhost/odata.svc/Customers?$skiptoken=\'2\', '
                         . 'guid\'123e4567-e89b-12d3-a456-426655440000\'');

        $objectResult               = new ODataURLCollection([$url], $nextLink, 1);
        $ironicResult               = $ironic->writeUrlElements($collection);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
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
        $query = m::mock(IReadQueryProvider::class);

        return [$host, $meta, $query];
    }

    /**
     * @param $query
     * @param $meta
     * @param $host
     * @param  mixed $count
     * @return array
     */
    private function setUpSerialisers($query, $meta, $host, $count = 1)
    {
        // default data service
        $service                            = new TestDataService($query, $meta, $host);
        $processor                          = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue($count);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());
        return $ironic;
    }
}
