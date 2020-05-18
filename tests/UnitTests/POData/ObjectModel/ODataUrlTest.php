<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel;

use Mockery as m;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataURL;
use UnitTests\POData\TestCase;

class ODataUrlTest extends TestCase
{
    public function testNotOkWhenEmptyUrl()
    {
        $foo      = new ODataURL('');
        $expected = 'Url value must be non-empty';
        $actual   = null;

        $this->assertFalse($foo->isOk($actual));
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testOkWhenNonEmptyUrl()
    {
        $foo      = new ODataURL('url');
        $expected = null;
        $actual   = null;

        $this->assertTrue($foo->isOk($actual));
        $this->assertNull($actual);
        $this->assertEquals($expected, $actual);
    }
}
