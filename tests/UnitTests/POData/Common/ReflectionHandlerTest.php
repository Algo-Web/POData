<?php

namespace UnitTests\POData\Common;

use Mockery as m;
use POData\Common\ReflectionHandler;
use UnitTests\POData\TestCase;

class ReflectionHandlerTest extends TestCase
{
    public function testSetGetPrivateValue()
    {
        $expected = 'oldName';
        $foo = new reflectionTest1('newName');
        ReflectionHandler::setProperty($foo, 'name', 'oldName');
        $actual = ReflectionHandler::getProperty($foo, 'name');
        $this->assertEquals($expected, $actual);
    }
}

class reflectionTest1
{
    private $name;

    public function __construct($name)
    {
    }
}
