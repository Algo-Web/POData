<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use UnitTests\POData\TestCase;

class ResourceAssociationSetEndTest extends TestCase
{
    public function testConstructorResourcePropertyBadInstanceThrowException()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceType::class);
        $property = m::mock(ResourceProperty::class);

        $expected = 'The argument \'$resourceProperty\' must be either null or instance of \'ResourceProperty\'.';
        $actual = null;

        try {
            $foo = new ResourceAssociationSetEnd($set, $type, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorNullResourcePropertyTypeNotAssignableToSetThrowException()
    {
        $middleType = m::mock(ResourceType::class);

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getName')->andReturn('fakeSet');
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('isAssignableFrom')->andReturn(false)->once();
        $type->shouldReceive('getFullName')->andReturn('fakeType');
        $set->shouldReceive('getResourceType')->andReturn($middleType);

        $middleType->shouldReceive('isAssignableFrom')->andReturn(false)->once();

        $expected = 'The resource type fakeType must be assignable to the resource set fakeSet.';
        $actual = null;

        try {
            $foo = new ResourceAssociationSetEnd($set, $type, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
