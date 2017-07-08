<?php

namespace UnitTests\POData\UriProcessor\UriProcessorNew;

use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceFunctionType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\UriProcessor;
use POData\UriProcessor\UriProcessorNew;
use UnitTests\POData\TestCase;
use Mockery as m;

class ExecuteGetTest extends TestCase
{
    public function testExecuteGetOnSingleton()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/whoami');

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

        $singleSet = m::mock(ResourceSetWrapper::class);

        $singleType = m::mock(ResourceType::class);
        $singleType->shouldReceive('getName')->andReturn('Object');
        $singleType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);

        $singleResult = new \DateTime();
        $singleton = m::mock(ResourceFunctionType::class);
        $singleton->shouldReceive('getResourceType')->andReturn($singleType);
        $singleton->shouldReceive('get')->andReturn($singleResult);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn($singleton);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($singleSet);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);

        $original = UriProcessor::process($service);
        $remix = UriProcessorNew::process($service);

        $original->execute();
        $origSegments = $original->getRequest()->getSegments();
        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $this->assertEquals(1, count($origSegments));
        $this->assertEquals(1, count($remixSegments));
        $this->assertEquals($singleResult, $origSegments[0]->getResult());
        $this->assertEquals($singleResult, $remixSegments[0]->getResult());
    }

    public function testExecuteGetOnResourceSet()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers');

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

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
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

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);

        $original = UriProcessor::process($service);
        $remix = UriProcessorNew::process($service);

        $original->execute();
        $origSegments = $original->getRequest()->getSegments();
        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $this->assertEquals(1, count($origSegments));
        $this->assertEquals(1, count($remixSegments));
        $this->assertEquals($origSegments[0]->getResult(), $remixSegments[0]->getResult());
    }

    public function testExecuteGetOnResourceSingle()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(id=1)');

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

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->twice();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);

        $original = UriProcessor::process($service);
        $remix = UriProcessorNew::process($service);

        $original->execute();
        $origSegments = $original->getRequest()->getSegments();
        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $this->assertEquals(1, count($origSegments));
        $this->assertEquals(1, count($remixSegments));
        $this->assertEquals($origSegments[0]->getResult(), $remixSegments[0]->getResult());
    }
}
