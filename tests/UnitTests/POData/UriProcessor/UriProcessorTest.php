<?php

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
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\UriProcessor;
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

    public function setUp()
    {
        parent::setUp();

        // set up mock objects for later use
        $this->mockServiceHost = m::mock(ServiceHost::class)->makePartial();
        $this->mockService = m::mock(IService::class)->makePartial();
        $this->mockMetadataProvider = m::mock(IMetadataProvider::class)->makePartial();
        $this->mockProvidersWrapper = m::mock(ProvidersWrapper::class)->makePartial();
        $this->mockCollectionResourceSetWrapper = m::mock(ResourceSetWrapper::class)->makePartial();
        $this->mockCollectionResourceType = m::mock(ResourceType::class)->makePartial();
        $this->mockCollectionKeyProperty = m::mock(ResourceProperty::class)->makePartial();
        $this->mockCollectionRelatedCollectionProperty = m::mock(ResourceProperty::class)->makePartial();
        $this->mockRelatedCollectionResourceSetWrapper = m::mock(ResourceSetWrapper::class)->makePartial();
        $this->mockRelatedCollectionResourceType = m::mock(ResourceType::class)->makePartial();
        $this->mockRelatedCollectionKeyProperty = m::mock(ResourceProperty::class)->makePartial();

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
            ->andReturn([$this->mockCollectionKeyProperty]);

        $this->mockCollectionKeyProperty->shouldReceive('getInstanceType')->andReturn(new Int32());

        $this->mockCollectionResourceType->shouldReceive('resolveProperty')->withArgs(['RelatedCollection'])
            ->andReturn($this->mockCollectionRelatedCollectionProperty);

        $this->mockProvidersWrapper->shouldReceive('resolveResourceSet')->withArgs(['RelatedCollection'])
            ->andReturn($this->mockRelatedCollectionResourceSetWrapper);

        $this->mockRelatedCollectionResourceSetWrapper->shouldReceive('getResourceType')
            ->andReturn($this->mockRelatedCollectionResourceType);

        $this->mockRelatedCollectionResourceType->shouldReceive('getKeyProperties')
            ->andReturn([$this->mockRelatedCollectionKeyProperty]);

        $this->mockRelatedCollectionKeyProperty->shouldReceive('getInstanceType')->andReturn(new Int32());

        $this->fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
        $this->mockService->shouldReceive('getConfiguration')->andReturn($this->fakeServiceConfig);
    }

    public function testProcessRequestForCollection()
    {
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $requestURI = new Url('http://host.com/data.svc/Collection');
        $this->mockService->shouldReceive('getOperationContext')->andReturnNull();
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasNamedStreams')->andReturn(false);
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasBagProperty')->andReturn(false);
        $this->mockProvidersWrapper->shouldReceive('handlesOrderedPaging')->andReturn(false);
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = [1, 2, 3];

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

        $this->assertEquals([1, 2, 3], $actual->results);
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
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1()); //because this is V1 and $count requires V2, this will fail

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
        $requestURI = new Url('http://host.com/data.svc/Collection/$count');
        $this->mockService->shouldReceive('getOperationContext')->andReturnNull();
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

        $fakeQueryResult = new QueryResult();
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
        $requestURI = new Url('http://host.com/data.svc/Collection/$count');

        $this->mockService->shouldReceive('getOperationContext')->andReturnNull();
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('2.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('2.0');
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockServiceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockProvidersWrapper->shouldReceive('resolveSingleton')->andReturn(null);

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = [1, 2, 3];
        $fakeQueryResult->count = 10; //note this differs from the size of the results array

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

    public function testProcessRequestForCollectionWithNoInlineCountWhenVersionIsTooLow()
    {
        //I'm not so sure about this test...basically $inlinecount is ignored if it's none, but maybe we should
        //be throwing an exception?

        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=none');
        $this->mockService->shouldReceive('getOperationContext')->andReturnNull();
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

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = [1, 2, 3];
        $fakeQueryResult->count = 10; //note this is different than the size of the array

        /* TODO: Figure out why commented version loses plot while anyArgs version passes
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withArgs([
            QueryType::ENTITIES(),
            $this->mockCollectionResourceSetWrapper,
            null,
            null,
            null,
            0,
            null])->andReturn($fakeQueryResult);*/
        $this->mockProvidersWrapper->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($fakeQueryResult);

        //indicate that POData must perform the paging (thus it will use the count of the results in QueryResult)
        $this->mockProvidersWrapper->shouldReceive('handlesOrderedPaging')->andReturn(false);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();
        $this->assertTrue($actual instanceof QueryResult);

        $this->assertEquals([1, 2, 3], $actual->results);
        $this->assertNull(
            $request->getCountValue(),
            'Since $inlinecount is specified as none, there should be no count set'
        );
    }

    public function testProcessRequestForCollectionWithInlineCountProviderDoesNotHandlePaging()
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

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = [1, 2, 3];
        $fakeQueryResult->count = 10; //note this is different than the size of the array

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

        $this->assertEquals([1, 2, 3], $actual->results);
        $this->assertEquals(3, $request->getCountValue());
    }

    public function testProcessRequestForCollectionWithInlineCountProviderHandlesPaging()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        $this->mockServiceHost->shouldReceive('getAbsoluteRequestUri')->andReturn($requestURI);
        $this->mockService->shouldReceive('getOperationContext')->andReturnNull();
        $this->mockServiceHost->shouldReceive('getRequestVersion')->andReturn('3.0');
        $this->mockServiceHost->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $this->mockCollectionResourceSetWrapper->shouldReceive('checkResourceSetRightsForRead')->andReturnNull();
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasNamedStreams')->andReturn(true);
        $this->mockCollectionResourceSetWrapper->shouldReceive('hasBagProperty')->andReturn(true);
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

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = [1, 2, 3];
        $fakeQueryResult->count = 10;

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

        $this->assertEquals([1, 2, 3], $actual->results);
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
