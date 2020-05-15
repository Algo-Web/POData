<?php

declare(strict_types=1);

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use UnitTests\POData\TestCase;

/**
 * Class ResourceAssociationSetTest
 * @package UnitTests\POData\Providers\Metadata
 */
class ResourceAssociationSetTest extends TestCase
{
    public function testConstructorBothPropertiesNullThrowException()
    {
        $end1 = m::mock(ResourceAssociationSetEnd::class)->makePartial();
        $end2 = m::mock(ResourceAssociationSetEnd::class)->makePartial();

        $expected = 'Both the resource properties of the association set cannot be null.';
        $actual   = null;

        try {
            new ResourceAssociationSet('name', $end1, $end2);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorBothEndsSameWhenSelfReferencingAndThrowException()
    {
        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive('getName')->andReturn('foo');
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn($prop1)->once();
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn($prop1)->times(1);
        $end2->shouldReceive('getResourceType')->andReturn($type1)->once();

        $expected = 'Bidirectional self referencing association is not allowed.';
        $actual   = null;

        try {
            new ResourceAssociationSet('name', $end1, $end2);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceAssociationSetEndFirstSet()
    {
        $set      = m::mock(ResourceSet::class);
        $type     = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive("getName")->andReturn('foo');
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive("getName")->andReturn('bar');
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');
        $type2 = m::mock(ResourceEntityType::class);
        $type2->shouldReceive('getName')->andReturn('foo');


        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn($prop1)->times(2);
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn($prop2)->once();
        $end2->shouldReceive('getResourceType')->andReturn($type2)->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->never();

        $foo    = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getResourceAssociationSetEnd($set, $type, $property);
        $this->assertTrue($result instanceof ResourceAssociationSetEnd);
        $this->assertEquals('foo', $result->getResourceProperty()->getName());
    }

    public function testGetResourceAssociationSetEndSecondSet()
    {
        $set      = m::mock(ResourceSet::class);
        $type     = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive('getName')->andReturn('foo');
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive('getName')->andReturn('bar');
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');
        $type2 = m::mock(ResourceEntityType::class);
        $type2->shouldReceive('getName')->andReturn('foo');

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn($prop1)->once();
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();

        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn($prop2)->twice();
        $end2->shouldReceive('getResourceType')->andReturn($type2)->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->once();

        $foo    = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getResourceAssociationSetEnd($set, $type, $property);
        $this->assertTrue($result instanceof ResourceAssociationSetEnd);
        $this->assertEquals('bar', $result->getResourceProperty()->getName());
    }

    public function testGetResourceAssociationSetEndNeitherSet()
    {
        $set      = m::mock(ResourceSet::class);
        $type     = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive("getName")->andReturn('foo');
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive("getName")->andReturn('bar');
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');
        $type2 = m::mock(ResourceEntityType::class);
        $type2->shouldReceive('getName')->andReturn('foo');

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn($prop1)->times(1);
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn($prop2)->once();
        $end2->shouldReceive('getResourceType')->andReturn($type2)->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();

        $foo    = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getResourceAssociationSetEnd($set, $type, $property);
        $this->assertNull($result);
    }

    public function testGetRelatedResourceAssociationSetEndFirstSet()
    {
        $set      = m::mock(ResourceSet::class);
        $type     = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive("getName")->andReturn('foo');
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive("getName")->andReturn('bar');
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');
        $type2 = m::mock(ResourceEntityType::class);
        $type2->shouldReceive('getName')->andReturn('foo');

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn($prop1)->times(1);
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->once();
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive('getName')->andReturn('bar');
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn($prop2)->times(2);
        $end2->shouldReceive('getResourceType')->andReturn($type2)->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->never();

        $foo    = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getRelatedResourceAssociationSetEnd($set, $type, $property);
        $this->assertTrue($result instanceof ResourceAssociationSetEnd);
        $this->assertEquals('bar', $result->getResourceProperty()->getName());
    }

    public function testGetRelatedResourceAssociationSetEndSecondSet()
    {
        $set      = m::mock(ResourceSet::class);
        $type     = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive("getName")->andReturn('foo');
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive("getName")->andReturn('bar');
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');
        $type2 = m::mock(ResourceEntityType::class);
        $type2->shouldReceive('getName')->andReturn('foo');

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn($prop1)->atLeast(2);
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn($prop2);
        $end2->shouldReceive('getResourceType')->andReturn($type2)->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->once();

        $foo    = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getRelatedResourceAssociationSetEnd($set, $type, $property);
        $this->assertTrue($result instanceof ResourceAssociationSetEnd);
        $this->assertEquals('foo', $result->getResourceProperty()->getName());
    }

    public function testGetRelatedResourceAssociationSetEndNeitherSet()
    {
        $set      = m::mock(ResourceSet::class);
        $type     = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive("getName")->andReturn('foo');
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive("getName")->andReturn('bar');
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');
        $type2 = m::mock(ResourceEntityType::class);
        $type2->shouldReceive('getName')->andReturn('foo');

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn($prop1)->times(1);
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn($prop2)->once();
        $end2->shouldReceive('getResourceType')->andReturn($type2)->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();

        $foo    = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getRelatedResourceAssociationSetEnd($set, $type, $property);
        $this->assertNull($result);
    }

    public function testIsBidirectionalBothEndsHaveResourcen()
    {
        $prop1 = m::mock(ResourceProperty::class);
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');
        $type2 = m::mock(ResourceEntityType::class);
        $type2->shouldReceive('getName')->andReturn('foo');

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn($prop1)->times(2);
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $prop2 = m::mock(ResourceProperty::class);
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn($prop2)->twice();
        $end2->shouldReceive('getResourceType')->andReturn($type2)->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $this->assertTrue($foo->isBidirectional());
    }

    public function testIsBidirectionalLeftEndHasResourcen()
    {
        $prop1 = m::mock(ResourceProperty::class);
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');
        $type2 = m::mock(ResourceEntityType::class);
        $type2->shouldReceive('getName')->andReturn('foo');

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn($prop1)->times(2);
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn(null)->times(2);
        $end2->shouldReceive('getResourceType')->andReturn($type2)->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $this->assertFalse($foo->isBidirectional());
    }

    public function testIsBidirectionalRightEndHasResourcen()
    {
        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive("getName")->andReturn('foo');
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive("getName")->andReturn('bar');
        $type1 = m::mock(ResourceEntityType::class);
        $type1->shouldReceive('getName')->andReturn('bar');
        $type2 = m::mock(ResourceEntityType::class);
        $type2->shouldReceive('getName')->andReturn('foo');

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn(null)->times(2);
        $end1->shouldReceive('getResourceType')->andReturn($type1)->once();
        $prop2 = m::mock(ResourceProperty::class);
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn($prop2)->once();
        $end2->shouldReceive('getResourceType')->andReturn($type2)->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $this->assertFalse($foo->isBidirectional());
    }
}
