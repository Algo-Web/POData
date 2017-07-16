<?php

namespace UnitTests\POData\Common;

use POData\Common\ReflectionHandler;
use UnitTests\POData\TestCase;
use Mockery as m;

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
