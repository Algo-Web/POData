<?php

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\RequestExpander;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessor;
use UnitTests\POData\TestCase;

class UriProcessorNewTest extends TestCase
{
    public function testTripExceptionInFactoryMethod()
    {
        $url1 = m::mock(Url::class);
        $url1->shouldReceive('isBaseOf')->andReturn(false)->never();
        $url1->shouldReceive('getUrlAsString')->andReturn('www.example.org');
        $url2 = m::mock(Url::class);
        $url2->shouldReceive('isBaseOf')->andReturn(false)->once();
        $url2->shouldReceive('getUrlAsString')->andReturn('www.example.net');

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url1);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($url2);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        $expected = 'The URI \'www.example.org\' is not valid since it is not based on \'www.example.net\'';
        $actual = null;

        try {
            $result = UriProcessor::process($service);
        } catch (\POData\Common\ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePostAndTripBadRequestMethodException()
    {
        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());

        $url1 = m::mock(Url::class);
        $url1->shouldReceive('isBaseOf')->andReturn(true);
        $url1->shouldReceive('getUrlAsString')->andReturn('www.example.org');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::POST());
        $request->shouldReceive('getSegments')->andReturn([$seg1]);

        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteRequestUri')->andReturn($url1);
        $service->shouldReceive('getHost->getAbsoluteServiceUri')->andReturn($url1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePost')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);

        $expected = 'The URI \'www.example.org\' is not valid for POST method.';
        $actual = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePostAndTripNoDataException()
    {
        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());
        $seg1->shouldReceive('getTargetResourceSetWrapper')->andReturn('resourceSet');

        $url1 = m::mock(Url::class);
        $url1->shouldReceive('isBaseOf')->andReturn(true);
        $url1->shouldReceive('getUrlAsString')->andReturn('www.example.org');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::POST());
        $request->shouldReceive('getSegments')->andReturn([$seg1]);

        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteRequestUri')->andReturn($url1);
        $service->shouldReceive('getHost->getAbsoluteServiceUri')->andReturn($url1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePost')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);

        $expected = 'Method POST expecting some data, but received empty data.';
        $actual = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePostSuccessfully()
    {
        $resourceWrapper = m::mock(ResourceSetWrapper::class)->makePartial();

        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());
        $seg1->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceWrapper);
        $seg1->shouldReceive('setResult')->andReturnNull()->once();

        $url1 = m::mock(Url::class);
        $url1->shouldReceive('isBaseOf')->andReturn(true);
        $url1->shouldReceive('getUrlAsString')->andReturn('www.example.org');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::POST());
        $request->shouldReceive('getSegments')->andReturn([$seg1]);
        $request->shouldReceive('getData')->andReturn('data');

        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();
        $wrapper->shouldReceive('createResourceforResourceSet')->withAnyArgs()->andReturnNull()->once();

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteRequestUri')->andReturn($url1);
        $service->shouldReceive('getHost->getAbsoluteServiceUri')->andReturn($url1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePost')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);
        //$processor->shouldReceive('getProviders->createResourceForResourceSet')->withAnyArgs()->andReturnNull()->once();

        $processor->execute();
    }

    public function testGetResourceNotFound()
    {
        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());
        $seg1->shouldReceive('getPrevious->getResult')->andReturnNull();
        $seg1->shouldReceive('getPrevious->getIdentifier')->andReturn('Identifier');

        $url1 = m::mock(Url::class);
        $url1->shouldReceive('isBaseOf')->andReturn(true);
        $url1->shouldReceive('getUrlAsString')->andReturn('www.example.org');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getSegments')->andReturn([$seg1]);

        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteRequestUri')->andReturn($url1);
        $service->shouldReceive('getHost->getAbsoluteServiceUri')->andReturn($url1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executeGet')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);

        $expected = 'Resource not found for the segment \'Identifier\'.';
        $actual = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetMediaResourceNotFound()
    {
        $seg0 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg0->shouldReceive('getResult')->andReturn(null);
        $seg0->shouldReceive('getIdentifier')->andReturn('Identifier');

        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $seg1->shouldReceive('getPrevious')->andReturn($seg0);

        $url1 = m::mock(Url::class);
        $url1->shouldReceive('isBaseOf')->andReturn(true);
        $url1->shouldReceive('getUrlAsString')->andReturn('www.example.org');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getSegments')->andReturn([$seg1]);

        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteRequestUri')->andReturn($url1);
        $service->shouldReceive('getHost->getAbsoluteServiceUri')->andReturn($url1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executeGet')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);

        $expected = 'Resource not found for the segment \'Identifier\'.';
        $actual = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceSuccessful()
    {
        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();
        $wrapper->shouldReceive('getRelatedResourceReference')->andReturnNull()->once();

        $resourceSet = m::mock(ResourceSet::class);
        $resourceWrapper = m::mock(ResourceSetWrapper::class);

        $propKind = ResourcePropertyKind::RESOURCE_REFERENCE;

        $property = m::mock(ResourceProperty::class)->makePartial();
        $property->shouldReceive('getKind')->andReturn($propKind);

        $seg0 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg0->shouldReceive('getResult')->andReturn('result');
        $seg0->shouldReceive('getIdentifier')->andReturn('Identifier');
        $seg0->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet);

        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());
        $seg1->shouldReceive('getPrevious')->andReturn($seg0);
        $seg1->shouldReceive('getProjectedProperty')->andReturn($property);
        $seg1->shouldReceive('setResult')->andReturnNull()->once();
        $seg1->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet);

        $url1 = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $projNode = m::mock(RootProjectionNode::class);
        $projNode->shouldReceive('isExpansionSpecified')->andReturn(true);

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRequestUrl')->andReturn($url1);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getSegments')->andReturn([$seg1]);
        $request->shouldReceive('getTargetResult')->andReturn(null);
        $request->shouldReceive('getRootProjectionNode')->andReturn($projNode);

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteRequestUri')->andReturn($url1);
        $service->shouldReceive('getHost->getAbsoluteServiceUri')->andReturn($url1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $expander = m::mock(RequestExpander::class);
        $expander->shouldReceive('handleExpansion')->andReturnNull()->once();

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executeGet')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);
        $processor->shouldReceive('getExpander')->andReturn($expander);

        $processor->execute();
    }

    public function testExecuteBadMethodChoice()
    {
        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest->getMethod')->andReturn(HTTPRequestMethod::MERGE())->once();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('getService')->andReturn($service);

        $expected = 'This release of library supports only GET (read) request, received a request with method MERGE';
        $actual = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePutBadRequestInvalidUriException()
    {
        $url1 = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $resourceSet = m::mock(ResourceSet::class);

        $propKind = ResourcePropertyKind::RESOURCE_REFERENCE;

        $property = m::mock(ResourceProperty::class)->makePartial();
        $property->shouldReceive('getKind')->andReturn($propKind);

        $seg0 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg0->shouldReceive('getResult')->andReturn('MC');

        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::LINK());
        $seg1->shouldReceive('getPrevious')->andReturn($seg0);

        $seg1->shouldReceive('getProjectedProperty')->andReturn($property);
        $seg1->shouldReceive('setResult')->andReturnNull()->once();
        $seg1->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet);

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest->getMethod')->andReturn(HTTPRequestMethod::PUT())->twice();

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRequestUrl')->andReturn($url1);
        $request->shouldReceive('getSegments')->andReturn([$seg1]);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url1);

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getHost')->andReturn($host);

        $expander = m::mock(RequestExpander::class);
        $expander->shouldReceive('handleExpansion')->andReturnNull()->never();

        $wrapper = m::mock(ProvidersWrapper::class);

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePut')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);
        $processor->shouldReceive('getExpander')->andReturn($expander);

        $expected = 'The URI \'http://192.168.2.1/abm-master/public/odata.svc/Entity(1)\' is not valid for PUT method.';
        $actual = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePutBadRequestNoDataException()
    {
        $url1 = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $resourceSet = m::mock(ResourceSet::class);
        $resourceSetWrapper = m::mock(ResourceSetWrapper::class);
        $keyDescript = m::mock(KeyDescriptor::class);

        $propKind = ResourcePropertyKind::RESOURCE_REFERENCE;

        $property = m::mock(ResourceProperty::class)->makePartial();
        $property->shouldReceive('getKind')->andReturn($propKind);

        $seg0 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg0->shouldReceive('getResult')->andReturn('MC');

        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::LINK());
        $seg1->shouldReceive('getPrevious')->andReturn($seg0);
        $seg1->shouldReceive('getKeyDescriptor')->andReturn($keyDescript);

        $seg1->shouldReceive('getProjectedProperty')->andReturn($property);
        $seg1->shouldReceive('setResult')->andReturnNull()->once();
        $seg1->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet);

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest->getMethod')->andReturn(HTTPRequestMethod::PUT())->twice();

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRequestUrl')->andReturn($url1);
        $request->shouldReceive('getSegments')->andReturn([$seg1]);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url1);

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getHost')->andReturn($host);

        $expander = m::mock(RequestExpander::class);
        $expander->shouldReceive('handleExpansion')->andReturnNull()->never();

        $wrapper = m::mock(ProvidersWrapper::class);

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePut')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);
        $processor->shouldReceive('getExpander')->andReturn($expander);

        $expected = 'Method PUT expecting some data, but received empty data.';
        $actual = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePutRequestGoodData()
    {
        $url1 = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $resourceSet = m::mock(ResourceSet::class);
        $resourceSetWrapper = m::mock(ResourceSetWrapper::class);
        $keyDescript = m::mock(KeyDescriptor::class);

        $propKind = ResourcePropertyKind::RESOURCE_REFERENCE;

        $property = m::mock(ResourceProperty::class)->makePartial();
        $property->shouldReceive('getKind')->andReturn($propKind);

        $seg0 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg0->shouldReceive('getResult')->andReturn('MC');

        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::LINK());
        $seg1->shouldReceive('getPrevious')->andReturn($seg0);
        $seg1->shouldReceive('getKeyDescriptor')->andReturn($keyDescript);

        $seg1->shouldReceive('getProjectedProperty')->andReturn($property);
        $seg1->shouldReceive('setResult')->andReturnNull()->twice();
        $seg1->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet);

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest->getMethod')->andReturn(HTTPRequestMethod::PUT())->twice();

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRequestUrl')->andReturn($url1);
        $request->shouldReceive('getSegments')->andReturn([$seg1]);
        $request->shouldReceive('getData')->andReturn(['stop!', 'hammer', 'time!']);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url1);

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getHost')->andReturn($host);

        $expander = m::mock(RequestExpander::class);
        $expander->shouldReceive('handleExpansion')->andReturnNull()->once();

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('updateResource')->andReturnNull()->once();

        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePut')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);
        $processor->shouldReceive('getExpander')->andReturn($expander);

        $processor->execute();
    }
}
