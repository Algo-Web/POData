<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
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
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\ObjectModel\reusableEntityClass1;

class SerialiserWriteComplexTest extends SerialiserTestBase
{
    public function testWriteNullComplexObject()
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

        $collection          = new QueryResult();
        $collection->results = null;

        $objProp                                   = new ODataProperty();
        $objProp->name                             = 'makeItPhunkee';
        $objProp->typeName                         = 'stopHammerTime';
        $objectResult                              = new ODataPropertyContent();
        $objectResult->properties['makeItPhunkee'] = $objProp;
        $ironicResult                              = $ironic->writeTopLevelComplexObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteBadComplexObject()
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
        $collection->results = [ 'foo' ];

        $expected               = 'Supplied $customObject must be an object';
        $expectedExceptionClass = InvalidOperationException::class;
        $actual                 = null;
        $actualExceptionClass   = null;

        try {
            $ironic->writeTopLevelComplexObject($collection, $propName, $rType);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual               = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteArrayAsComplexObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $subType1 = m::mock(ResourceType::class);
        $subType1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $subType2 = m::mock(ResourceType::class);
        $subType2->shouldReceive('getInstanceType')->andReturn(new StringType());

        $rProp1 = m::mock(ResourceProperty::class);
        $rProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $rProp1->shouldReceive('getName')->andReturn('name');
        $rProp1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $rProp1->shouldReceive('getResourceType')->andReturn($subType1);
        $rProp2 = m::mock(ResourceProperty::class);
        $rProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $rProp2->shouldReceive('getName')->andReturn('type');
        $rProp2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp2->shouldReceive('getResourceType')->andReturn($subType2);

        $propName = 'makeItPhunkee';
        $rType    = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX());
        $rType->shouldReceive('getAllProperties')->andReturn([$rProp1, $rProp2]);

        $model       = new reusableEntityClass1();
        $model->name = 11;
        $model->type = 'bar';

        $collection          = new QueryResult();
        $collection->results = [$model];

        $expected               = 'Supplied $customObject must be an object';
        $expectedExceptionClass = InvalidOperationException::class;
        $actual                 = null;
        $actualExceptionClass   = null;

        try {
            $ironic->writeTopLevelComplexObject($collection, $propName, $rType);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual               = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteNonEloquentModelComplexObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $subType1 = m::mock(ResourceType::class);
        $subType1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $subType2 = m::mock(ResourceType::class);
        $subType2->shouldReceive('getInstanceType')->andReturn(new StringType());

        $rProp1 = m::mock(ResourceProperty::class);
        $rProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $rProp1->shouldReceive('getName')->andReturn('name');
        $rProp1->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp1->shouldReceive('getResourceType')->andReturn($subType1);
        $rProp2 = m::mock(ResourceProperty::class);
        $rProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $rProp2->shouldReceive('getName')->andReturn('type');
        $rProp2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp2->shouldReceive('getResourceType')->andReturn($subType2);

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
        $collection->results = $model;

        $comp1           = new ODataProperty();
        $comp1->name     = 'name';
        $comp1->typeName = 'Edm.String';
        $comp1->value    = 'name';
        $comp2           = new ODataProperty();
        $comp2->name     = 'type';
        $comp2->typeName = 'Edm.String';
        $comp2->value    = 'type';

        $complex             = new ODataPropertyContent();
        $complex->properties = ['name' => $comp1, 'type' => $comp2];

        $objProp                                   = new ODataProperty();
        $objProp->name                             = 'makeItPhunkee';
        $objProp->typeName                         = 'stopHammerTime';
        $objProp->value                            = $complex;
        $objectResult                              = new ODataPropertyContent();
        $objectResult->properties['makeItPhunkee'] = $objProp;
        $ironicResult                              = $ironic->writeTopLevelComplexObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteEloquentComplexObjectWithNonEloquentComplexProperty()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $rTypeBase = m::mock(ResourceType::class);
        $rTypeBase->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rTypeBase->shouldReceive('getFullName')->andReturn('putEmHigh');

        $subProp1 = m::mock(ResourceProperty::class);
        $subProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $subProp1->shouldReceive('getName')->andReturn('name');
        $subProp1->shouldReceive('getInstanceType')->andReturn(new StringType());
        $subProp1->shouldReceive('getResourceType')->andReturn($rTypeBase);

        $subProp2 = m::mock(ResourceProperty::class);
        $subProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $subProp2->shouldReceive('getName')->andReturn('type');
        $subProp2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $subProp2->shouldReceive('getResourceType')->andReturn($rTypeBase);

        $subType2 = m::mock(ResourceType::class);
        $subType2->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX());
        $subType2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $subType2->shouldReceive('getFullName')->andReturn('paintItBlack');
        $subType2->shouldReceive('getAllProperties')->andReturn([$subProp1, $subProp2]);

        $rProp1 = m::mock(ResourceProperty::class);
        $rProp1->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG()])->andReturn(false);
        $rProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $rProp1->shouldReceive('getName')->andReturn('name');
        $rProp1->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp1->shouldReceive('getResourceType')->andReturn($subType2);

        $rProp2 = m::mock(ResourceProperty::class);
        $rProp2->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG()])->andReturn(false);
        $rProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE());
        $rProp2->shouldReceive('getName')->andReturn('type');
        $rProp2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp2->shouldReceive('getResourceType')->andReturn($subType2);

        $propName = 'makeItPhunkee';
        $rType    = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX());
        $rType->shouldReceive('getAllProperties')->andReturn([$rProp1, $rProp2]);

        $zoidberg       = new reusableEntityClass1();
        $zoidberg->name = 'name';
        $zoidberg->type = 'type';

        $model       = new reusableEntityClass1();
        $model->name = 11;
        $model->type = $zoidberg;

        $collection          = new QueryResult();
        $collection->results = $model;

        $zoid1           = new ODataProperty();
        $zoid1->name     = 'name';
        $zoid1->typeName = 'Edm.String';
        $zoid1->value    = 'name';
        $zoid2           = new ODataProperty();
        $zoid2->name     = 'type';
        $zoid2->typeName = 'Edm.String';
        $zoid2->value    = 'type';

        $zoidContent             = new ODataPropertyContent();
        $zoidContent->properties = ['name' => $zoid1, 'type' => $zoid2];

        $comp1           = new ODataProperty();
        $comp1->name     = 'name';
        $comp1->typeName = 'Edm.String';
        $comp1->value    = '11';
        $comp2           = new ODataProperty();
        $comp2->name     = 'type';
        $comp2->typeName = 'paintItBlack';
        $comp2->value    = $zoidContent;

        $complex             = new ODataPropertyContent();
        $complex->properties = ['name' => $comp1, 'type' => $comp2];

        $objProp                                   = new ODataProperty();
        $objProp->name                             = 'makeItPhunkee';
        $objProp->typeName                         = 'stopHammerTime';
        $objProp->value                            = $complex;
        $objectResult                              = new ODataPropertyContent();
        $objectResult->properties['makeItPhunkee'] = $objProp;
        $ironicResult                              = $ironic->writeTopLevelComplexObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteEloquentModelComplexObjectLoopDeLoop()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $subType1 = m::mock(ResourceType::class);
        $subType1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $subType1->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());

        $rProp1 = m::mock(ResourceProperty::class);
        $rProp1->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG()])->andReturn(false);
        $rProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $rProp1->shouldReceive('getName')->andReturn('type');
        $rProp1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $rProp1->shouldReceive('getResourceType')->andReturn($subType1);

        $rProp2 = m::mock(ResourceProperty::class);
        $rProp2->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG()])->andReturn(false);
        $rProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE());
        $rProp2->shouldReceive('getName')->andReturn('name');
        $rProp2->shouldReceive('getInstanceType')->andReturn(new StringType());

        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX());
        $rType->shouldReceive('getAllProperties')->andReturn([$rProp1, $rProp2]);
        $rProp2->shouldReceive('getResourceType')->andReturn($rType);

        $zoidberg       = new reusableEntityClass1();
        $zoidberg->type = 11;
        $zoidberg->name = null;

        $subModel       = new reusableEntityClass1();
        $subModel->type = 11;
        $subModel->name = $zoidberg;

        $model       = new reusableEntityClass1();
        $model->type = 11;
        $model->name = $subModel;

        $zoidberg->name = $model;

        $collection          = new QueryResult();
        $collection->results = $model;

        $propName = 'makeItPhunkee';

        $expected = 'A circular loop was detected while serializing the property \'name\'. You must make sure'
                    . ' that loops are not present in properties that return a bag or complex type.';
        $expectedExceptionClass = InvalidOperationException::class;
        $actual                 = null;
        $actualExceptionClass   = null;

        try {
            $ironic->writeTopLevelComplexObject($collection, $propName, $rType);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual               = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testMatchPrimitiveHighball()
    {
        $this->assertFalse(IronicSerialiser::isMatchPrimitive(29));
        $this->assertTrue(IronicSerialiser::isMatchPrimitive(28));
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
        $service                            = new TestDataService($query, $meta, $host);
        $processor                          = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $ironic = new IronicSerialiser($service, $processor->getRequest());
        return $ironic;
    }
}
