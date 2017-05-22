<?php

namespace UnitTests\POData\Providers\Metadata;

use InvalidArgumentException;
use Mockery as m;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use UnitTests\POData\TestCase;

class ResourcePropertyTest extends TestCase
{
    public function testConstructorNullNameThrowsException()
    {
        $kind = ResourcePropertyKind::BAG;
        $type = m::mock(ResourceType::class);
        $name = null;
        $mimeName = 'mime';

        $expected = 'Property name violates OData specification.';
        $actual = null;

        try {
            new ResourceProperty($name, $mimeName, $kind, $type);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorEmptyNameThrowsException()
    {
        $kind = ResourcePropertyKind::BAG;
        $type = m::mock(ResourceType::class);
        $name = '';
        $mimeName = 'mime';

        $expected = 'Property name violates OData specification.';
        $actual = null;

        try {
            new ResourceProperty($name, $mimeName, $kind, $type);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorNonStringNameThrowsException()
    {
        $kind = ResourcePropertyKind::BAG;
        $type = m::mock(ResourceType::class);
        $name = new \DateTime();
        $mimeName = 'mime';

        $expected = 'Property name violates OData specification.';
        $actual = null;

        try {
            new ResourceProperty($name, $mimeName, $kind, $type);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorNameWithLeadingUnderscoreThrowsException()
    {
        $kind = ResourcePropertyKind::BAG;
        $type = m::mock(ResourceType::class);
        $name = '_name';
        $mimeName = 'mime';

        $expected = 'Property name violates OData specification.';
        $actual = null;

        try {
            new ResourceProperty($name, $mimeName, $kind, $type);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetMimeType()
    {
        $name = "name";
        $mimeName = "foo";
        $kind = ResourcePropertyKind::RESOURCE_REFERENCE;
        $entKind = ResourceTypeKind::ENTITY;
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn($entKind);

        $foo = new ResourceProperty($name, $mimeName, $kind, $type);
        $this->assertEquals($mimeName, $foo->getMIMEType());
    }

    public function testResourceTypePropertyMismatchOnPrimitive()
    {
        $name = "name";
        $mimeName = "foo";
        $kind = ResourcePropertyKind::PRIMITIVE;
        $entKind = ResourceTypeKind::ENTITY;
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn($entKind);

        $expected = 'The \'$kind\' parameter does not match with the type of the resource '
                    .'type in parameter \'$propertyResourceType\'';
        $actual = null;
        try {
            $foo = new ResourceProperty($name, $mimeName, $kind, $type);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testResourceTypePropertyMismatchOnResource()
    {
        $name = "name";
        $mimeName = "foo";
        $kind = ResourcePropertyKind::RESOURCE_REFERENCE;
        $entKind = ResourceTypeKind::PRIMITIVE;
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn($entKind);

        $expected = 'The \'$kind\' parameter does not match with the type of the resource '
                    .'type in parameter \'$propertyResourceType\'';
        $actual = null;
        try {
            $foo = new ResourceProperty($name, $mimeName, $kind, $type);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
