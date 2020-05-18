<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\IncomingRequest;
use POData\OperationContext\Web\WebOperationContext as OperationContextAdapter;
use POData\Providers\Expression\IExpressionProvider;
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
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\ObjectModel\reusableEntityClass1;

class SerialiserWriteBagTest extends SerialiserTestBase
{
    public function testWriteNullBagObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $propName = 'makeItPhunkee';
        $rType    = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX());

        $collection          = new QueryResult();
        $collection->results = null;

        $objectResult                              = new ODataPropertyContent(
            [
                'makeItPhunkee' =>  new ODataProperty('makeItPhunkee', 'Collection(stopHammerTime)')
            ]
        );
        $ironicResult                              = $ironic->writeTopLevelBagObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteBadBagObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $propName = 'makeItPhunkee';
        $rType    = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX());

        $collection          = new QueryResult();
        $collection->results = 'NARF!';

        $expected               = 'Bag parameter must be null or array';
        $expectedExceptionClass = InvalidOperationException::class;
        $actual                 = null;
        $actualExceptionClass   = null;

        try {
            $ironic->writeTopLevelBagObject($collection, $propName, $rType);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual               = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteBagObjectWithInconsistentType()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $propName = 'makeItPhunkee';
        $rType    = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());

        $collection          = new QueryResult();
        $collection->results = null;

        $expected = '$bagItemResourceTypeKind != ResourceTypeKind::PRIMITIVE &&'
                    . ' $bagItemResourceTypeKind != ResourceTypeKind::COMPLEX';
        $expectedExceptionClass = InvalidOperationException::class;
        $actual                 = null;
        $actualExceptionClass   = null;

        try {
            $ironic->writeTopLevelBagObject($collection, $propName, $rType);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual               = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteEmptyBagObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $propName = 'makeItPhunkee';
        $rType    = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());

        $collection          = new QueryResult();
        $collection->results = [];

        $objectResult                              = new ODataPropertyContent(
            [
                'makeItPhunkee' =>  new ODataProperty('makeItPhunkee', 'Collection(stopHammerTime)', null)
            ]
        );
        $ironicResult                              = $ironic->writeTopLevelBagObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteBagObjectOfPrimitiveTypes()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $iType = new StringType();

        $propName = 'makeItPhunkee';
        $rType    = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $collection          = new QueryResult();
        $collection->results = ['eins', 'zwei', 'polizei'];

        $bag                                       = new ODataBagContent();
        $bag->setPropertyContents(['eins', 'zwei', 'polizei']);
        $objectResult                              = new ODataPropertyContent(
            [
                'makeItPhunkee' => new ODataProperty('makeItPhunkee', 'Collection(stopHammerTime)', $bag)
            ]
        );
        $ironicResult                              = $ironic->writeTopLevelBagObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteBagObjectOfPrimitiveTypesIncludingNulls()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $iType = new StringType();

        $propName = 'makeItPhunkee';
        $rType    = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $collection          = new QueryResult();
        $collection->results = ['eins', null, 'zwei', null, 'polizei'];

        $bag                                       = new ODataBagContent();
        $bag->setPropertyContents(['eins', 'zwei', 'polizei']);
        $objectResult                              = new ODataPropertyContent(
            [
                'makeItPhunkee' => new ODataProperty('makeItPhunkee', 'Collection(stopHammerTime)', $bag)
            ]
        );
        $ironicResult                              = $ironic->writeTopLevelBagObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteBagObjectOfComplexObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $iType = new StringType();

        $subType1 = m::mock(ResourceType::class);
        $subType1->shouldReceive('getInstanceType')->andReturn(new StringType());
        $subType1->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());

        $rProp1 = m::mock(ResourceProperty::class);
        $rProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $rProp1->shouldReceive('getName')->andReturn('name');
        $rProp1->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp1->shouldReceive('getResourceType')->andReturn($subType1);

        $rProp2 = m::mock(ResourceProperty::class);
        $rProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $rProp2->shouldReceive('getName')->andReturn('type');
        $rProp2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp2->shouldReceive('getResourceType')->andReturn($subType1);

        $propName = 'makeItPhunkee';
        $rType    = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX());
        $rType->shouldReceive('getAllProperties')->andReturn([$rProp1, $rProp2]);

        $model       = new reusableEntityClass1();
        $model->name = 'name';
        $model->type = 'type';

        $collection          = new QueryResult();
        $collection->results = [$model];

        $complex             = new ODataPropertyContent(
            [
                'name' => new ODataProperty('name', 'Edm.String', 'name'),
                'type' => new ODataProperty('type', 'Edm.String', 'type')
            ]
        );

        $bag                                       = new ODataBagContent();
        $bag->setPropertyContents([$complex]);
        $objectResult                              = new ODataPropertyContent(
            [
                'makeItPhunkee' => new ODataProperty('makeItPhunkee', 'Collection(stopHammerTime)', $bag)
            ]
        );
        $ironicResult                              = $ironic->writeTopLevelBagObject($collection, $propName, $rType);

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

        $meta     = NorthWindMetadata::Create();
        $provider = m::mock(IExpressionProvider::class);

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getExpressionProvider')->andReturn($provider);

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
