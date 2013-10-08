<?php

namespace UnitTests\POData\UriProcessor;

use POData\Configuration\ServiceConfiguration;
use POData\Providers\Metadata\ResourceSet;
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
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Common\Messages;
use POData\Common\ODataConstants;

use Phockito;
use POData\IService;
use UnitTests\POData\BaseUnitTestCase;

use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
//These are in the file loaded by above use statement
//TODO: move to own class files
use UnitTests\POData\Facets\NorthWind1\Customer2;


class UriProcessorTest extends BaseUnitTestCase
{

    /**
     * Test with request uri where TargetKind is NONE. TargetKind will be
     * NONE for service directory, metadata and batch.
     */
    public function testUriProcessorWithRequestUriOfNoneTargetSourceKind()
    {

        //Request for service directory
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertEquals($requestDescription->getTargetSource(), TargetSource::NONE);
        $this->assertEquals($requestDescription->getTargetKind(), TargetKind::SERVICE_DIRECTORY);
        


        //Request for metadata
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/$metadata'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertEquals($requestDescription->getTargetSource(), TargetSource::NONE);
        $this->assertEquals($requestDescription->getTargetKind(), TargetKind::METADATA);


        //Request for batch
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/$batch'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertEquals($requestDescription->getTargetSource(), TargetSource::NONE);
        $this->assertEquals($requestDescription->getTargetKind(), TargetKind::BATCH);

    }

    /**
     * Test request uri for row count ($count)     
     * DataServiceVersion and MaxDataServiceVersion should be >= 2.0 for $count
     */
    public function testUriProcessorForCountRequest1()
    {
        //Test $count with DataServiceVersion < 2.0
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null,
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for failure of capability negotiation over DataServiceVersion has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0", $ex->getMessage(), $ex->getTraceAsString());
        }


        //Test $count with MaxDataServiceVersion < 2.0
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null,
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for failure of capability negoitation over MaxDataServiceVersion has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0", $ex->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * $count is a version 2 feature so service devloper should use protocol version 2.0
     */
    public function testUriProcessorForCountRequest2()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null,
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindServiceV1();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for failure of capability negoitation due to V1 configuration has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("The response requires that version 2.0 of the protocol be used, but the MaxProtocolVersion of the data service is set to 1.0", $ex->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * 
     * Suppose $top option is absent, still
     * RequestDescription::topCount will be set if the resource targeted by the
     * uri has paging enabled, if RequestDescription::topCount 
     * is set then internal orderby info will be generated. But if the request 
     * is for raw count for a resource collection then paging is not applicable
     * for that, so topCount will be null and internal orderby info will not be 
     * generated.
     */
    public function testUriProcessorForCountRequest3()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null,
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertNull($requestDescription->getInternalOrderByInfo());

    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $orderby option can be applied to a $count request.
     */
    public function testUriProcessorForCountRequest4()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => '$orderby=Country',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

        $object = $internalOrderByInfo->getDummyObject();
        $this->assertNotNull($object);
        $this->assertTrue($object instanceof Customer2);

    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $skip and $top options can be applied to $count request, this cause
     * processor to generate internalorderinfo.
     */
    public function testUriProcessorForCountRequest5()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => '$skip=2&$top=4',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertEquals($requestDescription->getTopCount(), 4);
        $this->assertEquals($requestDescription->getSkipCount(), 2);

        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

        $object = $internalOrderByInfo->getDummyObject();
        $this->assertNotNull($object);
        $this->assertTrue($object instanceof Customer2);

    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $skip and/or $top options along with $orderby option cause internalOrderInfo
     * to include sorter functions using keys + paths in the $orderby clause
     */
    public function testUriProcessorForCountRequest6()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => '$skip=2&$top=4&$orderby=Country',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertEquals($requestDescription->getTopCount(), 4);
        $this->assertEquals($requestDescription->getSkipCount(), 2);

        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

        $object = $internalOrderByInfo->getDummyObject();
        $this->assertNotNull($object);
        $this->assertTrue($object instanceof Customer2);

        $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
        $this->assertNotNull($pathSegments);
        $this->assertTrue(is_array($pathSegments));
        $this->assertEquals(count($pathSegments), 3);

        $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
        $subPathSegments = $pathSegments[0]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'Country');

        $this->assertTrue($pathSegments[1] instanceof OrderByPathSegment);
        $subPathSegments = $pathSegments[1]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'CustomerID');

        $this->assertTrue($pathSegments[2] instanceof OrderByPathSegment);
        $subPathSegments = $pathSegments[2]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'CustomerGuid');
    }

    /**
     * Test request uri for row count ($count)     
     * $skiptoken is not applicable for $count request, as it requires
     * paging and paging is not applicable for $count request
     */
    public function testUriProcessorForCountRequest7()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => '$top=1&$skiptoken=\'ALFKI\',guid\'05b242e752eb46bd8f0e6568b72cd9a5\'',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for applying $skiptoken on $count has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Query option \$skiptoken cannot be applied to the requested resource", $ex->getMessage());
        }

    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $filter is applicable for $count segment.
     */
    public function testUriProcessorForCountRequest8()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => '$filter=Country eq \'USA\'',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);


        $this->assertEquals(array(), $filterInfo->getNavigationPropertiesUsed());

        $this->assertEquals("", $filterInfo->getExpressionAsString(), "because northwind expression provider does nothing, this is empty");

    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $select and $expand options are applicable for $count segment.
     * but when we do query execution we will ignore them.
     */
    public function testUriProcessorForCountRequest9()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => '$select=Country&$expand=Orders',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $projectionTreeRoot = $requestDescription->getRootProjectionNode();
        $this->assertNotNull($projectionTreeRoot);
        $this->assertTrue($projectionTreeRoot instanceof RootProjectionNode);

        //There will be one child node for 'Country', 'Orders' wont be included
        //as its not selected
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertNotNull($childNodes);
        $this->assertTrue(is_array($childNodes));
        $this->assertEquals(count($childNodes), 1);
        $this->assertTrue(array_key_exists('Country', $childNodes));
        $this->assertTrue($childNodes['Country'] instanceof ProjectionNode);

    }

    /**
     * Test request uri for row count ($count)     
     * $count with $inlinecount not allowed
     */
    public function testUriProcessorForCountWithInline()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => '$inlinecount=allpages',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for applying $skiptoken on $count has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("\$inlinecount cannot be applied to the resource segment \$count", $ex->getMessage());
        }
    }

    /**
     * If paging is enabled for a resource set, then the uri 
     * processor should generate orderinfo irrespective of
     * whether $top or $orderby is specified or not.
     * 
     * Request DataServiceVersion => 1.0
     * Request MaxDataServiceVersion => 2.0
     */
    public function testUriProcessorForResourcePageInfo1()
    {
        //Test for generation of orderinfo for resource set
        //with request DataServiceVersion 1.0
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null,
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        //Page size is 5, so take count is 5
        $this->assertEquals($requestDescription->getTopCount(), 5);

        //order info is required for pagination
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

        $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
        $this->assertNotNull($pathSegments);
        $this->assertTrue(is_array($pathSegments));
        //Customer has two keys
        $this->assertEquals(count($pathSegments), 2);
        $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);

        $subPathSegments = $pathSegments[0]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'CustomerID');

        $this->assertTrue($pathSegments[1] instanceof OrderByPathSegment);

        $subPathSegments = $pathSegments[1]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'CustomerGuid');

    }

    /**
     * If paging is enabled for a resource set, then the uri 
     * processor should generate orderinfo irrespective of
     * whether $top or $orderby is specified or not.
     * 
     * Request DataServiceVersion => 1.0
     * Request MaxDataServiceVersion => 1.0
     * 
     * This will fail as paging requires version 2.0 or above
     */
    public function testUriProcessorForResourcePageInfo2()
    {
        //Test for generation of orderinfo for resource set
        //with request DataServiceVersion 1.0
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null,
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException due to capability negotiation has not been thrown (paged result but client\'s max supportedd version is 1.0)');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $ex->getMessage());
        }

    }

    /**
     * Paging is enabled only for resource set, so with resource set
     * reference there will not be any paginginfo.
     */
    public function testUriProcessorForResourcePageInfo3()
    {

        //Test for generation of orderinfo for resource set
        //with request DataServiceVersion 1.0
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Orders(123)'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null,
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        //Page is not appliable for single resouce
        $this->assertNull($requestDescription->getTopCount());

        //order info wont be generated as resource is not applicable for pagination
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNull($internalOrderByInfo);

    }

    /**
     * If paging is enabled for a resource set, then $link request for that resource set
     * will also paged
     * e.g. http://host/service.svc/Customers('A')/$links/Orders
     * here if paging is enabled for Orders then prcoessor must generate orderbyinfo for
     * this.     
     */
    public function testUriProcessorForResourcePageInfo4()
    {
        //Test for generation of orderinfo for resource set in $links query
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => null,
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        //Page size is 5, so take count is 5
        $this->assertEquals($requestDescription->getTopCount(), 5);

        //order info is required for pagination
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

        $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
        $this->assertNotNull($pathSegments);
        $this->assertTrue(is_array($pathSegments));
        //Order has one key
        $this->assertEquals(count($pathSegments), 1);
        $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);

        $subPathSegments = $pathSegments[0]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');

    }

    /**
     * $orderby option can be applied to $links resource set
     */
    public function testUriProcessorForLinksResourceSet1()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$orderby=ShipName asc, OrderDate desc',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertFalse($requestDescription->isSingleResult());
        //Page size is 5, so take count is 5 means you will get only 5 links for a request
        $this->assertEquals($requestDescription->getTopCount(), 5);
        //Paging requires ordering, the result should be ordered like
        //Note: additional ordering constraint
        //
        //SELECT links(d.orderID) FROM Customers JOIN Orders WHERE CustomerID='ALFKI' AND
        //CustomerGuid=guid'05b242e752eb46bd8f0e6568b72cd9a5' ORDER BY
        //d.ShipName ASC, d.OrderDate DESC, d.OrderID ASC

        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

        $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
        $this->assertNotNull($pathSegments);
        $this->assertTrue(is_array($pathSegments));
        $this->assertEquals(count($pathSegments), 3);
        $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);

        $subPathSegments = $pathSegments[0]->getSubPathSegments();
        $this->assertTrue($pathSegments[0]->isAscending());
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'ShipName');
        $this->assertTrue($pathSegments[1] instanceof OrderByPathSegment);
        $this->assertFalse($pathSegments[1]->isAscending());

        $subPathSegments = $pathSegments[1]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'OrderDate');
        $this->assertTrue($pathSegments[2] instanceof OrderByPathSegment);
        $this->assertTrue($pathSegments[2]->isAscending());

        $subPathSegments = $pathSegments[2]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');

    }

    /**
     * $skiptoken option can be applied to $links resource set
     */
    public function testUriProcessorForLinksResourceSet2()
    {
        //Test with skiptoken that corrosponds to default ordering key
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$skiptoken=123',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);


        $uriProcessor = $dataService->handleRequest();

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertFalse($requestDescription->isSingleResult());
        //Page size is 5, so take count is 5 means you will get only 5 links for a request
        $this->assertEquals($requestDescription->getTopCount(), 5);

        //paging requires ordering
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

        $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
        $this->assertNotNull($pathSegments);
        $this->assertTrue(is_array($pathSegments));
        $this->assertEquals(count($pathSegments), 1);
        $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);

        $subPathSegments = $pathSegments[0]->getSubPathSegments();
        $this->assertTrue($pathSegments[0]->isAscending());
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');

        //check the skiptoken details
        $internalSkiptokenInfo = $requestDescription->getInternalSkipTokenInfo();
        $this->assertNotNull($internalSkiptokenInfo);
        $this->assertTrue($internalSkiptokenInfo instanceof InternalSkipTokenInfo);

        $skipTokenInfo = $internalSkiptokenInfo->getSkipTokenInfo();
        $this->assertNotNull($skipTokenInfo);
        $this->assertTrue($skipTokenInfo instanceof SkipTokenInfo);

        $orderByValuesInSkipToken = $skipTokenInfo->getOrderByKeysInToken();
        $this->assertNotNull($orderByValuesInSkipToken);
        $this->assertTrue(is_array($orderByValuesInSkipToken));
        $this->assertEquals(count($orderByValuesInSkipToken), 1);
        $this->assertNotNull($orderByValuesInSkipToken[0]);
        $this->assertTrue(is_array($orderByValuesInSkipToken[0]));
        $this->assertEquals(count($orderByValuesInSkipToken[0]), 2);
        $this->assertEquals($orderByValuesInSkipToken[0][0], 123);
        $this->assertTrue(is_object($orderByValuesInSkipToken[0][1]));
        $this->assertTrue($orderByValuesInSkipToken[0][1] instanceof Int32);


        //Test with skiptoken that corresponds to explict ordering keys
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$orderby=OrderID asc, OrderDate desc&$skiptoken=123, datetime\'2000-11-11\'',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertEquals($requestDescription->isSingleResult(), false);
        //Page size is 5, so take count is 5 means you will get only 5 links for a request
        $this->assertEquals($requestDescription->getTopCount(), 5);

        //paging requires ordering
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);
        $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
        $this->assertNotNull($pathSegments);
        $this->assertTrue(is_array($pathSegments));
        $this->assertEquals(count($pathSegments), 2);
        $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);

        $subPathSegments = $pathSegments[0]->getSubPathSegments();
        $this->assertTrue($pathSegments[0]->isAscending());
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');
        $this->assertTrue($pathSegments[1] instanceof OrderByPathSegment);
        $this->assertFalse($pathSegments[1]->isAscending());

        $subPathSegments = $pathSegments[1]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'OrderDate');

        //check the skiptoken details
        $internalSkiptokenInfo = $requestDescription->getInternalSkipTokenInfo();
        $this->assertNotNull($internalSkiptokenInfo);
        $this->assertTrue($internalSkiptokenInfo instanceof InternalSkipTokenInfo);

        $skipTokenInfo = $internalSkiptokenInfo->getSkipTokenInfo();
        $this->assertNotNull($skipTokenInfo);
        $this->assertTrue($skipTokenInfo instanceof SkipTokenInfo);

        $orderByValuesInSkipToken = $skipTokenInfo->getOrderByKeysInToken();
        $this->assertNotNull($orderByValuesInSkipToken);
        $this->assertTrue(is_array($orderByValuesInSkipToken));
        $this->assertEquals(count($orderByValuesInSkipToken), 2);
        $this->assertNotNull($orderByValuesInSkipToken[0]);
        $this->assertTrue(is_array($orderByValuesInSkipToken[0]));
        $this->assertEquals(count($orderByValuesInSkipToken[0]), 2);
        $this->assertEquals($orderByValuesInSkipToken[0][0], 123);
        $this->assertTrue(is_object($orderByValuesInSkipToken[0][1]));
        $this->assertTrue($orderByValuesInSkipToken[0][1] instanceof Int32);
        $this->assertNotNull($orderByValuesInSkipToken[1]);
        $this->assertTrue(is_array($orderByValuesInSkipToken[1]));
        $this->assertEquals(count($orderByValuesInSkipToken[1]), 2);
        $this->assertEquals($orderByValuesInSkipToken[1][0], '\'2000-11-11\'');
        $this->assertTrue(is_object($orderByValuesInSkipToken[1][1]));
        $this->assertTrue($orderByValuesInSkipToken[1][1] instanceof DateTime);

    }

    /**
     * $top and $skip option can be applied to $links resource set
     */
    public function testUriProcessorForLinksResourceSet3()
    {
        //TODO: split into separate tests
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$skip=1',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);
        $uriProcessor = $dataService->handleRequest();
        
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertEquals($requestDescription->isSingleResult(), false);
        //$skip has been specified
        $this->assertEquals($requestDescription->getSkipCount(), 1);   
        //Page size is 5, so take count is 5 means you will get only 5 links for a request
        $this->assertEquals($requestDescription->getTopCount(), 5); 
        
        //paging requires ordering
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);
        
        $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
        $this->assertNotNull($pathSegments);
        $this->assertTrue(is_array($pathSegments));            
        $this->assertEquals(count($pathSegments), 1);
        $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
        
        $subPathSegments = $pathSegments[0]->getSubPathSegments();
        $this->assertTrue($pathSegments[0]->isAscending());
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');
        
        //specification of a $top value less than pagesize also need sorting,
        //$skiptoken also applicable, only thing is nextlink will be absent


        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$top=4&$skiptoken=1234',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);
        $uriProcessor = $dataService->handleRequest();
        
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertEquals($requestDescription->isSingleResult(), false);
        //$skip has not been specified
        $this->assertEquals($requestDescription->getSkipCount(), null);   
        //top is specified and is less than page size
        $this->assertEquals($requestDescription->getTopCount(), 4); 
        
        //top requires ordering
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);
        
        $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
        $this->assertNotNull($pathSegments);
        $this->assertTrue(is_array($pathSegments));            
        $this->assertEquals(count($pathSegments), 1);
        $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
        
        $subPathSegments = $pathSegments[0]->getSubPathSegments();
        $this->assertTrue($pathSegments[0]->isAscending());
        $this->assertNotNull($subPathSegments);
        $this->assertTrue(is_array($subPathSegments));
        $this->assertEquals(count($subPathSegments), 1);
        $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
        $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');
        //$skiptoken is specified
        
        $internalSkiptokenInfo = $requestDescription->getInternalSkipTokenInfo();
        $this->assertNotNull($internalSkiptokenInfo);
        $this->assertTrue($internalSkiptokenInfo instanceof InternalSkipTokenInfo);
        
        $skipTokenInfo = $internalSkiptokenInfo->getSkipTokenInfo();
        $this->assertTrue($skipTokenInfo instanceof SkipTokenInfo);
        
        $orderByValuesInSkipToken = $skipTokenInfo->getOrderByKeysInToken();
        $this->assertNotNull($orderByValuesInSkipToken);
        $this->assertTrue(is_array($orderByValuesInSkipToken));
        $this->assertEquals(count($orderByValuesInSkipToken), 1);
        $this->assertNotNull($orderByValuesInSkipToken[0]);
        $this->assertTrue(is_array($orderByValuesInSkipToken[0]));
        $this->assertEquals(count($orderByValuesInSkipToken[0]), 2);
        $this->assertEquals($orderByValuesInSkipToken[0][0], 1234);
        $this->assertTrue(is_object($orderByValuesInSkipToken[0][1]));
        $this->assertTrue($orderByValuesInSkipToken[0][1] instanceof Int32);
       
       
        //specification of a $top value greater than pagesize
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$top=10&$skiptoken=1234',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);
        $uriProcessor = $dataService->handleRequest();
        
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertEquals($requestDescription->isSingleResult(), false);
        //$skip has not been specified
        $this->assertEquals($requestDescription->getSkipCount(), null);   
        //top is specified and is greater than page size, so take count should be page size
        $this->assertEquals($requestDescription->getTopCount(), 5); 
        
        //top requires ordering
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);
        
        //$skiptoken is specified
        $internalSkiptokenInfo = $requestDescription->getInternalSkipTokenInfo();
        $this->assertNotNull($internalSkiptokenInfo);

    }

    /**
     * $filter option can be applied to $links resource set
     */
    public function testUriProcessorForLinksResourceSet4()
    {
        $this->markTestSkipped("This test checks that POData will generate a filter function for providers that don't handle filtering...but i temporarily removed that functionality by elimination IDataServiceQueryProvider1.  Need to make this service provider use PHPExpressionProvider, then re-enable tests");
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$filter=OrderID eq 123 and OrderDate le datetime\'2000-11-11\'',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);
        
        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertFalse($requestDescription->isSingleResult());
        $this->assertEquals($requestDescription->getTopCount(), 5); 
        
        //paging enabled
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);
        
        //$filter applied
        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);
        $this->assertTrue($filterInfo instanceof FilterInfo);

	    $expected ='((!(is_null($lt->OrderID)) && !(is_null($lt->OrderDate))) && (($lt->OrderID == 123) && (POData\Providers\Metadata\Type\DateTime::dateTimeCmp($lt->OrderDate, \'2000-11-11\') <= 0)))';
	    $this->assertEquals($expected, $filterInfo->getExpressionAsString(), "because northwind expression provider does nothing, this is empty");

    }

    /**
     * $inlinecount can be applied to $links identifying resource set     
     */
    public function testUriProcessorForLinksResourceSet5()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$inlinecount=allpages',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertFalse($requestDescription->isSingleResult());
        $this->assertEquals($requestDescription->getTopCount(), 5);

        //paging enabled
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

	    $this->assertEquals(QueryType::ENTITIES_WITH_COUNT(), $requestDescription->queryType);


    }

    /**
     * $filter option can be applied to $links resource set reference
     */
    public function testUriProcessorForLinksResourceSetReference1()
    {
	    $this->markTestSkipped("This test checks that POData will generate a filter function for providers that don't handle filtering...but i temporarily removed that functionality by elimination IDataServiceQueryProvider1.  Need to make this service provider use PHPExpressionProvider, then re-enable tests");
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$filter=OrderID eq 123 and OrderDate le datetime\'2000-11-11\'',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertTrue($requestDescription->isSingleResult());
        $this->assertNull($requestDescription->getTopCount());
        $this->assertNull($requestDescription->getSkipCount());

        //paging not applicable enabled
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNull($internalOrderByInfo);

        //$filter applied
        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);
        $this->assertTrue($filterInfo instanceof FilterInfo);

	    $expected = '((!(is_null($lt->OrderID)) && !(is_null($lt->OrderDate))) && (($lt->OrderID == 123) && (POData\Providers\Metadata\Type\DateTime::dateTimeCmp($lt->OrderDate, \'2000-11-11\') <= 0)))';
	    $this->assertEquals($expected, $filterInfo->getExpressionAsString(), "because northwind expression provider does nothing, this is empty");




        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(1234)/$links/Customer';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$filter=true',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertTrue($requestDescription->isSingleResult());
        $this->assertNull($requestDescription->getTopCount());
        $this->assertNull($requestDescription->getSkipCount());

        //paging not applicable enabled
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNull($internalOrderByInfo);

        //$filter applied
        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);
        $this->assertTrue($filterInfo instanceof FilterInfo);

	    $expected = 'true';
	    $this->assertEquals($expected, $filterInfo->getExpressionAsString(), "because northwind expression provider does nothing, this is empty");


    }

    /**
     * $orderby option cannot be applied to $links resource set reference
     */
    public function testUriProcessorForLinksResourceSetReference2()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$orderby=OrderID',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $orderby query option on non-set has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $ex->getMessage());
        }

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(1234)/$links/Customer';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$orderby=CustomerID',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $orderby query option on non-set has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $ex->getMessage());
        }
    }

    /**
     * $skiptoken option cannot be applied to $links resource set reference
     */
    public function testUriProcessorForLinksResourceSetReference3()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$skiptoken=345',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $skiptoken query option on non-set has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query option $skiptoken cannot be applied to the requested resource', $ex->getMessage());
        }

    }

    /**
     * $top and $skip option cannot be applied to $links resource set reference
     */
    public function testUriProcessorForLinksResourceSetReference4()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$skip=1',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $skip query option on non-set has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $ex->getMessage());
        }

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(234)';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$top=4',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $top query option on non-set has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $ex->getMessage());
        }
    }

    /**
     * $inlinecount option cannot be applied to $links resource set reference
     */
    public function testUriProcessorForLinksResourceSetReference5()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$inlinecount=allpages',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $inlinecount query option on non-set has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $ex->getMessage());
        }

    }

    /**
     * $expand, $select option cannot be applied to $links resource set reference or $link resource set
     */
    public function testUriProcessorForLinksResource()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$expand=Order_Details',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $expand query option on $link resource has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query option $expand cannot be applied to the requested resource', $ex->getMessage());
        }

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$select=OrderID',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $select query option on $link resource has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query option $select cannot be applied to the requested resource', $ex->getMessage());
        }


    }

    /**
     * $inline count is not supported for protocol version V1
     */
    public function testUriProcessorForInlineCount1()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Products(11)/Order_Details';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            //Paging is enabled this cause skiptoken to be included
            //in the response, thus reponse version become 2.0
            //But if $top is specified and its value is less than page size
            //then we don't need to include $skiptoken in response
            //so response version is 1.0.
            //If the paging is enabled and protocol to be used is V1
            //then a request (without a $top or $top value > pagesize)
            //cause the repsonse version of 2.0 to be used, but server
            //will throw error as protocol version is set to V1.
            //This error will be thrown from ProcessSkipAndTopCount function.

            //$inlinecount is a V2 feature, so with configured version of V1
            //ProcessCount will thorow error.

            //We are adding a $top value (< pagesize) with $inlinecount so that
            //version error will be thrown from ProcessCount instead of from
            //ProcessSkipAndTopCount
            'QueryString' => '$inlinecount=allpages&$top=3',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindServiceV1();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $inlinecount query option with V1 configured service has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('The response requires that version 2.0 of the protocol be used, but the MaxProtocolVersion of the data service is set to 1.0', $ex->getMessage());
        }

    }

    /**
     * For $inline request, client's DataServiceVersion header must be >= 2.0
     */
    public function testUriProcessorForInlineCount2()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Products(11)/Order_Details';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$inlinecount=allpages',
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $inlinecount query option request DataServiceVersion 1.0 has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $ex->getMessage());
        }

    }

    /**
     * For $inline request, client's MaxDataServiceVersion header must be >= 2.0
     */
    public function testUriProcessorForInlineCount3()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Products(11)/Order_Details';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$inlinecount=allpages',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $inlinecount query option with request DataServiceVersion 1.0 has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $ex->getMessage());
        }

    }

    /**
     * only supported $inlinecount values are 'allpages' and 'none'
     */
    public function testUriProcessorForInlineCount4()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Products(11)/Order_Details';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$inlinecount=partialpages',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for invalid $inlinecount query option has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Unknown $inlinecount option, only "allpages" and "none" are supported', $ex->getMessage());
        }

    }

    /**
     * $filter can be applied on complex resource
     */
    public function testUriProcessorForFilterOnComplex()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(123)/Customer/Address';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$filter=HouseNumber eq null',
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertTrue($requestDescription->isSingleResult());

        //$filter applied
        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);
        $this->assertTrue($filterInfo instanceof FilterInfo);

	    $this->assertEquals("", $filterInfo->getExpressionAsString(), "because northwind expression provider does nothing, this is empty");


	    $this->assertNull($requestDescription->getRootProjectionNode());

    }

    /**
     * $filter cannot be applied on primitive resource
     */
    public function testUriProcessorForFilterOnPrimitiveType()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Products(11)/ProductID';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$filter=true',
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
               $dataService->handleRequest();
            $this->fail('An expected ODataException for $filter query on primitve  has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query option $filter cannot be applied to the requested resource', $ex->getMessage());
        }

    }

    /**
     * $filter cannot be applied on bag resource
     */
    public function testUriProcessorForFilterOnBag()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Employees(\'EMP1\')/Emails';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$filter=true',
            'DataServiceVersion' => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        //Note we are using V3 data service
        $dataService = new NorthWindServiceV3();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $filter query option on bag has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query option $filter cannot be applied to the requested resource', $ex->getMessage());
        }

    }

    /**
     * $filter cannot be applied on primitve value
     */
   public function testUriProcessorForFilterOnValue()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(11)/Customer/CustomerID/$value';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$filter=true',
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $filter query option on primitve value has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query option $filter cannot be applied to the requested resource', $ex->getMessage());
        }

    }

    /**
     * When requesting for a bag DataServiceVersion should be >= 3.0     
     */
    public function testUriProcessorWithTargetAsBag1()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Employees(\'EMP1\')/Emails';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => null,
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for a bag request with  MaxDataServiceVersion < 3.0 has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '2.0' is not supported for the request payload. The only supported version is '3.0'", $ex->getMessage());
        }


    }

    /**
     * The MaxProtocolVersion configured for the service should be >=3.0 to respond to request for Bag 
     */
   public function testUriProcessorWithTargetAsBag2()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Employees(\'EMP1\')/Emails';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => null,
            'DataServiceVersion' => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for a bag request to a service configured with V2 has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("The response requires that version 3.0 of the protocol be used, but the MaxProtocolVersion of the data service is set to 2.0", $ex->getMessage());
        }

    }

    /**
     * $select cannot be applied if its disabled on configuration
     */
    public function testUriProcessorForSelectWhereProjectionDisabled()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(11)/Customer';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$expand=Orders&$select=CustomerID,Orders',
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindServiceV3();
        $dataService->setHost($host);

        try {
              $dataService->handleRequest();
            $this->fail('An expected ODataException for $select option on projection disabled service  has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('The ability to use the $select query option to define a projection in a data service query is disabled', $ex->getMessage());
        }

    }

    /**
     * select and expand can be applied to request url identifying resource set
     */
    /** public function testUriProcessorForSelelctExpandOnResourceSet()
    {
        
    }

    /**
     * $select is a V2 feature so client should request with  'DataServiceVersion' 2.0
     * but the response of select can be handled by V1 client so a value of 1.0 for MaxDataServiceVersion
     * will work
     */
    public function testUriProcessorForSelectExpandOnResourceWithDataServiceVersion1_0()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(11)/Customer';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$expand=Orders&$select=CustomerID,Orders',
            //use of $select requires this header to 2.0
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $select query option with  DataServiceVersion 1.0 has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $ex->getMessage());
        }


    }

    /**
     * if paging is applicable for top level resource
     *  (1) Paging enabled and $top > pageSize => require next link
     *  (2) Paging enabled and no $top => require next link
     * Then 'MaxDataServiceVersion' in request header must be >= 2.0
     */
    public function testUriProcessorForPagedTopLevelResourceWithMaxDataServiceVersion1_0()
    {
        //Paging enabled for top level resource set and $top > pageSize => require next link
        //so MaxDataServiceVersion 1.0 will not work
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$top=10&$expand=Customer',
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for a paged top level result (having $top) with  MaxDataServiceVersion 1.0 has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $ex->getMessage());
        }

        //Paging enabled for top level resource set and no $top => require next link
        //so MaxDataServiceVersion 1.0 will not work
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            //error will be thrown from processskipAndTopOption before processor process expand
            'QueryString' => '$expand=Customer',
            //DataServiceVersion can be 1.0 no issue
            'DataServiceVersion' => new Version(1, 0),
            //But MaxDataServiceVersion must be 2.0 as respose will include
            //a nextlink for expanded 'Orders' property
            'MaxDataServiceVersion' => new Version(1, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for a paged top level result with  MaxDataServiceVersion 1.0 has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $ex->getMessage());
        }

        //Paging enabled for top level resource set and $top < pageSize => not require next link
        //so MaxDataServiceVersion 1.0 will work
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$top=2&$expand=Customer',
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(1, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();

        $this->assertNotNull($requestDescription);
        $this->assertEquals($requestDescription->isSingleResult(), false);
        $this->assertEquals($requestDescription->getTopCount(), 2);

        //has orderby info
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

        $projectionTreeRoot = $requestDescription->getRootProjectionNode();
        $this->assertNotNull($projectionTreeRoot);
        $this->assertTrue($projectionTreeRoot instanceof RootProjectionNode);

        //There will be one child nodes
        //Expand Projection Node => 'Customer'
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertNotNull($childNodes);
        $this->assertTrue(is_array($childNodes));
        //$this->assertEquals(count($childNodes), 1);
        $this->assertTrue(array_key_exists('Customer', $childNodes));
        $this->assertTrue($childNodes['Customer'] instanceof ExpandedProjectionNode);

        $customerExpandedNode = $childNodes['Customer'];
        //Sort info will not be there for expanded 'Customer' as its resource set reference
        $internalOrderByInfo = $customerExpandedNode->getInternalOrderByInfo();
        $this->assertNull($internalOrderByInfo);

    }

    /**
     * If paging is enabled expanded result is resource set (top level is resource set reference 
     * so no paging for top level resource) then client should request with
     * MaxDataServiceVersion >= 2.0     
     */
    public function testUriProcessorForPagedExpandedResourceSetWithMaxDataServiceVersion1_0()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(11)/Customer';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$expand=Orders',
            //DataServiceVersion can be 1.0 no issue
            'DataServiceVersion' => new Version(1, 0),
            //But MaxDataServiceVersion must be 2.0 as respose will include
            //a nextlink for expanded 'Orders' property
            'MaxDataServiceVersion' => new Version(1, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for an paged expanded result with  MaxDataServiceVersion 1.0 has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $ex->getMessage());
        }

    }

    /**
     * select and expand can be applied to request url identifying resource set reference
     * Here the top level resource will not be paged as its a resource set reference
     * But if there is an expansion that leads to resource set then paging will be required for
     * the expanded result means hould request with MaxDataServiceVersion 2_0     
     */
    public function testUriProcessorForSelectExpandOnResourceSetReference()
    {
        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(11)/Customer';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$expand=Orders&$select=CustomerID,Orders',
            //use of $select requires this header to 1.0
            'DataServiceVersion' => new Version(2, 0),
            //The expanded property will be paged, so skiptoken will be there
            //client says i can handle it
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);
        $this->assertEquals($requestDescription->isSingleResult(), true);

        //paging is not applicable for resource set reference 'Customer'
        $this->assertEquals($requestDescription->getTopCount(), null);

        //no orderby infor
        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNull($internalOrderByInfo);

        $projectionTreeRoot = $requestDescription->getRootProjectionNode();
        $this->assertNotNull($projectionTreeRoot);
        $this->assertTrue($projectionTreeRoot instanceof RootProjectionNode);

        //There will be two child nodes
        //Expand Projection Node => 'Orders'
        //Projection Node => 'CustomerID'
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertNotNull($childNodes);
        $this->assertTrue(is_array($childNodes));
        $this->assertEquals(count($childNodes), 2);
        $this->assertTrue(array_key_exists('Orders', $childNodes));
        $this->assertTrue($childNodes['Orders'] instanceof ExpandedProjectionNode);
        $this->assertTrue(array_key_exists('Orders', $childNodes));
        $this->assertTrue($childNodes['Orders'] instanceof ProjectionNode);

        $ordersExpandedNode = $childNodes['Orders'];

        //Sort info will be there for expanded 'Orders' as paging is
        //enabled for this resource set
        $internalOrderByInfo = $ordersExpandedNode->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);
        $this->assertTrue($internalOrderByInfo instanceof InternalOrderByInfo);
    }

    /**
     * select and expand can be applied to only request uri identifying a resource set
     * or resource set reference.
     */
    public function testUriProcessorForSelectExpandOnNonResourceSetOrReference()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(123)/Customer/Address';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$expand=Address2',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $expand on  non resource set or resource set refernce has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query option $expand cannot be applied to the requested resource', $ex->getMessage());
        }

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(123)/Customer/Address';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$select=LineNumber',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);
        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for $select on  non resource set or resource set refernce has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Query option $select cannot be applied to the requested resource', $ex->getMessage());
        }

    }

    /**
     * Test uri prcoessor for $skip and $top options
     */
    public function testUriProcessorForSkipAndTop()
    {
        //TODO: Break this apart into separate tests

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$top=\'ABC\'',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for incorrect $top value has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Incorrect format for \$top", $ex->getMessage());
        }


        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$top=-123',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for incorrect $top value has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Incorrect format for $top', $ex->getMessage());
        }


        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$skip=\'ABC\'',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for incorrect $skip value has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Incorrect format for \$skip", $ex->getMessage());
        }


        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$skip=-123',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );
        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        try {
            $dataService->handleRequest();
            $this->fail('An expected ODataException for incorrect $skip value has not been thrown');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Incorrect format for $skip', $ex->getMessage());
        }


    }

    /**
     * Test uri processor with all options
     */
    public function testUriProcessorWithBigQuery()
    {

        $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders(123)/Customer/Orders';
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            'AbsoluteServiceUri' => new Url($baseUri),
            'QueryString' => '$expand=Customer&$select=Customer,OrderDate&$filter=OrderID eq 123&$orderby=OrderDate&top=6&$skip=10&$skiptoken=datetime\'2000-11-11\',567',
            'DataServiceVersion' => new Version(2, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $requestDescription = $uriProcessor->getRequest();
        $this->assertEquals($requestDescription->getTopCount(), 5);
        $this->assertEquals($requestDescription->getSkipCount(), 10);

        $this->assertNotNull($requestDescription->getInternalOrderByInfo());
        $this->assertNotNull($requestDescription->getFilterInfo());
        $this->assertNotNull($requestDescription->getInternalSkipTokenInfo());
        $this->assertNotNull($requestDescription->getRootProjectionNode());

    }

    /**
     * test Request Description with all geter method.
     */
    public function testRequestDescription()
    {
        $hostInfo = array(
            'AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Orders'),
            'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
            'QueryString' => null,
            'DataServiceVersion' => new Version(1, 0),
            'MaxDataServiceVersion' => new Version(2, 0),
        );

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService2();
        $dataService->setHost($host);
        $uriProcessor = $dataService->handleRequest();

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $countValue = $requestDescription->getCountValue();
        $this->assertNull($countValue);

        $identifier = $requestDescription->getIdentifier();
        $this->assertNotNull($identifier);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNull($filterInfo);

        $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
        $this->assertNotNull($internalOrderByInfo);

        $internalSkipTokenInfo = $requestDescription->getInternalSkipTokenInfo();
        $this->assertNull($internalSkipTokenInfo);

        $knownDataServiceVersions = $requestDescription->getKnownDataServiceVersions();
        $this->assertNotNull($knownDataServiceVersions);

        $lastSegmentDescriptor = $requestDescription->getLastSegment();
        $this->assertNotNull($lastSegmentDescriptor);

        $projectedProperty = $requestDescription->getProjectedProperty();
        $this->assertNull($projectedProperty);


        $this->assertEquals(QueryType::ENTITIES(), $requestDescription->queryType);

        $requestUri = $requestDescription->getRequestUrl();
        $this->assertNotNull($requestUri);

        $resourceStreamInfo = $requestDescription->getResourceStreamInfo();
        $this->assertNull($resourceStreamInfo);

        $rootProjectionNode = $requestDescription->getRootProjectionNode();
        $this->assertNotNull($rootProjectionNode);

        $segmentDescriptors = $requestDescription->getSegments();
        $this->assertNotNull($segmentDescriptors);

        $skipCount = $requestDescription->getSkipCount();
        $this->assertNull($skipCount);

        $targetKind = $requestDescription->getTargetKind();
        $this->assertNotNull($targetKind);

        $targetResourceSetWrapper = $requestDescription->getTargetResourceSetWrapper();
        $this->assertNotNull($targetResourceSetWrapper);

        $targetResourceType = $requestDescription->getTargetResourceType();
        $this->assertNotNull($targetResourceType);

        $targetSource = $requestDescription->getTargetSource();
        $this->assertNotNull($targetSource);

        $topCount = $requestDescription->getTopCount();
        $this->assertNotNull($topCount);

    }

	/** @var  IService */
	protected $mockService;

	/** @var  ServiceHost */
	protected $mockServiceHost;

	/** @var  ServiceConfiguration */
	protected $mockServiceConfig;

	/** @var  IMetadataProvider */
	protected $mockMetadataProvider;

    /** @var  ProvidersWrapper */
    protected $mockProvidersWrapper;

    /** @var ResourceSetWrapper */
    protected $mockCollectionResourceSetWrapper;

	/** @var ResourceType */
	protected $mockCollectionResourceType;

	public function testProcessRequestForCollection()
	{
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		Phockito::when($this->mockService->getConfiguration())
			->return($this->mockServiceConfig);

		Phockito::when($this->mockServiceConfig->getMaxDataServiceVersion())
			->return(new Version(2, 0));


		$requestURI = new Url('http://host.com/data.svc/Collection');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

        Phockito::when($this->mockService->getProvidersWrapper())
            ->return($this->mockProvidersWrapper);


        Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
            ->return($this->mockCollectionResourceSetWrapper);


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


	public function testProcessRequestForCollectionCountThrowsWhenServiceVersionIs10()
	{
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		$requestURI = new Url('http://host.com/data.svc/Collection/$count');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

		Phockito::when($this->mockService->getProvidersWrapper())
			->return($this->mockProvidersWrapper);


		Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
			->return($this->mockCollectionResourceSetWrapper);

		Phockito::when($this->mockCollectionResourceSetWrapper->getResourceType())
			->return($this->mockCollectionResourceType);


		$fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
		$fakeServiceConfig->setAcceptCountRequests(true);
        $fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1()); //because this is V1 and $count requires V2, this will fail

		Phockito::when($this->mockService->getConfiguration())
			->return($fakeServiceConfig);


		try{
			UriProcessor::process($this->mockService);
			$this->fail("Expected exception not thrown");
		} catch(ODataException $ex) {
			$expected = Messages::requestVersionTooLow("1.0", "2.0");
			$this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
		}



	}

	public function testProcessRequestForCollectionCountThrowsWhenCountsAreDisabled()
	{
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		$requestURI = new Url('http://host.com/data.svc/Collection/$count');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

		Phockito::when($this->mockService->getProvidersWrapper())
			->return($this->mockProvidersWrapper);


		Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
			->return($this->mockCollectionResourceSetWrapper);

		Phockito::when($this->mockCollectionResourceSetWrapper->getResourceType())
			->return($this->mockCollectionResourceType);



		$fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
		$fakeServiceConfig->setAcceptCountRequests(false);
		Phockito::when($this->mockService->getConfiguration())
			->return($fakeServiceConfig);

		try{
			UriProcessor::process($this->mockService);
			$this->fail("Expected exception not thrown");
		} catch(ODataException $ex) {
			$expected = Messages::configurationCountNotAccepted();
			$this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
		}
	}

	public function testProcessRequestForCollectionCountProviderDoesNotHandlePaging()
	{
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		$requestURI = new Url('http://host.com/data.svc/Collection/$count');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

		Phockito::when($this->mockService->getProvidersWrapper())
			->return($this->mockProvidersWrapper);


		Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
			->return($this->mockCollectionResourceSetWrapper);


		Phockito::when($this->mockCollectionResourceSetWrapper->getResourceType())
			->return($this->mockCollectionResourceType);



		$fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
		$fakeServiceConfig->setAcceptCountRequests(true);
		$fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());
		Phockito::when($this->mockService->getConfiguration())
			->return($fakeServiceConfig);



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
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		$requestURI = new Url('http://host.com/data.svc/Collection/$count');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

		Phockito::when($this->mockService->getProvidersWrapper())
			->return($this->mockProvidersWrapper);


		Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
			->return($this->mockCollectionResourceSetWrapper);


		Phockito::when($this->mockCollectionResourceSetWrapper->getResourceType())
			->return($this->mockCollectionResourceType);


		$fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
		$fakeServiceConfig->setAcceptCountRequests(true);
		$fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());
		Phockito::when($this->mockService->getConfiguration())
			->return($fakeServiceConfig);



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


	public function testProcessRequestForCollectionWithInlineCountWhenCountsAreDisabled()
	{
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		$requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		//mock inline count as all pages
		Phockito::when($this->mockServiceHost->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_INLINECOUNT ))
			->return("allpages");

		$fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
		$fakeServiceConfig->setAcceptCountRequests(false);
		//$fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2);
		Phockito::when($this->mockService->getConfiguration())
			->return($fakeServiceConfig);


		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

		Phockito::when($this->mockService->getProvidersWrapper())
			->return($this->mockProvidersWrapper);


		Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
			->return($this->mockCollectionResourceSetWrapper);



		try{
			UriProcessor::process($this->mockService);
			$this->fail("Expected exception not thrown");
		} catch(ODataException $ex) {
			$expected = Messages::configurationCountNotAccepted();
			$this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
		}
	}

	public function testProcessRequestForCollectionWithInlineCountWhenServiceVersionIs10()
	{
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		$requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		//mock inline count as all pages
		Phockito::when($this->mockServiceHost->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_INLINECOUNT ))
			->return("allpages");

		$fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
		$fakeServiceConfig->setAcceptCountRequests(true);
		$fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1());
		Phockito::when($this->mockService->getConfiguration())
			->return($fakeServiceConfig);


		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

		Phockito::when($this->mockService->getProvidersWrapper())
			->return($this->mockProvidersWrapper);


		Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
			->return($this->mockCollectionResourceSetWrapper);


		try{
			UriProcessor::process($this->mockService);
			$this->fail("Expected exception not thrown");
		} catch(ODataException $ex) {
			$expected = Messages::requestVersionTooLow("1.0", "2.0");
			$this->assertEquals($expected, $ex->getMessage(), $ex->getTraceAsString());
		}
	}


	public function testProcessRequestForCollectionWithNoInlineCountWhenVersionIsTooLow()
	{
		//I'm not so sure about this test...basically $inlinecount is ignored if it's none, but maybe we should
		//be throwing an exception?
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		$requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=none');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		//mock inline count as all pages
		Phockito::when($this->mockServiceHost->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_INLINECOUNT ))
			->return("none");

		$fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
		$fakeServiceConfig->setAcceptCountRequests(true);
		$fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V1());
		Phockito::when($this->mockService->getConfiguration())
			->return($fakeServiceConfig);


		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

		Phockito::when($this->mockService->getProvidersWrapper())
			->return($this->mockProvidersWrapper);


		Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
			->return($this->mockCollectionResourceSetWrapper);


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


	public function testProcessRequestForCollectionWithInlineCountProviderDoesNotHandlePaging()
	{
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		$requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		//mock inline count as all pages
		Phockito::when($this->mockServiceHost->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_INLINECOUNT ))
			->return("allpages");

		$fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
		$fakeServiceConfig->setAcceptCountRequests(true);
		$fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());
		Phockito::when($this->mockService->getConfiguration())
			->return($fakeServiceConfig);


		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

		Phockito::when($this->mockService->getProvidersWrapper())
			->return($this->mockProvidersWrapper);


		Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
			->return($this->mockCollectionResourceSetWrapper);



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
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		$requestURI = new Url('http://host.com/data.svc/Collection/?$inlinecount=allpages');
		Phockito::when($this->mockServiceHost->getAbsoluteRequestUri())
			->return($requestURI);

		//mock inline count as all pages
		Phockito::when($this->mockServiceHost->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_INLINECOUNT ))
			->return("allpages");

		$fakeServiceConfig = new ServiceConfiguration($this->mockMetadataProvider);
		$fakeServiceConfig->setAcceptCountRequests(true);
		$fakeServiceConfig->setMaxDataServiceVersion(ProtocolVersion::V2());
		Phockito::when($this->mockService->getConfiguration())
			->return($fakeServiceConfig);


		$serviceURI = new Url("http://host.com/data.svc");
		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($serviceURI);

		Phockito::when($this->mockService->getProvidersWrapper())
			->return($this->mockProvidersWrapper);


		Phockito::when($this->mockProvidersWrapper->resolveResourceSet("Collection"))
			->return($this->mockCollectionResourceSetWrapper);



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




}