<?php

namespace UnitTests\POData\ObjectModel;

use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataLink;
use UnitTests\POData\TestCase;

class ODataLinkTest extends TestCase
{
    public function testSetGetODataEntryAsExpandedResult()
    {
        $entry = new ODataEntry();
        $expand = new ODataExpandedResult($entry);

        $foo = new ODataLink();
        $foo->setExpandResult($expand);
        $result = $foo->getExpandResult();

        $this->assertNotNull($result->entry);
        $this->assertNull($result->feed);
    }
}
