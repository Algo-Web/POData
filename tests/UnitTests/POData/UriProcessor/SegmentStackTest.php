<?php

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\SegmentStack;
use UnitTests\POData\TestCase;

class SegmentStackTest extends TestCase
{
    public function testConstructor()
    {
        $wrap = m::mock(RequestDescription::class)->makePartial();
        $wrap->shouldReceive('getContainerName')->andReturn('wrap');
        $foo = new SegmentStack($wrap);

        $this->assertEquals('wrap', $foo->getRequest()->getContainerName());
        $this->assertEquals(0, count($foo->getSegmentNames()));
        $this->assertEquals(0, count($foo->getSegmentWrappers()));
    }

    public function testPopFromEmptyStackAndThrowException()
    {
        $wrap = m::mock(RequestDescription::class)->makePartial();
        $wrap->shouldReceive('getContainerName')->andReturn('wrap');
        $foo = new SegmentStack($wrap);

        $expected = 'Found non-balanced call to pushSegment and popSegment';
        $actual = null;

        try {
            $foo->popSegment(true);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testPushNonExpandableSegment()
    {
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(false);

        $wrap = m::mock(RequestDescription::class)->makePartial();
        $wrap->shouldReceive('getContainerName')->andReturn('wrap');
        $wrap->shouldReceive('getRootProjectionNode')->andReturn($node);
        $foo = new SegmentStack($wrap);

        $segName = 'Segment';
        $setWrap = m::mock(ResourceSetWrapper::class);
        $setWrap->shouldReceive('getName')->andReturn('entity');

        $result = $foo->pushSegment($segName, $setWrap);
        $this->assertFalse($result);
    }

    public function testPushExpandableSegmentAndPop()
    {
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);

        $wrap = m::mock(RequestDescription::class)->makePartial();
        $wrap->shouldReceive('getContainerName')->andReturn('wrap');
        $wrap->shouldReceive('getRootProjectionNode')->andReturn($node);
        $foo = new SegmentStack($wrap);
        $this->assertEquals(0, count($foo->getSegmentNames()));
        $this->assertEquals(0, count($foo->getSegmentWrappers()));

        $segName = 'Segment';
        $setWrap = m::mock(ResourceSetWrapper::class);
        $setWrap->shouldReceive('getName')->andReturn('entity');

        $result = $foo->pushSegment($segName, $setWrap);
        $this->assertTrue($result);
        $this->assertEquals(1, count($foo->getSegmentNames()));
        $this->assertEquals(1, count($foo->getSegmentWrappers()));

        $foo->popSegment(true);
        $this->assertEquals(0, count($foo->getSegmentNames()));
        $this->assertEquals(0, count($foo->getSegmentWrappers()));
    }

    public function testPushNonStringSegmentNameAndThrowException()
    {
        $wrap = m::mock(RequestDescription::class)->makePartial();
        $foo = new SegmentStack($wrap);

        $segName = new \StdClass();
        $setWrap = m::mock(ResourceSetWrapper::class);

        $expected = 'segmentName must be a string';
        $actual = null;
        try {
            $foo->pushSegment($segName, $setWrap);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
