<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\HttpStatus;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\IService;
use POData\ObjectModel\CynicDeserialiser;
use POData\ObjectModel\ModelDeserialiser;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataPropertyContent;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\InternalSkipTokenInfo;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\RequestExpander;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\UriProcessor\UriProcessor;
use POData\UriProcessor\UriProcessorNew;
use UnitTests\POData\Facets\NorthWind1\Customer2;
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
        $actual   = null;

        try {
            $result = UriProcessorNew::process($service);
        } catch (\POData\Common\ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
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

        $processor = m::mock(UriProcessorNew::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePost')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);

        $expected = 'The URI \'www.example.org\' is not valid for POST method.';
        $actual   = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePostAndTripNoDataException()
    {
        $resourceType = m::mock(ResourceEntityType::class);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);

        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());
        $seg1->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet);

        $url1 = m::mock(Url::class);
        $url1->shouldReceive('isBaseOf')->andReturn(true);
        $url1->shouldReceive('getUrlAsString')->andReturn('www.example.org');

        $requestPayload                  = new ODataEntry();
        $requestPayload->type            = new ODataCategory('Customer');
        $requestPayload->propertyContent = new ODataPropertyContent();

        $cereal = m::mock(ModelDeserialiser::class);
        $cereal->shouldReceive('bulkDeserialise')->andReturn(null)->once();

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::POST());
        $request->shouldReceive('getSegments')->andReturn([$seg1]);
        $request->shouldReceive('getData')->andReturn($requestPayload);

        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteRequestUri')->andReturn($url1);
        $service->shouldReceive('getHost->getAbsoluteServiceUri')->andReturn($url1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $processor = m::mock(UriProcessorNew::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePost')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getModelDeserialiser')->andReturn($cereal);

        $expected = 'Method POST expecting some data, but received empty data.';
        $actual   = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceNotFound()
    {
        $prev = m::mock(SegmentDescriptor::class)->makePartial();
        $prev->shouldReceive('getIdentifier')->andReturn('Identifier');
        $prev->shouldReceive('getResult')->andReturn(null);

        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());
        $seg1->shouldReceive('getPrevious')->andReturn($prev);

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

        $processor = m::mock(UriProcessorNew::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executeGet')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);

        $expected = 'Resource not found for the segment \'Identifier\'.';
        $actual   = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
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

        $expander = m::mock(RequestExpander::class);
        $expander->shouldReceive('handleExpansion')->andReturnNull()->never();

        $context = m::mock(IOperationContext::class)->makePartial();
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteRequestUri')->andReturn($url1);
        $service->shouldReceive('getHost->getAbsoluteServiceUri')->andReturn($url1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $processor = m::mock(UriProcessorNew::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executeGet')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getExpander')->andReturn($expander);

        $expected = 'Resource not found for the segment \'Identifier\'.';
        $actual   = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceSuccessful()
    {
        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();
        $wrapper->shouldReceive('getRelatedResourceReference')->andReturnNull()->once();

        $resourceSet     = m::mock(ResourceSet::class);
        $resourceWrapper = m::mock(ResourceSetWrapper::class);

        $propKind = ResourcePropertyKind::RESOURCE_REFERENCE();

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
        $projNode->shouldReceive('getEagerLoadList')->andReturn([])->atLeast(1);

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

        $processor = m::mock(UriProcessorNew::class)->shouldAllowMockingProtectedMethods()->makePartial();
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

        $processor = m::mock(UriProcessorNew::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('getService')->andReturn($service);

        $expected = 'This release of library supports only GET (read) request, received a request with method MERGE';
        $actual   = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
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
        $seg0->shouldReceive('getIdentifier')->andReturn(null);
        $seg0->shouldReceive('getTargetResourceSetWrapper')->andReturn(null);

        $seg1 = m::mock(SegmentDescriptor::class)->makePartial();
        $seg1->shouldReceive('getTargetKind')->andReturn(TargetKind::LINK());
        $seg1->shouldReceive('getPrevious')->andReturn($seg0);
        $seg1->shouldReceive('getIdentifier')->andReturn('Entity');
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

        $processor = m::mock(UriProcessorNew::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePut')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);
        $processor->shouldReceive('getExpander')->andReturn($expander);

        $expected = 'The URI \'http://192.168.2.1/abm-master/public/odata.svc/Entity(1)\' is not valid for PUT method.';
        $actual   = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePutBadRequestNoDataException()
    {
        $url1 = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $requestPayload                  = new ODataEntry();
        $requestPayload->id              = 'http://192.168.2.1/abm-master/public/odata.svc/Entity(1)';
        $requestPayload->type            = new ODataCategory('Customer');
        $requestPayload->propertyContent = new ODataPropertyContent();

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceSet  = m::mock(ResourceSet::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSetWrapper = m::mock(ResourceSetWrapper::class);
        $keyDescript        = m::mock(KeyDescriptor::class);

        $cereal = m::mock(ModelDeserialiser::class);
        $cereal->shouldReceive('bulkDeserialise')->andReturn(null)->once();

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
        $request->shouldReceive('getData')->andReturn($requestPayload);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url1);

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getHost')->andReturn($host);

        $expander = m::mock(RequestExpander::class);
        $expander->shouldReceive('handleExpansion')->andReturnNull()->never();

        $wrapper = m::mock(ProvidersWrapper::class);

        $processor = m::mock(UriProcessorNew::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePut')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);
        $processor->shouldReceive('getExpander')->andReturn($expander);
        $processor->shouldReceive('getFinalEffectiveSegment')->andReturn($seg1);
        $processor->shouldReceive('getModelDeserialiser')->andReturn($cereal);

        $expected = 'Method PUT expecting some data, but received empty data.';
        $actual   = null;

        try {
            $processor->execute();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecutePutRequestGoodData()
    {
        $url1 = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceSet  = m::mock(ResourceSet::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSetWrapper = m::mock(ResourceSetWrapper::class);
        $keyDescript        = m::mock(KeyDescriptor::class);

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

        $requestPayload                  = new ODataEntry();
        $requestPayload->id              = 'http://192.168.2.1/abm-master/public/odata.svc/Entity(1)';
        $requestPayload->type            = new ODataCategory('Customer');
        $requestPayload->propertyContent = new ODataPropertyContent();

        $model = new Customer2();

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRequestUrl')->andReturn($url1);
        $request->shouldReceive('getSegments')->andReturn([$seg1]);
        $request->shouldReceive('getData')->andReturn($requestPayload);

        $cereal = m::mock(ModelDeserialiser::class);
        $cereal->shouldReceive('bulkDeserialise')->andReturn(['stop!', 'hammer', 'time!']);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url1);

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getHost')->andReturn($host);

        $expander = m::mock(RequestExpander::class);
        $expander->shouldReceive('handleExpansion')->andReturnNull()->once();

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('updateResource')->andReturnNull()->once();
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($model)->once();

        $metaProv = m::mock(IMetadataProvider::class);
        $metaProv->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

        $key = m::mock(KeyDescriptor::class);

        $cynic = m::mock(CynicDeserialiser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $cynic->shouldReceive('getDeserialiser')->andReturn($cereal);
        $cynic->shouldReceive('getMetaProvider')->andReturn($metaProv);
        $cynic->shouldReceive('getWrapper')->andReturn($wrapper);
        $cynic->shouldReceive('generateKeyDescriptor')->andReturn($key)->once();

        $processor = m::mock(UriProcessorNew::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $processor->shouldReceive('executePut')->passthru()->once();
        $processor->shouldReceive('getService')->andReturn($service);
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);
        $processor->shouldReceive('getExpander')->andReturn($expander);
        $processor->shouldReceive('getFinalEffectiveSegment')->andReturn($seg1);
        $processor->shouldReceive('getModelDeserialiser')->andReturn($cereal);
        $processor->shouldReceive('getCynicDeserialiser')->andReturn($cynic);

        $processor->execute();
    }

    public function testExecuteGetWithBadMethod()
    {
        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetKind')->andReturnNull()->once();

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getSegments')->andReturn([$segment]);
        $request->shouldReceive('getRootProjectionNode')->andReturn(null)->once();

        $processor = m::mock(UriProcessorDummy::class)->makePartial();
        $processor->shouldReceive('getRequest')->andReturn($request);

        $expected = 'Not implemented yet';
        $actual   = null;

        try {
            $processor->executeGet();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecuteGetPerformPagingWithSkipToken()
    {
        $result          = new QueryResult();
        $result->results = ['eins', 'zwei', 'polizei', 'drei', 'vier', 'grenadier'];

        $setWrapper = m::mock(ResourceSetWrapper::class);

        $skipToken = m::mock(InternalSkipTokenInfo::class);
        $skipToken->shouldReceive('getIndexOfFirstEntryInTheNextPage')->andReturn(1)->once();
        $skipToken->shouldReceive('getSkipTokenInfo')->andReturnNull()->once();

        $segment = m::mock(SegmentDescriptor::class)->makePartial();
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE())->once();
        $segment->shouldReceive('getTargetSource')->andReturn(TargetSource::ENTITY_SET())->once();
        $segment->shouldReceive('getTargetResourceSetWrapper')->andReturn($setWrapper)->once();

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getSegments')->andReturn([$segment]);
        $request->shouldReceive('getInternalSkipTokenInfo')->andReturn($skipToken);
        $request->queryType = QueryType::ENTITIES_WITH_COUNT();

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('handlesOrderedPaging')->andReturn(false)->atLeast(1);
        $wrapper->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($result)->once();

        $processor = m::mock(UriProcessorDummy::class)->makePartial();
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);

        $processor->executeGet();

        $this->assertEquals(5, count($result->results));
    }

    public function testExecuteGetPerformPagingWithInternalSortGubbins()
    {
        $expected = ['drei', 'eins', 'grenadier', 'polizei', 'vier', 'zwei'];

        $result          = new QueryResult();
        $result->results = ['eins', 'zwei', 'polizei', 'drei', 'vier', 'grenadier'];

        $setWrapper = m::mock(ResourceSetWrapper::class);

        $sort = function ($a, $b) {
            return strcmp($a, $b);
        };

        $order = m::mock(InternalOrderByInfo::class)->makePartial();
        $order->shouldReceive('getSorterFunction')->andReturn($sort)->once();

        $segment = m::mock(SegmentDescriptor::class)->makePartial();
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE())->once();
        $segment->shouldReceive('getTargetSource')->andReturn(TargetSource::ENTITY_SET())->once();
        $segment->shouldReceive('getTargetResourceSetWrapper')->andReturn($setWrapper)->once();

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getSegments')->andReturn([$segment]);
        $request->shouldReceive('getInternalOrderByInfo')->andReturn($order);
        $request->queryType = QueryType::ENTITIES_WITH_COUNT();

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('handlesOrderedPaging')->andReturn(false)->atLeast(1);
        $wrapper->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($result)->once();

        $processor = m::mock(UriProcessorDummy::class)->makePartial();
        $processor->shouldReceive('getRequest')->andReturn($request);
        $processor->shouldReceive('getProviders')->andReturn($wrapper);

        $processor->executeGet();

        $this->assertEquals(6, count($result->results));
        $this->assertEquals($expected, $result->results);
    }
}
