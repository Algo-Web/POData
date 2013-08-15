<?php
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
use ODataProducer\Common\NotImplementedException;
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
ODataProducer\Common\ClassAutoLoader::register();
require_once (dirname(__FILE__) . "\.\..\..\Resources\NorthWind4\NorthWindMetadata4.php");
require_once (dirname(__FILE__) . "\.\..\..\Resources\NorthWind4\NorthWindDataService4.php");
require_once (dirname(__FILE__) . "\.\..\..\Resources\NorthWind4\DataServiceHost4.php");

class TestIDSQP2_NorthWind extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
	}

	/**
	 * Test the generated string comaprsion expression in sql server
	 */
	function testStringCompareSQLServer()
	{
		$host = null;
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Customers';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=CustomerID gt \'ALFKI\'',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
		
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((CustomerID >  'ALFKI'))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * Test the generated function-call expression in sql server
	 */
	function testFunctionCallSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Customers';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=replace(CustomerID, \'LFK\', \'RTT\') eq \'ARTTI\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((REPLACE(CustomerID, 'LFK', 'RTT') =  'ARTTI'))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * Test the generated expression for nullability check in sql server
	 */
	function testNullabilityCheckSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Customers';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=CustomerID eq null',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("(CustomerID = NULL)", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression for negation in sql server
	 */
	function testNegationSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Orders';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=-OrderID eq -10248',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("(-(OrderID) = -10248)", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression for datetime comaprsion in sql server
	 */
	function testDateTimeComparisionSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	         $resourcePath = 'Orders';
	         $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=OrderDate eq datetime\'1996-07-04\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((OrderDate =  '1996-07-04'))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * Test the generated expression for YEAR function call in sql server
	 */
	function testYearFunctionCallSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Orders';
			$requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=year(OrderDate) eq  year(datetime\'1996-07-09\')',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("(YEAR(OrderDate) = YEAR('1996-07-09'))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * Test the generated expression for YEAR function call with aritmetic and equality sql server
	 */
	function testYearFunctionCallWtihAriRelSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Orders';
			$requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=year(OrderDate) add 2 eq 1998',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((YEAR(OrderDate) + 2) = 1998)", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression for ceil and floor sql server
	 */
	function testCeilFloorFunctionCallSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Orders';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=ceiling(floor(Freight)) eq 32',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("(CEILING(FLOOR(Freight)) = 32)", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression for round function-call for sql server
	 */
	function testRoundFunctionCallSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Orders';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=round(Freight) eq 34',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("(ROUND(Freight, 0) = 34)", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * Test the generated expression for mod operator sql server
	 */
	function testModOperatorSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Orders';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=Freight mod 10 eq 2.38',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((Freight % 10) = 2.38)", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression 2 param version of sub-string in sql server
	 */
	function testSubString2ParamSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=substring(CompanyName, 1) eq \'lfreds Futterkiste\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((SUBSTRING(CompanyName, 1 + 1, LEN(CompanyName)) =  'lfreds Futterkiste'))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression 3 param version of sub-string in sql server
	 */
	function testSubString3ParamSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=substring(CompanyName, 1, 6) eq \'lfreds\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((SUBSTRING(CompanyName, 1 + 1, 6) =  'lfreds'))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression trim in sql server
	 */
	function testSubStringTrimSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=trim(\'  ALFKI  \') eq CustomerID',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((RTRIM(LTRIM('  ALFKI  ')) =  CustomerID))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression endswith function-call in sql server
	 */
	function testEndsWithSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=endswith(CustomerID, \'KI\')',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("(('KI') = RIGHT((CustomerID), LEN('KI')))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression startswith function-call in sql server
	 */
	function testStartsWithSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=startswith(CustomerID, \'AL\')',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("(('AL') = LEFT((CustomerID), LEN('AL')))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * Test the generated expression indexof function-call in sql server
	 */
	function testIndexOfSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
            $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=indexof(CustomerID, \'FKI\') eq 2',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((CHARINDEX('FKI', CustomerID) - 1) = 2)", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * Test the generated expression replace function-call in sql server
	 */
	function testReplaceSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=replace(CompanyName, \' \', \'\') eq \'AlfredsFutterkiste\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((REPLACE(CompanyName, ' ', '') =  'AlfredsFutterkiste'))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression substringof function-call in sql server
	 */
	function testSubStringOfSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=substringof(\'Alfreds\', CompanyName)',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("(CHARINDEX('Alfreds', CompanyName) != 0)", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * Test the generated expression substringof and indexof function-call in sql server
	 */
	function testSubStringOfIndexOfSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=substringof(\'Alfreds\', CompanyName) and indexof(CustomerID, \'FKI\') eq 2',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((CHARINDEX('Alfreds', CompanyName) != 0) AND ((CHARINDEX('FKI', CustomerID) - 1) = 2))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	
	/**
	 * Test the generated expression concat function-call in sql server
	 */
	function testSubConcatSQLServer()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=concat(concat(CustomerID, \', \'), ContactName) eq \'ALFKI, Maria Anders\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((CustomerID + ', ' + ContactName =  'ALFKI, Maria Anders'))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * Test the generated expression level 2 property access in sql server
	 */
	function testLevel2PropertyAccessSQLServer()
	{
		try {
	        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	        $resourcePath = 'Customers';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=Address/Country eq \'USA\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost4($hostInfo);
			$dataService = new NorthWindDataService4();
			$dataService->setHost($host);
			$uriProcessor = $dataService->handleRequest();
			$check = !is_null($uriProcessor);
			$this->assertTrue($check);
			$requestDescription = $uriProcessor->getRequestDescription();
			$check = !is_null($requestDescription);
			$this->assertTrue($check);
			$internalFilterInfo = $requestDescription->getInternalFilterInfo();
			$check = !is_null($internalFilterInfo);
			$this->assertTrue($check);
			$sqlexpression = $internalFilterInfo->getExpressionAsString();
			print_r("'" . $sqlexpression . "'");
			$this->AssertEquals("((Country =  'USA'))", $sqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	protected function tearDown()
	{
	}
}

/**
try {
	$exceptionThrown = false;
	$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
	$resourcePath = 'Customers';
	$requestUri = $serviceUri . $resourcePath;
	$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=Address eq null',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));

	$host = new DataServiceHost4($hostInfo);
	$dataService = new NorthWindDataService4();
	$dataService->setHost($host);
	$thrownException = false;	
    try {
	    $uriProcessor = $dataService->handleRequest();
    } Catch (NotImplementedException $exception) {
    	$thrownException = true;
    }

    if (!$thrownException) {
    	// TODO Expecting the exception 
    }
} catch (\Exception $exception) {
	$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
}
**/
// 
?>