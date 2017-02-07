<?php

namespace UnitTests\POData\UriProcessor;

use POData\IService;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\RequestExpander;

use Mockery as m;
use POData\UriProcessor\SegmentStack;

class RequestExpanderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorTest()
    {
        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getContainerName')->andReturn('request');
        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('getContainerName')->andReturn('wrapper');
        $service = m::mock(IService::class);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);

        $foo = new RequestExpander($request, $service, $wrapper);
        $this->assertEquals(0, count($foo->getStack()->getSegmentWrappers()));
        $this->assertEquals('request', $foo->getRequest()->getContainerName());
        $this->assertEquals('wrapper', $foo->getProviders()->getContainerName());
        $this->assertEquals('wrapper', $foo->getService()->getProvidersWrapper()->getContainerName());
    }

    public function testHandleExpansionNullResult()
    {
        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('pushSegment')->andReturnNull()->never();
        $stack->shouldReceive('popSegment')->andReturnNull()->never();

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node);
        $request->shouldReceive('getTargetResult')->andReturn(null);

        $foo = m::mock(RequestExpander::class)->makePartial();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);

        $foo->handleExpansion();
    }

    public function testHandleExpansionEmptyResult()
    {
        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('pushSegment')->andReturnNull()->never();
        $stack->shouldReceive('popSegment')->andReturnNull()->never();

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node);
        $request->shouldReceive('getTargetResult')->andReturn([]);

        $foo = m::mock(RequestExpander::class)->makePartial();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);

        $foo->handleExpansion();
    }

    public function testHandleExpansionHasResult()
    {
        $wrap = m::mock(ResourceSetWrapper::class);

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('pushSegment')->andReturnNull()->once();
        $stack->shouldReceive('popSegment')->andReturnNull()->once();
        $stack->shouldReceive('getSegmentNames')->andReturn([])->once();

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('getChildNodes')->andReturn([])->once();

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node);
        $request->shouldReceive('getTargetResult')->andReturn(['hammer']);
        $request->shouldReceive('getContainerName')->andReturn('request');
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);

        $foo = m::mock(RequestExpander::class)->makePartial();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);

        $foo->handleExpansion();
    }
}
