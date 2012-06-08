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
require_once 'PHPUnit\Framework\test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\testCase.php';
require_once 'PHPUnit\Framework\testSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
ODataProducer\Common\ClassAutoLoader::register();
require_once (dirname(__FILE__) . "\.\..\..\Resources\WordPress2\WordPressMetadata.php");
require_once (dirname(__FILE__) . "\.\..\..\Resources\WordPress2\WordPressDataService.php");
require_once (dirname(__FILE__) . "\.\..\..\Resources\WordPress2\DataServiceHost5.php");

class testIDSQP2_WordPress extends PHPUnit_Framework_testCase
{
	protected function setUp()
	{
	}

	/**
	 * test the generated string comaprsion expression in sql server
	 */
	function testStringCompareMySQL()
	{
		$host = null;
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
				'AbsoluteRequestUri' => new Url($requestUri),
				'QueryString' => '$filter=Title eq \'OData PHP Producer\'',
				'DataServiceVersion' => new Version(3, 0),
				'MaxDataServiceVersion' => new Version(3, 0));
		
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(STRCMP(post_title, 'OData PHP Producer') = 0)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * test the generated function-call expression in sql server
	 */
	function testFunctionCallMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
				'AbsoluteRequestUri' => new Url($requestUri),
				'QueryString' => '$filter=replace(Title, \'PHP\', \'Java\') eq \'OData Java Producer\'',
				'DataServiceVersion' => new Version(3, 0),
				'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(STRCMP(REPLACE(post_title,'PHP','Java'), 'OData Java Producer') = 0)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * test the generated expression for nullability check in sql server
	 */
	function testNullabilityCheckMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
				'AbsoluteRequestUri' => new Url($requestUri),
				'QueryString' => '$filter=PostID eq  null',
				'DataServiceVersion' => new Version(3, 0),
				'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(ID = NULL)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression for negation in sql server
	 */
	function testNegationMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
			$resourcePath = 'Posts';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=-PostID eq -1',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// echo "\n";
			// print_r("\n\n'" . $mysqlexpression . "'\n\n");
			// echo "\n";
			$this->AssertEquals("(-(ID) = -1)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression for datetime comaprsion in sql server
	 */
	function testDateTimeComparisionMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=Date eq datetime\'2011-12-24 19:54:00\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// echo "\n";
			// print_r("\n\n'" . $mysqlexpression . "'\n\n");
			// echo "\n";
			$this->AssertEquals("((post_date =  '2011-12-24 19:54:00'))", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * test the generated expression for YEAR function call in sql server
	 */
	function testYearFunctionCallMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
			$resourcePath = 'Posts';
			$requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=year(Date) eq  year(datetime\'1996-07-09\')',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// echo "\n";
			// print_r("\n\n'" . $mysqlexpression . "'\n\n");
			// echo "\n";
			$this->AssertEquals("(EXTRACT(YEAR from post_date) = EXTRACT(YEAR from '1996-07-09'))", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * test the generated expression for YEAR function call with aritmetic and equality sql server
	 */
	function testYearFunctionCallWtihAriRelMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
			$resourcePath = 'Posts';
			$requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=year(Date) add 2 eq 2013',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// echo "\n";
			// print_r("\n\n'" . $mysqlexpression . "'\n\n");
			// echo "\n";
			$this->AssertEquals("((EXTRACT(YEAR from post_date) + 2) = 2013)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression for ceil and floor sql server
	 */
	function testCeilFloorFunctionCallMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
			$resourcePath = 'Posts';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=ceiling(floor(PostID)) eq 2',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// echo "\n";
			// print_r("\n\n'" . $mysqlexpression . "'\n\n");
			// echo "\n";
			$this->AssertEquals("(CEIL(FLOOR(ID)) = 2)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression for round function-call for sql server
	 */
	function testRoundFunctionCallMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
			$resourcePath = 'Posts';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=round(PostID) eq 1',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(ROUND(ID) = 1)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * test the generated expression for mod operator sql server
	 */
	function testModOperatorMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
			$resourcePath = 'Posts';
			$requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=PostID mod 5 eq 4',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("((ID % 5) = 4)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression 2 param version of sub-string in sql server
	 */
	function testSubString2ParamMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=substring(Title, 1) eq \'Data PHP Producer\'',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// echo "\n";
			// print_r("\n\n'" . $mysqlexpression . "'\n\n");
			// echo "\n";
			$this->AssertEquals("(STRCMP(SUBSTRING(post_title, 1 + 1), 'Data PHP Producer') = 0)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression 3 param version of sub-string in sql server
	 */
	function testSubString3ParamMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
      	    $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=substring(Title, 1, 6) eq \'Data P\'',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(STRCMP(SUBSTRING(post_title, 1 + 1, 6), 'Data P') = 0)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression trim in sql server
	 */
	function testSubStringTrimMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
					'AbsoluteRequestUri' => new Url($requestUri),
					'QueryString' => '$filter=trim(\'  OData PHP Producer   \') eq Title',
					'DataServiceVersion' => new Version(3, 0),
					'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// echo "\n";
			// print_r("\n\n'" . $mysqlexpression . "'\n\n");
			// echo "\n";
			$this->AssertEquals("(STRCMP(TRIM('  OData PHP Producer   '), post_title) = 0)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression endswith function-call in sql server
	 */
	function testEndsWithMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=endswith(Title, \'umer\')',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(STRCMP('umer',RIGHT(post_title,LENGTH('umer'))) = 0)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression startswith function-call in sql server
	 */
	function testStartsWithMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=startswith(Title, \'OData\')',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(STRCMP('OData',LEFT(post_title,LENGTH('OData'))) = 0)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * test the generated expression indexof function-call in sql server
	 */
	function testIndexOfMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
            $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=indexof(Title, \'ata\') eq 2',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(INSTR(post_title, 'ata') - 1 = 2)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * test the generated expression replace function-call in sql server
	 */
	function testReplaceMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=replace(Title, \' \', \'\') eq \'ODataPHPProducer\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(STRCMP(REPLACE(post_title,' ',''), 'ODataPHPProducer') = 0)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}
	
	/**
	 * test the generated expression substringof function-call in sql server
	 */
	function testSubStringOfMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=substringof(\'Producer\', Title)',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// print_r("'" . $mysqlexpression . "'");
			$this->AssertEquals("(LOCATE('Producer', post_title) > 0)", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	/**
	 * test the generated expression substringof and indexof function-call in sql server
	 */
	function testSubStringOfIndexOfMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=substringof(\'Producer\', Title) and indexof(Title, \'Producer\') eq 11',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// echo "\n";
			// print_r("\n\n'" . $mysqlexpression . "'\n\n");
			// echo "\n";
			$this->AssertEquals("((LOCATE('Producer', post_title) > 0) && (INSTR(post_title, 'Producer') - 1 = 11))", $mysqlexpression);
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
			$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
			$host->getWebOperationContext()->resetWebContextInternal();
		}
	}

	
	/**
	 * test the generated expression concat function-call in sql server
	 */
	function testSubConcatMySQL()
	{
		try {
			$exceptionThrown = false;
			$serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=concat(concat(Title, \', \'), \'Open source now\') eq \'OData .NET Producer, Open source now\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));
	
			$host = new DataServiceHost5($hostInfo);
			$dataService = new WordPressDataService();
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
			$mysqlexpression = $internalFilterInfo->getExpressionAsString();
			// echo "\n";
			// print_r("'" . $mysqlexpression . "'");
			// echo "\n";
			$this->AssertEquals("(STRCMP(CONCAT(CONCAT(post_title,', '),'Open source now'), 'OData .NET Producer, Open source now') = 0)", $mysqlexpression);
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
    $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
	        $resourcePath = 'Posts';
	        $requestUri = $serviceUri . $resourcePath;
	        $hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
			'AbsoluteRequestUri' => new Url($requestUri),
			'QueryString' => '$filter=concat(concat(Title, \', \'), \'Open source now\') eq \'OData .NET Producer, Open source now\'',
			'DataServiceVersion' => new Version(3, 0),
			'MaxDataServiceVersion' => new Version(3, 0));

	$host = new DataServiceHost5($hostInfo);
	$dataService = new WordPressDataService();
	$dataService->setHost($host);
	$uriProcessor = $dataService->handleRequest();
	$requestDescription = $uriProcessor->getRequestDescription();
	$internalFilterInfo = $requestDescription->getInternalFilterInfo();
	$mysqlexpression = $internalFilterInfo->getExpressionAsString();
	// TODO Assert that exp is   (STRCMP(REPLACE(post_title,' ',''), 'ODataPHPProducer') = 0)
	$host->getWebOperationContext()->resetWebContextInternal();
	echo $mysqlexpression;
} catch (\Exception $exception) {
	$this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
}
**/
// 
?>