<?php

namespace UnitTests\POData\UriProcessor\UriProcessorNew;

use POData\Common\ODataConstants;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceComplexType;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceFunctionType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\UriProcessor;
use POData\UriProcessor\UriProcessorNew;
use UnitTests\POData\TestCase;
use Mockery as m;

class ExecuteGetTest extends TestCase
{
    public function testExecuteBadMethod()
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
        $request->shouldReceive('getMethod')->andReturn(null);
        $request->shouldReceive('getAllInput')->andReturn(null);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $wrapper = m::mock(ProvidersWrapper::class);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);

        $expected = null;
        $expectedClass = null;
        $actual = null;
        $actualClass = null;

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

    public function testGetOnResourceSingleWithExpansion()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(id=1)?expand=orders');

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

        $rPropType = new Int32();

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType')->andReturn($rPropType);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($rProp)->atLeast(2);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $result = ['eins'];

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->withArgs(['customers'])->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->times(2);

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
        $this->assertEquals($origSegments[0]->isSingleResult(), $remixSegments[0]->isSingleResult());
        $this->assertEquals($origSegments[0]->getNext(), $remixSegments[0]->getNext());
        $this->assertEquals($origSegments[0]->getPrevious(), $remixSegments[0]->getPrevious());
    }

    public function testExecuteGetOnMediaResourceBadRequestVersion()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(id=1)/photo');

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

        $rPropType = new Int32();

        $photoStream = m::mock(ResourceStreamInfo::class);

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType')->andReturn($rPropType);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($rProp)->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['photo'])->andReturn(null);
        $resourceType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['photo'])->andReturn($photoStream);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(true);

        $result = ['eins'];

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->times(0);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);

        $expected = null;
        $expectedClass = null;
        $actual = null;
        $actualClass = null;

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

    public function testExecuteGetOnMediaResourceGoodRequestVersion()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(id=1)/photo');

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('3.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturn(null);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $rPropType = new Int32();

        $photoStream = m::mock(ResourceStreamInfo::class);

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType')->andReturn($rPropType);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($rProp)->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['photo'])->andReturn(null);
        $resourceType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['photo'])->andReturn($photoStream);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(true);

        $result = ['eins'];

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->times(2);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

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

        $this->assertEquals(2, count($origSegments));
        $this->assertEquals(2, count($remixSegments));
        for ($i = 0; $i < 2; $i++) {
            $this->assertEquals($origSegments[$i]->getResult(), $remixSegments[$i]->getResult());
            $this->assertEquals($origSegments[$i]->isSingleResult(), $remixSegments[$i]->isSingleResult());
            $this->assertEquals($origSegments[$i]->getNext(), $remixSegments[$i]->getNext());
            $this->assertEquals($origSegments[$i]->getPrevious(), $remixSegments[$i]->getPrevious());
        }
    }

    public function testExecuteGetOnFirstSegmentLink()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/'.ODataConstants::URI_COUNT_SEGMENT);

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

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $wrapper = m::mock(ProvidersWrapper::class);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);

        $expected = null;
        $expectedClass = null;
        $actual = null;
        $actualClass = null;

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

    public function testExecuteGetOnLinkAfterSingleResource()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(id=1)/'.ODataConstants::URI_COUNT_SEGMENT);

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

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->andReturn(null)->atLeast(2);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);

        $expected = null;
        $expectedClass = null;
        $actual = null;
        $actualClass = null;

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

    public function testExecuteGetOnLinkAfterResourceSet()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers/'.ODataConstants::URI_COUNT_SEGMENT);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('2.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturn(null);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $config->shouldReceive('getAcceptCountRequests')->andReturn(true)->atLeast(2);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->andReturn(null)->atLeast(2);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);

        $result = ['eins', 'zwei', 'polizei'];

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceSet')->andReturn($result)->atLeast(2);

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

        $this->assertEquals(2, count($origSegments));
        $this->assertEquals(2, count($remixSegments));
        for ($i = 0; $i < 2; $i++) {
            $this->assertEquals($origSegments[$i]->getResult(), $remixSegments[$i]->getResult());
            $this->assertEquals($origSegments[$i]->isSingleResult(), $remixSegments[$i]->isSingleResult());
            $this->assertEquals($origSegments[$i]->getNext(), $remixSegments[$i]->getNext());
            $this->assertEquals($origSegments[$i]->getPrevious(), $remixSegments[$i]->getPrevious());
        }
    }

    public function testExecuteGetOnComplexType()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(id=1)/address');

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

        $complexType = m::mock(ResourceComplexType::class);

        $complexProp = m::mock(ResourceProperty::class);
        $complexProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE)->atLeast(2);
        $complexProp->shouldReceive('getResourceType')->andReturn($complexType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['address'])->andReturn($complexProp)->atLeast(2);

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
        $this->assertEquals(2, count($origSegments));
        $this->assertEquals(2, count($remixSegments));
        for ($i = 0; $i < 2; $i++) {
            $this->assertEquals($origSegments[$i]->getResult(), $remixSegments[$i]->getResult());
            $this->assertEquals($origSegments[$i]->isSingleResult(), $remixSegments[$i]->isSingleResult());
            $this->assertEquals($origSegments[$i]->getNext(), $remixSegments[$i]->getNext());
            $this->assertEquals($origSegments[$i]->getPrevious(), $remixSegments[$i]->getPrevious());
        }
    }

    public function testExecuteGetOnBagOfPrimitivesType()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(id=1)/addresses');

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

        $bagType = m::mock(ResourceComplexType::class);

        $bagProp = m::mock(ResourceProperty::class);
        $bagProp->shouldReceive('getKind')
            ->andReturn(ResourcePropertyKind::BAG | ResourcePropertyKind::PRIMITIVE)->atLeast(2);
        $bagProp->shouldReceive('getResourceType')->andReturn($bagType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['addresses'])->andReturn($bagProp)->atLeast(2);

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(true);
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
        $this->assertEquals(2, count($origSegments));
        $this->assertEquals(2, count($remixSegments));
        for ($i = 0; $i < 2; $i++) {
            $this->assertEquals($origSegments[$i]->getResult(), $remixSegments[$i]->getResult());
            $this->assertEquals($origSegments[$i]->isSingleResult(), $remixSegments[$i]->isSingleResult());
            $this->assertEquals($origSegments[$i]->getNext(), $remixSegments[$i]->getNext());
            $this->assertEquals($origSegments[$i]->getPrevious(), $remixSegments[$i]->getPrevious());
        }
    }

    public function testExecuteGetOnBagOfComplexType()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(id=1)/addresses');

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

        $bagType = m::mock(ResourceComplexType::class);

        $bagProp = m::mock(ResourceProperty::class);
        $bagProp->shouldReceive('getKind')
            ->andReturn(ResourcePropertyKind::BAG | ResourcePropertyKind::COMPLEX_TYPE)->atLeast(2);
        $bagProp->shouldReceive('getResourceType')->andReturn($bagType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['addresses'])->andReturn($bagProp)->atLeast(2);

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(true);
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
        $this->assertEquals(2, count($origSegments));
        $this->assertEquals(2, count($remixSegments));
        for ($i = 0; $i < 2; $i++) {
            $this->assertEquals($origSegments[$i]->getResult(), $remixSegments[$i]->getResult());
            $this->assertEquals($origSegments[$i]->isSingleResult(), $remixSegments[$i]->isSingleResult());
            $this->assertEquals($origSegments[$i]->getNext(), $remixSegments[$i]->getNext());
            $this->assertEquals($origSegments[$i]->getPrevious(), $remixSegments[$i]->getPrevious());
        }
    }

    public function testExecuteGetOnBatchFirstSegment()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/'.ODataConstants::URI_BATCH_SEGMENT);

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
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);

        $expected = null;
        $expectedClass = null;
        $actual = null;
        $actualClass = null;

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
}