<?php

namespace UnitTests\POData\ObjectModel;

use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataPropertyContent;
use UnitTests\POData\TestCase;

class ODataEntryTest extends TestCase
{
    public function testOkNoContent()
    {
        $foo = new ODataEntry();
        $expected = 'Property content must be instanceof ODataPropertyContent.';

        $actual = null;
        $foo->isOK($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testOkEmptyContent()
    {
        $foo = new ODataEntry();
        $foo->propertyContent = new ODataPropertyContent();
        $expected = 'Must have at least one property present.';

        $actual = null;
        $foo->isOK($actual);
        $this->assertEquals($expected, $actual);
    }
}
