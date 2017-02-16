<?php

namespace UnitTests\POData\Providers\Metadata;

use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\EdmPrimitiveType;

use Mockery as m;

class ResourceTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPrimitiveResourceTypeByte()
    {
        $type = EdmPrimitiveType::BYTE;
        $result = ResourceType::getPrimitiveResourceType($type);
        $this->assertTrue($result instanceof ResourceType);
        $this->assertEquals("Byte", $result->getName());
        $this->assertEquals("Edm", $result->getNamespace());
        $this->assertEquals("Edm.Byte", $result->getFullName());
    }

    public function testGetPrimitiveResourceTypeSByte()
    {
        $type = EdmPrimitiveType::SBYTE;
        $result = ResourceType::getPrimitiveResourceType($type);
        $this->assertTrue($result instanceof ResourceType);
        $this->assertEquals("SByte", $result->getName());
        $this->assertEquals("Edm", $result->getNamespace());
        $this->assertEquals("Edm.SByte", $result->getFullName());
    }

    public function testHasETagPropertiesYes()
    {
        $foo = m::mock(ResourceType::class)->makePartial();
        $foo->shouldReceive('getETagProperties')->andReturn(['a', 'b'])->once();
        $this->assertTrue($foo->hasETagProperties());
    }

    public function testHasETagPropertiesNo()
    {
        $foo = m::mock(ResourceType::class)->makePartial();
        $foo->shouldReceive('getETagProperties')->andReturn()->once();
        $this->assertFalse($foo->hasETagProperties());
    }

    public function testGetETagProperties()
    {
        $property = m::mock(ResourceType::class);
        $property->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::ETAG])->andReturn(true);
        $property->shouldReceive('getName')->andReturn('property');

        $foo = m::mock(ResourceType::class)->makePartial();
        $foo->shouldReceive('getAllProperties')->andReturn(['name' => $property]);
        $result = $foo->getETagProperties();
        $this->assertTrue(is_array($result));
        $this->assertTrue($result['name'] instanceof ResourceType);
        $this->assertEquals('property', $result['name']->getName());
    }

    public function testAddNamedStreamWhenNotEntityThrowException()
    {
        $info = m::mock(ResourceStreamInfo::class);
        $foo = m::mock(ResourceType::class)->makePartial();

        $expected = 'Named streams can only be added to entity types.';
        $actual = null;

        try {
            $foo->addNamedStream($info);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}