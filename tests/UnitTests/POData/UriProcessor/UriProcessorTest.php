<?php

namespace UnitTests\POData\UriProcessor;

use POData\Configuration\ServiceConfiguration;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderBySubPathSegment;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\InternalSkipTokenInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\Configuration\ProtocolVersion;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\DateTime;
use POData\Common\Url;
use POData\Common\Version;
use POData\Common\ODataException;
use POData\OperationContext\ServiceHost;
use POData\UriProcessor\UriProcessor;
use UnitTests\POData\Facets\ServiceHostTestFake;
use UnitTests\POData\Facets\NorthWind1\NorthWindService2;
use UnitTests\POData\Facets\NorthWind1\NorthWindServiceV1;
use UnitTests\POData\Facets\NorthWind1\NorthWindServiceV3;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Providers\Metadata\ResourceProperty;

use Phockito\Phockito;

use POData\IService;
use PhockitoUnit\PhockitoUnitTestCase;
//These are in the file loaded by above use statement
//TODO: move to own class files
use UnitTests\POData\Facets\NorthWind1\Customer2;

class UriProcessorTest extends PhockitoUnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        //setup some general navigation between POData types

        $serviceURI = new Url('http://host.com/data.svc');
        Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
            ->return($serviceURI);

        Phockito::when($this->mockService->getHost())
            ->return($this->mockServiceHost);

        Phockito::when($this->mockService->getProvidersWrapper())
            ->return($this->mockProvidersWrapper);

        Phockito::when($this->mockProvidersWrapper->resolveResourceSet('Collection'))
            ->return($this->mockCollectionResourceSetWrapper);

        Phockito::when($this->mockCollectionResourceSetWrapper->getResourceType())
            ->return($this->mockCollectionResourceType);

        Phockito::when($this->mockCollectionResourceType->getKeyProperties())
            ->return(array($this->mockCollectionKeyProperty));

        Phockito::when($this->mockCollectionKeyProperty->getInstanceType())
            ->return(new Int32());

        Phockito::when($this->mockCollectionResourceType->resolveProperty('RelatedCollection'))
            ->return($this->mockCollectionRelatedCollectionProperty);

        Phockito::when($this->mockProvidersWrapper->resolveResourceSet('RelatedCollection'))
            ->return($this->mockRelatedCollectionResourceSetWrapper);

        Phockito::when($this->mockRelatedCollectionResourceSetWrapper->getResourceType())
            ->return($this->mockRelatedCollectionResourceType);

        Phockito::when($this->mockRelatedCollectionResourceType->getKeyProperties())
            ->return(array($this->mockRelatedCollectionKeyProperty));

        Phockito::when($this->mockRelatedCollectionKeyProperty->getInstanceType())
            ->return(new Int32());

        $this->fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
        Phockito::when($this->mockService->getConfiguration())
            ->return($this->fakeServiceConfig);
    }

    /** @var IService */
    protected $mockService;

    /** @var ServiceHost */
    protected $mockServiceHost;

    /** @var ServiceConfiguration */
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

    public function testProcessRequestForCollection()
    {
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $requestURI = new Url('http://host.com/data.svc/Collection');
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1, 2, 3);

        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::ENTITIES(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                0,
                null
            )
        )->return($fakeQueryResult);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(array(1, 2, 3), $actual);
    }

    public function testProcessRequestForCollectionCountThrowsWhenServiceVersionIs10()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/$count');
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

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
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

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
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1, 2, 3);
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::COUNT(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                0,
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
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1, 2, 3);
        $fakeQueryResult->count = 10; //note this differs from the size of the results array
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::COUNT(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                0,
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

    public function testProcessRequestForCollectionWithInlineCountWhenCountsAreDisabled()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        //mock inline count as all pages
        Phockito::when($this->mockServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->return('allpages');

        $this->fakeServiceConfig->setAcceptCountRequests(false);

        try {
            UriProcessor::process($this->mockService);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $expected = Messages::configurationCountNotAccepted();
            $this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
        }
    }

    public function testProcessRequestForCollectionWithInlineCountWhenServiceVersionIs10()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        //mock inline count as all pages
        Phockito::when($this->mockServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->return('allpages');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1());

        try {
            UriProcessor::process($this->mockService);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $expected = Messages::requestVersionTooLow('1.0', '2.0');
            $this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
        }
    }

    public function testProcessRequestForCollectionWithNoInlineCountWhenVersionIsTooLow()
    {
        //I'm not so sure about this test...basically $inlinecount is ignored if it's none, but maybe we should
        //be throwing an exception?

        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=none');
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        //mock inline count as all pages
        Phockito::when($this->mockServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->return('none');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1, 2, 3);
        $fakeQueryResult->count = 10; //note this is different than the size of the array
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::ENTITIES(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                0,
                null
            )
        )->return($fakeQueryResult);

        //indicate that POData must perform the paging (thus it will use the count of the results in QueryResult)
        Phockito::when($this->mockProvidersWrapper->handlesOrderedPaging())
            ->return(false);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(array(1, 2, 3), $actual);
        $this->assertNull($request->getCountValue(), 'Since $inlinecount is specified as none, there should be no count set');
    }

    public function testProcessRequestForCollectionWithInlineCountProviderDoesNotHandlePaging()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        //mock inline count as all pages
        Phockito::when($this->mockServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->return('allpages');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1, 2, 3);
        $fakeQueryResult->count = 10; //note this is different than the size of the array
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                0,
                null
            )
        )->return($fakeQueryResult);

        //indicate that POData must perform the paging (thus it will use the count of the results in QueryResult)
        Phockito::when($this->mockProvidersWrapper->handlesOrderedPaging())
            ->return(false);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(array(1, 2, 3), $actual);
        $this->assertEquals(3, $request->getCountValue());
    }

    public function testProcessRequestForCollectionWithInlineCountProviderHandlesPaging()
    {
        $requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        //mock inline count as all pages
        Phockito::when($this->mockServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            ->return('allpages');

        $this->fakeServiceConfig->setAcceptCountRequests(true);
        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $uriProcessor = UriProcessor::process($this->mockService);

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = array(1, 2, 3);
        $fakeQueryResult->count = 10;
        Phockito::when(
            $this->mockProvidersWrapper->getResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockCollectionResourceSetWrapper,
                null,
                null,
                null,
                0,
                null
            )
        )->return($fakeQueryResult);

        //indicate that the Provider performs the paging (thus it will use the count in the QueryResult)
        Phockito::when($this->mockProvidersWrapper->handlesOrderedPaging())
            ->return(true);

        $uriProcessor->execute();

        $request = $uriProcessor->getRequest();

        $actual = $request->getTargetResult();

        $this->assertEquals(array(1, 2, 3), $actual);
        $this->assertEquals(10, $request->getCountValue());
    }

    /*
    public function testProcessRequestForRelatedCollection()
    {

        $this->fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());

        $requestURI = new Url('http://host.com/data.svc/Collection(0)/RelatedCollection');
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

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
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

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
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

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
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        //mock inline count as all pages
        Phockito::when($this->mockServiceHost->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_INLINECOUNT ))
            ->return("allpages");

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
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        //mock inline count as all pages
        Phockito::when($this->mockServiceHost->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_INLINECOUNT ))
            ->return("allpages");

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
        Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
            ->return($requestURI);

        //mock inline count as all pages
        Phockito::when($this->mockServiceHost->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_INLINECOUNT ))
            ->return("none");

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
