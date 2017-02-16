<?php

namespace UnitTests\POData\Common;

use POData\Common\ODataException;

class ODataExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateForbiddenODataException()
    {
        $foo = ODataException::createForbiddenError();
        $expected = 'Forbidden.';
        $actual = $foo->getMessage();
        $this->assertEquals($expected, $actual);
        $this->assertEquals(403, $foo->getStatusCode());
    }

    public function testCreatePreconditionFailedODataException()
    {
        $condition = 'foo == bar';
        $foo = ODataException::createPreConditionFailedError($condition);
        $expected = 'foo == bar';
        $actual = $foo->getMessage();
        $this->assertEquals($expected, $actual);
        $this->assertEquals(412, $foo->getStatusCode());
    }

    public function testCreateUnacceptableValueODataException()
    {
        $value = '$i < 0';
        $foo = ODataException::notAcceptableError($value);
        $expected = '$i < 0';
        $actual = $foo->getMessage();
        $this->assertEquals($expected, $actual);
        $this->assertEquals(406, $foo->getStatusCode());
    }
}