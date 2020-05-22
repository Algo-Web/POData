<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/05/20
 * Time: 12:52 AM
 */

namespace UnitTests\POData\ObjectModel;

use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use UnitTests\POData\TestCase;
use Mockery as m;

class ODataPropertyContentTest extends TestCase
{
    public function testOffsetExistsThenUnset()
    {
        $foo = new ODataPropertyContent([]);
        $foo['1'] = m::mock(ODataProperty::class)->makePartial();

        $this->assertTrue(isset($foo[1]));

        unset($foo[1]);
        $this->assertFalse(isset($foo[1]));
    }
}
