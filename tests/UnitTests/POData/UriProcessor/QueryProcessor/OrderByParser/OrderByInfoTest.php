<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\OrderByParser;

use Mockery as m;
use POData\Common\ODataException;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByInfo;
use UnitTests\POData\TestCase;

class OrderByInfoTest extends TestCase
{
    public function testCreateWithNonArraySegments()
    {
        $expected = 'The argument orderByPathSegments should be a non-empty array';
        $actual = null;

        try {
            new OrderByInfo(new \DateTime(), null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateWithEmptyArraySegments()
    {
        $expected = 'The argument orderByPathSegments should be a non-empty array';
        $actual = null;

        try {
            new OrderByInfo([], null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateWithNonArrayNavProperties()
    {
        $expected = 'The argument navigationPropertiesUsedInTheOrderByClause should be'
                    .' either null or a non-empty array';
        $actual = null;

        try {
            new OrderByInfo(['abc'], new \DateTime());
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateWithEmptyArrayNavProperties()
    {
        $expected = 'The argument navigationPropertiesUsedInTheOrderByClause should be'
                    .' either null or a non-empty array';
        $actual = null;

        try {
            new OrderByInfo(['abc'], []);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetIsSortedRoundTrip()
    {
        $foo = new OrderByInfo(['abc'], null);

        $foo->setSorted(false);
        $this->assertTrue($foo->requireInternalSorting());
    }
}
