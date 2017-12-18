<?php

namespace UnitTests\POData\ObjectModel;

use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataLink;
use UnitTests\POData\TestCase;

class ODataURLTest extends TestCase
{
    public function testNotOkWhenNullUrl()
    {
        $foo = new ODataURL();
        $expected = "Url value must be non-empty";
        $actual = null;

        $this->assertFalse($foo->isOk($actual));
        $this->assertEquals($expected, $actual);
    }

    public function testNotOkWhenEmptyUrl()
    {
        $foo = new ODataURL();
        $foo->url = '';
        $expected = "Url value must be non-empty";
        $actual = null;

        $this->assertFalse($foo->isOk($actual));
        $this->assertEquals($expected, $actual);
    }

    public function testOkWhenNonEmptyUrl()
    {
        $foo = new ODataURL();
        $foo->url = 'url';
        $expected = null;
        $actual = null;

        $this->assertTrue($foo->isOk($actual));
        $this->assertEquals($expected, $actual);
    }
}
