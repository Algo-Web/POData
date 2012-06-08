<?php
use ODataProducer\Common\Url;
use ODataProducer\Common\ODataException;
use ODataProducer\OperationContext\DataServiceHost;
require_once 'ODataProducer\Common\ClassAutoLoader.php';

require_once (dirname(__FILE__) . "\.\..\Resources\NorthWind2\NorthWindDataService.php");
require_once (dirname(__FILE__) . "\.\..\Resources\NorthWind2\DataServiceHost2.php");

require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
ODataProducer\Common\ClassAutoLoader::register();
use ODataProducer\Common\Version;
class TestUriProcessorExecution extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    /**
     * test uri processor executor with combination of all querystring options.
     */
    public function testCombinationWithAllQueryStringOptions()
    {
    	try {
        $exceptionThrown = false;
	    	$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Customers(CustomerID=\'ALFKI\', CompanyName=\'Alfreds Futterkiste\')/Orders(10643)/Customer/Orders(10692)/Order_Details(OrderID=10692,ProductID=63)/Product/Order_Details(OrderID=10692,ProductID=63)/Order/Customer/Orders';
			$requestUri = $serviceUri . $resourcePath;
			
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
							  'AbsoluteRequestUri' => new Url($requestUri), 
			   		    	  'QueryString' => '$filter=OrderID gt 10836 and OrderID le 20000&$orderby=CustomerID&$top=7&$skip=1&$inlinecount=allpages',
			  				  'DataServiceVersion' => new Version(2, 0),
			  				  'MaxDataServiceVersion' => new Version(2, 0));
	
			$host = new DataServiceHost1($hostInfo);
			$dataService = new NorthWindDataService1();
			$dataService->setHost($host);
			$queryProcessor = $dataService->handleRequest();
			$queryProcessor->execute();
			
			$result = $queryProcessor->getRequestDescription()->getLastSegmentDescriptor()->getResult();
			$isarray = is_array($result);
			$this->assertTrue($isarray);
    	} catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    	
    }
    
    /**
     * test uri processor executor with combination of all querystring options without skip. 
     */
	public function testCombinationWithAllQueryStringOptionsWithoutSkip()
    {
    	try {
        	$exceptionThrown = false;
	    	$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Customers(CustomerID=\'ALFKI\', CompanyName=\'Alfreds Futterkiste\')/Orders(10643)/Customer/Orders(10692)/Order_Details(OrderID=10692,ProductID=63)/Product/Order_Details(OrderID=10692,ProductID=63)/Order/Customer/Orders';
			$requestUri = $serviceUri . $resourcePath;
			
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
							  'AbsoluteRequestUri' => new Url($requestUri), 
			   		    	  'QueryString' => '$filter=OrderID gt 10600 and OrderID le 20000&$orderby=CustomerID&$top=7&$inlinecount=allpages',
			  				  'DataServiceVersion' => new Version(2, 0),
			  				  'MaxDataServiceVersion' => new Version(2, 0));
	
			$host = new DataServiceHost1($hostInfo);
			$dataService = new NorthWindDataService1();
			$dataService->setHost($host);
			$queryProcessor = $dataService->handleRequest();
			$queryProcessor->execute();
			$result = $queryProcessor->getRequestDescription()->getLastSegmentDescriptor()->getResult();
			$this->assertTrue(is_array($result));
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
		    if ($host != null) {
		        $host->getWebOperationContext()->resetWebContextInternal();
		    }
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    }
    
    /**
     * test uri processor executor with combination of all querystring options with count option.
     */
	public function testCombinationWithAllQueryStringOptionsWithCount()
    {
    	try {
        	$exceptionThrown = false;
	    	$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Customers(CustomerID=\'ALFKI\', CompanyName=\'Alfreds Futterkiste\')/Orders(10643)/Customer/Orders(10692)/Order_Details(OrderID=10692,ProductID=63)/Product/Order_Details(OrderID=10692,ProductID=63)/Order/Customer/Orders/$count';
			$requestUri = $serviceUri . $resourcePath;
			
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
							  'AbsoluteRequestUri' => new Url($requestUri), 
			   		    	  'QueryString' => '$filter=OrderID gt 10600 and OrderID le 20000&$orderby=CustomerID&$top=7&$skip=1',
			  				  'DataServiceVersion' => new Version(2, 0),
			  				  'MaxDataServiceVersion' => new Version(2, 0));
	
			$host = new DataServiceHost1($hostInfo);
			$dataService = new NorthWindDataService1();
			$dataService->setHost($host);
			$queryProcessor = $dataService->handleRequest();
			$queryProcessor->execute();			
			$result = $queryProcessor->getRequestDescription()->getCountValue();
			$this->assertEquals($result, '5');
			$host->getWebOperationContext()->resetWebContextInternal();
		} catch (\Exception $exception) {
		    if ($host != null) {
		        $host->getWebOperationContext()->resetWebContextInternal();
		    }
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    }

    /**
     * test uri processor executor with not exist resource request.
     */
    public function testResourceNotFound()
    {
    	try {
        	$exceptionThrown = false;
	    	$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Orders(10247)';
			$requestUri = $serviceUri . $resourcePath;
			
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
							  'AbsoluteRequestUri' => new Url($requestUri), 
			   		    	  'QueryString' => null,
			  				  'DataServiceVersion' => new Version(2, 0),
			  				  'MaxDataServiceVersion' => new Version(2, 0));
			
			$host = new DataServiceHost1($hostInfo);
			$dataService = new NorthWindDataService1();
			$dataService->setHost($host);
			$exceptionThrown = false;
	            try {
	            	$queryProcessor = $dataService->handleRequest();
					$queryProcessor->execute();
	                $result = $queryProcessor->getRequestDescription()->getLastSegmentDescriptor()->getResult();
	            } catch (ODataException $odataException) {
	                $exceptionThrown = true;                
	                $this->assertStringStartsWith("Resource not found for the segment", $odataException->getMessage());
	            }
	            $host->getWebOperationContext()->resetWebContextInternal();
    	} catch (\Exception $exception) {
    		if ($host != null) {
		        $host->getWebOperationContext()->resetWebContextInternal();
		    }
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    }
    

    /**
     * test uri processor executor with combination of all querystring options for perticular order request.
     */
    public function testCombinationWithAllQueryStringOptionsForEntity()
    {
    	try {
        	$exceptionThrown = false;
	    	$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Customers(CustomerID=\'ALFKI\', CompanyName=\'Alfreds Futterkiste\')/Orders(10643)/Customer/Orders(10692)/Order_Details(OrderID=10692,ProductID=63)/Product/Order_Details(OrderID=10692,ProductID=63)/Order/Customer/Orders(10835)';
			$requestUri = $serviceUri . $resourcePath;
			
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
							  'AbsoluteRequestUri' => new Url($requestUri), 
			   		    	  'QueryString' => '$filter=OrderID gt 10836 and OrderID le 20000',
			  				  'DataServiceVersion' => new Version(2, 0),
			  				  'MaxDataServiceVersion' => new Version(2, 0));
	
			$host = new DataServiceHost1($hostInfo);
			$dataService = new NorthWindDataService1();
			$dataService->setHost($host);
			$queryProcessor = $dataService->handleRequest();
			$queryProcessor->execute();
			$result = $queryProcessor->getRequestDescription()->getLastSegmentDescriptor()->getResult();
			$this->assertTrue(!is_array($result));
			$host->getWebOperationContext()->resetWebContextInternal();
    	} catch (\Exception $exception) {
    	    if ($host != null) {
		        $host->getWebOperationContext()->resetWebContextInternal();
		    }
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    }

    /**
     * test uri processor executor for get value complex property.
     */
    public function testGetComplexPropertyValue()
    {
    	try {
        	$exceptionThrown = false;
	    	$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Customers(CustomerID=\'ALFKI\', CompanyName=\'Alfreds Futterkiste\')/Address/Address2/LineNumber2/$value';
			$requestUri = $serviceUri . $resourcePath;
			
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
							  'AbsoluteRequestUri' => new Url($requestUri), 
			   		    	  'QueryString' => null,
			  				  'DataServiceVersion' => new Version(2, 0),
			  				  'MaxDataServiceVersion' => new Version(2, 0));
	
			$host = new DataServiceHost1($hostInfo);
			$dataService = new NorthWindDataService1();
			$dataService->setHost($host);
			$queryProcessor = $dataService->handleRequest();
			$queryProcessor->execute();			
			$result = $queryProcessor->getRequestDescription()->getLastSegmentDescriptor()->getResult();			
			$this->assertEquals($result, '14');
			$host->getWebOperationContext()->resetWebContextInternal();
    	} catch (\Exception $exception) {
    	    if ($host != null) {
		        $host->getWebOperationContext()->resetWebContextInternal();
		    }
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    }

    /**
     * test uri processor executor with combination of all querystring options.
     */
    public function testCombinationOfEntitySetWithSkipToken()
    {
    	try {
        	$exceptionThrown = false;
	    	$serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
			$resourcePath = 'Customers(CustomerID=\'ALFKI\', CompanyName=\'Alfreds Futterkiste\')/Orders(10643)/Customer/Orders(10692)/Order_Details(OrderID=10692,ProductID=63)/Product/Order_Details(OrderID=10692,ProductID=63)/Order/Customer/Orders';
			$requestUri = $serviceUri . $resourcePath;
			
			$hostInfo = array('AbsoluteServiceUri' => new Url($serviceUri),
							  'AbsoluteRequestUri' => new Url($requestUri), 
			   		    	  'QueryString' => '$skiptoken=10835',
			  				  'DataServiceVersion' => new Version(2, 0),
			  				  'MaxDataServiceVersion' => new Version(2, 0));
	
			$host = new DataServiceHost1($hostInfo);
			$dataService = new NorthWindDataService1();
			$dataService->setHost($host);
			$queryProcessor = $dataService->handleRequest();
			$queryProcessor->execute();			
			$result = $queryProcessor->getRequestDescription()->getLastSegmentDescriptor()->getResult();			
			$this->assertTrue(is_array($result));
			$host->getWebOperationContext()->resetWebContextInternal();
    	} catch (\Exception $exception) {
    	    if ($host != null) {
		        $host->getWebOperationContext()->resetWebContextInternal();
		    }
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    }
    
    /**
     * test uri processor executor for invalid base uri.
     */
    public function testInvalidBaseUri()
    {
    	try {
        	$exceptionThrown = false;
			$hostInfo = array('AbsoluteServiceUri' => new Url('http://localhost:8083/NorthWindDataService.svc/'),
							  'AbsoluteRequestUri' => new Url('http://localhost:8085/NorthWindDataService.svc/'), 
			   		    	  'QueryString' => null,
			  				  'DataServiceVersion' => new Version(2, 0),
			  				  'MaxDataServiceVersion' => new Version(2, 0));
	
			$host = new DataServiceHost1($hostInfo);
			$dataService = new NorthWindDataService1();
			$dataService->setHost($host);		
			$exceptionThrown = false;
	            try {
	            	$queryProcessor = $dataService->handleRequest();
					$queryProcessor->execute();
	                $result = $queryProcessor->getRequestDescription()->getLastSegmentDescriptor()->getResult();
	            } catch (ODataException $odataException) {
	                $exceptionThrown = true;                
	                $this->assertStringStartsWith("The URI 'http://localhost:8085/NorthWindDataService.svc/' is not valid since it is not based on 'http://localhost:8083/NorthWindDataService.svc/'", $odataException->getMessage());
	            }
	            $host->getWebOperationContext()->resetWebContextInternal();
    	} catch (\Exception $exception) {
    	    if ($host != null) {
		        $host->getWebOperationContext()->resetWebContextInternal();
		    }
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    }

    protected function tearDown()
    {
    }
}
?>