<?php
ob_start();
/**
 * Note: 
 * 1. This test case requires the service 'NorthWind' to be
 *    accessed using http://localhost:8086/NorthWind.svc
 * 2. Do not remove the  ob_start statement above.
 * 
 */
use POData\ObjectModel\ODataBagContent;
use POData\Common\ODataConstants;
use POData\Common\Messages;
use POData\Common\HttpStatus                             ;
use POData\Common\Url;
use POData\Common\UrlFormatException;
use POData\Common\ODataException;
use POData\OperationContext\DataServiceHost;
use POData\OperationContext\Web\IncomingRequest;
use POData\OperationContext\Web\OutgoingResponse;
use POData\Dispatcher;


class TestObjectModelSerializer extends PHPUnit_Framework_TestCase
{	
    protected function setUp()
    {
        ob_start();
    }

    /**
     * Tests ObjectModelSerializer::WriteTopLevelElements'
     */
	function testWriteTopLevelElements1()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	//    = '$top=1';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<feed'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</feed>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    	
	}

    /**
     * Tests ObjectModelSerializer::WriteTopLevelElements'
     */
	function testWriteTopLevelElements2()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers";
        $_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	    = '$top=2&$skip=3';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<feed'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</feed>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    	
	}

    /**
     * Tests ObjectModelSerializer::WriteTopLevelElements'
     */
	function testWriteTopLevelElements3()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Orders(10643)/Customer/Orders";
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<feed'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</feed>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    	
	}

	/**
	 * Test ObjectModelSerializer::WriteTopLevelElement'
	 */
	function testWriteTopLevelElement1()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers('ALFKI')";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	//    = '$top=1';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<entry'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</entry>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}   
	}

	/**
	 * Test ObjectModelSerializer::WriteTopLevelElement'
	 */
	function testWriteTopLevelElement2()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Orders(10643)/Customer";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	//    = '$top=1';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<entry'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</entry>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}   
	}

	/**
	 * Test ObjectModelSerializer::writeUrlElements'
	 */
	function testWriteTopLevelUrlElements()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers('ALFKI')/\$links/Orders";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	//    = '$top=1';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<links'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</links>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}   
	}

	/**
	 * Test ObjectModelSerializer::writeUrlElements'
	 */
	function testWriteTopLevelUrlElement()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Orders(10643)/\$links/Customer";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	//    = '$top=1';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<uri'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</uri>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}   
	}

	/**
	 * Test ObjectModelSerializer::writeTopLevelComplexObject'
	 */
	function testWriteTopLevelComplexObject()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers('ALFKI')/Address";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	//    = '$top=1';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<d:Address m:type="NorthWind.Address"'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</d:Address>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}   
	}

	/**
	 * Test ObjectModelSerializer::writeTopLevelPrimitive'
	 */
	function testWriteTopLevelPrimitive()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers('ALFKI')/Address/Country";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	//    = '$top=1';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<d:Country m:type="Edm.String"'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</d:Country>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}   
	}

	/**
	 * Test ObjectModelSerializer::writeTopLevelBagObject'
	 */
	function TestWriteTopLevelBagObject()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers('ALFKI')/OtherAddresses";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	//    = '$top=1';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<d:OtherAddresses m:type="Collection(NorthWind.Address)"'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</d:OtherAddresses>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}   
	}

	/**
	 * Test with $select and $expansion
	 */
	function TestSelectionAndExpansion()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers('ALFKI')";
        $_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	    = '$expand=Orders,$select=CustomerID,Orders/OrderID';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<entry'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</entry>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}   
	}

    /**
     * Tests orderby
     */
	function testOrderBy()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers";
        $_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	    = '$orderby=Address/Country';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<feed'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</feed>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    	
	}

    /**
     * Tests inlinecount
     */
	function testInlineCountAllPages()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers";
        $_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	    = '$inlinecount=allpages';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<feed'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</feed>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    	
	}

    /**
     * Tests inlinecount
     */
	function testInlineCountNone()
	{
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_METHOD] 	
    	    = ODataConstants::HTTP_METHOD_GET;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL]	
    	    = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST]		
    	    = "localhost:8086";
    	$_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]
    	    = "/NorthWind.svc/Customers";
        $_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
    	    = '$inlinecount=none';
    	try {            
 	        $dispatcher = new Dispatcher(); 	        
 	        $dispatcher->dispatch();
 	        $my_str = ob_get_contents();
 	        ob_end_clean(); 	        
 	        $dispatcher->getHost()->getWebOperationContext()->resetWebContextInternal();
 	        $this->assertStringStartsWith(
 	        	'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . '<feed'
 	            , $my_str
            );

 	        $this->assertStringEndsWith(
 	        	'</feed>' . "\n"
 	            , $my_str
            );
		} catch (\Exception $exception) {
		    // Should call ob_end_clean
		    ob_end_clean();
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    	
	}
}