<?php

namespace UnitTests\POData\Providers\Metadata;

use POData\Providers\Metadata\ResourceType;
use UnitTests\POData\BaseServiceDummy;
use UnitTests\POData\TestCase;
use POData\Providers\Metadata\ResourceFunctionType;
use AlgoWeb\ODataMetadata\MetadataV3\edm\EntityContainer\FunctionImportAnonymousType;
use Mockery as m;

class ResourceFunctionTypeTest extends TestCase
{
    public function testCreateNullName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = null;

        $expected = "FunctionName must not be null";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateEmptyArrayName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = [];

        $expected = "FunctionName must have 1 or 2 elements";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateOverflowingArrayName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['stop!', 'hammer', 'time!'];

        $expected = "FunctionName must have no more than 2 elements";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithBadContentsFirstElementName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = [['stop!'], 'hammer'];

        $expected = "First element of FunctionName must be either object or string";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithBadContentsSecondElementName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['hammer', ['stop!']];

        $expected = "Second element of FunctionName must be string";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithEncapsulatedBadContentsFirstElementName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['eval', 'hammer'];

        $expected = "First element of FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithEncapsulatedMixedCaseBadContentsFirstElementName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['SYSTEM', 'hammer'];

        $expected = "First element of FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithEmptyFirstElementName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['', 'hammer'];

        $expected = "First element of FunctionName must not be empty";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithTwoNullElementName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = [null, null];

        $expected = "First element of FunctionName must be either object or string";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateObjectName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = new \DateTime();

        $expected = "Function name must be string or array";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateEmptyName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ' ';

        $expected = "FunctionName must be a non-empty string";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateBlacklistedName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = 'exec';

        $expected = "FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateEncapsulatedAndBlacklistedName()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = 'exec';

        $expected = "FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType([$name], $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateBlacklistedNameMixedCase()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = 'SyStEm';

        $expected = "FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateBadType()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(false)->once();

        $name = "func";

        $expected = "";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type, $resource);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetters()
    {
        $resource = m::mock(ResourceType::class);
        $resource->shouldReceive('getName')->andReturn('foo');
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getName')->andReturn('type')->once();
        $type->shouldReceive('getParameter')->andReturn([])->once();

        $name = "func";

        $foo = new ResourceFunctionType($name, $type, $resource);
        $parms = $foo->getParms();
        $this->assertEquals("func", $foo->getFunctionName());
        $this->assertEquals("type", $foo->getName());
        $this->assertTrue(is_array($parms));
        $this->assertEquals(0, count($parms));
        $res = $foo->getResourceType();
        $this->assertTrue($res instanceof ResourceType);
        $this->assertEquals('foo', $res->getName());
    }

    public function testGetTooManyParms()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn([])->once();

        $name = "func";
        $foo = new ResourceFunctionType($name, $type, $resource);

        $expected = "Was expecting 0 arguments, received 1 instead";
        $actual = null;

        try {
            $foo->get(['a']);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetTooFewParms()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn(['a'])->once();

        $name = "func";
        $foo = new ResourceFunctionType($name, $type, $resource);

        $expected = "Was expecting 1 arguments, received 0 instead";
        $actual = null;

        try {
            $foo->get();
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetWithBuiltinFunctionOneParms()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn(['a'])->once();

        $name = "date";
        $foo = new ResourceFunctionType($name, $type, $resource);

        $parms = 'Y-m-d';

        $result = $foo->get([$parms]);
        $this->assertTrue(is_string($result));
    }

    public function testWithObjectAndMethodCallNoParms()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn([])->once();

        // needed an object, this was close to hand
        $obj = m::mock(BaseServiceDummy::class)->makePartial();
        $method = 'getQueryProvider';

        $name = [$obj, $method];

        $foo = new ResourceFunctionType($name, $type, $resource);

        $result = $foo->get();
        $this->assertNull($result);
    }

    public function testWithStaticMethodCallBifurcatedAndParms()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn(['a', 'b'])->once();

        $name = [get_class($this), 'addTwoNumbers'];

        $foo = new ResourceFunctionType($name, $type, $resource);

        $result = $foo->get([1, 2]);
        $this->assertEquals(3, $result);
    }

    public function testWithStaticMethodCallUnbifurcatedAndParms()
    {
        $resource = m::mock(ResourceType::class);
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn(['a', 'b'])->once();

        $name = [get_class($this) . '::addTwoNumbers'];

        $foo = new ResourceFunctionType($name, $type, $resource);

        $result = $foo->get([1, 2]);
        $this->assertEquals(3, $result);
    }


    public static function addTwoNumbers($a, $b)
    {
        return $a + $b;
    }
}
