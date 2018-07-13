<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\OrderByParser;

use Mockery as m;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use UnitTests\POData\TestCase;

class OrderByPathSegmentTest extends TestCase
{
    public function testConstructWithSegmentsNotArray()
    {
        $expected = 'The argument orderBySubPathSegments should be a non-empty array';
        $actual = null;

        try {
            new OrderByPathSegment(null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testConstructWithSegmentsEmptyArray()
    {
        $expected = 'The argument orderBySubPathSegments should be a non-empty array';
        $actual = null;

        try {
            new OrderByPathSegment([]);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }
}
