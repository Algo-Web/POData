<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use UnitTests\POData\TestCase;

class ResourceAssociationSetTest extends TestCase
{
    public function testConstructorBothPropertiesNullThrowException()
    {
        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturnNull()->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturnNull()->once();

        $expected = 'Both the resource properties of the association set cannot be null.';
        $actual = null;

        try {
            new ResourceAssociationSet('name', $end1, $end2);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorBothEndsSameWhenSelfReferencingAndThrowException()
    {
        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn('foo')->twice();
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn('foo')->times(1);
        $end2->shouldReceive('getResourceType')->andReturn('bar')->once();

        $expected = 'Bidirectional self referencing association is not allowed.';
        $actual = null;

        try {
            new ResourceAssociationSet('name', $end1, $end2);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceAssociationSetEndFirstSet()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn('foo')->times(2);
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn('bar')->never();
        $end2->shouldReceive('getResourceType')->andReturn('foo')->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->never();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getResourceAssociationSetEnd($set, $type, $property);
        $this->assertTrue($result instanceof ResourceAssociationSetEnd);
        $this->assertEquals('foo', $result->getResourceProperty());
    }

    public function testGetResourceAssociationSetEndSecondSet()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn('foo')->once();
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn('bar')->once();
        $end2->shouldReceive('getResourceType')->andReturn('foo')->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getResourceAssociationSetEnd($set, $type, $property);
        $this->assertTrue($result instanceof ResourceAssociationSetEnd);
        $this->assertEquals('bar', $result->getResourceProperty());
    }

    public function testGetResourceAssociationSetEndNeitherSet()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn('foo')->times(1);
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn('bar')->never();
        $end2->shouldReceive('getResourceType')->andReturn('foo')->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getResourceAssociationSetEnd($set, $type, $property);
        $this->assertNull($result);
    }

    public function testGetRelatedResourceAssociationSetEndFirstSet()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn('foo')->times(1);
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn('bar')->times(1);
        $end2->shouldReceive('getResourceType')->andReturn('foo')->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->never();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getRelatedResourceAssociationSetEnd($set, $type, $property);
        $this->assertTrue($result instanceof ResourceAssociationSetEnd);
        $this->assertEquals('bar', $result->getResourceProperty());
    }

    public function testGetRelatedResourceAssociationSetEndSecondSet()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn('foo')->atLeast(2);
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn('bar');
        $end2->shouldReceive('getResourceType')->andReturn('foo')->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(true)->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getRelatedResourceAssociationSetEnd($set, $type, $property);
        $this->assertTrue($result instanceof ResourceAssociationSetEnd);
        $this->assertEquals('foo', $result->getResourceProperty());
    }

    public function testGetRelatedResourceAssociationSetEndNeitherSet()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn('foo')->times(1);
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end1->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn('bar')->never();
        $end2->shouldReceive('getResourceType')->andReturn('foo')->once();
        $end2->shouldReceive('isBelongsTo')->withAnyArgs()->andReturn(false)->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $result = $foo->getRelatedResourceAssociationSetEnd($set, $type, $property);
        $this->assertNull($result);
    }

    public function testIsBidirectionalBothEndsHaveResourcen()
    {
        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn('foo')->times(2);
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn('bar')->once();
        $end2->shouldReceive('getResourceType')->andReturn('foo')->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $this->assertTrue($foo->isBidirectional());
    }

    public function testIsBidirectionalLeftEndHasResourcen()
    {
        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn('foo')->times(2);
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn(null)->once();
        $end2->shouldReceive('getResourceType')->andReturn('foo')->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $this->assertFalse($foo->isBidirectional());
    }

    public function testIsBidirectionalRightEndHasResourcen()
    {
        $end1 = m::mock(ResourceAssociationSetEnd::class);
        $end1->shouldReceive('getResourceProperty')->andReturn(null)->times(2);
        $end1->shouldReceive('getResourceType')->andReturn('bar')->once();
        $end2 = m::mock(ResourceAssociationSetEnd::class);
        $end2->shouldReceive('getResourceProperty')->andReturn('bar')->once();
        $end2->shouldReceive('getResourceType')->andReturn('foo')->once();

        $foo = new ResourceAssociationSet('name', $end1, $end2);
        $this->assertFalse($foo->isBidirectional());
    }
}
