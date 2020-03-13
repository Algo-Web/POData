<?php

namespace UnitTests\POData\Providers\Metadata;

use AlgoWeb\ODataMetadata\MetadataV3\edm\TComplexTypeType;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TEntityTypeType;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceComplexType;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Query\QueryResult;
use ReflectionClass;
use UnitTests\POData\Facets\NorthWind4\NorthWindMetadata;
use UnitTests\POData\ObjectModel\reusableEntityClass2;
use UnitTests\POData\TestCase;

class ResourceTypeTest extends TestCase
{
    public function testGetPrimitiveResourceTypeByte()
    {
        $type = EdmPrimitiveType::BYTE();
        $result = ResourceType::getPrimitiveResourceType($type);
        $this->assertTrue($result instanceof ResourceType);
        $this->assertEquals('Byte', $result->getName());
        $this->assertEquals('Edm', $result->getNamespace());
        $this->assertEquals('Edm.Byte', $result->getFullName());
    }

    public function testGetPrimitiveResourceTypeSByte()
    {
        $type = EdmPrimitiveType::SBYTE();
        $result = ResourceType::getPrimitiveResourceType($type);
        $this->assertTrue($result instanceof ResourceType);
        $this->assertEquals('SByte', $result->getName());
        $this->assertEquals('Edm', $result->getNamespace());
        $this->assertEquals('Edm.SByte', $result->getFullName());
    }

    public function testHasETagPropertiesYes()
    {
        $foo = m::mock(ResourceType::class)->makePartial();
        $foo->shouldReceive('getETagProperties')->andReturn(['a', 'b'])->once();
        $this->assertTrue($foo->hasETagProperties());
    }

    public function testHasETagPropertiesNo()
    {
        $foo = m::mock(ResourceType::class)->makePartial();
        $foo->shouldReceive('getETagProperties')->andReturn()->once();
        $this->assertFalse($foo->hasETagProperties());
    }

    public function testGetETagProperties()
    {
        $property = m::mock(ResourceType::class);
        $property->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::ETAG])->andReturn(true);
        $property->shouldReceive('getName')->andReturn('property');

        $foo = m::mock(ResourceType::class)->makePartial();
        $foo->shouldReceive('getAllProperties')->andReturn(['name' => $property]);
        $result = $foo->getETagProperties();
        $this->assertTrue(is_array($result));
        $this->assertTrue($result['name'] instanceof ResourceType);
        $this->assertEquals('property', $result['name']->getName());
    }

    public function testAddNamedStreamWhenNotEntityThrowException()
    {
        $info = m::mock(ResourceStreamInfo::class);
        $foo = m::mock(ResourceType::class)->makePartial();

        $expected = 'Named streams can only be added to entity types.';
        $actual = null;

        try {
            $foo->addNamedStream($info);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testHasBagPropertyCheckTwice()
    {
        $foo = m::mock(ResourceType::class)->makePartial();

        $bar = [];
        $this->assertNull($foo->hasBagProperty($bar));
        $this->assertNull($foo->hasBagProperty($bar));
    }

    public function testTryResolveNamedStream()
    {
        $foo = m::mock(ResourceType::class)->makePartial();
        $this->assertNull($foo->tryResolveNamedStreamDeclaredOnThisTypeByName('foo'));
    }

    public function testSleepWakeupRealObjectITypeRoundTrip()
    {
        $instanceType = m::mock(IType::class);
        $instanceType->shouldReceive('getName')->andReturn('label');
        $resourceTypeKind = ResourceTypeKind::PRIMITIVE;
        $foo = new ResourcePrimitiveType($instanceType);

        $result = $foo->__sleep();

        $expected = ['name', 'namespaceName', 'fullName', 'resourceTypeKind', 'abstractType', 'baseType',
            'propertiesDeclaredOnThisType', 'namedStreamsDeclaredOnThisType', 'allProperties', 'allNamedStreams',
            'eTagProperties', 'keyProperties', 'isMediaLinkEntry', 'hasBagProperty', 'hasNamedStreams', 'type',
            'customState', 'arrayToDetectLoopInComplexBag', ];

        foreach ($expected as $property) {
            $this->assertTrue(in_array($property, $result), $property);
        }

        $foo->__wakeup();
    }

    public function testSleepWakeupRealObjectReflectableRoundTrip()
    {
        $complex = m::mock(TComplexTypeType::class);
        $complex->shouldReceive('getName')->andReturn('label');
        $instanceType = new reusableEntityClass2('foo', 'bar');
        $resourceTypeKind = ResourceTypeKind::COMPLEX();
        $foo = new ResourceComplexType(new ReflectionClass($instanceType), $complex);

        $result = $foo->__sleep();

        $expected = ['name', 'namespaceName', 'fullName', 'resourceTypeKind', 'abstractType', 'baseType',
            'propertiesDeclaredOnThisType', 'namedStreamsDeclaredOnThisType', 'allProperties', 'allNamedStreams',
            'eTagProperties', 'keyProperties', 'isMediaLinkEntry', 'hasBagProperty', 'hasNamedStreams', 'type',
            'customState', 'arrayToDetectLoopInComplexBag', ];

        foreach ($expected as $property) {
            $this->assertTrue(in_array($property, $result), $property);
        }

        $foo->__wakeup();
    }

    public function testGetBaseTypeOnResourcePrimitiveType()
    {
        $foo = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::BINARY());
        $this->assertFalse($foo->hasBaseType());
        $this->assertNull($foo->getBaseType());
        $this->assertFalse($foo->isAbstract());
    }

    public function testPrimitiveTypeAssignableFromOtherPrimitiveType()
    {
        $foo = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::BINARY());
        $bar = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::STRING());
        $this->assertFalse($foo->isAssignableFrom($bar));
    }

    public function testGetNamedStreamsOnPrimitiveType()
    {
        $foo = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::BINARY());
        $result = $foo->getNamedStreamsDeclaredOnThisType();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testResolvePropertyOnPrimitiveType()
    {
        $foo = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::BINARY());
        $this->assertNull($foo->resolvePropertyDeclaredOnThisType(null));
    }

    public function testAddKeyPropertyToEntityTypeWithAbstractBase()
    {
        $baseType = m::mock(ResourceEntityType::class);
        $baseType->shouldReceive('isAbstract')->andReturn(true)->atLeast(1);

        $meta = m::mock(IMetadataProvider::class);
        $meta->shouldReceive('resolveResourceType')->andReturn($baseType)->atLeast(1);
        $meta->shouldReceive('getContainerNamespace')->andReturn('Data');

        $reflec = new \ReflectionClass(new \stdClass());
        $entity = m::mock(TEntityTypeType::class);
        $entity->shouldReceive('getName')->andReturn('foo');
        $entity->shouldReceive('getBaseType')->andReturn('baseType');
        $entity->shouldReceive('getAbstract')->andReturn(false)->once();

        $foo = new ResourceEntityType($reflec, $entity, $meta);

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getName')->andReturn('RType');
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::KEY])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);

        $foo->addProperty($rProp);
        $result = $foo->resolvePropertyDeclaredOnThisType('RType');
        $this->assertTrue($result instanceof ResourceProperty);
    }

    public function testAddKeyPropertyToEntityTypeWithConcreteBase()
    {
        $baseType = m::mock(ResourceEntityType::class);
        $baseType->shouldReceive('isAbstract')->andReturn(false)->atLeast(1);

        $meta = m::mock(IMetadataProvider::class);
        $meta->shouldReceive('resolveResourceType')->andReturn($baseType)->atLeast(1);
        $meta->shouldReceive('getContainerNamespace')->andReturn('Data');

        $reflec = new \ReflectionClass(new \stdClass());
        $entity = m::mock(TEntityTypeType::class);
        $entity->shouldReceive('getName')->andReturn('foo');
        $entity->shouldReceive('getBaseType')->andReturn('baseType');
        $entity->shouldReceive('getAbstract')->andReturn(false)->once();

        $foo = new ResourceEntityType($reflec, $entity, $meta);

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getName')->andReturn('RType');
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::KEY])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);

        $expected = 'Key properties cannot be defined in derived types';
        $actual = null;

        try {
            $foo->addProperty($rProp);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddPropertyTwiceWithKaboomSuppressed()
    {
        $rType = m::mock(ResourceEntityType::class)->makePartial();

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getName')->andReturn('number')->twice();
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::KEY])->andReturn(false);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::ETAG])->andReturn(false);

        $this->assertEquals(0, count($rType->getAllProperties()));
        $rType->addProperty($rProp, false);
        $this->assertEquals(1, count($rType->getAllProperties()));
        $rType->addProperty($rProp, false);
        $this->assertEquals(1, count($rType->getAllProperties()));
    }

    public function testAddETagToNonResourceEntityProperty()
    {
        $rType = m::mock(ResourceEntityType::class)->makePartial();

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getName')->andReturn('number')->once();
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(false);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::KEY])->andReturn(false);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::ETAG])->andReturn(true);

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('ETag properties can only be added to ResourceType instances with a ResourceTypeKind equal to \'EntityType\'');

        $rType->addProperty($rProp, false);
    }

    public function testGetInt64Type()
    {
        $foo = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::INT64());
        $this->assertEquals('Int64', $foo->getName());
    }

    /**
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    public function testSetValueWithEmptyQueryResult()
    {
        $meta = NorthWindMetadata::create();
        $type = $meta->resolveResourceType('Customer');

        $entity = new QueryResult();
        $entity->results = [];
        $propName = 'CompanyName';
        $value = 'Company';

        $expected = 'The parameter class is expected to be either a string or an object';
        $actual = null;

        try {
            $type->setPropertyValue($entity, $propName, $value);
        } catch (\ReflectionException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetValueWithNull()
    {
        $meta = NorthWindMetadata::create();
        $type = $meta->resolveResourceType('Customer');

        $entity = null;
        $propName = 'CompanyName';
        $value = 'Company';

        $expected = 'The parameter class is expected to be either a string or an object';
        $actual = null;

        try {
            $type->setPropertyValue($entity, $propName, $value);
        } catch (\ReflectionException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetValueWithEmptyQueryResult()
    {
        $meta = NorthWindMetadata::create();
        $type = $meta->resolveResourceType('Customer');

        $entity = new QueryResult();
        $entity->results = [];
        $propName = 'CompanyName';

        $expected = 'Property POData\Common\ReflectionHandler::$CompanyName does not exist';
        $actual = null;

        try {
            $type->getPropertyValue($entity, $propName);
        } catch (\ReflectionException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetValueWithNull()
    {
        $meta = NorthWindMetadata::create();
        $type = $meta->resolveResourceType('Customer');

        $entity = null;
        $propName = 'CompanyName';

        $expected = 'Property POData\Common\ReflectionHandler::$CompanyName does not exist';
        $actual = null;

        try {
            $type->getPropertyValue($entity, $propName);
        } catch (\ReflectionException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws InvalidOperationException
     */
    public function testGetFullNameIncludingNamespace()
    {
        $meta = NorthWindMetadata::create();
        $this->assertEquals('NorthWind', $meta->getContainerNamespace());

        $type = $meta->resolveResourceType('Customer');
        $expectedFullName = 'NorthWind.Customer';
        $actualFullName = $type->getFullName();
        $this->assertEquals($expectedFullName, $actualFullName);
    }
}
