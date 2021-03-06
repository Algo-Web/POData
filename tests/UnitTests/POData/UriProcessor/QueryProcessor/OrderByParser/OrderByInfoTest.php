<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor\QueryProcessor\OrderByParser;

use Mockery as m;
use POData\Common\ODataException;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByInfo;
use UnitTests\POData\TestCase;

class OrderByInfoTest extends TestCase
{
    public function testCreateWithEmptyArraySegments()
    {
        $expected = 'The argument orderByPathSegments should be a non-empty array';
        $actual   = null;

        try {
            new OrderByInfo([], null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testCreateWithEmptyArrayNavProperties()
    {
        $expected = 'The argument navigationPropertiesUsedInTheOrderByClause should be'
                    . ' either null or a non-empty array';
        $actual = null;

        try {
            new OrderByInfo(['abc'], []);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSetIsSortedRoundTrip()
    {
        $foo = new OrderByInfo(['abc'], null);

        $foo->setSorted(false);
        $this->assertTrue($foo->requireInternalSorting());
    }
}
