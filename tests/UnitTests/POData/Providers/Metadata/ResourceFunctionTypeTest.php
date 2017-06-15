<?php

namespace UnitTests\POData\Providers\Metadata;

use UnitTests\POData\BaseServiceDummy;
use UnitTests\POData\TestCase;
use POData\Providers\Metadata\ResourceFunctionType;
use AlgoWeb\ODataMetadata\MetadataV3\edm\EntityContainer\FunctionImportAnonymousType;
use Mockery as m;

class ResourceFunctionTypeTest extends TestCase
{
    public function testCreateNullName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = null;

        $expected = "FunctionName must not be null";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateEmptyArrayName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = [];

        $expected = "FunctionName must have 1 or 2 elements";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateOverflowingArrayName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['stop!', 'hammer', 'time!'];

        $expected = "FunctionName must have no more than 2 elements";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithBadContentsFirstElementName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = [['stop!'], 'hammer'];

        $expected = "First element of FunctionName must be either object or string";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithBadContentsSecondElementName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['hammer', ['stop!']];

        $expected = "Second element of FunctionName must be string";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithEncapsulatedBadContentsFirstElementName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['eval', 'hammer'];

        $expected = "First element of FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithEncapsulatedMixedCaseBadContentsFirstElementName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['SYSTEM', 'hammer'];

        $expected = "First element of FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithEmptyFirstElementName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ['', 'hammer'];

        $expected = "First element of FunctionName must not be empty";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateArrayWithTwoNullElementName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = [null, null];

        $expected = "First element of FunctionName must be either object or string";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateObjectName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = new \DateTime();

        $expected = "Function name must be string or array";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateEmptyName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = ' ';

        $expected = "FunctionName must be a non-empty string";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateBlacklistedName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = 'exec';

        $expected = "FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateEncapsulatedAndBlacklistedName()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = 'exec';

        $expected = "FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType([$name], $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateBlacklistedNameMixedCase()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $name = 'SyStEm';

        $expected = "FunctionName blacklisted";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateBadType()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(false)->once();

        $name = "func";

        $expected = "";
        $actual = null;

        try {
            $foo = new ResourceFunctionType($name, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetters()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getName')->andReturn('type')->once();
        $type->shouldReceive('getParameter')->andReturn([])->once();

        $name = "func";

        $foo = new ResourceFunctionType($name, $type);
        $parms = $foo->getParms();
        $this->assertEquals("func", $foo->getFunctionName());
        $this->assertEquals("type", $foo->getName());
        $this->assertTrue(is_array($parms));
        $this->assertEquals(0, count($parms));
    }

    public function testGetTooManyParms()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn([])->once();

        $name = "func";
        $foo = new ResourceFunctionType($name, $type);

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
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn(['a'])->once();

        $name = "func";
        $foo = new ResourceFunctionType($name, $type);

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
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn(['a'])->once();

        $name = "date";
        $foo = new ResourceFunctionType($name, $type);

        $parms = 'Y-m-d';

        $result = $foo->get([$parms]);
        $this->assertTrue(is_string($result));
    }

    public function testWithObjectAndMethodCallNoParms()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn([])->once();

        // needed an object, this was close to hand
        $obj = m::mock(BaseServiceDummy::class)->makePartial();
        $method = 'getQueryProvider';

        $name = [$obj, $method];

        $foo = new ResourceFunctionType($name, $type);

        $result = $foo->get();
        $this->assertNull($result);
    }

    public function testWithStaticMethodCallBifurcatedAndParms()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn(['a', 'b'])->once();

        $name = [get_class($this), 'addTwoNumbers'];

        $foo = new ResourceFunctionType($name, $type);

        $result = $foo->get([1, 2]);
        $this->assertEquals(3, $result);
    }

    public function testWithStaticMethodCallUnbifurcatedAndParms()
    {
        $type = m::mock(FunctionImportAnonymousType::class)->makePartial();
        $type->shouldReceive('isOK')->andReturn(true)->once();
        $type->shouldReceive('getParameter')->andReturn(['a', 'b'])->once();

        $name = [get_class($this) . '::addTwoNumbers'];

        $foo = new ResourceFunctionType($name, $type);

        $result = $foo->get([1, 2]);
        $this->assertEquals(3, $result);
    }


    public static function addTwoNumbers($a, $b)
    {
        return $a + $b;
    }
}
