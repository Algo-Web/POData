<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\SkipTokenParser;

use Mockery as m;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use UnitTests\POData\TestCase;

class SkipTokenInfoTest extends TestCase
{
    public function testGetOrderByInfo()
    {
        $info = m::mock(OrderByInfo::class);

        $foo = new SkipTokenInfo($info, []);
        $this->assertTrue($foo->getOrderByInfo() instanceof OrderByInfo);
    }
}
