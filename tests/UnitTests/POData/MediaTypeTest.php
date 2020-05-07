<?php

declare(strict_types=1);

namespace UnitTests\POData;

use Mockery as m;
use POData\MediaType;
use UnitTests\POData\TestCase;

/**
 * Class MediaTypeTest.
 * @package UnitTests\POData
 */
class MediaTypeTest extends TestCase
{
    public function testGetParameters()
    {
        $foo = new MediaType('foo', 'bar', []);
        $this->assertEquals(0, count($foo->getParameters()));
    }

    public function testGetMatchingRatingOnUniversalMatch()
    {
        $foo = new MediaType('*', '*', []);

        $expected = 0;
        $actual   = $foo->getMatchingRating('application/json');
        $this->assertEquals($expected, $actual);
    }

    public function testGetMatchingRatingOnUniversalSubtypeMatch()
    {
        $foo = new MediaType('application', '*', []);

        $expected = 1;
        $actual   = $foo->getMatchingRating('application/json');
        $this->assertEquals($expected, $actual);
    }
}
