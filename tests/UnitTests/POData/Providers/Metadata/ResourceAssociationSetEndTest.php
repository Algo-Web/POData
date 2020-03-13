<?php

declare(strict_types=1);

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use UnitTests\POData\TestCase;

class ResourceAssociationSetEndTest extends TestCase
{
    public function testConstructorNullResourcePropertyTypeNotAssignableToSetThrowException()
    {
        $middleType = m::mock(ResourceEntityType::class);

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getName')->andReturn('fakeSet');
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('isAssignableFrom')->andReturn(false)->once();
        $type->shouldReceive('getFullName')->andReturn('fakeType');
        $set->shouldReceive('getResourceType')->andReturn($middleType);

        $middleType->shouldReceive('isAssignableFrom')->andReturn(false)->once();

        $expected = 'The resource type fakeType must be assignable to the resource set fakeSet.';
        $actual   = null;

        try {
            $foo = new ResourceAssociationSetEnd($set, $type, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorWithBadResourceProperty()
    {
        $set  = m::mock(ResourceSet::class);
        $type = m::mock(ResourceEntityType::class);

        $property = new \stdClass();

        // Type-hint mismatch error message is slightly different in PHP 7.1
        $expected = 'Argument 3 passed to POData\\Providers\\Metadata\\ResourceAssociationSetEnd::__construct() must be'
                    . ' an instance of POData\\Providers\\Metadata\\ResourceProperty, instance of stdClass given,';
        $expected71 = 'Argument 3 passed to POData\\Providers\\Metadata\\ResourceAssociationSetEnd::__construct() must'
                      . ' be an instance of POData\\Providers\\Metadata\\ResourceProperty or null, instance of stdClass'
                      . ' given,';
        $actual = null;

        try {
            $foo = new ResourceAssociationSetEnd($set, $type, $property);
        } catch (\Exception $e) {
            // PHP 5.6
            $actual = $e->getMessage();
        } catch (\Error $e) {
            // PHP 7.x
            $actual = $e->getMessage();
        }

        // If we're running under PHP 7.1 or later, use "or null" expectation
        $targ = version_compare(phpversion(), '7.1', '>=') ? $expected71 : $expected;

        $this->assertStringStartsWith($targ, $actual);
    }

    public function testResourceAssociationSetEndWithAbstractConcreteType()
    {
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('property');
        $property->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE);

        $base = m::mock(ResourceEntityType::class);
        $base->shouldReceive('isAbstract')->andReturn(true);
        $base->shouldReceive('resolveProperty')->andReturn($property);
        $base->shouldReceive('isAssignableFrom')->andReturn(true);
        $concrete = m::mock(ResourceEntityType::class);
        $concrete->shouldReceive('isAbstract')->andReturn(true);

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getResourceType')->andReturn($base);

        $expected = 'Concrete type must not be abstract if explicitly supplied';
        $actual   = null;

        try {
            $foo = new ResourceAssociationSetEnd($set, $base, $property, $concrete);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetConcreteTypeWhenNotExplicitlySupplied()
    {
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('property');
        $property->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE);
        $expected = 'TypeWithNoName';

        $base = m::mock(ResourceEntityType::class);
        $base->shouldReceive('isAbstract')->andReturn(false);
        $base->shouldReceive('resolveProperty')->andReturn($property);
        $base->shouldReceive('isAssignableFrom')->andReturn(true);
        $base->shouldReceive('getName')->andReturn($expected);

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getResourceType')->andReturn($base);

        $foo    = new ResourceAssociationSetEnd($set, $base, $property);
        $result = $foo->getConcreteType();
        $actual = $result->getName();
        $this->assertEquals($expected, $actual);
    }

    public function testGetConcreteTypeWhenExplicitlySupplied()
    {
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('property');
        $property->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE);
        $expected = 'TypeWithNoName';

        $concrete = m::mock(ResourceEntityType::class);
        $concrete->shouldReceive('isAbstract')->andReturn(false);
        $concrete->shouldReceive('getName')->andReturn($expected);

        $base = m::mock(ResourceEntityType::class);
        $base->shouldReceive('isAbstract')->andReturn(false);
        $base->shouldReceive('resolveProperty')->andReturn($property);
        $base->shouldReceive('isAssignableFrom')->andReturn(true);
        $base->shouldReceive('getName')->andReturn('foo');

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getResourceType')->andReturn($base);

        $foo    = new ResourceAssociationSetEnd($set, $base, $property, $concrete);
        $result = $foo->getConcreteType();
        $actual = $result->getName();
        $this->assertEquals($expected, $actual);
    }
}
