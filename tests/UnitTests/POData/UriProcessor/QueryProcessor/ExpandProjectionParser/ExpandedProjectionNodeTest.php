<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpandProjectionParser;

use Mockery as m;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use UnitTests\POData\TestCase;

class ExpandedProjectionNodeTest extends TestCase
{
    public function testScalarProperties()
    {
        $propName = 'property';
        $wrapper = m::mock(ResourceSetWrapper::class);
        $order = m::mock(InternalOrderByInfo::class);
        $skipCount = 1;
        $takeCount = 2;
        $maxResultCount = 10;

        $foo = new ExpandedProjectionNode($propName, $wrapper, $order, $skipCount, $takeCount, $maxResultCount);
        $this->assertEquals($skipCount, $foo->getSkipCount());
        $this->assertEquals($takeCount, $foo->getTakeCount());
        $this->assertEquals($maxResultCount, $foo->getMaxResultCount());
    }

    public function testSelectionFoundRoundTripTrue()
    {
        $foo = m::mock(ExpandedProjectionNode::class)->makePartial();
        $foo->setSelectionFound(true);
        $this->assertTrue($foo->isSelectionFound());
    }

    public function testSelectionFoundRoundTripFalse()
    {
        $foo = m::mock(ExpandedProjectionNode::class)->makePartial();
        $foo->setSelectionFound(false);
        $this->assertFalse($foo->isSelectionFound());
    }
}
