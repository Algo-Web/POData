<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationType;
use POData\Providers\Metadata\ResourceAssociationTypeEnd;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use UnitTests\POData\TestCase;

class ResourceAssociationTypeTest extends TestCase
{
    public function testGetResourceAssociationTypeEndFirst()
    {
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationTypeEnd::class);
        $end1->shouldReceive('getName')->andReturn('foo');
        $end1->shouldReceive('isBelongsTo')->andReturn(true)->once();
        $end2 = m::mock(ResourceAssociationTypeEnd::class);
        $end2->shouldReceive('getName')->andReturn('bar');
        $end2->shouldReceive('isBelongsTo')->andReturn(false)->never();

        $foo = new ResourceAssociationType('name', 'space', $end1, $end2);
        $result = $foo->getResourceAssociationTypeEnd($type, $property);
        $this->assertTrue($result instanceof ResourceAssociationTypeEnd);
        $this->assertEquals('foo', $result->getName());
    }

    public function testGetResourceAssociationTypeEndSecond()
    {
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationTypeEnd::class);
        $end1->shouldReceive('getName')->andReturn('foo');
        $end1->shouldReceive('isBelongsTo')->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationTypeEnd::class);
        $end2->shouldReceive('getName')->andReturn('bar');
        $end2->shouldReceive('isBelongsTo')->andReturn(true)->once();

        $foo = new ResourceAssociationType('name', 'space', $end1, $end2);
        $result = $foo->getResourceAssociationTypeEnd($type, $property);
        $this->assertTrue($result instanceof ResourceAssociationTypeEnd);
        $this->assertEquals('bar', $result->getName());
    }

    public function testGetResourceAssociationTypeEndNeither()
    {
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationTypeEnd::class);
        $end1->shouldReceive('getName')->andReturn('foo');
        $end1->shouldReceive('isBelongsTo')->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationTypeEnd::class);
        $end2->shouldReceive('getName')->andReturn('bar');
        $end2->shouldReceive('isBelongsTo')->andReturn(false)->once();

        $foo = new ResourceAssociationType('name', 'space', $end1, $end2);
        $result = $foo->getResourceAssociationTypeEnd($type, $property);
        $this->assertNull($result);
    }

    public function testGetRelatedResourceAssociationSetEndFirst()
    {
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationTypeEnd::class);
        $end1->shouldReceive('getName')->andReturn('foo');
        $end1->shouldReceive('isBelongsTo')->andReturn(true)->once();
        $end2 = m::mock(ResourceAssociationTypeEnd::class);
        $end2->shouldReceive('getName')->andReturn('bar');
        $end2->shouldReceive('isBelongsTo')->andReturn(false)->never();

        $foo = new ResourceAssociationType('name', 'space', $end1, $end2);
        $result = $foo->getRelatedResourceAssociationSetEnd($type, $property);
        $this->assertTrue($result instanceof ResourceAssociationTypeEnd);
        $this->assertEquals('bar', $result->getName());
    }

    public function testGetRelatedResourceAssociationSetEndSecond()
    {
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationTypeEnd::class);
        $end1->shouldReceive('getName')->andReturn('foo');
        $end1->shouldReceive('isBelongsTo')->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationTypeEnd::class);
        $end2->shouldReceive('getName')->andReturn('bar');
        $end2->shouldReceive('isBelongsTo')->andReturn(true)->once();

        $foo = new ResourceAssociationType('name', 'space', $end1, $end2);
        $result = $foo->getRelatedResourceAssociationSetEnd($type, $property);
        $this->assertTrue($result instanceof ResourceAssociationTypeEnd);
        $this->assertEquals('foo', $result->getName());
    }

    public function testGetRelatedResourceAssociationSetEndNeither()
    {
        $type = m::mock(ResourceEntityType::class);
        $property = m::mock(ResourceProperty::class);

        $end1 = m::mock(ResourceAssociationTypeEnd::class);
        $end1->shouldReceive('getName')->andReturn('foo');
        $end1->shouldReceive('isBelongsTo')->andReturn(false)->once();
        $end2 = m::mock(ResourceAssociationTypeEnd::class);
        $end2->shouldReceive('getName')->andReturn('bar');
        $end2->shouldReceive('isBelongsTo')->andReturn(false)->once();

        $foo = new ResourceAssociationType('name', 'space', $end1, $end2);
        $result = $foo->getRelatedResourceAssociationSetEnd($type, $property);
        $this->assertNull($result);
    }
}
