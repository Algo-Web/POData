<?php

namespace UnitTests\POData;

use Mockery as m;
use POData\MediaType;
use UnitTests\POData\TestCase;

class MediaTypeTest extends TestCase
{
    public function testGetParameters()
    {
        $foo = new MediaType('foo', 'bar', []);
        $this->assertEquals(0, count($foo->getParameters()));
    }
}
