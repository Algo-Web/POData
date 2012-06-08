<?php
/**
 * Mainly test UriProcessor, but also do some partial test for DataService class.
 */
use ODataProducer\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode;
use ODataProducer\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use ODataProducer\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use ODataProducer\UriProcessor\QueryProcessor\AnonymousFunction;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\OrderBySubPathSegment;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use ODataProducer\UriProcessor\QueryProcessor\SkipTokenParser\InternalSkipTokenInfo;
use ODataProducer\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\InternalFilterInfo;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use ODataProducer\UriProcessor\RequestCountOption;
use ODataProducer\Configuration\DataServiceProtocolVersion;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetSource;
use ODataProducer\Providers\Metadata\Type\Int32;
use ODataProducer\Providers\Metadata\Type\DateTime;
use ODataProducer\Common\Url;
use ODataProducer\Common\Version;
use ODataProducer\Common\ODataException;
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
require_once (dirname(__FILE__) . "\.\..\Resources\NorthWindMetadata.php");
require_once (dirname(__FILE__) . "\.\..\Resources\NorthWindDataServiceV1.php");
require_once (dirname(__FILE__) . "\.\..\Resources\NorthWindDataService.php");
require_once (dirname(__FILE__) . "\.\..\Resources\NorthWindDataServiceV3.php");
require_once (dirname(__FILE__) . "\.\..\Resources\DataServiceHost2.php");
ODataProducer\Common\ClassAutoLoader::register();
class TestUriProcessor extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    /**
     * The request uri should be based on the service uri specified in the configuration.
     */
    /**public function testRequestUriWithInvalidBaseUri()
    {
        try {
            $hostInfo = array('AbsoluteRequestUri' => 
                                    new Url('http://localhost:8083/NorthWindDataService.svc'), 
				      		  'AbsoluteServiceUri' => 
                                    new Url('http://localhost:8083/XX/NorthWindDataService.svc'),
				  		      'QueryString' => 
                                    null);
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;
                $this->assertStringStartsWith("The URI 'http://localhost:8083/NorthWindDataService.svc' is not ", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for invalid base uri in the request uri has not been thrown');
            }
                        
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
        
        
    }**/

    /**
     * Test with request uri where RequestTargetKind is NONE. RequestTargetKind will be
     * NONE for service directory, metadata and batch.
     */
    public function testUriProcessorWithRequestUriOfNoneTargetSourceKind()
    {
        try {
            //Request for service directory
            $hostInfo = array('AbsoluteRequestUri' => 
                                    new Url('http://localhost:8083/NorthWindDataService.svc'), 
				      		  'AbsoluteServiceUri' => 
                                    new Url('http://localhost:8083/NorthWindDataService.svc'),
				  		      'QueryString' => 
                                    null);
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;           
            $uriProcessor = $dataService->handleRequest();
            $requestDescripton = $uriProcessor->getRequestDescription();
            $this->assertEquals($requestDescripton->getTargetSource(), RequestTargetSource::NONE);
            $this->assertEquals($requestDescripton->getTargetKind(), RequestTargetKind::SERVICE_DIRECTORY);
            // Context is a singleton class reset it
            $host->getWebOperationContext()->resetWebContextInternal();
            
            //Request for metadata
            $hostInfo = array('AbsoluteRequestUri' => 
                                    new Url('http://localhost:8083/NorthWindDataService.svc/$metadata'), 
				      		  'AbsoluteServiceUri' => 
                                    new Url('http://localhost:8083/NorthWindDataService.svc'),
				  		      'QueryString' => 
                                    null);
                                    
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;   
            $requestDescripton = $uriProcessor = null;        
            $uriProcessor = $dataService->handleRequest();
            $requestDescripton = $uriProcessor->getRequestDescription();            
            $this->assertEquals($requestDescripton->getTargetSource(), RequestTargetSource::NONE);
            $this->assertEquals($requestDescripton->getTargetKind(), RequestTargetKind::METADATA);
            // Context is a singleton class reset it
            $host->getWebOperationContext()->resetWebContextInternal();
            
            //Request for batch
            $hostInfo = array('AbsoluteRequestUri' => 
                                    new Url('http://localhost:8083/NorthWindDataService.svc/$batch'), 
				      		  'AbsoluteServiceUri' => 
                                    new Url('http://localhost:8083/NorthWindDataService.svc'),
				  		      'QueryString' => 
                                    null);
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;           
            $uriProcessor = $dataService->handleRequest();
            $requestDescripton = $uriProcessor->getRequestDescription();
            $this->assertEquals($requestDescripton->getTargetSource(), RequestTargetSource::NONE);
            $this->assertEquals($requestDescripton->getTargetKind(), RequestTargetKind::BATCH);
            // Context is a singleton class reset it
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        } 
    }

    /**
     * Test request uri for row count ($count)     
     * DataServiceVersion and MaxDataServiceVersion should be >= 2.0 for $count
     */
    public function testUriProcessorForCountRequest1()
    {
        try {
            //Test $count with DataServiceVersion < 2.0
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => null,
                  	  'DataServiceVersion' => new Version(1, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $uriProcessor = $dataService->handleRequest();                
            } catch (ODataException $odataException) {                
                $exceptionThrown = true;                
                $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for failure of capability negoitation over DataServiceVersion has not been thrown');
            }

            // Context is a singleton class reset it
            $host->getWebOperationContext()->resetWebContextInternal();
            //Test $count with MaxDataServiceVersion < 2.0
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => null,
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(1, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $uriProcessor = $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;
                $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for failure of capability negoitation over MaxDataServiceVersion has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());            
        }
    }

    /**
     * Test request uri for row count ($count)     
     * $count is a version 2 feature so service devloper should use protocol version 2.0
     */
    public function testUriProcessorForCountRequest2()
    {
        try {            
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => null,
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataServiceV1();                
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $uriProcessor = $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith("The response requires that version 2.0 of the protocol be used, but the MaxProtocolVersion of the data service is set to 1.0", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for failure of capability negoitation due to V1 configuration has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * 
     * Suppose $top option is absent, still
     * RequestDescription::topCount will be set if the resource targetted by the
     * uri has paging enabled, if RequestDescription::topCount 
     * is set then internal orderby info will be generated. But if the request 
     * is for raw count for a resource collection then paging is not applicable
     * for that, so topCount will be null and internal orderby info will not be 
     * generated.
     */
    public function testUriProcessorForCountRequest3()
    {
        try {            
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => null,
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();                   
            $dataService->setHost($host);
            $exceptionThrown = false;            
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertTrue(is_null($requestDescription->getInternalOrderByInfo()));
            $host->getWebOperationContext()->resetWebContextInternal();
            
        } catch (\Exception $exception) {            
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $orderby option can be applied to a $count request.
     */
    public function testUriProcessorForCountRequest4()
    {
        try {            
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => '$orderby=Country',
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();                   
            $dataService->setHost($host);
            $exceptionThrown = false;            
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $object = $internalOrderByInfo->getDummyObject();
            $this->assertTrue(!is_null($object));
            $this->assertTrue($object instanceof Customer2);
            $host->getWebOperationContext()->resetWebContextInternal();
            
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $skip and $top options can be applied to $count request, this cause
     * processor to generate internalorderinfo.
     */
    public function testUriProcessorForCountRequest5()
    {
        try {            
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => '$skip=2&$top=4',
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();                   
            $dataService->setHost($host);
            $exceptionThrown = false;            
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->getTopCount(), 4);
            $this->assertEquals($requestDescription->getSkipCount(), 2);
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $object = $internalOrderByInfo->getDummyObject();
            $this->assertTrue(!is_null($object));
            $this->assertTrue($object instanceof Customer2);
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $skip and/or $top options along with $orderby option cause internalOrderInfo
     * to include sorter functions using keys + paths in the $orderby clause
     */
    public function testUriProcessorForCountRequest6()
    {
        try {            
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => '$skip=2&$top=4&$orderby=Country',
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();                   
            $dataService->setHost($host);
            $exceptionThrown = false;            
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->getTopCount(), 4);
            $this->assertEquals($requestDescription->getSkipCount(), 2);
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $object = $internalOrderByInfo->getDummyObject();
            $this->assertTrue(!is_null($object));
            $this->assertTrue($object instanceof Customer2);
            $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
            $this->assertTrue(!is_null($pathSegments));
            $this->assertTrue(is_array($pathSegments));
            $this->assertEquals(count($pathSegments), 3);
            $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[0]->getSubPathSegments();
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'Country');
            
            $this->assertTrue($pathSegments[1] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[1]->getSubPathSegments();
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'CustomerID');
            
            $this->assertTrue($pathSegments[2] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[2]->getSubPathSegments();
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'CustomerGuid');
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * $skiptoken is not applicable for $count request, as it requires
     * paging and paging is not applicable for $count request
     */
    public function testUriProcessorForCountRequest7()
    {
        try {            
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => '$top=1&$skiptoken=\'ALFKI\',guid\'05b242e752eb46bd8f0e6568b72cd9a5\'',
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $uriProcessor = $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;
                $this->assertStringStartsWith("Query option \$skiptoken cannot be applied to the requested resource", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for applying $skiptoken on $count has not been thrown');
            }

            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $filter is applicable for $count segment.
     */
    public function testUriProcessorForCountRequest8()
    {
        try {            
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => '$filter=Country eq \'USA\'',
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();                   
            $dataService->setHost($host);                  
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $internalFilterInfo = $requestDescription->getInternalFilterInfo();
            $this->assertTrue(!is_null($internalFilterInfo));
            $filterInfo = $internalFilterInfo->getFilterInfo();
            $this->assertTrue(!is_null($filterInfo));
            $this->assertTrue(is_null($filterInfo->getNavigationPropertiesUsed()));
            $filterFunction = $internalFilterInfo->getFilterFunction();
            $this->assertTrue(!is_null($filterFunction));
            $this->assertTrue($filterFunction instanceof  AnonymousFunction);
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * 
     * $select and $expand options are applicable for $count segment.
     * but when we do query execution we will ignore them.
     */
    public function testUriProcessorForCountRequest9()
    {
        try {            
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => '$select=Country&$expand=Orders',
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();                   
            $dataService->setHost($host);                  
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $projectionTreeRoot = $requestDescription->getRootProjectionNode();
            $this->assertTrue(!is_null($projectionTreeRoot));
            $this->assertTrue($projectionTreeRoot instanceof RootProjectionNode);
            //There will be one child node for 'Country', 'Orders' wont be included
            //as its not selected
            $childNodes = $projectionTreeRoot->getChildNodes();
            $this->assertTrue(!is_null($childNodes));
            $this->assertTrue(is_array($childNodes));
            $this->assertEquals(count($childNodes), 1);
            $this->assertTrue(array_key_exists('Country', $childNodes));
            $this->assertTrue($childNodes['Country'] instanceof ProjectionNode);
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test request uri for row count ($count)     
     * $count with $inlinecount not allowed
     */
    public function testUriProcessorForCountWithInline()
    {
        try {            
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers/$count'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => '$inlinecount=allpages',
                  	  'DataServiceVersion' => new Version(2, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $uriProcessor = $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;
                $this->assertStringStartsWith("\$inlinecount cannot be applied to the resource segment \$count", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for applying $skiptoken on $count has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
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
        try {
            //Test for generation of orderinfo for resource set
            //with request DataServiceVersion 1.0
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => null,
                  	  'DataServiceVersion' => new Version(1, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            //Page size is 5, so take count is 5
            $this->assertEquals($requestDescription->getTopCount(), 5);
            
            //order info is required for pagination
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
            $this->assertTrue(!is_null($pathSegments));
            $this->assertTrue(is_array($pathSegments));
            //Customer has two keys
            $this->assertEquals(count($pathSegments), 2);
            $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[0]->getSubPathSegments();
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'CustomerID');
            
            $this->assertTrue($pathSegments[1] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[1]->getSubPathSegments();
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'CustomerGuid');
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
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
        try {
            //Test for generation of orderinfo for resource set
            //with request DataServiceVersion 1.0
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Customers'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => null,
                  	  'DataServiceVersion' => new Version(1, 0),
                  	  'MaxDataServiceVersion' => new Version(1, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $uriProcessor = $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException due to capability negotiation has not been thrown (paged result but client\'s max supportedd version is 1.0)');
            }
            $host->getWebOperationContext()->resetWebContextInternal();    
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Paging is enabled only for resource set, so with resource set
     * reference there will not be any paginginfo.
     */
    public function testUriProcessorForResourcePageInfo3()
    {
        try {
            //Test for generation of orderinfo for resource set
            //with request DataServiceVersion 1.0
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Orders(123)'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => null,
                  	  'DataServiceVersion' => new Version(1, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            //Page is not appliable for single resouce
            $this->assertEquals($requestDescription->getTopCount(), null);            
            //order info wont be generated as resource is not applicable for pagination
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(is_null($internalOrderByInfo));
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
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
        try {
            //Test for generation of orderinfo for resource set in $links query
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => null,
                  	  			'DataServiceVersion' => new Version(1, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            //Page size is 5, so take count is 5
            $this->assertEquals($requestDescription->getTopCount(), 5);            
            //order info is required for pagination
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
            $this->assertTrue(!is_null($pathSegments));
            $this->assertTrue(is_array($pathSegments));
            //Order has one key
            $this->assertEquals(count($pathSegments), 1);
            $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[0]->getSubPathSegments();
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $orderby option can be applied to $links resource set
     */
    public function testUriProcessorForLinksResourceSet1()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$orderby=ShipName asc, OrderDate desc',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
             $this->assertEquals($requestDescription->isSingleResult(), false); 
            //Page size is 5, so take count is 5 means you will get only 5 links for a request
            $this->assertEquals($requestDescription->getTopCount(), 5); 
            //Paging requires ordering, the result should be ordered like
            //Note: additional ordering constraint
            //
            //SELECT links(d.orderID) FROM Customers JOIN Orders WHERE CustomerID='ALFKI' AND 
            //CustomerGuid=guid'05b242e752eb46bd8f0e6568b72cd9a5' ORDER BY
            //d.ShipName ASC, d.OrderDate DESC, d.OrderID ASC

            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
            $this->assertTrue(!is_null($pathSegments));
            $this->assertTrue(is_array($pathSegments));            
            $this->assertEquals(count($pathSegments), 3);
            $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[0]->getSubPathSegments();
            $this->assertTrue($pathSegments[0]->isAscending());
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'ShipName');
            $this->assertTrue($pathSegments[1] instanceof OrderByPathSegment);
            $this->assertFalse($pathSegments[1]->isAscending());
            $subPathSegments = $pathSegments[1]->getSubPathSegments();
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'OrderDate');
            $this->assertTrue($pathSegments[2] instanceof OrderByPathSegment);
            $this->assertTrue($pathSegments[2]->isAscending());
            $subPathSegments = $pathSegments[2]->getSubPathSegments();
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $skiptoken option can be applied to $links resource set
     */
    public function testUriProcessorForLinksResourceSet2()
    {
        try {
            //Test with skiptoken that corrosponds to default ordering key
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$skiptoken=123',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
             $this->assertEquals($requestDescription->isSingleResult(), false); 
            //Page size is 5, so take count is 5 means you will get only 5 links for a request
            $this->assertEquals($requestDescription->getTopCount(), 5); 
            //paging requires ordering
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
            $this->assertTrue(!is_null($pathSegments));
            $this->assertTrue(is_array($pathSegments));            
            $this->assertEquals(count($pathSegments), 1);
            $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[0]->getSubPathSegments();
            $this->assertTrue($pathSegments[0]->isAscending());
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');
            //check the skiptoken details
            $internalSkiptokenInfo = $requestDescription->getInternalSkipTokenInfo();
            $this->assertTrue(!is_null($internalSkiptokenInfo));
            $this->assertTrue($internalSkiptokenInfo instanceof InternalSkipTokenInfo);
            $skipTokenInfo = $internalSkiptokenInfo->getSkipTokenInfo();
            $this->assertTrue(!is_null($skipTokenInfo));
            $this->assertTrue($skipTokenInfo instanceof SkipTokenInfo);
            $orderByValuesInSkipToken = $skipTokenInfo->getOrderByKeysInToken();
            $this->assertTrue(!is_null($orderByValuesInSkipToken));
            $this->assertTrue(is_array($orderByValuesInSkipToken));
            $this->assertEquals(count($orderByValuesInSkipToken), 1);
            $this->assertTrue(!is_null($orderByValuesInSkipToken[0]));
            $this->assertTrue(is_array($orderByValuesInSkipToken[0]));
            $this->assertEquals(count($orderByValuesInSkipToken[0]), 2);
            $this->assertEquals($orderByValuesInSkipToken[0][0], 123);
            $this->assertTrue(is_object($orderByValuesInSkipToken[0][1]));
            $this->assertTrue($orderByValuesInSkipToken[0][1] instanceof Int32);
            $host->getWebOperationContext()->resetWebContextInternal();
            
            //Test with skiptoken that corrosponds to explict ordering keys
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$orderby=OrderID asc, OrderDate desc&$skiptoken=123, datetime\'2000-11-11\'',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
             $this->assertEquals($requestDescription->isSingleResult(), false); 
            //Page size is 5, so take count is 5 means you will get only 5 links for a request
            $this->assertEquals($requestDescription->getTopCount(), 5); 
            //paging requires ordering
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
            $this->assertTrue(!is_null($pathSegments));
            $this->assertTrue(is_array($pathSegments));
            $this->assertEquals(count($pathSegments), 2);
            $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[0]->getSubPathSegments();
            $this->assertTrue($pathSegments[0]->isAscending());
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');
            $this->assertTrue($pathSegments[1] instanceof OrderByPathSegment);            
            $this->assertFalse($pathSegments[1]->isAscending());
            $subPathSegments = $pathSegments[1]->getSubPathSegments();
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'OrderDate');
            //check the skiptoken details
            $internalSkiptokenInfo = $requestDescription->getInternalSkipTokenInfo();
            $this->assertTrue(!is_null($internalSkiptokenInfo));
            $this->assertTrue($internalSkiptokenInfo instanceof InternalSkipTokenInfo);
            $skipTokenInfo = $internalSkiptokenInfo->getSkipTokenInfo();
            $this->assertTrue(!is_null($skipTokenInfo));
            $this->assertTrue($skipTokenInfo instanceof SkipTokenInfo);
            $orderByValuesInSkipToken = $skipTokenInfo->getOrderByKeysInToken();
            $this->assertTrue(!is_null($orderByValuesInSkipToken));
            $this->assertTrue(is_array($orderByValuesInSkipToken));
            $this->assertEquals(count($orderByValuesInSkipToken), 2);
            $this->assertTrue(!is_null($orderByValuesInSkipToken[0]));
            $this->assertTrue(is_array($orderByValuesInSkipToken[0]));
            $this->assertEquals(count($orderByValuesInSkipToken[0]), 2);
            $this->assertEquals($orderByValuesInSkipToken[0][0], 123);
            $this->assertTrue(is_object($orderByValuesInSkipToken[0][1]));
            $this->assertTrue($orderByValuesInSkipToken[0][1] instanceof Int32);
            $this->assertTrue(!is_null($orderByValuesInSkipToken[1]));
            $this->assertTrue(is_array($orderByValuesInSkipToken[1]));
            $this->assertEquals(count($orderByValuesInSkipToken[1]), 2);
            $this->assertEquals($orderByValuesInSkipToken[1][0], '\'2000-11-11\'');
            $this->assertTrue(is_object($orderByValuesInSkipToken[1][1]));
            $this->assertTrue($orderByValuesInSkipToken[1][1] instanceof DateTime);
            $host->getWebOperationContext()->resetWebContextInternal();

        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
           $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $top and $skip option can be applied to $links resource set
     */
    public function testUriProcessorForLinksResourceSet3()
    {
         try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$skip=1',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), false);
            //$skip has been specified
            $this->assertEquals($requestDescription->getSkipCount(), 1);   
            //Page size is 5, so take count is 5 means you will get only 5 links for a request
            $this->assertEquals($requestDescription->getTopCount(), 5); 
            //paging requires ordering
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
            $this->assertTrue(!is_null($pathSegments));
            $this->assertTrue(is_array($pathSegments));            
            $this->assertEquals(count($pathSegments), 1);
            $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[0]->getSubPathSegments();
            $this->assertTrue($pathSegments[0]->isAscending());
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');
            $host->getWebOperationContext()->resetWebContextInternal();
            //specification of a $top value less than pagesize also need sorting,
            //$skiptoken also applicable, only thing is nextlink will be absent
          
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$top=4&$skiptoken=1234',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), false);
            //$skip has not been specified
            $this->assertEquals($requestDescription->getSkipCount(), null);   
            //top is specified and is less than page size
            $this->assertEquals($requestDescription->getTopCount(), 4); 
            //top requires ordering
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $pathSegments = $internalOrderByInfo->getOrderByPathSegments();
            $this->assertTrue(!is_null($pathSegments));
            $this->assertTrue(is_array($pathSegments));            
            $this->assertEquals(count($pathSegments), 1);
            $this->assertTrue($pathSegments[0] instanceof OrderByPathSegment);
            $subPathSegments = $pathSegments[0]->getSubPathSegments();
            $this->assertTrue($pathSegments[0]->isAscending());
            $this->assertTrue(!is_null($subPathSegments));
            $this->assertTrue(is_array($subPathSegments));
            $this->assertEquals(count($subPathSegments), 1);
            $this->assertTrue($subPathSegments[0] instanceof OrderBySubPathSegment);
            $this->assertEquals($subPathSegments[0]->getName(), 'OrderID');
            //$skiptoken is specified
            $internalSkiptokenInfo = $requestDescription->getInternalSkipTokenInfo();
            $this->assertTrue(!is_null($internalSkiptokenInfo));
            $this->assertTrue($internalSkiptokenInfo instanceof InternalSkipTokenInfo);
            $skipTokenInfo = $internalSkiptokenInfo->getSkipTokenInfo();
            $this->assertTrue($skipTokenInfo instanceof SkipTokenInfo);
            $orderByValuesInSkipToken = $skipTokenInfo->getOrderByKeysInToken();
            $this->assertTrue(!is_null($orderByValuesInSkipToken));
            $this->assertTrue(is_array($orderByValuesInSkipToken));
            $this->assertEquals(count($orderByValuesInSkipToken), 1);
            $this->assertTrue(!is_null($orderByValuesInSkipToken[0]));
            $this->assertTrue(is_array($orderByValuesInSkipToken[0]));
            $this->assertEquals(count($orderByValuesInSkipToken[0]), 2);
            $this->assertEquals($orderByValuesInSkipToken[0][0], 1234);
            $this->assertTrue(is_object($orderByValuesInSkipToken[0][1]));
            $this->assertTrue($orderByValuesInSkipToken[0][1] instanceof Int32);
            $host->getWebOperationContext()->resetWebContextInternal();
            //specification of a $top value greater than pagesize          
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$top=10&$skiptoken=1234',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), false);
            //$skip has not been specified
            $this->assertEquals($requestDescription->getSkipCount(), null);   
            //top is specified and is greater than page size, so take count should be page size
            $this->assertEquals($requestDescription->getTopCount(), 5); 
            //top requires ordering
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            //$skiptoken is specified
            $internalSkiptokenInfo = $requestDescription->getInternalSkipTokenInfo();
            $this->assertTrue(!is_null($internalSkiptokenInfo));
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $filter option can be applied to $links resource set
     */
    public function testUriProcessorForLinksResourceSet4()
    {
       try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$filter=OrderID eq 123 and OrderDate le datetime\'2000-11-11\'',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), false);
            $this->assertEquals($requestDescription->getTopCount(), 5); 
            //paging enabled
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            //$filter applied
            $internalFilterInfo = $requestDescription->getInternalFilterInfo();
            $this->assertTrue(!is_null($internalFilterInfo));
            $this->assertTrue($internalFilterInfo instanceof InternalFilterInfo);
            $filterFunction = $internalFilterInfo->getFilterFunction();
            $this->assertTrue(!is_null($filterFunction));
            $this->assertTrue($filterFunction instanceof AnonymousFunction);
            $code = $filterFunction->getCode();            
            $this->assertEquals($code, 
            'if(((!(is_null($lt->OrderID)) && !(is_null($lt->OrderDate))) && (($lt->OrderID == 123) && (ODataProducer\Providers\Metadata\Type\DateTime::dateTimeCmp($lt->OrderDate, \'2000-11-11\') <= 0)))) { return true; } else { return false;}');
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $inlinecount can be applied to $links identifying resource set     
     */
    public function testUriProcessorForLinksResourceSet5()
    {
        try {
                $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
                $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders';
                $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
	    	  	    		'AbsoluteServiceUri' => new Url($baseUri),
		      		    	'QueryString' => '$inlinecount=allpages',
          	  				'DataServiceVersion' => new Version(2, 0),
          	  				'MaxDataServiceVersion' => new Version(2, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), false);
            $this->assertEquals($requestDescription->getTopCount(), 5); 
            //paging enabled
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            //count mode is all pages
            $this->assertEquals($requestDescription->getRequestCountOption(), RequestCountOption::INLINE);
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $filter option can be applied to $links resource set reference
     */
    public function testUriProcessorForLinksResourceSetReference1()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$filter=OrderID eq 123 and OrderDate le datetime\'2000-11-11\'',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), true);
            $this->assertEquals($requestDescription->getTopCount(), null); 
            $this->assertEquals($requestDescription->getSkipCount(), null); 
            //paging not applicable enabled
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(is_null($internalOrderByInfo));
            //$filter applied
            $internalFilterInfo = $requestDescription->getInternalFilterInfo();
            $this->assertTrue(!is_null($internalFilterInfo));
            $this->assertTrue($internalFilterInfo instanceof InternalFilterInfo);
            $filterFunction = $internalFilterInfo->getFilterFunction();
            $this->assertTrue(!is_null($filterFunction));
            $this->assertTrue($filterFunction instanceof AnonymousFunction);
            $code = $filterFunction->getCode();            
            $this->assertEquals($code, 
            'if(((!(is_null($lt->OrderID)) && !(is_null($lt->OrderDate))) && (($lt->OrderID == 123) && (ODataProducer\Providers\Metadata\Type\DateTime::dateTimeCmp($lt->OrderDate, \'2000-11-11\') <= 0)))) { return true; } else { return false;}');
            $host->getWebOperationContext()->resetWebContextInternal();
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(1234)/$links/Customer';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$filter=true',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), true);
            $this->assertEquals($requestDescription->getTopCount(), null); 
            $this->assertEquals($requestDescription->getSkipCount(), null); 
            //paging not applicable enabled
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(is_null($internalOrderByInfo));
            //$filter applied
            $internalFilterInfo = $requestDescription->getInternalFilterInfo();
            $this->assertTrue(!is_null($internalFilterInfo));
            $this->assertTrue($internalFilterInfo instanceof InternalFilterInfo);
            $filterFunction = $internalFilterInfo->getFilterFunction();
            $this->assertTrue(!is_null($filterFunction));
            $this->assertTrue($filterFunction instanceof AnonymousFunction);
            $code = $filterFunction->getCode();                        
            $this->assertEquals($code, 'if(true) { return true; } else { return false;}');
            $host->getWebOperationContext()->resetWebContextInternal();

        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $orderby option cannot be applied to $links resource set reference
     */
    public function testUriProcessorForLinksResourceSetReference2()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$orderby=OrderID',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $orderby query option on non-set has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(1234)/$links/Customer';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			        	  			'AbsoluteServiceUri' => new Url($baseUri),
				          			'QueryString' => '$orderby=CustomerID',
                  	      			'DataServiceVersion' => new Version(2, 0),
                  	  	    		'MaxDataServiceVersion' => new Version(2, 0));           
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $orderby query option on non-set has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $skiptoken option cannot be applied to $links resource set reference
     */
   public function testUriProcessorForLinksResourceSetReference3()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$skiptoken=345',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('Query option $skiptoken cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $skiptoken query option on non-set has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $top and $skip option cannot be applied to $links resource set reference
     */
    public function testUriProcessorForLinksResourceSetReference4()
    {
         try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$skip=1',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $skip query option on non-set has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(234)';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$top=4',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $top query option on non-set has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();    
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $inlinecount option cannot be applied to $links resource set reference
     */
    public function testUriProcessorForLinksResourceSetReference5()
    {
         try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
	    	  			'AbsoluteServiceUri' => new Url($baseUri),
		      			'QueryString' => '$inlinecount=allpages',
          	  			'DataServiceVersion' => new Version(2, 0),
          	  			'MaxDataServiceVersion' => new Version(2, 0));
            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $inlinecount query option on non-set has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();

        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $expand, $select option cannot be applied to $links resource set reference or $link resource set
     */
    public function testUriProcessorForLinksResource()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$expand=Order_Details',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                               
                $this->assertStringStartsWith('Query option $expand cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $expand query option on $link resource has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Customers(CustomerID=\'ALFKI\', CustomerGuid=guid\'05b242e752eb46bd8f0e6568b72cd9a5\')/$links/Orders(123)';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$select=OrderID',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                               
                $this->assertStringStartsWith('Query option $select cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $select query option on $link resource has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $inline count is not supported for protocol version V1
     */
    public function testUriProcessorForInlineCount1()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Products(11)/Order_Details';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
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
          	  				'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataServiceV1();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                               
                $this->assertStringStartsWith('The response requires that version 2.0 of the protocol be used, but the MaxProtocolVersion of the data service is set to 1.0', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $inlinecount query option with V1 configured service has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * For $inline request, client's DataServiceVersion header must be >= 2.0
     */
    public function testUriProcessorForInlineCount2()
    {
            try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Products(11)/Order_Details';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$inlinecount=allpages',
          	  				'DataServiceVersion' => new Version(1, 0),
          	  				'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                               
                $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $inlinecount query option request DataServiceVersion 1.0 has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * For $inline request, client's MaxDataServiceVersion header must be >= 2.0
     */
    public function testUriProcessorForInlineCount3()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Products(11)/Order_Details';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$inlinecount=allpages',
          	  				'DataServiceVersion' => new Version(2, 0),
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                               
                $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for $inlinecount query option with request DataServiceVersion 1.0 has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * only supported $inlinecount valus are 'allpages' and 'none'
     */
    public function testUriProcessorForInlineCount4()
    {
         try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Products(11)/Order_Details';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$inlinecount=partialpages',
          	  				'DataServiceVersion' => new Version(2, 0),
          	  				'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                               
                $this->assertStringStartsWith('Unknown $inlinecount option, only "allpages" and "none" are supported', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for invalid $inlinecount query option has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $filter can be applied on complex resource
     */
    public function testUriProcessorForFilterOnComplex()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(123)/Customer/Address';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$filter=HouseNumber eq null',
          	  				'DataServiceVersion' => new Version(1, 0),
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;            
            $dataService->handleRequest();
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), true);             
            //$filter applied
            $internalFilterInfo = $requestDescription->getInternalFilterInfo();
            $this->assertTrue(!is_null($internalFilterInfo));
            $this->assertTrue($internalFilterInfo instanceof InternalFilterInfo);
            $filterFunction = $internalFilterInfo->getFilterFunction();
            $this->assertTrue(!is_null($filterFunction));
            $this->assertTrue($filterFunction instanceof AnonymousFunction);
            $code = $filterFunction->getCode();    
            $this->assertTrue(is_null($requestDescription->getRootProjectionNode()));
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $filter cannot be applied on primitve resource
     */
   public function testUriProcessorForFilterOnPrimitiveType()
    {
             try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Products(11)/ProductID';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$filter=true',
          	  				'DataServiceVersion' => new Version(1, 0),
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                   $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                               
                $this->assertStringStartsWith('Query option $filter cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for $filter query on primitve  has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $filter cannot be applied on bag resource
     */
    public function testUriProcessorForFilterOnBag()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Employees(\'EMP1\')/Emails';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$filter=true',
          	  				'DataServiceVersion' => new Version(3, 0),
          	  				'MaxDataServiceVersion' => new Version(3, 0));
            $host = new DataServiceHost2($hostInfo);
            //Note we are using V3 data service
            $dataService = new NorthWindDataServiceV3();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                                                               
                $this->assertStringStartsWith('Query option $filter cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for $filter query option on bag has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $filter cannot be applied on primitve value
     */
   public function testUriProcessorForFilterOnValue()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(11)/Customer/CustomerID/$value';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$filter=true',
          	  				'DataServiceVersion' => new Version(1, 0),
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;           
                $this->assertStringStartsWith('Query option $filter cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for $filter query option on primitve value has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * When requesting for a bag DataServiceVersion should be >= 3.0     
     */
    public function testUriProcessorWithTargetAsBag1()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Employees(\'EMP1\')/Emails';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => null,
          	  				'DataServiceVersion' => new Version(2, 0),
          	  				'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                  $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;       
                $this->assertStringStartsWith("Request version '2.0' is not supported for the request payload. The only supported version is '3.0'", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for a bag request with  MaxDataServiceVersion < 3.0 has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * The MaxProtocolVersion configured for the service should be >=3.0 to respond to request for Bag 
     */
   public function testUriProcessorWithTargetAsBag2()
    {
        try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Employees(\'EMP1\')/Emails';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => null,
          	  				'DataServiceVersion' => new Version(3, 0),
          	  				'MaxDataServiceVersion' => new Version(3, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                  $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;       
                $this->assertStringStartsWith("The response requires that version 3.0 of the protocol be used, but the MaxProtocolVersion of the data service is set to 2.0", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for a bag request to a service configured with V2 has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * $select cannot be applied if its disabled on configuration
     */
    public function testUriProcessorForSelectWhereProjectionDisabled()
    {
         try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(11)/Customer';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$expand=Orders&$select=CustomerID,Orders',
          	  				'DataServiceVersion' => new Version(1, 0),
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataServiceV3();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                  $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;       
                $this->assertStringStartsWith('The ability to use the $select query option to define a projection in a data service query is disabled', $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for $select option on projection disabled service  has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * select and expand can be applied to request url identifying resource set
     */
 /**   public function testUriProcessorForSelelctExpandOnResourceSet()
    {
        
    }

    /**
     * $select is a V2 feature so client should request with  'DataServiceVersion' 2.0
     * but the response of select can be handled by V1 client so a value of 1.0 for MaxDataServiceVersion
     * will work
     */
    public function testUriProcessorForSelectExpandOnResourceWithDataServiceVersion1_0()
    {
             try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(11)/Customer';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$expand=Orders&$select=CustomerID,Orders',
                            //use of $select requires this header to 2.0 
          	  				'DataServiceVersion' => new Version(1, 0),
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                                                                      
                $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for $select query option with  DataServiceVersion 1.0 has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
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
         try {
             
            //Paging enabled for top level resource set and $top > pageSize => require next link
            //so MaxDataServiceVersion 1.0 will not work
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$top=10&$expand=Customer',
          	  				'DataServiceVersion' => new Version(1, 0),
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);          
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                                                                      
                $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for a paged top level result (having $top) with  MaxDataServiceVersion 1.0 has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
            //Paging enabled for top level resource set and no $top => require next link
            //so MaxDataServiceVersion 1.0 will not work
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
                            //error will be thrown from processskipAndTopOption before processor process expand
		      		    	'QueryString' => '$expand=Customer',
                            //DataServiceVersion can be 1.0 no issue                            
          	  				'DataServiceVersion' => new Version(1, 0),
                            //But MaxDataServiceVersion must be 2.0 as respose will include
                            //a nextlink for expanded 'Orders' property
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                                                                      
                $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for a paged top level result with  MaxDataServiceVersion 1.0 has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
            //Paging enabled for top level resource set and $top < pageSize => not require next link
            //so MaxDataServiceVersion 1.0 will work
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$top=2&$expand=Customer',
          	  				'DataServiceVersion' => new Version(1, 0),
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();    
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), false);
            $this->assertEquals($requestDescription->getTopCount(), 2); 
            //has orderby infor            
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            $projectionTreeRoot = $requestDescription->getRootProjectionNode();
            $this->assertTrue(!is_null($projectionTreeRoot));
            $this->assertTrue($projectionTreeRoot instanceof RootProjectionNode);
            //There will be one child nodes 
            //Expand Projection Node => 'Customer'
            $childNodes = $projectionTreeRoot->getChildNodes();
            $this->assertTrue(!is_null($childNodes));
            $this->assertTrue(is_array($childNodes));
            //$this->assertEquals(count($childNodes), 1);
            $this->assertTrue(array_key_exists('Customer', $childNodes));
            $this->assertTrue($childNodes['Customer'] instanceof ExpandedProjectionNode);
            $customerExpandedNode = $childNodes['Customer'];
            //Sort info will not be there for expanded 'Customer' as its resource set reference
            $internalOrderByInfo = $customerExpandedNode->getInternalOrderByInfo();
            $this->assertTrue(is_null($internalOrderByInfo));
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * If paging is enabled expanded result is resource set (top level is resource set reference 
     * so no paging for top level resoure) then client should request with 
     * MaxDataServiceVersion >= 2.0     
     */
    public function testUriProcessorForPagedExpandedResourceSetWithMaxDataServiceVersion1_0()
    {
         try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(11)/Customer';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$expand=Orders',
                            //DataServiceVersion can be 1.0 no issue                            
          	  				'DataServiceVersion' => new Version(1, 0),
                            //But MaxDataServiceVersion must be 2.0 as respose will include
                            //a nextlink for expanded 'Orders' property
          	  				'MaxDataServiceVersion' => new Version(1, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                                                                      
                $this->assertStringStartsWith("Request version '1.0' is not supported for the request payload. The only supported version is '2.0'", $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for an paged expanded result with  MaxDataServiceVersion 1.0 has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
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
         try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(11)/Customer';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$expand=Orders&$select=CustomerID,Orders',
                            //use of $select requires this header to 1.0 
          	  				'DataServiceVersion' => new Version(2, 0),
                            //The expanded property will be paged, so skiptoken will be there
                            //client says i can handle it
          	  				'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);          
            $uriProcessor = $dataService->handleRequest();    
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            $this->assertEquals($requestDescription->isSingleResult(), true);
            //paging is not applicable for resource set reference 'Customer'
            $this->assertEquals($requestDescription->getTopCount(), null); 
            //no orderby infor
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(is_null($internalOrderByInfo));
            $projectionTreeRoot = $requestDescription->getRootProjectionNode();
            $this->assertTrue(!is_null($projectionTreeRoot));
            $this->assertTrue($projectionTreeRoot instanceof RootProjectionNode);
            //There will be two child nodes 
            //Expand Projection Node => 'Orders'
            //Projection Node => 'CustomerID'
            $childNodes = $projectionTreeRoot->getChildNodes();
            $this->assertTrue(!is_null($childNodes));
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
            $this->assertTrue(!is_null($internalOrderByInfo));
            $this->assertTrue($internalOrderByInfo instanceof InternalOrderByInfo);
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * select and expand can be applied to only request uri identifying a resource set
     * or resource set refernce.     
     */
    public function testUriProcessorForSelectExpandOnNonResourceSetOrReference()
    {
        try {             
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(123)/Customer/Address';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$expand=Address2',
          	  				'DataServiceVersion' => new Version(2, 0),
          	  				'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);          
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                                                                      
                $this->assertStringStartsWith('Query option $expand cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for $expand on  non resource set or resource set refernce has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(123)/Customer/Address';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri),                             
		      		    	'QueryString' => '$select=LineNumber',
          	  				'DataServiceVersion' => new Version(2, 0),                            
          	  				'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                                                                      
                $this->assertStringStartsWith('Query option $select cannot be applied to the requested resource', $odataException->getMessage());
            }

            if (!$exceptionThrown) {
              $this->fail('An expected ODataException for $select on  non resource set or resource set refernce has not been thrown');
            }                        
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test uri prcoessor for $skip and $top options
     */
   public function testUriPrcoessorForSkipAndTop()
    {
            try {
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$top=\'ABC\'',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith("Incorrect format for \$top", $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for incorrect $top value has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			        	  			'AbsoluteServiceUri' => new Url($baseUri),
				          			'QueryString' => '$top=-123',
                  	      			'DataServiceVersion' => new Version(2, 0),
                  	  	    		'MaxDataServiceVersion' => new Version(2, 0));           
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('Incorrect format for $top', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for incorrect $top value has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }

        try {
            $host->getWebOperationContext()->resetWebContextInternal();
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			    	  			'AbsoluteServiceUri' => new Url($baseUri),
				      			'QueryString' => '$skip=\'ABC\'',
                  	  			'DataServiceVersion' => new Version(2, 0),
                  	  			'MaxDataServiceVersion' => new Version(2, 0));            
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith("Incorrect format for \$skip", $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for incorrect $skip value has not been thrown');
            }
            
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),                                
			        	  			'AbsoluteServiceUri' => new Url($baseUri),
				          			'QueryString' => '$skip=-123',
                  	      			'DataServiceVersion' => new Version(2, 0),
                  	  	    		'MaxDataServiceVersion' => new Version(2, 0));           
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $exceptionThrown = false;
            try {
                $dataService->handleRequest();
            } catch (ODataException $odataException) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('Incorrect format for $skip', $odataException->getMessage());
            }

            if (!$exceptionThrown) {                
                $this->fail('An expected ODataException for incorrect $skip value has not been thrown');
            }
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test uri processor with all options
     */
    public function testUriProcessorWithBigQuery()
    {
            try {             
            $baseUri = 'http://localhost:8083/NorthWindDataService.svc/';
            $resourcePath = 'Orders(123)/Customer/Orders';
            $hostInfo = array('AbsoluteRequestUri' => new Url($baseUri . $resourcePath),
            				'AbsoluteServiceUri' => new Url($baseUri), 
		      		    	'QueryString' => '$expand=Customer&$select=Customer,OrderDate&$filter=OrderID eq 123&$orderby=OrderDate&top=6&$skip=10&$skiptoken=datetime\'2000-11-11\',567',
          	  				'DataServiceVersion' => new Version(2, 0),
          	  				'MaxDataServiceVersion' => new Version(2, 0));
            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertEquals($requestDescription->getTopCount(), 5);
            $this->assertEquals($requestDescription->getSkipCount(), 10);
            $this->assertTrue(!is_null($requestDescription->getInternalOrderByInfo()));
            $this->assertTrue(!is_null($requestDescription->getInternalFilterInfo()));
            $this->assertTrue(!is_null($requestDescription->getInternalSkipTokenInfo()));
            $this->assertTrue(!is_null($requestDescription->getRootProjectionNode()));
            $host->getWebOperationContext()->resetWebContextInternal();
        } catch (\Exception $exception) {
            if ($host != null) {
                $host->getWebOperationContext()->resetWebContextInternal();
            }
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * test Request Description with all geter method.
     */
    public function testRequestDescription()
    {
        try {
            $hostInfo = array('AbsoluteRequestUri' => new Url('http://localhost:8083/NorthWindDataService.svc/Orders'), 
			    	  'AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc'),
				      'QueryString' => null,
                  	  'DataServiceVersion' => new Version(1, 0),
                  	  'MaxDataServiceVersion' => new Version(2, 0));

            $host = new DataServiceHost2($hostInfo);
            $dataService = new NorthWindDataService2();
            $dataService->setHost($host);
            $uriProcessor = $dataService->handleRequest();
            
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->assertTrue(!is_null($requestDescription));
            
            $countValue = $requestDescription->getCountValue();
            $this->assertTrue(is_null($countValue));
            
            $identifier = $requestDescription->getIdentifier();
            $this->assertTrue(!is_null($identifier));
            
            $internalFilterInfo = $requestDescription->getInternalFilterInfo();
            $this->assertTrue(is_null($internalFilterInfo));
            
            $internalOrderByInfo = $requestDescription->getInternalOrderByInfo();
            $this->assertTrue(!is_null($internalOrderByInfo));
            
            $internalSkipTokenInfo = $requestDescription->getInternalSkipTokenInfo();
            $this->assertTrue(is_null($internalSkipTokenInfo));
            
            $knownDataServiceVersions = $requestDescription->getKnownDataServiceVersions();
            $this->assertTrue(!is_null($knownDataServiceVersions));
            
            $lastSegmentDescriptor = $requestDescription->getLastSegmentDescriptor();
            $this->assertTrue(!is_null($lastSegmentDescriptor));
            
            $projectedProperty = $requestDescription->getProjectedProperty();
            $this->assertTrue(is_null($projectedProperty));
            
            $requestCountOption = $requestDescription->getRequestCountOption();
            $this->assertTrue(!is_null($requestCountOption));
            
            $requestUri = $requestDescription->getRequestUri();
            $this->assertTrue(!is_null($requestUri));
            
            $resourceStreamInfo = $requestDescription->getResourceStreamInfo();
            $this->assertTrue(is_null($resourceStreamInfo));
            
            $rootProjectionNode = $requestDescription->getRootProjectionNode();
            $this->assertTrue(!is_null($rootProjectionNode));
            
            $segmentDescriptors = $requestDescription->getSegmentDescriptors();
            $this->assertTrue(!is_null($segmentDescriptors));
            
            $skipCount = $requestDescription->getSkipCount();
            $this->assertTrue(is_null($skipCount));
            
            $targetKind = $requestDescription->getTargetKind();
            $this->assertTrue(!is_null($targetKind));
            
            $targetResourceSetWrapper = $requestDescription->getTargetResourceSetWrapper();
            $this->assertTrue(!is_null($targetResourceSetWrapper));
            
            $targetResourceType = $requestDescription->getTargetResourceType();
            $this->assertTrue(!is_null($targetResourceType));
            
            $targetSource = $requestDescription->getTargetSource();
            $this->assertTrue(!is_null($targetSource));
            
            $topCount = $requestDescription->getTopCount();
            $this->assertTrue(!is_null($topCount));
            $host->getWebOperationContext()->resetWebContextInternal();
            
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }
    
    protected function tearDown()
    {
    }
}
?>