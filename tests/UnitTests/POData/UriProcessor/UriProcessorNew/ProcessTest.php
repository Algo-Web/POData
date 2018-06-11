<?php

namespace UnitTests\POData\UriProcessor\UriProcessorNew;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\UriProcessor\UriProcessor;
use POData\UriProcessor\UriProcessorNew;
use TypeError;
use UnitTests\POData\TestCase;

class ProcessTest extends TestCase
{
    public function testCompareMismatchedBaseUrl()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/foodata.svc/$metadata');

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);

        $metaProv = m::mock(IMetadataProvider::class);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getMetadataProvider')->andReturn($metaProv);

        $expectedClass = null;
        $expected = null;

        $actualClass = null;
        $actual = null;

        try {
            UriProcessor::process($service);
        } catch (\Exception $e) {
            $expectedClass = get_class($e);
            $expected = $e->getMessage();
        }

        try {
            UriProcessorNew::process($service);
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual = $e->getMessage();
        }

        $this->assertEquals($expectedClass, $actualClass);
        $this->assertEquals($expected, $actual);
    }

    public function testCompareRetrieveServiceDoc()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc');

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('1.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturn(null);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $wrapper = m::mock(ProvidersWrapper::class);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpService($host, $wrapper, $context, $config);

        $original = UriProcessor::process($service);
        $remix = UriProcessorNew::process($service);

        $this->assertEquals($original->getProviders(), $remix->getProviders());
        $this->assertEquals($original->getService(), $remix->getService());
        $this->assertEquals($original->getRequest()->getRequestUrl(), $remix->getRequest()->getRequestUrl());
        $this->assertEquals($original->getRequest()->getResponseVersion(), $remix->getRequest()->getResponseVersion());
        $this->assertEquals($original->getRequest()->getData(), $remix->getRequest()->getData());
        $this->assertEquals($original->getRequest()->getContainerName(), $remix->getRequest()->getContainerName());
        $this->assertEquals($original->getRequest()->getLastSegment(), $remix->getRequest()->getLastSegment());
        $this->assertEquals($original->getRequest()->queryType, $remix->getRequest()->queryType);
        $this->assertEquals($original, $original->getRequest()->getUriProcessor());
        $this->assertEquals($remix, $remix->getRequest()->getUriProcessor());
        $this->assertEquals($original->getRequest(), $original->getExpander()->getRequest());
        $this->assertEquals($remix->getRequest(), $remix->getExpander()->getRequest());
        $this->assertEquals($original->getRequest(), $original->getExpander()->getStack()->getRequest());
        $this->assertEquals($remix->getRequest(), $remix->getExpander()->getStack()->getRequest());
    }

    public function testBadHttpVerbOnExecute()
    {
        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest->getMethod')->andReturn(HTTPRequestMethod::NONE());

        $metaProv = m::mock(IMetadataProvider::class);

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getMetadataProvider')->andReturn($metaProv);

        $remix = m::mock(UriProcessorNew::class)->makePartial();
        $remix->shouldReceive('getService')->andReturn($service);

        $expected = 'This release of library supports only GET (read) request, received a request with method NONE';
        $actual = null;

        try {
            $remix->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBadResourcePropertyKindOnRelatedResourceGet()
    {
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getKind')->andReturnNull();

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());
        $segment->shouldReceive('getTargetSource')->andReturn(TargetSource::PROPERTY);
        $segment->shouldReceive('getProjectedProperty')->andReturn($property);
        $segment->shouldReceive('isSingleResult')->andReturn(true);
        $segment->shouldReceive('getPrevious->getResult')->andReturn('abc');

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest->getMethod')->andReturn(HTTPRequestMethod::GET());

        $metaProv = m::mock(IMetadataProvider::class);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getSegments')->andReturn([$segment]);
        $request->shouldReceive('getRootProjectionNode')->andReturn(null);

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getMetadataProvider')->andReturn($metaProv);

        $remix = m::mock(UriProcessorNew::class)->makePartial();
        $remix->shouldReceive('getService')->andReturn($service);
        $remix->shouldReceive('getRequest')->andReturn($request);

        $expected = 'Invalid property kind type for resource retrieval';
        $actual = null;

        try {
            $remix->execute();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param $host
     * @param $wrapper
     * @param $context
     * @param $config
     * @return m\MockInterface
     */
    protected function setUpService($host, $wrapper, $context, $config)
    {
        $metaProv = m::mock(IMetadataProvider::class);
        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($metaProv);
        return $service;
    }
}
