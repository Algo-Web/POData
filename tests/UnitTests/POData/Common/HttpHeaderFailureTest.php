<?php

namespace UnitTests\POData\Common;

use POData\Common\HttpHeaderFailure;
use UnitTests\POData\TestCase;

class HttpHeaderFailureTest extends TestCase
{
    public function testGetStatusCode()
    {
        $foo = new HttpHeaderFailure('FAIL', 601);
        $this->assertEquals('FAIL', $foo->getMessage());
        $this->assertEquals(601, $foo->getStatusCode());
    }
}
