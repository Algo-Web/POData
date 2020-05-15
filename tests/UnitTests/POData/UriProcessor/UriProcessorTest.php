<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\Configuration\ProtocolVersion;
use POData\Configuration\ServiceConfiguration;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\Readers\Atom\AtomODataReader;
use POData\Readers\ODataReaderRegistry;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\UriProcessor;
use UnitTests\POData\Facets\NorthWind2\Customer1;
use UnitTests\POData\TestCase;

//These are in the file loaded by above use statement
//TODO: move to own class files

class UriProcessorTest extends TestCase
{
    /** @var IService */
    protected $mockService;

    /** @var ServiceHost */
    protected $mockServiceHost;

    /** @var IServiceConfiguration */
    protected $fakeServiceConfig;

    /** @var IMetadataProvider */
    protected $mockMetadataProvider;

    /** @var ProvidersWrapper */
    protected $mockProvidersWrapper;

    /** @var ResourceSetWrapper */
    protected $mockCollectionResourceSetWrapper;

    /** @var ResourceType */
    protected $mockCollectionResourceType;

    /** @var ResourceProperty */
    protected $mockCollectionKeyProperty;

    /** @var ResourceProperty */
    protected $mockCollectionRelatedCollectionProperty;

    /** @var ResourceSetWrapper */
    protected $mockRelatedCollectionResourceSetWrapper;

    /** @var ResourceType */
    protected $mockRelatedCollectionResourceType;

    /** @var ResourceProperty */
    protected $mockRelatedCollectionKeyProperty;

    /**
     * @throws \POData\Common\UrlFormatException
     */
    public function setUp()
    {
        parent::setUp();

        // set up mock objects for later use
        $this->mockServiceHost                         = m::mock(ServiceHost::class)->makePartial();
        $this->mockService                             = m::mock(IService::class)->makePartial();
        $readerRegistry                                = new ODataReaderRegistry();
        $readerRegistry->register(new AtomODataReader());
        $this->mockService->shouldReceive('getODataReaderRegistry')->andReturn($readerRegistry);
        $this->mockMetadataProvider                    = m::mock(IMetadataProvider::class)->makePartial();
        $this->mockProvidersWrapper                    = m::mock(ProvidersWrapper::class)->makePartial();
        $this->mockCollectionResourceSetWrapper        = m::mock(ResourceSetWrapper::class)->makePartial();
        $this->mockCollectionResourceSetWrapper->shouldReceive('getResourceSetPageSize')->andReturn(11);
        $this->mockCollectionResourceType              = m::mock(ResourceEntityType::class)->makePartial();
        $this->mockCollectionKeyProperty               = m::mock(ResourceProperty::class)->makePartial();
        $this->mockCollectionRelatedCollectionProperty = m::mock(ResourceProperty::class)->makePartial();
        $this->mockRelatedCollectionResourceSetWrapper = m::mock(ResourceSetWrapper::class)->makePartial();
        $this->mockRelatedCollectionResourceType       = m::mock(ResourceEntityType::class)->makePartial();
        $this->mockRelatedCollectionKeyProperty        = m::mock(ResourceProperty::class)->makePartial();

        $reflec                                        = m::mock(\ReflectionClass::class);
        $reflec->shouldReceive('newInstanceArgs')->andReturn(null);
        $this->mockCollectionResourceType->shouldReceive('getInstanceType')->andReturn($reflec);

        //setup some general navigation between POData types

        $serviceURI = new Url('http://host.com/data.svc');
        $this->mockServiceHost->shouldReceive('getAbsoluteServiceUri')->andReturn($serviceURI);

        $this->mockService->shouldReceive('getHost')->andReturn($this->mockServiceHost);

        $this->mockService->shouldReceive('getProvidersWrapper')->andReturn($this->mockProvidersWrapper);

        $this->mockProvidersWrapper->shouldReceive('resolveResourceSet')->withArgs(['Collection'])
            ->andReturn($this->mockCollectionResourceSetWrapper);

        $this->mockCollectionResourceSetWrapper->shouldReceive('getResourceType')
            ->andReturn($this->mockCollectionResourceType);

        $this->mockCollectionResourceType->shouldReceive('getKeyProperties')
            ->andReturn(['id' => $this->mockCollectionKeyProperty]);

        $this->mockCollectionKeyProperty->shouldReceive('getInstanceType')->andReturn(new Int32());
        $this->mockCollectionKeyProperty->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());

        $this->mockCollectionResourceType->shouldReceive('resolveProperty')->withArgs(['RelatedCollection'])
            ->andReturn($this->mockCollectionRelatedCollectionProperty);
        $this->mockCollectionResourceType->shouldReceive('getName')->andReturn('rType');
        $this->mockCollectionResourceType->shouldReceive('getResourceTypeKind')
            ->andReturn(m::mock(ResourceTypeKind::class)->makePartial());
        $this->mockCollectionResourceType->shouldReceive('resolveProperty')->withArgs(['id'])
            ->andReturn($this->mockCollectionKeyProperty);

        $this->mockProvidersWrapper->shouldReceive('resolveResourceSet')->withArgs(['RelatedCollection'])
            ->andReturn($this->mockRelatedCollectionResourceSetWrapper);

        $this->mockRelatedCollectionResourceSetWrapper->shouldReceive('getResourceType')
            ->andReturn($this->mockRelatedCollectionResourceType);

        $this->mockRelatedCollectionResourceSetWrapper->shouldReceive('getName')
            ->andReturn('rType');

        $this->mockRelatedCollectionResourceType->shouldReceive('getKeyProperties')
            ->andReturn(['id' => $this->mockRelatedCollectionKeyProperty]);

        $this->mockRelatedCollectionKeyProperty->shouldReceive('getInstanceType')->andReturn(new Int32());

        $this->fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
        $this->mockService->shouldReceive('getConfiguration')->andReturn($this->fakeServiceConfig);
        $this->mockService->shouldReceive('getMetadataProvider')->andReturn($this->mockMetadataProvider);
    }

    public function testProcessRequestForCollection()
    {
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturnNull();

        $opCon = m::mock(IOperationContext::class);
        $opCon->shouldReceive('incomingRequest')->andReturn($request);

        $requestURI = new Url('http://host.com/data.svc/Collection');
        $this->mockService->shouldReceive('getOperationContext')->andReturn($opCon);
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasNamedStreams')->andReturn(false);
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasBagProperty')->andReturn(false);
        $this->mockCollectionResourceSetWrapper->shouldReceive('getName')->andReturn('HAMMER TIME!');
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $this->mockProvidersWrapper->shouldReceive('handlesOrderedPaging')->andReturn(false);

        $uriProcessor = UriProcessor::process($this->mockService);

        $one = new \stdClass();
        $one->id = 1;
        $two = new \stdClass();
        $two->id = 2;
        $three = new \stdClass();
        $three->id = 3;

        $fakeQueryResult          = new QueryResult();
        $fakeQueryResult->results = [$one, $two, $three];

        /* TODO: Figure out why this doesn't work when it should
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withArgs([
            QueryType::ENTITIES(),
            $this->mockCollectionResourceSetWrapper,
            null,
            null,
            null,
            0,
            null])->andReturn($fakeQueryResult); */
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($fakeQueryResult);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();
        $this->assertTrue($actual instanceof QueryResult);

        $this->assertEquals([$one, $two, $three], $actual->results);
    }

    public function testProcessRequestForCollectionCountThrowsWhenServiceVersionIs10()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/$count');
        $this->mockService->shouldReceive('getOperationContext')->andReturnNull();
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('1.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('1.0');
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1());
        //because this is V1 and $count requires V2, this will fail

        try {
            UriProcessor::process($this->mockService);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $expected = Messages::requestVersionTooLow('1.0', '2.0');
            $this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
        }
    }

    public function testProcessRequestForCollectionCountThrowsWhenCountsAreDisabled()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/$count');

        $this->mockService->shouldReceive('getOperationContext')->andReturnNull();
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('2.0');
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        $this->fakeServiceConfig->setAcceptCountRequests(false);

        try {
            UriProcessor::process($this->mockService);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $expected = Messages::configurationCountNotAccepted();
            $this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
        }
    }

    public function testProcessRequestForCollectionCountProviderDoesNotHandlePaging()
    {
        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturnNull();

        $opCon = m::mock(IOperationContext::class);
        $opCon->shouldReceive('incomingRequest')->andReturn($request);

        $requestURI = new Url('http://host.com/data.svc/Collection/$count');
        $this->mockService->shouldReceive('getOperationContext')->andReturn($opCon);
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('2.0');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockCollectionResourceSetWrapper->shouldReceive('getName')->andReturn('STOP!');
        $this->mockServiceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult          = new QueryResult();
        $fakeQueryResult->results = [1, 2, 3];

        /* TODO: Figure out why commented version loses plot while anyArgs version passes - with same
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withArgs([
            QueryType::COUNT(),
            $this->mockCollectionResourceSetWrapper,
            null,
            null,
            null,
            0,
            null])->andReturn($fakeQueryResult);*/
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($fakeQueryResult);

        //indicate that POData must perform the paging (thus it will count the results)
        $this->mockProvidersWrapper->shouldReceive('handlesOrderedPaging')->andReturn(false);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(3, $actual);
    }

    public function testProcessRequestForCollectionCountProviderHandlesPaging()
    {
        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturnNull();

        $opCon = m::mock(IOperationContext::class);
        $opCon->shouldReceive('incomingRequest')->andReturn($request);

        $requestURI = new Url('http://host.com/data.svc/Collection/$count');

        $this->mockService->shouldReceive('getOperationContext')->andReturn($opCon);
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('2.0');
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockServiceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult          = new QueryResult();
        $fakeQueryResult->results = [1, 2, 3];
        $fakeQueryResult->count   = 10; //note this differs from the size of the results array

        /* TODO: Figure out why commented version loses plot while anyArgs version passes
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withArgs([
            QueryType::COUNT(),
            $this->mockCollectionResourceSetWrapper,
            null,
            null,
            null,
            0,
            null])->andReturn($fakeQueryResult);*/
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($fakeQueryResult);

        //indicate that the Provider performs the paging (thus it will use the count in the QueryResult)
        $this->mockProvidersWrapper->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(10, $actual);
    }

    public function testProcessRequestForCollectionWithInlineCountWhenCountsAreDisabled()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        $this->mockService->shouldReceive('getOperationContext')->andReturnNull();
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('2.0');
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasNamedStreams')->andReturn(false);
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasBagProperty')->andReturn(false);
        $this->mockCollectionResourceSetWrapper->shouldReceive('getName')->andReturn('HAMMER TIME!');
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        //mock inline count as all pages
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->with(\Mockery::not(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->andReturn(null);
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])
            ->andReturn('allpages');

        $this->fakeServiceConfig->setAcceptCountRequests(false);

        try {
            $res = UriProcessor::process($this->mockService);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $expected = Messages::configurationCountNotAccepted();
            $this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
        }
    }

    public function testProcessRequestForCollectionWithInlineCountWhenServiceVersionIs10()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);

        $this->mockService->shouldReceive('getOperationContext')->andReturnNull();
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('1.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('1.0');
        $this->mockServiceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasNamedStreams')->andReturn(true);
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasBagProperty')->andReturn(true);
        $this->mockCollectionResourceSetWrapper->shouldReceive('getName')->andReturn('HAMMER TIME!');
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        //mock inline count as all pages
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->with(\Mockery::not(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->andReturn(null);
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])
            ->andReturn('allpages');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1());

        try {
            UriProcessor::process($this->mockService);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $expected = Messages::requestVersionTooLow('1.0', '3.0');
            $this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
        }
    }

    /**
     * @throws ODataException
     * @throws \POData\Common\InvalidOperationException
     * @throws \POData\Common\NotImplementedException
     * @throws \POData\Common\UrlFormatException
     * @throws \ReflectionException
     */
    public function testProcessRequestForCollectionWithNoInlineCountWhenVersionIsTooLow()
    {
        //I'm not so sure about this test...basically $inlinecount is ignored if it's none, but maybe we should
        //be throwing an exception?

        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturnNull();

        $opCon = m::mock(IOperationContext::class);
        $opCon->shouldReceive('incomingRequest')->andReturn($request);

        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=none');
        $this->mockService->shouldReceive('getOperationContext')->andReturn($opCon);
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('1.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('1.0');
        $this->mockServiceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasNamedStreams')->andReturn(false);
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasBagProperty')->andReturn(false);
        $this->mockCollectionResourceSetWrapper->shouldReceive('getName')->andReturn('HAMMER TIME!');
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        //mock inline count as all pages
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->with(\Mockery::not(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->andReturn(null);
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])
            ->andReturn('none');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1());

        $this->expectException(ODataException::class);
        $this->expectExceptionMessage(
            'Request version \'1.0\' is not supported for the request payload. The only supported version is \'2.0\'.'
        );

        $uriProcessor = UriProcessor::process($this->mockService);
    }

    /**
     * @throws ODataException
     * @throws \POData\Common\InvalidOperationException
     * @throws \POData\Common\NotImplementedException
     * @throws \POData\Common\UrlFormatException
     * @throws \ReflectionException
     */
    public function testProcessRequestForCollectionWithInlineCountProviderDoesNotHandlePaging()
    {
        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturnNull();

        $opCon = m::mock(IOperationContext::class);
        $opCon->shouldReceive('incomingRequest')->andReturn($request);

        $requestURI = new Url('http://host.com/data.svc/Collection?$inlinecount=allpages');
        $this->mockService->shouldReceive('getOperationContext')->andReturn($opCon);
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('2.0');
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasNamedStreams')->andReturn(false);
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasBagProperty')->andReturn(false);
        $this->mockCollectionResourceSetWrapper->shouldReceive('getName')->andReturn('HAMMER TIME!');
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        //mock inline count as all pages
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])
            ->andReturn('allpages');
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->with(\Mockery::not(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->andReturn(null);

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $one = new \stdClass();
        $one->id = 1;
        $two = new \stdClass();
        $two->id = 2;
        $three = new \stdClass();
        $three->id = 3;

        $fakeQueryResult          = new QueryResult();
        $fakeQueryResult->results = [$one, $two, $three];
        $fakeQueryResult->count   = 10; //note this is different than the size of the array

        /* TODO: Figure out why commented version loses plot while anyArgs version passes
         *  $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withArgs([
            QueryType::ENTITIES_WITH_COUNT(),
            $this->mockCollectionResourceSetWrapper,
            null,
            null,
            null,
            0,
            null])->andReturn($fakeQueryResult); */
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($fakeQueryResult);

        //indicate that POData must perform the paging (thus it will use the count of the results in QueryResult)
        $this->mockProvidersWrapper->shouldReceive('handlesOrderedPaging')->andReturn(false);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();
        $this->assertTrue($actual instanceof QueryResult);

        $this->assertEquals([$one, $two, $three], $actual->results);
        $this->assertEquals(3, $request->getCountValue());
    }

    public function testProcessRequestForCollectionWithInlineCountProviderHandlesPaging()
    {
        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturnNull();

        $opCon = m::mock(IOperationContext::class);
        $opCon->shouldReceive('incomingRequest')->andReturn($request);

        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockService->shouldReceive('getOperationContext')->andReturn($opCon);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('3.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasNamedStreams')->andReturn(true);
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasBagProperty')->andReturn(true);
        $this->mockCollectionResourceSetWrapper->shouldReceive('getName')->andReturn('STOP!');
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        //mock inline count as all pages
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->with(\Mockery::not(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->andReturn(null);
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])
            ->andReturn('allpages');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V3());

        $one = new \stdClass();
        $one->id = 1;
        $two = new \stdClass();
        $two->id = 2;
        $three = new \stdClass();
        $three->id = 3;

        $fakeQueryResult          = new QueryResult();
        $fakeQueryResult->results = [$one, $two, $three];
        $fakeQueryResult->count   = 10;

        /* TODO: Figure out why commented version loses plot while anyArgs version passes
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withArgs([
            QueryType::ENTITIES_WITH_COUNT(),
            $this->mockCollectionResourceSetWrapper,
            null,
            null,
            null,
            0,
            null])->andReturn($fakeQueryResult);*/
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($fakeQueryResult);

        //indicate that the Provider performs the paging (thus it will use the count in the QueryResult)
        $this->mockProvidersWrapper->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $uriProcessor = UriProcessor::process($this->mockService);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();
        $this->assertTrue($actual instanceof QueryResult);

        $this->assertEquals([$one, $two, $three], $actual->results);
        $this->assertEquals(10, $request->getCountValue());
    }

    /*
    public function testProcessRequestForRelatedCollection()
    {

        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $requestURI = new Url('http://host.com/data.svc/Collection(0)/RelatedCollection');
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1,2,3);

        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::ENTITIES(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                null
            )
        )->return($fakeQueryResult);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(array(1,2,3), $actual);
    }


    public function testProcessRequestForCollectionCountProviderDoesNotHandlePaging()
    {

        $requestURI = new Url('http://host.com/data.svc/Collection/$count');
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1,2,3);
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::COUNT(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                null
            )
        )->return($fakeQueryResult);

        //indicate that POData must perform the paging (thus it will count the results)
        Phockito::when($this->mockProvidersWrapper->handlesOrderedPaging())
            ->return(false);


        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(3, $actual);
    }


    public function testProcessRequestForCollectionCountProviderHandlesPaging()
    {


        $requestURI = new Url('http://host.com/data.svc/Collection/$count');
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1,2,3);
        $fakeQueryResult->count = 10; //note this differs from the size of the results array
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::COUNT(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                null
            )
        )->return($fakeQueryResult);

        //indicate that the Provider performs the paging (thus it will use the count in the QueryResult)
        Phockito::when($this->mockProvidersWrapper->handlesOrderedPaging())
            ->return(true);


        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(10, $actual);
    }


    public function testProcessRequestForCollectionWithInlineCountProviderDoesNotHandlePaging()
    {

        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);

        //mock inline count as all pages
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])
            ->andReturn('allpages');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1,2,3);
        $fakeQueryResult->count = 10; //note this is different than the size of the array
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                null
            )
        )->return($fakeQueryResult);

        //indicate that POData must perform the paging (thus it will use the count of the results in QueryResult)
        Phockito::when($this->mockProvidersWrapper->handlesOrderedPaging())
            ->return(false);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(array(1,2,3), $actual);
        $this->assertEquals(3, $request->getCountValue());
    }


    public function testProcessRequestForCollectionWithInlineCountProviderHandlesPaging()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);

        //mock inline count as all pages
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])
            ->andReturn('allpages');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1,2,3);
        $fakeQueryResult->count = 10;
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                null
            )
        )->return($fakeQueryResult);

        //indicate that the Provider performs the paging (thus it will use the count in the QueryResult)
        Phockito::when($this->mockProvidersWrapper->handlesOrderedPaging())
            ->return(true);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(array(1,2,3), $actual);
        $this->assertEquals(10, $request->getCountValue());
    }


    public function testProcessRequestForCollectionWithNoInlineCountWhenVersionIsTooLow()
    {
        //I'm not so sure about this test...basically $inlinecount is ignored if it's none, but maybe we should
        //be throwing an exception?

        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=none');
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);

        //mock inline count as all pages
        $this->mockServiceHost->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])
            ->andReturn('none');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1,2,3);
        $fakeQueryResult->count = 10; //note this is different than the size of the array
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::ENTITIES(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                null
            )
        )->return($fakeQueryResult);

        //indicate that POData must perform the paging (thus it will use the count of the results in QueryResult)
        Phockito::when($this->mockProvidersWrapper->handlesOrderedPaging())
            ->return(false);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(array(1,2,3), $actual);
        $this->assertNull($request->getCountValue(), 'Since $inlinecount is specified as none, there should be no count set');
    }
    */
}
