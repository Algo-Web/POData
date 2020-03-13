<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor\UriProcessorNew;

use Mockery as m;
use POData\Common\ODataConstants;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\UriProcessorNew;
use UnitTests\POData\TestCase;

class ExecuteGetWithOptionsTest extends TestCase
{
    public function testExecuteGetOnResourceSetWithTopOptionSet()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers$top=1');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturn(null);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn([])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);

        $result = ['eins', 'zwei', 'polizei'];

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceSet')->andReturn($result);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $this->assertEquals(200, $remix->getRequest()->getTopCount());
        $this->assertEquals(null, $remix->getRequest()->getTopOptionCount());
    }

    public function testExecuteGetOnResourceSetWithSkipOptionSet()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers$skip=41');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturn(null);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn([])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);

        $result = ['eins', 'zwei', 'polizei'];

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceSet')->andReturn($result);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $this->assertEquals(null, $remix->getRequest()->getSkipCount());
    }

    /**
     * @param $reqUrl
     * @param $baseUrl
     * @return m\MockInterface
     */
    private function setUpMockHost($reqUrl, $baseUrl)
    {
        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('1.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        return $host;
    }

    /**
     * @param $host
     * @param $wrapper
     * @param $context
     * @param $config
     * @return m\MockInterface
     */
    private function setUpMockService($host, $wrapper, $context, $config)
    {
        $metaProv = m::mock(IMetadataProvider::class);
        $service  = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($metaProv);
        return $service;
    }
}
