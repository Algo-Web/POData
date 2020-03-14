<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor\QueryProcessor\OrderByParser;

use Mockery as m;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use UnitTests\POData\TestCase;

/**
 * Class OrderByPathSegmentTest
 * @package UnitTests\POData\UriProcessor\QueryProcessor\OrderByParser
 */
class OrderByPathSegmentTest extends TestCase
{
    public function testConstructWithSegmentsEmptyArray()
    {
        $expected = 'The argument orderBySubPathSegments should be a non-empty array';
        $actual   = null;

        try {
            new OrderByPathSegment([]);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }
}
