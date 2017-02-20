<?php

namespace UnitTests\POData\Providers\Metadata;

use POData\Providers\Query\QueryResult;
use UnitTests\POData\TestCase;

class QueryResultTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testAdjustCountForPaging($id, $count, $top, $skip, $expected)
    {
        $actual = QueryResult::adjustCountForPaging($count, $top, $skip);

        $this->assertEquals($expected, $actual, $id);
    }

    public function provider()
    {
        return [
                        //count //top   //skip  //expected
            [101,  0,      null,   null,   0],
            [102,  1,      null,   null,   1],
            [103,  10,     1,      null,   1],
            [104,  0,      1,      null,   0],
            [105,  0,      null,   1,      0],
            [105,  0,      1,      1,      0],
            [106,  10,     5,      5,      5],
            [107,  10,     5,      7,      3],
            [107,  10,     15,     7,      3],
        ];
    }
}
