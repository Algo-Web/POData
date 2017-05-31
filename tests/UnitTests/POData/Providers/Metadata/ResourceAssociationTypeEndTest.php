<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationTypeEnd;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use UnitTests\POData\TestCase;

class ResourceAssociationTypeEndTest extends TestCase
{
    public function testConstructorWithBothPropertiesNullThrowException()
    {
        $type = m::mock(ResourceEntityType::class);

        $expected = 'Both to and from property argument to ResourceAssociationTypeEnd constructor cannot be null.';
        $actual = null;

        try {
            new ResourceAssociationTypeEnd('name', $type, null, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorFromPropertyBadTypeThrowException()
    {
        $type = m::mock(ResourceEntityType::class);

        $expected = 'The argument \'$resourceProperty\' must be either null or instance of \'ResourceProperty\'.';
        $actual = null;

        try {
            new ResourceAssociationTypeEnd('name', $type, $type, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorToPropertyBadTypeThrowException()
    {
        $type = m::mock(ResourceEntityType::class);

        $expected = 'The argument \'$fromProperty\' must be either null or instance of \'ResourceProperty\'.';
        $actual = null;

        try {
            new ResourceAssociationTypeEnd('name', $type, null, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testIsBelongsToWithNullResourceType()
    {
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getFullName')->andReturn('Northwind.Customer', 'Northwind.Order');
        $from = m::mock(ResourceProperty::class);

        $foo = new ResourceAssociationTypeEnd('name', $type, null, $from);

        $result = $foo->isBelongsTo($type, null);
        $this->assertFalse($result);
    }

    public function testIsBelongsToWithNullMismatchOnResourceTypes()
    {
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getFullName')->andReturn('Northwind.Customer', 'Northwind.Order');
        $from = m::mock(ResourceProperty::class);

        $foo = new ResourceAssociationTypeEnd('name', $type, null, $from);
        $result = $foo->isBelongsTo($type, $from);
        $this->assertFalse($result);
    }
}
