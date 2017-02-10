<?php

namespace UnitTests\POData\Providers\Metadata;

use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\SimpleMetadataProvider;
use Mockery as m;
use UnitTests\POData\ObjectModel\reusableEntityClass2;

class SimpleMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testAddResourceSetThenGoAroundAgainAndThrowException()
    {
        $foo = new SimpleMetadataProvider("string", "String");
        $name = "Hammer";
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('setCustomState')->andReturnNull()->once();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);

        $result = $foo->addResourceSet($name, $type);
        $this->assertEquals($name, $result->getName());

        $expected = 'Resource Set already added';
        $actual = null;

        try {
            $foo->addResourceSet($name, $type);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceSetsNotArrayThrowException()
    {
        $foo = new SimpleMetadataProvider("string", "String");

        $expected = 'Input parameter must be absent, null, string or array';
        $actual = null;

        try {
            $foo->getResourceSets(new \StdClass());
        } catch (\ErrorException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceSetsOnlyOneExists()
    {
        $foo = new SimpleMetadataProvider("string", "String");
        $name = "Hammer";
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('setCustomState')->andReturnNull()->once();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);

        $foo->addResourceSet($name, $type);

        $parms = ['Hammer', 'Time'];
        $result = $foo->getResourceSets($parms);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($name, $result[0]->getName());
    }

    public function testGetResourceSetsByStringNoneExist()
    {
        $foo = new SimpleMetadataProvider("string", "String");
        $parms = 'Hammer';
        $result = $foo->getResourceSets($parms);
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testGetTypesOnEmpty()
    {
        $foo = new SimpleMetadataProvider("string", "String");

        $result = $foo->getTypes();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testResolveResourceTypeOnEmpty()
    {
        $foo = new SimpleMetadataProvider("string", "String");

        $result = $foo->resolveResourceType("Hammer");
        $this->assertNull($result);
    }

    public function testHasDerivedTypes()
    {
        $type = m::mock(ResourceType::class);
        $this->assertTrue($type instanceof ResourceType);

        $foo = new SimpleMetadataProvider("string", "String");

        $result = $foo->hasDerivedTypes($type);
        $this->assertFalse($result);
    }

    public function testGetResourceAssociationSetCustomStateNullThrowException()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceType::class);
        $targType = m::mock(ResourceType::class);
        $targType->shouldReceive('getCustomState')->andReturnNull()->once();
        $targType->shouldReceive('getName')->andReturn('Hammer');
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getResourceType')->andReturn($targType);

        $foo = new SimpleMetadataProvider("string", "String");

        $expected = 'Failed to retrieve the custom state from Hammer';
        $actual = null;

        try {
            $foo->getResourceAssociationSet($set, $type, $property);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceAssociationSetWhenEmpty()
    {
        $set = m::mock(ResourceSet::class);
        $targSet = m::mock(ResourceSet::class);
        $targSet->shouldReceive('getName')->andReturn('M.C.');
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getName')->andReturn('Hawking');
        $targType = m::mock(ResourceType::class);
        $targType->shouldReceive('getCustomState')->andReturn($targSet)->once();
        $targType->shouldReceive('getName')->andReturn('Hammer');
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getResourceType')->andReturn($targType);
        $property->shouldReceive('getName')->andReturn('Hammer');

        $foo = new SimpleMetadataProvider("string", "String");

        $result = $foo->getResourceAssociationSet($set, $type, $property);
        $this->assertNull($result);
    }

    public function testAddResourceTypeThenGoAroundAgainAndThrowException()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceType::class);
        $property = m::mock(ResourceProperty::class);

        $orig = new reusableEntityClass2("foo", "bar");
        $entity = new \ReflectionClass($orig);

        $foo = new SimpleMetadataProvider("string", "String");

        $result = $foo->addEntityType($entity, "Hammer");
        $this->assertTrue($result instanceof ResourceType);
        $this->assertEquals("Hammer", $result->getName());

        $expected = 'Type with same name already added';
        $actual = null;

        try {
            $foo->addEntityType($entity, "Hammer");
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);

    }

}