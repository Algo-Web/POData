<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor\UriProcessorNew;

use Mockery as m;
use POData\Common\HttpStatus;
use POData\Common\InvalidOperationException;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\ObjectModel\ModelDeserialiser;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataURL;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryType;
use POData\Readers\Atom\AtomODataReader;
use POData\Readers\ODataReaderRegistry;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessorNew;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\TestCase;
use UnitTests\POData\UriProcessor\UriProcessorDummy;

class ExecutePostTest extends TestCase
{
    public function testExecutePostOnSingleWithNoData()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)');

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('1.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestContentType')->andReturn(ODataConstants::FORMAT_ATOM)->atLeast(2);

        $requestPayload                  = new ODataEntry();
        $requestPayload->type            = new ODataCategory('Customer');
        $requestPayload->propertyContent = new ODataPropertyContent();

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::POST());
        $request->shouldReceive('getAllInput')->andReturn($requestPayload);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceEntityType::class)->makePartial();
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
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
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->never();
        $wrapper->shouldReceive('createResourceforResourceSet')->withAnyArgs()->andReturnNull()->never();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $expected      = 'Method POST expecting some data, but received empty data.';
        $expectedClass = ODataException::class;
        $actual        = null;
        $actualClass   = null;

        try {
            $remix->execute();
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual      = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePostOnSingleWithSomeData()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(CustomerID=42)');

        $expectedServiceLocation = 'http://localhost/odata.svc/Orders(CustomerID=42)';

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('1.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestContentType')->andReturn(ODataConstants::FORMAT_ATOM)->atLeast(1);
        $host->shouldReceive('setResponseStatusCode')->withArgs([HttpStatus::CODE_CREATED])->once();
        $host->shouldReceive('setResponseLocation')->withArgs([$expectedServiceLocation])->once();

        $requestPayload                                                    = new ODataEntry();
        $requestPayload->type                                              = new ODataCategory('Customer');
        $requestPayload->propertyContent                                   = new ODataPropertyContent();
        $requestPayload->propertyContent->properties['otherNumber']        = new ODataProperty();
        $requestPayload->propertyContent->properties['otherNumber']->value = 42;

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::POST());
        $request->shouldReceive('getAllInput')->andReturn($requestPayload);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(1);
        $iType->shouldReceive('convertToOData')->andReturn('42')->atLeast(1);

        $primResource = m::mock(ResourcePrimitiveType::class);

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
        $resourceType->shouldReceive('getAllProperties')
            ->andReturn(['CustomerID' => $keyProp, 'otherNumber' => $otherProp])
            ->atLeast(1);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['CustomerID' => $keyProp])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);

        $result = new Customer2();

        $rawSet = m::mock(ResourceSet::class);
        $rawSet->shouldReceive('getName')->andReturn('Orders');
        $rawSet->shouldReceive('getResourceType')->andReturn($resourceType);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);
        $resourceSet->shouldReceive('getResourceSet')->andReturn($rawSet);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->never();
        $wrapper->shouldReceive('createResourceforResourceSet')->with($resourceSet, m::any(), m::any())
            ->andReturn($result)->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpService($host, $wrapper, $context, $config);
        $service->getMetadataProvider()->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

        $remix = UriProcessorNew::process($service);

        $remix->execute();

        $origSegment = new SegmentDescriptor();
        $origSegment->setResult($result);
        $remixSegment = $remix->getRequest()->getLastSegment();
        $this->assertEquals($origSegment->getResult(), $remixSegment->getResult());
    }

    public function testExecutePostWithBadPayload()
    {
        $resourceSet = m::mock(ResourceSetWrapper::class)->makePartial();

        $segment = m::mock(SegmentDescriptor::class)->makePartial();
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE())->once();
        $segment->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet)->once();

        $payload = new ODataFeed();

        $remix = m::mock(UriProcessorDummy::class)->makePartial();
        $remix->shouldReceive('getRequest->getSegments')->andReturn([$segment]);
        $remix->shouldReceive('getService->getOperationContext->incomingRequest->getMethod')
            ->andReturn(HTTPRequestMethod::POST());
        $remix->shouldReceive('getRequest->getData')->andReturn($payload)->once();

        $expected = 'POData\\ObjectModel\\ODataFeed';
        $actual   = null;

        try {
            $result = $remix->executePost();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePostWithBadID()
    {
        $resourceSet = m::mock(ResourceSetWrapper::class)->makePartial();

        $segment = m::mock(SegmentDescriptor::class)->makePartial();
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE())->once();
        $segment->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet)->once();

        $payload     = new ODataEntry();
        $payload->id = '1';

        $remix = m::mock(UriProcessorDummy::class)->makePartial();
        $remix->shouldReceive('getRequest->getSegments')->andReturn([$segment]);
        $remix->shouldReceive('getService->getOperationContext->incomingRequest->getMethod')
            ->andReturn(HTTPRequestMethod::POST());
        $remix->shouldReceive('getRequest->getData')->andReturn($payload)->once();

        $expected = 'Payload ID must be empty for POST request';
        $actual   = null;

        try {
            $result = $remix->executePost();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePostWithBadHookup()
    {
        $reqUrl      = new Url('http://localhost/odata.svc/customers(CustomerID=42)');
        $resourceSet = m::mock(ResourceSetWrapper::class)->makePartial();

        $segment = m::mock(SegmentDescriptor::class)->makePartial();
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE())->once();
        $segment->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet)->twice();

        $payload      = new ODataURL();
        $payload->url = 'http://localhost/odata.svc/customers(CustomerID=42)/orders';

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($reqUrl);

        $set = m::mock(ResourceSetWrapper::class)->makePartial();
        $set->shouldReceive('getResourceType')->andReturnNull()->once();
        $set->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->once();

        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();
        $wrapper->shouldReceive('resolveSingleton')->andReturnNull()->once();
        $wrapper->shouldReceive('resolveResourceType')->andReturnNull()->never();
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($set)->once();
        $wrapper->shouldReceive('getResourceSet')->andReturn($set)->once();
        $wrapper->shouldReceive('hookSingleModel')->andReturn(false)->once();

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getSegments')->andReturn([$segment]);
        $request->shouldReceive('getData')->andReturn($payload)->once();
        $request->shouldReceive('getLastSegment->getIdentifier')->andReturn('nav')->once();
        $request->queryType = QueryType::ENTITIES();

        $remix = m::mock(UriProcessorDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $remix->shouldReceive('getRequest')->andReturn($request);
        $remix->shouldReceive('getService->getOperationContext->incomingRequest->getMethod')
            ->andReturn(HTTPRequestMethod::POST());
        $remix->shouldReceive('executeGet')->andReturnNull()->once();
        $remix->shouldReceive('getService->getHost')->andReturn($host)->once();
        $remix->shouldReceive('getService->getProvidersWrapper')->andReturn($wrapper)->once();
        $remix->shouldReceive('getProviders')->andReturn($wrapper)->twice();

        $expected = 'AdapterIndicatedLinkNotAttached';
        $actual   = null;

        try {
            $remix->executePost();
        } catch (ODataException $e) {
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
        $service  = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($metaProv);
        $readerRegistery = new ODataReaderRegistry();
        $readerRegistery->register(new AtomODataReader());
        $service->shouldReceive('getODataReaderRegistry')->andReturn($readerRegistery);
        return $service;
    }
}
