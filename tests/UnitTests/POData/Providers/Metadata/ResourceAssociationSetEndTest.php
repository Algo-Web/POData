<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
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
        $actual = null;

        try {
            $foo = new ResourceAssociationSetEnd($set, $type, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorWithBadResourceProperty()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceEntityType::class);

        $property = new \StdClass();

        // Type-hint mismatch error message is slightly different in PHP 7.1
        $expected = "Argument 3 passed to POData\\Providers\\Metadata\\ResourceAssociationSetEnd::__construct() must be"
                    ." an instance of POData\\Providers\\Metadata\\ResourceProperty, instance of stdClass given,";
        $expected71 = "Argument 3 passed to POData\\Providers\\Metadata\\ResourceAssociationSetEnd::__construct() must"
                      ." be an instance of POData\\Providers\\Metadata\\ResourceProperty or null, instance of stdClass"
                      ." given,";
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
        $targ = version_compare(phpversion(), "7.1", ">=") ? $expected71 : $expected;

        $this->assertStringStartsWith($targ, $actual);
    }
}
