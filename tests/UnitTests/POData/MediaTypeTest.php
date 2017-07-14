<?php

namespace UnitTests\POData;

use POData\MediaType;
use UnitTests\POData\TestCase;
use Mockery as m;

class MediaTypeTest extends TestCase
{
    public function testGetParameters()
    {
        $foo = new MediaType('foo', 'bar', []);
        $this->assertEquals(0, count($foo->getParameters()));
    }
}
