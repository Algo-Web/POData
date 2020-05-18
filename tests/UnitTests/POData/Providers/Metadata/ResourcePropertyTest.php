<?php

declare(strict_types=1);

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
    public function testConstructorEmptyNameThrowsException()
    {
        $kind     = ResourcePropertyKind::BAG();
        $type     = m::mock(ResourceType::class);
        $name     = '';
        $mimeName = 'mime';

        $expected = 'Property name violates OData specification.';
        $actual   = null;

        try {
            new ResourceProperty($name, $mimeName, $kind, $type);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorNameWithLeadingUnderscoreThrowsException()
    {
        $kind     = ResourcePropertyKind::BAG();
        $type     = m::mock(ResourceType::class);
        $name     = '_name';
        $mimeName = 'mime';

        $expected = 'Property name violates OData specification.';
        $actual   = null;

        try {
            new ResourceProperty($name, $mimeName, $kind, $type);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetMimeType()
    {
        $name     = 'name';
        $mimeName = 'foo';
        $kind     = ResourcePropertyKind::RESOURCE_REFERENCE();
        $entKind  = ResourceTypeKind::ENTITY();
        $type     = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn($entKind);

        $foo = new ResourceProperty($name, $mimeName, $kind, $type);
        $this->assertEquals($mimeName, $foo->getMIMEType());
    }

    public function testResourceTypePropertyMismatchOnPrimitive()
    {
        $name     = 'name';
        $mimeName = 'foo';
        $kind     = ResourcePropertyKind::PRIMITIVE();
        $entKind  = ResourceTypeKind::ENTITY();
        $type     = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn($entKind);

        $expected = 'The \'$kind\' parameter does not match with the type of the resource '
                    . 'type in parameter \'$propertyResourceType\'';
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
        $name     = 'name';
        $mimeName = 'foo';
        $kind     = ResourcePropertyKind::RESOURCE_REFERENCE();
        $entKind  = ResourceTypeKind::PRIMITIVE();
        $type     = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn($entKind);

        $expected = 'The \'$kind\' parameter does not match with the type of the resource '
                    . 'type in parameter \'$propertyResourceType\'';
        $actual = null;
        try {
            $foo = new ResourceProperty($name, $mimeName, $kind, $type);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function validResourcePropertyKindProvider(): array
    {
        $result = [];

        $result[] = [1, false];
        $result[] = [2, true];
        $result[] = [3, true];
        $result[] = [4, false];
        $result[] = [6, false]; // fail
        $result[] = [7, false]; // fail
        $result[] = [8, false];
        $result[] = [9, false];
        $result[] = [10, false]; // fail
        $result[] = [11, false]; // fail
        $result[] = [12, false];
        $result[] = [13, false];
        $result[] = [14, false]; // fail
        $result[] = [15, false]; // fail
        $result[] = [16, true];
        $result[] = [17, true];
        $result[] = [18, false]; // fail
        $result[] = [19, false]; // fail
        $result[] = [20, true];
        $result[] = [24, true];
        $result[] = [28, false]; // fail
        $result[] = [32, true];
        $result[] = [64, true];

        return $result;
    }

    /**
     * @dataProvider validResourcePropertyKindProvider
     *
     * @param int  $kind
     * @param bool $expected
     */
    public function testIsValidResourcePropertyKind(int $kind, bool $expected)
    {
        $actual = ResourceProperty::isValidResourcePropertyKind(new ResourcePropertyKind($kind));

        $this->assertEquals($expected, $actual);
    }
}
