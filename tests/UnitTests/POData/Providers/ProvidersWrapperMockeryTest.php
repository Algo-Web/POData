<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Common\ODataException;
use POData\Configuration\IServiceConfiguration;
use POData\Providers\Metadata\EdmSchemaVersion;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceComplexType;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceFunctionType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\ProvidersQueryWrapper;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use UnitTests\POData\Facets\NorthWind1\Address2;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\TestCase;

class ProvidersWrapperMockeryTest extends TestCase
{
    public function testGetResourceSetsByMatchingName()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn('cheese');

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn('biscuits');

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets('cheese');

        $this->assertEquals(1, count($actual));
        $this->assertEquals('cheese', $actual[0]->getName());
    }

    public function testGetResourceSetsByOverlappingArray()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn('cheese');

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn('biscuits');

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets(['biscuits', 'tea']);

        $this->assertEquals(1, count($actual));
        $this->assertEquals('biscuits', $actual[0]->getName());
    }

    public function testGetResourceSetsByDisjointArray()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn('cheese');

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn('biscuits');

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets(['coffee', 'tea']);

        $this->assertEquals(0, count($actual));
    }

    public function testGetResourceSetsDefault()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn('cheese');

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn('biscuits');

        $targNames = ['cheese', 'biscuits'];

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets(null);

        $this->assertEquals(2, count($actual));

        foreach ($actual as $result) {
            $resultName = $result->getName();
            $this->assertTrue(in_array($resultName, $targNames));
            $targNames = array_diff($targNames, [$resultName]);
        }
        $this->assertEquals(0, count($targNames));
    }

    public function testGetResourceSetsNonArrayNonStringNonNullInput()
    {
        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        $exceptionThrown = false;
        $expectedMessage = 'Input parameter must be absent, null, string or array';

        try {
            $foo->getResourceSets(new \StdClass());
        } catch (\ErrorException $e) {
            $exceptionThrown = ($expectedMessage == $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Object input should have thrown error exception');
    }

    public function testResolveNullSingleton()
    {
        $meta = m::mock(SimpleMetadataProvider::class)->makePartial();
        $query = m::mock(IQueryProvider::class);
        $service = m::mock(IServiceConfiguration::class);

        $foo = new ProvidersWrapper($meta, $query, $service);
        $this->assertNull($foo->resolveSingleton('singleton'));
    }

    public function testGetNullSingletons()
    {
        $meta = m::mock(SimpleMetadataProvider::class)->makePartial();
        $query = m::mock(IQueryProvider::class);
        $service = m::mock(IServiceConfiguration::class);

        $foo = new ProvidersWrapper($meta, $query, $service);
        $result = $foo->getSingletons();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testResolveNonNullSingleton()
    {
        $func = m::mock(ResourceFunctionType::class);
        $func->shouldReceive('getName')->andReturn('hammerTime')->once();

        $meta = m::mock(SimpleMetadataProvider::class)->makePartial();
        $meta->shouldReceive('getSingletons')->andReturn(['singleton' => $func]);
        $query = m::mock(IQueryProvider::class);
        $service = m::mock(IServiceConfiguration::class);

        $foo = new ProvidersWrapper($meta, $query, $service);
        $result = $foo->resolveSingleton('singleton');
        $this->assertTrue($result instanceof ResourceFunctionType);
        $this->assertEquals('hammerTime', $func->getName());
    }

    public function testGetNonNullSingletons()
    {
        $func = m::mock(ResourceFunctionType::class);

        $meta = m::mock(SimpleMetadataProvider::class)->makePartial();
        $meta->shouldReceive('getSingletons')->andReturn(['singleton' => $func]);
        $query = m::mock(IQueryProvider::class);
        $service = m::mock(IServiceConfiguration::class);

        $foo = new ProvidersWrapper($meta, $query, $service);
        $result = $foo->getSingletons();
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
    }

    public function testGetEmptySingletons()
    {
        $meta = m::mock(SimpleMetadataProvider::class)->makePartial();
        $meta->shouldReceive('getSingletons')->andReturn([]);
        $query = m::mock(IQueryProvider::class);
        $service = m::mock(IServiceConfiguration::class);

        $foo = new ProvidersWrapper($meta, $query, $service);
        $result = $foo->getSingletons();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testGetResourceFromResourceSet()
    {
        $query = m::mock(ProvidersQueryWrapper::class);
        $query->shouldReceive('getResourceFromResourceSet')->andReturn(null)->once();

        $set = m::mock(ResourceSet::class);
        $key = m::mock(KeyDescriptor::class);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($query);

        $this->assertNull($foo->getResourceFromResourceSet($set, $key));
    }

    public function testPutResource()
    {
        $query = m::mock(ProvidersQueryWrapper::class);
        $query->shouldReceive('putResource')->andReturn(null)->once();

        $set = m::mock(ResourceSet::class);
        $key = m::mock(KeyDescriptor::class);
        $data = [];

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($query);

        $this->assertNull($foo->putResource($set, $key, $data));
    }

    public function testGetRelatedResourceSet()
    {
        $query = m::mock(ProvidersQueryWrapper::class);
        $query->shouldReceive('getRelatedResourceSet')->andReturn(null)->once();

        $type = QueryType::ENTITIES();

        $set = m::mock(ResourceSet::class);
        $key = m::mock(KeyDescriptor::class);
        $property = m::mock(ResourceProperty::class);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($query);

        $this->assertNull(
            $foo->getRelatedResourceSet($type, $set, new \stdClass(), $set, $property, null, null, null, null)
        );
    }

    public function testGetResourceFromRelatedResourceSet()
    {
        $query = m::mock(ProvidersQueryWrapper::class);
        $query->shouldReceive('getResourceFromRelatedResourceSet')->andReturn(null)->once();

        $set = m::mock(ResourceSet::class);
        $key = m::mock(KeyDescriptor::class);
        $property = m::mock(ResourceProperty::class);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($query);

        $this->assertNull(
            $foo->getResourceFromRelatedResourceSet($set, new \stdClass(), $set, $property, $key)
        );
    }

    public function testGetRelatedResourceReference()
    {
        $query = m::mock(ProvidersQueryWrapper::class);
        $query->shouldReceive('getRelatedResourceReference')->andReturn(null)->once();

        $set = m::mock(ResourceSet::class);
        $property = m::mock(ResourceProperty::class);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($query);

        $this->assertNull(
            $foo->getRelatedResourceReference($set, new \stdClass(), $set, $property)
        );
    }

    public function testUpdateResource()
    {
        $query = m::mock(ProvidersQueryWrapper::class);
        $query->shouldReceive('updateResource')->andReturn(null)->once();

        $set = m::mock(ResourceSet::class);
        $key = m::mock(KeyDescriptor::class);
        $property = m::mock(ResourceProperty::class);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($query);

        $this->assertNull(
            $foo->updateResource($set, new \stdClass(), $key, true)
        );
    }

    public function testDeleteResource()
    {
        $query = m::mock(ProvidersQueryWrapper::class);
        $query->shouldReceive('deleteResource')->andReturn(null)->once();

        $set = m::mock(ResourceSet::class);
        $key = m::mock(KeyDescriptor::class);
        $property = m::mock(ResourceProperty::class);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($query);

        $this->assertNull(
            $foo->deleteResource($set, new \stdClass())
        );
    }

    public function testCreateResourceforResourceSet()
    {
        $query = m::mock(ProvidersQueryWrapper::class);
        $query->shouldReceive('createResourceforResourceSet')->andReturn(null)->once();

        $set = m::mock(ResourceSet::class);
        $key = m::mock(KeyDescriptor::class);
        $property = m::mock(ResourceProperty::class);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($query);

        $this->assertNull(
            $foo->createResourceforResourceSet($set, new \stdClass(), [])
        );
    }

    public function testHandlesOrderedPaging()
    {
        $query = m::mock(ProvidersQueryWrapper::class);
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true)->once();

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($query);

        $this->assertTrue(
            $foo->handlesOrderedPaging()
        );
    }

    public function testGetResourcePropertiesOnNonEntityType()
    {
        $expected = ['eins', 'zwei', 'polizei'];
        $wrap = m::mock(ResourceSetWrapper::class);
        $type = m::mock(ResourceComplexType::class);
        $type->shouldReceive('getAllProperties')->andReturn($expected);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $actual = $foo->getResourceProperties($wrap, $type);
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourcePropertiesOnEntityType()
    {
        $rProp1 = m::mock(ResourceProperty::class)->makePartial();
        $rProp1->shouldReceive('getName')->andReturn('first')->atLeast(1);
        $rProp2 = m::mock(ResourceProperty::class)->makePartial();
        $rProp2->shouldReceive('getName')->andReturn('second')->atLeast(1);

        $wrap = m::mock(ResourceSetWrapper::class);
        $wrap->shouldReceive('getName')->andReturn('STOP!');
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getFullName')->andReturn('HammerTime!');
        $type->shouldReceive('getAllProperties')->andReturn([$rProp1, $rProp2]);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $actual = $foo->getResourceProperties($wrap, $type);
        $this->assertEquals(2, count($actual));
        $this->assertTrue(isset($actual['first']));
        $this->assertEquals('first', $actual['first']->getName());
        $this->assertTrue(isset($actual['second']));
        $this->assertEquals('second', $actual['second']->getName());
    }

    public function testGetResourceAssociationSetWhereAssociationSetHasTwoNullEnds()
    {
        $expected = 'IDSMP::GetResourceSet returns invalid instance of ResourceSet when invoked with params'
                    .' {ResourceSet with name rSet, ResourceType with name rTypeDelta, ResourceProperty with'
                    .' name rProp}.';
        $actual = null;

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getName')->andReturn('rSet');
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('resolvePropertyDeclaredOnThisType')->andReturn('foo')->once();
        $type->shouldReceive('getFullName')->andReturn('rTypeDelta');

        $prop = m::mock(ResourceProperty::class);
        $prop->shouldReceive('getName')->andReturn('rProp');

        $associationSet = m::mock(ResourceAssociationSet::class);
        $associationSet->shouldReceive('getResourceAssociationSetEnd')->andReturn(null)->once();
        $associationSet->shouldReceive('getRelatedResourceAssociationSetEnd')->andReturn(null)->once();

        $meta = m::mock(IMetadataProvider::class);
        $meta->shouldReceive('getResourceAssociationSet')->andReturn($associationSet);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getMetaProvider')->andReturn($meta);

        try {
            $foo->getResourceAssociationSet($set, $type, $prop);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
            $this->assertEquals(500, $e->getStatusCode());
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceAssociationSetWhereAssociationSetHasThisEndNull()
    {
        $expected = 'IDSMP::GetResourceSet returns invalid instance of ResourceSet when invoked with params'
                    .' {ResourceSet with name rSet, ResourceType with name rTypeDelta, ResourceProperty with'
                    .' name rProp}.';
        $actual = null;

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getName')->andReturn('rSet');
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('resolvePropertyDeclaredOnThisType')->andReturn('foo')->once();
        $type->shouldReceive('getFullName')->andReturn('rTypeDelta');

        $prop = m::mock(ResourceProperty::class);
        $prop->shouldReceive('getName')->andReturn('rProp');

        $associationSet = m::mock(ResourceAssociationSet::class);
        $associationSet->shouldReceive('getResourceAssociationSetEnd')->andReturn(null)->once();
        $associationSet->shouldReceive('getRelatedResourceAssociationSetEnd')->andReturn('foo')->once();

        $meta = m::mock(IMetadataProvider::class);
        $meta->shouldReceive('getResourceAssociationSet')->andReturn($associationSet);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getMetaProvider')->andReturn($meta);

        try {
            $foo->getResourceAssociationSet($set, $type, $prop);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
            $this->assertEquals(500, $e->getStatusCode());
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceAssociationSetWhereAssociationSetHasRelatedEndNull()
    {
        $expected = 'IDSMP::GetResourceSet returns invalid instance of ResourceSet when invoked with params'
                    .' {ResourceSet with name rSet, ResourceType with name rTypeDelta, ResourceProperty with'
                    .' name rProp}.';
        $actual = null;

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getName')->andReturn('rSet');
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('resolvePropertyDeclaredOnThisType')->andReturn('foo')->once();
        $type->shouldReceive('getFullName')->andReturn('rTypeDelta');

        $prop = m::mock(ResourceProperty::class);
        $prop->shouldReceive('getName')->andReturn('rProp');

        $associationSet = m::mock(ResourceAssociationSet::class);
        $associationSet->shouldReceive('getResourceAssociationSetEnd')->andReturn('foo')->once();
        $associationSet->shouldReceive('getRelatedResourceAssociationSetEnd')->andReturn(null)->once();

        $meta = m::mock(IMetadataProvider::class);
        $meta->shouldReceive('getResourceAssociationSet')->andReturn($associationSet);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getMetaProvider')->andReturn($meta);

        try {
            $foo->getResourceAssociationSet($set, $type, $prop);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
            $this->assertEquals(500, $e->getStatusCode());
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetSchemaVersion()
    {
        $expected = EdmSchemaVersion::VERSION_1_DOT_1();
        /** @var ProvidersWrapper $foo */
        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $actual = $foo->getEdmSchemaVersion();
        $this->assertEquals($expected, $actual);
    }

    public function testGetMetadataXML()
    {
        $expected = 'xml';
        $meta = m::mock(IMetadataProvider::class);
        $meta->shouldReceive('getXML')->andReturn($expected)->once();
        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getMetaProvider')->andReturn($meta);
        $actual = $foo->getMetadataXML();
        $this->assertEquals($expected, $actual);
    }

    public function testCreateBulkResource()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('createBulkResourceforResourceSet')->andReturn('eins')->once();

        $wrap = m::mock(ProvidersQueryWrapper::class)->makePartial();
        $wrap->shouldReceive('getQueryProvider')->andReturn($query);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($wrap);

        $set = m::mock(ResourceSet::class);
        $payload = [];

        $expected = 'eins';
        $actual = $foo->createBulkResourceForResourceSet($set, $payload);
        $this->assertEquals($expected, $actual);
    }

    public function testUpdateBulkResource()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('updateBulkResource')->andReturn('eins')->once();

        $wrap = m::mock(ProvidersQueryWrapper::class)->makePartial();
        $wrap->shouldReceive('getQueryProvider')->andReturn($query);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($wrap);

        $set = m::mock(ResourceSet::class);
        $payload = [];
        $source = new Customer2();
        $keys = [];

        $expected = 'eins';
        $actual = $foo->updateBulkResource($set, $source, $payload, $keys);
        $this->assertEquals($expected, $actual);
    }

    public function testHookSingleModel()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('hookSingleModel')->andReturn('eins')->once();

        $wrap = m::mock(ProvidersQueryWrapper::class)->makePartial();
        $wrap->shouldReceive('getQueryProvider')->andReturn($query);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($wrap);

        $set = m::mock(ResourceSet::class);
        $source = new Customer2();
        $target = new Address2();
        $propName = 'property';

        $expected = 'eins';
        $actual = $foo->hookSingleModel($set, $source, $set, $target, $propName);
        $this->assertEquals($expected, $actual);
    }

    public function testUnHookSingleModel()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('unhookSingleModel')->andReturn('eins')->once();

        $wrap = m::mock(ProvidersQueryWrapper::class)->makePartial();
        $wrap->shouldReceive('getQueryProvider')->andReturn($query);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($wrap);

        $set = m::mock(ResourceSet::class);
        $source = new Customer2();
        $target = new Address2();
        $propName = 'property';

        $expected = 'eins';
        $actual = $foo->unhookSingleModel($set, $source, $set, $target, $propName);
        $this->assertEquals($expected, $actual);
    }

    public function testStartTransaction()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('startTransaction')->once();

        $wrap = m::mock(ProvidersQueryWrapper::class)->makePartial();
        $wrap->shouldReceive('getQueryProvider')->andReturn($query);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($wrap);

        $foo->startTransaction();
    }

    public function testCommitTransaction()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('commitTransaction')->once();

        $wrap = m::mock(ProvidersQueryWrapper::class)->makePartial();
        $wrap->shouldReceive('getQueryProvider')->andReturn($query);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($wrap);

        $foo->commitTransaction();
    }

    public function testRollBackTransaction()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('rollBackTransaction')->once();

        $wrap = m::mock(ProvidersQueryWrapper::class)->makePartial();
        $wrap->shouldReceive('getQueryProvider')->andReturn($query);

        $foo = m::mock(ProvidersWrapper::class)->makePartial();
        $foo->shouldReceive('getProviderWrapper')->andReturn($wrap);

        $foo->rollBackTransaction();
    }

    public static function mockProperty($object, $propertyName, $value)
    {
        $bar = new \ReflectionClass($object);
        $property = $bar->getProperty($propertyName);
        $oldAcc = $property->isPublic() ? true : false;

        $property->setAccessible(true);
        $property->setValue($object, $value);
        $property->setAccessible($oldAcc);
    }
}
