<?php

namespace UnitTests\POData\Providers\Metadata;

use POData\Providers\Metadata\ResourceProperty;
use POData\Common\ODataException;
use POData\Providers\Query\QueryResult;


use Phockito;
use UnitTests\POData\BaseUnitTestCase;

class QueryResultTest extends BaseUnitTestCase
{
	/**
	 * @dataProvider provider
	 */
	public function testAdjustCountForPaging($id, $count, $top, $skip, $expected)
	{
		$actual = QueryResult::adjustCountForPaging($count, $top, $skip);

		$this->assertEquals($expected, $actual, $id);
	}

	public function provider(){
		return array(
					    //count //top   //skip  //expected
			array(101,  0,      null,   null,   0),
			array(102,  1,      null,   null,   1),
			array(103,  10,     1,      null,   1),
			array(104,  0,      1,      null,   0),
			array(105,  0,      null,   1,      0),
			array(105,  0,      1,      1,      0),
			array(106,  10,     5,      5,      5),
			array(107,  10,     5,      7,      3),
			array(107,  10,     15,     7,      3),
		);
	}

}