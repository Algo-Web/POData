<?php

namespace UnitTests\POData\Providers\Metadata\Type;

use Mockery as m;
use POData\Common\NotImplementedException;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\Navigation;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Metadata\Type\TypeCode;
use UnitTests\POData\TestCase;

class NavigationTest extends TestCase
{
    protected $resource;

    public function setUp()
    {
        $this->resource = m::mock(ResourceType::class)->makePartial();
    }

    /**
     * @return IType
     */
    public function getAsIType($kind = ResourceTypeKind::COMPLEX)
    {
        $this->resource->shouldReceive('getResourceTypeKind')->andReturn($kind);

        return new Navigation($this->resource);
    }

    public function testConstructWithBadResourceTypeThrowException()
    {
        $this->resource->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE);

        $expected = 'Only possible Navigation types are Complex and Entity.';
        $actual = null;

        try {
            $foo = new Navigation($this->resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetTypeCode()
    {
        $foo = $this->getAsIType();

        $this->assertEquals(TypeCode::NAVIGATION, $foo->getTypeCode());
    }

    public function testIncompatibleWithOtherIType()
    {
        $foo = $this->getAsIType(ResourceTypeKind::COMPLEX);

        $bar = new StringType();
        $this->assertFalse($foo->isCompatibleWith($bar));
    }

    public function testCompatibleWithOwnType()
    {
        $foo = $this->getAsIType();
        $bar = $this->getAsIType(ResourceTypeKind::ENTITY);

        $this->assertTrue($foo->isCompatibleWith($bar));
        $this->assertTrue($bar->isCompatibleWith($foo));
    }

    public function testInCompatibleWithOwnTypeDifferentNames()
    {
        $foo = $this->getAsIType();
        $bar = $this->getAsIType(ResourceTypeKind::ENTITY);

        $this->resource->shouldReceive('getFullName')->andReturn('foo', 'bar', 'bar', 'foo');

        $this->assertFalse($foo->isCompatibleWith($bar));
        $this->assertFalse($bar->isCompatibleWith($foo));
    }

    public function testValidateDifferentIType()
    {
        $foo = $this->getAsIType(ResourceTypeKind::COMPLEX);

        $bar = new StringType();
        $out = '';
        $this->assertFalse($foo->validate($bar, $out));
    }

    public function testValidateSameIType()
    {
        $foo = $this->getAsIType(ResourceTypeKind::COMPLEX);

        $bar = $this->getAsIType();
        $out = '';
        $this->assertTrue($foo->validate($bar, $out));
    }

    public function testGetNameTest()
    {
        $foo = $this->getAsIType();
        $this->resource->shouldReceive('getFullName')->andReturn('foo');

        $this->assertEquals('foo', $foo->getName());
    }

    public function testConvertThrowException()
    {
        $foo = $this->getAsIType();

        $expected = '';
        $actual = null;

        try {
            $foo->convert('foo');
        } catch (NotImplementedException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConvertToODataThrowException()
    {
        $foo = $this->getAsIType();

        $expected = '';
        $actual = null;

        try {
            $foo->convertToOData('foo');
        } catch (NotImplementedException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceType()
    {
        $foo = $this->getAsIType();
        $result = $foo->getResourceType();
        $this->assertEquals(ResourceTypeKind::COMPLEX, $result->getResourceTypeKind());
    }
}
