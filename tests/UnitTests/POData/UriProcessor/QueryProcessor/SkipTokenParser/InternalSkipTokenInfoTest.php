<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\SkipTokenParser;

use Mockery as m;
use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\InternalSkipTokenInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenParser;
use UnitTests\POData\Facets\NorthWind2\NorthWindMetadata;
use UnitTests\POData\TestCase;

class InternalSkipTokenInfoTest extends TestCase
{
    public function testGetIndexOfNextPageFromEmptyArray()
    {
        $foo = m::mock(InternalSkipTokenInfo::class)->makePartial();

        $searchArray = [];
        $this->assertEquals(-1, $foo->getIndexOfFirstEntryInTheNextPage($searchArray));
    }

    public function testGetIndexOfNextPageFromMiddleOfArray()
    {
        $comparer = function ($object1, $object2) {
            if ($object1 == $object2) {
                return 0;
            }
            return $object1 < $object2 ? -1 : 1;
        };

        $foo = m::mock(InternalSkipTokenInfo::class)->makePartial();
        $foo->shouldReceive('getKeyObject')->andReturn(3);
        $foo->shouldReceive('getInternalOrderByInfo->getSorterFunction')->andReturn($comparer);

        $searchArray = [0, 1, 2, 3, 4, 5, 6, 7];

        $expected = 4;
        $actual = $foo->getIndexOfFirstEntryInTheNextPage($searchArray);
        $this->assertEquals($expected, $actual);
    }

    public function testGetIndexOfNextPageFromBeyondEndOfArray()
    {
        $comparer = function ($object1, $object2) {
            if ($object1 == $object2) {
                return 0;
            }
            return $object1 < $object2 ? -1 : 1;
        };

        $foo = m::mock(InternalSkipTokenInfo::class)->makePartial();
        $foo->shouldReceive('getKeyObject')->andReturn(8);
        $foo->shouldReceive('getInternalOrderByInfo->getSorterFunction')->andReturn($comparer);

        $searchArray = [0, 1, 2, 3, 4, 5, 6, 7];

        $expected = -1;
        $actual = $foo->getIndexOfFirstEntryInTheNextPage($searchArray);
        $this->assertEquals($expected, $actual);
    }

    public function testGetIndexOfNextPageAtEndOfArray()
    {
        $comparer = function ($object1, $object2) {
            if ($object1 == $object2) {
                return 0;
            }
            return $object1 < $object2 ? -1 : 1;
        };

        $foo = m::mock(InternalSkipTokenInfo::class)->makePartial();
        $foo->shouldReceive('getKeyObject')->andReturn(7);
        $foo->shouldReceive('getInternalOrderByInfo->getSorterFunction')->andReturn($comparer);

        $searchArray = [0, 1, 2, 3, 4, 5, 6, 7];

        $expected = -1;
        $actual = $foo->getIndexOfFirstEntryInTheNextPage($searchArray);
        $this->assertEquals($expected, $actual);
    }

    public function testGetIndexOfNextPageFromBeforeStartOfArray()
    {
        $comparer = function ($object1, $object2) {
            if ($object1 == $object2) {
                return 0;
            }
            return $object1 < $object2 ? -1 : 1;
        };

        $foo = m::mock(InternalSkipTokenInfo::class)->makePartial();
        $foo->shouldReceive('getKeyObject')->andReturn(-1);
        $foo->shouldReceive('getInternalOrderByInfo->getSorterFunction')->andReturn($comparer);

        $searchArray = [0, 1, 2, 3, 4, 5, 6, 7];

        $expected = 0;
        $actual = $foo->getIndexOfFirstEntryInTheNextPage($searchArray);
        $this->assertEquals($expected, $actual);
    }

    public function testGetIndexOfNextPageBetweenArrayElements()
    {
        $comparer = function ($object1, $object2) {
            if ($object1 == $object2) {
                return 0;
            }
            return $object1 < $object2 ? -1 : 1;
        };

        $foo = m::mock(InternalSkipTokenInfo::class)->makePartial();
        $foo->shouldReceive('getKeyObject')->andReturn(4.5);
        $foo->shouldReceive('getInternalOrderByInfo->getSorterFunction')->andReturn($comparer);

        $searchArray = [0, 1, 2, 3, 4, 5, 6, 7];

        $expected = 5;
        $actual = $foo->getIndexOfFirstEntryInTheNextPage($searchArray);
        $this->assertEquals($expected, $actual);
    }
}
