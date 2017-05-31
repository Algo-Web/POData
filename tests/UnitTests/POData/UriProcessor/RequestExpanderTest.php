<?php

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\IService;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\RequestExpander;
use POData\UriProcessor\SegmentStack;
use UnitTests\POData\TestCase;

class RequestExpanderTest extends TestCase
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

    public function testHandleExpansionOfSingleNode()
    {
        $providers = m::mock(ProvidersWrapper::class);
        $providers->shouldReceive('getRelatedResourceReference')->andReturnNull()->once();

        $resource = m::mock(ResourceSet::class);

        $wrap = m::mock(ResourceSetWrapper::class);
        $wrap->shouldReceive('getResourceSet')->andReturn($resource);

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('pushSegment')->andReturn(true)->once();
        $stack->shouldReceive('popSegment')->andReturnNull()->once();
        $stack->shouldReceive('getSegmentWrappers')->andReturn([])->once();

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('getChildNodes')->andReturn([])->never();

        $resProperty = m::mock(ResourceProperty::class);
        $resProperty->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE);
        $resProperty->shouldReceive('getName')->andReturn('resourceProperty');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('setPropertyValue')->withAnyArgs()->andReturnNull()->once();

        $nuNode = m::mock(RootProjectionNode::class);
        $nuNode->shouldReceive('getChildNodes')->andReturn([])->never();
        $nuNode->shouldReceive('getResourceType')->andReturn($type);
        $nuNode->shouldReceive('getResourceProperty')->andReturn($resProperty);
        $nuNode->shouldReceive('getResourceSetWrapper')->andReturn($wrap);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node);
        $request->shouldReceive('getTargetResult')->andReturn('hammer');
        $request->shouldReceive('getContainerName')->andReturn('request');
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);

        $foo = m::mock(RequestExpander::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);
        $foo->shouldReceive('getExpandedProjectionNodes')->andReturn([$nuNode]);
        $foo->shouldReceive('getProviders')->andReturn($providers);

        $foo->handleExpansion();
    }

    public function testHandleExpansionOfSingleCollection()
    {
        $closure = function ($a, $b) {
            return 0;
        };

        $orderInfo = m::mock(InternalOrderByInfo::class);
        // just need a dummy function that doesn't sort
        $orderInfo->shouldReceive('getSorterFunction->getReference')->andReturn($closure);

        $queryResult = m::mock(QueryResult::class);

        $resource = m::mock(ResourceSet::class)->makePartial();
        $resource->results = $queryResult;

        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('setPropertyValue')->withAnyArgs()->andReturnNull()->once();

        $wrap = m::mock(ResourceSetWrapper::class);
        $wrap->shouldReceive('getRelatedResourceSet')->andReturn($resource);
        $wrap->shouldReceive('getResourceSet')->andReturn($resource);
        $wrap->shouldReceive('getResourceType')->andReturn($type);

        $providers = m::mock(ProvidersWrapper::class);
        $providers->shouldReceive('getRelatedResourceReference')->andReturnNull()->never();
        $providers->shouldReceive('getRelatedResourceSet')->andReturn($resource)->once();
        $providers->shouldReceive('getResourceSetWrapperForNavigationProperty')->andReturn($wrap)->once();

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('pushSegment')->andReturnNull()->times(2);
        $stack->shouldReceive('popSegment')->andReturnNull()->times(2);
        $stack->shouldReceive('getSegmentWrappers')->andReturn([])->times(2);
        $stack->shouldReceive('getSegmentNames')->andReturn(['time'])->once();

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('getChildNodes')->andReturn([])->never();

        $resProperty = m::mock(ResourceProperty::class);
        $resProperty->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCESET_REFERENCE);
        $resProperty->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resProperty->shouldReceive('getName')->andReturn('resourceProperty');

        $nuNode = m::mock(RootProjectionNode::class);
        $nuNode->shouldReceive('getChildNodes')->andReturn([])->never();
        $nuNode->shouldReceive('getResourceType')->andReturn($type);
        $nuNode->shouldReceive('getResourceProperty')->andReturn($resProperty);
        $nuNode->shouldReceive('getResourceSetWrapper')->andReturn($wrap);
        $nuNode->shouldReceive('getInternalOrderByInfo')->andReturn(null);
        $nuNode->shouldReceive('getTakeCount')->andReturnNull();

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node);
        $request->shouldReceive('getTargetResult')->andReturn([['hammer']]);
        $request->shouldReceive('getContainerName')->andReturn('request');
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);

        $service = m::mock(IService::class);
        $service->shouldReceive('getProvidersWrapper')->andReturn($providers);

        $foo = m::mock(RequestExpander::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);
        $foo->shouldReceive('getExpandedProjectionNodes')->andReturn([$nuNode], []);
        $foo->shouldReceive('getProviders')->andReturn($providers);
        $foo->shouldReceive('getService')->andReturn($service);

        $foo->handleExpansion();
    }

    public function testExpandCollectionWithNothingRelated()
    {
        $resource = m::mock(ResourceSet::class)->makePartial();
        $resource->results = null;

        $resProperty = m::mock(ResourceProperty::class);
        $resProperty->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCESET_REFERENCE);
        $resProperty->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resProperty->shouldReceive('getName')->andReturn('resourceProperty');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('setPropertyValue')->withAnyArgs()->andReturnNull()->once();

        $wrap = m::mock(ResourceSetWrapper::class);
        $wrap->shouldReceive('getResourceSet')->andReturn($resource);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('getChildNodes')->andReturn([$node])->once();
        $node->shouldReceive('findNode')->andReturn($node)->once();
        $node->shouldReceive('getResourceType')->andReturn($type);
        $node->shouldReceive('getResourceProperty')->andReturn($resProperty);
        $node->shouldReceive('getResourceSetWrapper')->andReturn($wrap);

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('pushSegment')->andReturnNull()->once();
        $stack->shouldReceive('popSegment')->andReturnNull()->once();
        $stack->shouldReceive('getSegmentWrappers')->andReturn([])->once();
        $stack->shouldReceive('getSegmentNames')->andReturn(['hammer', 'time'])->once();

        $providers = m::mock(ProvidersWrapper::class);
        $providers->shouldReceive('getRelatedResourceSet')->andReturn($resource)->once();

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node);
        $request->shouldReceive('getContainerName')->andReturn('request');
        $request->shouldReceive('getTargetResult')->andReturn([['hammer']]);
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);

        $foo = m::mock(RequestExpander::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);
        $foo->shouldReceive('getProviders')->andReturn($providers);

        $foo->handleExpansion();
    }

    public function testExpandCollectionWithSomethingRelatedNullNavigationWrapperTripAssertion()
    {
        $closure = function ($a, $b) {
            return 0;
        };

        $resource = m::mock(ResourceSet::class)->makePartial();
        $resource->results = ['foo', 'bar'];

        $resProperty = m::mock(ResourceProperty::class);
        $resProperty->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCESET_REFERENCE);
        $resProperty->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resProperty->shouldReceive('getName')->andReturn('resourceProperty');

        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('setPropertyValue')->withAnyArgs()->andReturnNull()->once();

        $wrap = m::mock(ResourceSetWrapper::class);
        $wrap->shouldReceive('getResourceSet')->andReturn($resource);
        $wrap->shouldReceive('getResourceType')->andReturn($type);

        $info = m::mock(InternalOrderByInfo::class);
        $info->shouldReceive('getSorterFunction')->andReturn($closure);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('getChildNodes')->andReturn([$node])->once();
        $node->shouldReceive('findNode')->andReturn($node)->once();
        $node->shouldReceive('getResourceType')->andReturn($type);
        $node->shouldReceive('getResourceProperty')->andReturn($resProperty);
        $node->shouldReceive('getResourceSetWrapper')->andReturn($wrap);
        $node->shouldReceive('getInternalOrderByInfo')->andReturn($info);
        $node->shouldReceive('getTakeCount')->andReturn(2)->once();

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('pushSegment')->andReturnNull()->once();
        $stack->shouldReceive('popSegment')->andReturnNull()->never();
        $stack->shouldReceive('getSegmentWrappers')->andReturn([])->twice();
        $stack->shouldReceive('getSegmentNames')->andReturn(['hammer', 'time'])->twice();

        $providers = m::mock(ProvidersWrapper::class);
        $providers->shouldReceive('getRelatedResourceSet')->andReturn($resource)->once();
        $providers->shouldReceive('getResourceSetWrapperForNavigationProperty')->andReturn(null)->once();

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node);
        $request->shouldReceive('getContainerName')->andReturn('request');
        $request->shouldReceive('getTargetResult')->andReturn([['hammer']]);
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);

        $service = m::mock(IService::class);
        $service->shouldReceive('getProvidersWrapper')->andReturn($providers);

        $foo = m::mock(RequestExpander::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);
        $foo->shouldReceive('getProviders')->andReturn($providers);
        $foo->shouldReceive('getService')->andReturn($service);

        $expected = 'assert(): !null($currentResourceSetWrapper) failed';
        $actual = null;

        try {
            $foo->handleExpansion();
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testExpandSingletonAndThrowExceptionOnNavigation()
    {
        $queryResult = m::mock(QueryResult::class);

        $resource = m::mock(ResourceSet::class)->makePartial();
        $resource->results = $queryResult;

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('setPropertyValue')->withAnyArgs()->andReturnNull()->once();

        $resProperty = m::mock(ResourceProperty::class);
        $resProperty->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCESET_REFERENCE);
        $resProperty->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::COMPLEX);
        $resProperty->shouldReceive('getName')->andReturn('resourceProperty');

        $wrap = m::mock(ResourceSetWrapper::class);
        $wrap->shouldReceive('getResourceSet')->andReturn($resource);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('getChildNodes')->andReturn([$node])->once();
        $node->shouldReceive('getResourceType')->andReturn($type);
        $node->shouldReceive('getResourceType')->andReturn($type);
        $node->shouldReceive('getResourceProperty')->andReturn($resProperty);
        $node->shouldReceive('getResourceSetWrapper')->andReturn($wrap);
        $node->shouldReceive('getInternalOrderByInfo')->andReturn(null);

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('pushSegment')->andReturn(true)->once();
        $stack->shouldReceive('popSegment')->andReturnNull()->never();
        $stack->shouldReceive('getSegmentWrappers')->andReturn([])->once();
        $stack->shouldReceive('getSegmentNames')->andReturn([])->once();

        $providers = m::mock(ProvidersWrapper::class);
        $providers->shouldReceive('getRelatedResourceSet')->andReturn($resource)->once();

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node);
        $request->shouldReceive('getTargetResult')->andReturn('hammer');
        $request->shouldReceive('getContainerName')->andReturn('request');
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);

        $foo = m::mock(RequestExpander::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);
        $foo->shouldReceive('getProviders')->andReturn($providers);

        $expected = 'pushSegmentForNavigationProperty should not be called with non-entity type';
        $actual = null;

        try {
            $foo->handleExpansion();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
