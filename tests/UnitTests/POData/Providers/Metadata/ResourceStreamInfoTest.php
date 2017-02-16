<?php

namespace UnitTests\POData\Providers\Metadata;

use POData\Providers\Metadata\ResourceStreamInfo;
use UnitTests\POData\ObjectModel\reusableEntityClass2;

class ResourceStreamInfoTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSetCustomStateRoundTrip()
    {
        $foo = new ResourceStreamInfo("name");
        $object = new reusableEntityClass2("foo", "bar");

        $foo->setCustomState($object);
        $result = $foo->getCustomState();
        $this->assertEquals("foo", $result->name);
        $this->assertEquals("bar", $result->type);
    }

}