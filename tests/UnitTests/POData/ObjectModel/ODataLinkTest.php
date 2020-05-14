<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel;

use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use UnitTests\POData\TestCase;

class ODataLinkTest extends TestCase
{
    public function testSetGetODataEntryAsExpandedResult()
    {
        $entry  = new ODataEntry();
        $expand = new ODataExpandedResult($entry);

        $foo = new ODataLink();
        $foo->setExpandResult($expand);
        $result = $foo->getExpandResult();

        $this->assertNotNull($result->getEntry());
        $this->assertNull($result->feed);
    }

    public function testSetGetODataFeedAsExpandedResult()
    {
        $entry  = new ODataFeed();
        $expand = new ODataExpandedResult(null, $entry);

        $foo = new ODataLink();
        $foo->setExpandResult($expand);
        $result = $foo->getExpandResult();

        $this->assertNull($result->getEntry());
        $this->assertNotNull($result->feed);
    }

    public function testGetExpandedResultOnEmptyLink()
    {
        $foo = new ODataLink();
        $this->assertNull($foo->getExpandResult());
    }
}
