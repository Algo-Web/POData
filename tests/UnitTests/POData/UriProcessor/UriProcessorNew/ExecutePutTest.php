<?php

namespace UnitTests\POData\UriProcessor\UriProcessorNew;

use Mockery as m;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\UriProcessorNew;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\TestCase;

class ExecutePutTest extends TestCase
{
    public function testExecutePutOnResourceSet()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers');

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('1.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestContentType')->andReturn(ODataConstants::FORMAT_ATOM)->atLeast(1);

        $requestPayload = new ODataEntry();
        $requestPayload->type = new ODataCategory('Customer');
        $requestPayload->propertyContent = new ODataPropertyContent();

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT());
        $request->shouldReceive('getAllInput')->andReturn($requestPayload);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn([])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);

        $result = ['eins', 'zwei', 'polizei'];

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceSet')->andReturn($result);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $expected = 'The URI \'http://localhost/odata.svc/customers\' is not valid for PUT method.';
        $expectedClass = ODataException::class;
        $actual = null;
        $actualClass = null;

        try {
            $remix->execute();
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePutOnResourceSingleNoData()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(id=1)');

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('1.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestContentType')->andReturn(ODataConstants::FORMAT_ATOM)->atLeast(1);

        $requestPayload = new ODataEntry();
        $requestPayload->id = 'http://localhost/odata.svc/customers(id=1)';
        $requestPayload->type = new ODataCategory('Customer');
        $requestPayload->propertyContent = new ODataPropertyContent();

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT());
        $request->shouldReceive('getAllInput')->andReturn($requestPayload);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(1);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);
        $keyProp->shouldReceive('getName')->andReturn('id');

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getAllProperties')->andReturn(['id' => $keyProp])->atLeast(1);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->once();
        $wrapper->shouldReceive('updateResource')->withAnyArgs()->andReturnNull()->never();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $expected = 'Method PUT expecting some data, but received empty data.';
        $expectedClass = ODataException::class;
        $actual = null;
        $actualClass = null;

        try {
            $remix->execute();
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePutOnSingleWithData()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl = new Url('http://localhost/odata.svc/customers(CustomerID=42)');

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('1.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestContentType')->andReturn(ODataConstants::FORMAT_ATOM)->atLeast(2);

        $requestPayload = new ODataEntry();
        $requestPayload->id = 'http://localhost/odata.svc/customers(CustomerID=42)';
        $requestPayload->type = new ODataCategory('Customer');
        $requestPayload->propertyContent = new ODataPropertyContent();
        $requestPayload->propertyContent->properties['otherNumber'] = new ODataProperty();
        $requestPayload->propertyContent->properties['otherNumber']->value = 42;
        $requestPayload->propertyContent->properties['CustomerID'] = new ODataProperty();
        $requestPayload->propertyContent->properties['CustomerID']->value = 42;

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT());
        //$request->shouldReceive('getAllInput')->andReturn(['a', 'b', 'c']);
        $request->shouldReceive('getAllInput')->andReturn($requestPayload);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $primResource = m::mock(ResourcePrimitiveType::class);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);
        $iType->shouldReceive('convertToOData')->andReturn('42')->atLeast(2);

        $otherProp = m::mock(ResourceProperty::class);
        $otherProp->shouldReceive('getInstanceType')->andReturn($iType);
        $otherProp->shouldReceive('getResourceType')->andReturn($primResource);
        $otherProp->shouldReceive('getName')->andReturn('otherNumber');

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);
        $keyProp->shouldReceive('getName')->andReturn('CustomerID');

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['CustomerID' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getAllProperties')
            ->andReturn(['CustomerID' => $keyProp, 'otherNumber' => $otherProp])
            ->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);

        $result = new Customer2();

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
        $wrapper->shouldReceive('updateResource')->with($resourceSet, m::any(), m::any(), m::any())
            ->andReturn('zwei')->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpService($host, $wrapper, $context, $config);
        $service->getMetadataProvider()->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

        $remix = UriProcessorNew::process($service);

        $remix->execute();

        $origSegment = new SegmentDescriptor();
        $origSegment->setResult('zwei');
        $remixSegment = $remix->getRequest()->getLastSegment();
        $this->assertEquals($origSegment->getResult(), $remixSegment->getResult());
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
