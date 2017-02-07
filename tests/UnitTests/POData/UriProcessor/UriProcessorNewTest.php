<?php

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\Url;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IOperationContext;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessor;

class UriProcessorNewTest extends \PHPUnit_Framework_TestCase
{
    public function testTripExceptionInFactoryMethod()
    {
        $url1 = m::mock(Url::class);
        $url1->shouldReceive('isBaseOf')->andReturn(false)->never();
        $url1->shouldReceive('getUrlAsString')->andReturn('www.example.org');
        $url2 = m::mock(Url::class);
        $url2->shouldReceive('isBaseOf')->andReturn(false)->once();
        $url2->shouldReceive('getUrlAsString')->andReturn('www.example.net');

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteRequestUri')->andReturn($url1);
        $service->shouldReceive('getHost->getAbsoluteServiceUri')->andReturn($url2);

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
        $processor->shouldReceive('executePost')->andReturnNull()->once();
        $processor->shouldReceive('getService')->andReturn($service);

        $processor->execute();
    }

    public function testAddRequestGetter()
    {
        $processor = m::mock(UriProcessor::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('getRequest')->andReturn('request');

        $expected = 'request';
        $result = $processor->request;
        $this->assertEquals($expected, $result);
    }
}
