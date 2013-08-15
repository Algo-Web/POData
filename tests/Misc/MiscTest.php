<?php    
/**
 * Note: 
 * 1. This test case requires the service 'NorthWind' to be
 *    accessed using http://localhost:8086/NorthWind.svc 
 */
use ODataProducer\ObjectModel\ODataBagContent;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
use ODataProducer\Common\HttpStatus                             ;
use ODataProducer\Common\Url;
use ODataProducer\Common\UrlFormatException;
use ODataProducer\Common\ODataException;
use ODataProducer\OperationContext\DataServiceHost;
use ODataProducer\OperationContext\Web\IncomingRequest;
use ODataProducer\OperationContext\Web\OutgoingResponse;
require_once dirname(__FILE__)."\..\..\Dispatcher.php";
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
ODataProducer\Common\ClassAutoLoader::register();

class TestETag extends PHPUnit_Framework_TestCase
{	
    const BASE_SERIVE_URL = 'http://localhost:8089/NorthWind.svc';

    protected function setUp()
    {        
    }

    /**
     * Test content ype header for service document.
     */
	function testContentTypeHeader_ServiceDocument()
	{
	    try {
	        // Atom service document
            $ch = curl_init();
            $headers = array();
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);            
            $this->assertTrue(array_key_exists('content_type', $info));            
            $this->assertEquals($info['content_type'], 'application/xml;charset=utf-8');
            
	        // JSON service document
            $ch = curl_init();
            $headers = array();
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL  . '?$format=json'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);            
            $this->assertTrue(array_key_exists('content_type', $info));            
            $this->assertEquals($info['content_type'], 'application/json;charset=utf-8');
            

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}
	}

    /**
     * Test content type header for metadata
     */
	function testContentTypeHeader_Metadata()
	{
	    try {
	        // Atom metadata document
            $ch = curl_init();
            $headers = array();
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/$metadata'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/xml;charset=utf-8');

	        // request unsupported content type (metadata cannot be in json format)
            $ch = curl_init();
            $headers = array(ODataConstants::HTTP_REQUEST_ACCEPT .':'. ODataConstants::MIME_APPLICATION_JSON);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/$metadata'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $pos = strpos($result, '"value": "Unsupported media type requested."');
            $this->assertTrue($pos !== false);
            // Should get error message for unsupported media type in json format
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/json');

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}
	}

	/**
	 * Test content type of feed response	 
	 */
	function testContentType_Feed()
	{
		    try {
	        // Request feed in atom format
            $ch = curl_init();
            $headers = array();
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);            
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/atom+xml;charset=utf-8');

	        // Request feed in json format
            $ch = curl_init();
            $headers = array(ODataConstants::HTTP_REQUEST_ACCEPT .':'. ODataConstants::MIME_APPLICATION_JSON);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/json;charset=utf-8');

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}
	}

	/**
	 * Test content type of entry response	 
	 */
	function testContentType_Entry()
	{
        try {
	        // Request feed in atom format
            $ch = curl_init();
            $headers = array();
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers(\'ALFKI\')'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);            
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/atom+xml;charset=utf-8');

	        // Request feed in json format
            $ch = curl_init();
            $headers = array(ODataConstants::HTTP_REQUEST_ACCEPT .':'. ODataConstants::MIME_APPLICATION_JSON);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers(\'ALFKI\')'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/json;charset=utf-8');

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}
	}

	/**
	 * Test content type of primitive value
	 */
	function testContentType_Count_Value()
	{
        try {
	        // Request for count
            $ch = curl_init();
            $headers = array();
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers/$count'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'text/plain;charset=utf-8');

	        // Request for count in json format, this iwll cause service to thow error
            $ch = curl_init();
            $headers = array(ODataConstants::HTTP_REQUEST_ACCEPT .':'. ODataConstants::MIME_APPLICATION_JSON);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers/$count'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $pos = strpos($result, '"value": "Unsupported media type requested."');
            $this->assertTrue($pos !== false);
            // Should get error message for unsupported media type in json format
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/json');
            
	        // Request for count in atom format, this iwll cause service to thow error
            $ch = curl_init();
            $headers = array(ODataConstants::HTTP_REQUEST_ACCEPT .':'. ODataConstants::MIME_APPLICATION_ATOM);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers/$count'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $pos = strpos($result, '<message>Unsupported media type requested.</message>');
            $this->assertTrue($pos !== false);
            // Should get error message for unsupported media type in json format
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/xml');

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}
	}

	/**
	 * Test content type for $links response
	 */
	function testContentType_Links()
	{
	        try {
	        // Request for count
            $ch = curl_init();
            $headers = array();
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers(\'ALFKI\')/$links/Orders'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/xml;charset=utf-8');

	        // Request for $links in json format, is allowed
            $ch = curl_init();
            $headers = array(ODataConstants::HTTP_REQUEST_ACCEPT .':'. ODataConstants::MIME_APPLICATION_JSON);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers(\'ALFKI\')/$links/Orders'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            $pos = strpos($result, '"uri": "http://');
            $this->assertTrue($pos !== false);
            // Should get error message for unsupported media type in json format
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/json;charset=utf-8');
            
	        // Request for $links in atom format, does not support
            $ch = curl_init();
            $headers = array(ODataConstants::HTTP_REQUEST_ACCEPT .':'. ODataConstants::MIME_APPLICATION_ATOM);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers(\'ALFKI\')/$links/Orders'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $pos = strpos($result, '<message>Unsupported media type requested.</message>');
            $this->assertTrue($pos !== false);
            // Should get error message for unsupported media type in json format
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/xml');

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}
	}

	/**
	 * Test content tyoe for complex value
	 */
	function testContentType_Complex()
	{
        try {
	        // Request for complex
            $ch = curl_init();
            $headers = array();
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers(\'ALFKI\')/Address'); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/xml;charset=utf-8');

	        // Request for complex in json format, is allowed
            $ch = curl_init();
            $headers = array(ODataConstants::HTTP_REQUEST_ACCEPT .':'. ODataConstants::MIME_APPLICATION_JSON);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers(\'ALFKI\')/Address');  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            $pos = strpos($result, '"Address": {');
            $this->assertTrue($pos !== false);
            // Should get error message for unsupported media type in json format
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/json;charset=utf-8');
            
	        // Request for $links in atom format, does not support
            $ch = curl_init();
            $headers = array(ODataConstants::HTTP_REQUEST_ACCEPT .':'. ODataConstants::MIME_APPLICATION_ATOM);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers(\'ALFKI\')/Address');  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $pos = strpos($result, '<message>Unsupported media type requested.</message>');
            $this->assertTrue($pos !== false);
            // Should get error message for unsupported media type in json format
            $this->assertTrue(array_key_exists('content_type', $info));
            $this->assertEquals($info['content_type'], 'application/xml');

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}
	}


    // Test if-Match
	function testETag_For_Entry1()
	{
        try {
	        // Get ETag of an entry
	        $responseHeaders = get_headers(self::BASE_SERIVE_URL . '/Orders(OrderID=10248)', 1);
	        $this->assertTrue(array_key_exists('ETag', $responseHeaders));
	        $eTag = $responseHeaders['ETag'];
	        
	        //Get the same entry without if-Match or If-None-Match header 
            $ch = curl_init();
            $headers = array();
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Orders(OrderID=10248)');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $order_entry1 = curl_exec($ch);            
            curl_close($ch);
            
            //Get the same entry with correct if-Match value
            $ch = curl_init();
            $headers = array('If-Match:' . $eTag);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Orders(OrderID=10248)');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $order_entry2 = curl_exec($ch);            
            curl_close($ch);
            
            $this->assertNotEmpty($order_entry1);
            $this->assertNotEmpty($order_entry2);
            $this->assertContains("<id>".self::BASE_SERIVE_URL."/Orders(OrderID=10248)</id>",$order_entry1);
            $this->assertContains("<id>".self::BASE_SERIVE_URL."/Orders(OrderID=10248)</id>",$order_entry2);
            $order_entry1 = preg_replace('/<updated>.*<\/updated>/', '', $order_entry1);
            $order_entry2 = preg_replace('/<updated>.*<\/updated>/', '', $order_entry2);
            $this->assertEquals($order_entry1, $order_entry2);

            //Try to get the same entry with incorrect if-Match value
            $ch = curl_init();
            $headers = array('If-Match:' . 'ABC');
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Orders(OrderID=10248)');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            curl_close($ch);
            $pos = strpos($result, '<message>The etag value in the request header does not match with the current etag value of the object.</message>');
            $this->assertTrue($pos !== false);
	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    
	}

	//test If-None-Match
	function testETag_For_Entry2()
	{
        try {
	        // Get ETag of an entry
	        $responseHeaders = get_headers(self::BASE_SERIVE_URL . '/Orders(OrderID=10248)', 1);
	        $this->assertTrue(array_key_exists('ETag', $responseHeaders));
	        $eTag = $responseHeaders['ETag'];
        
            // Server will give entry only if eTag does not match, we are trying wih
            // matching etag so server should not give response body.
            $ch = curl_init();
            $headers = array('If-None-Match:' . $eTag);
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Orders(OrderID=10248)');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $this->assertTrue(array_key_exists('http_code', $info));  
            $this->assertEquals($info['http_code'], HttpStatus::CODE_NOT_MODIFIED);

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    
	}

	// eTag headers are not allowed if the requested entry doed not have etag property defined
	function testETag_For_Entry3()
	{
        try {        
            // If-Match/If-None-Match allowed only for entry with etag properties
            $ch = curl_init();
            $headers = array('If-None-Match:' . 'AAA');
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers(\'ALFKI\')');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $pos = strpos($result, '<message>If-Match or If-None-Match headers cannot ');
            $this->assertTrue($pos !== false);

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    
	}

	// ETag headrs are not allowed for feed
	function testETag_For_Feed()
	{
        try {        
            // If-Match/If-None-Match allowed only for entry with etag properties
            $ch = curl_init();
            $headers = array('If-None-Match:' . 'AAA');
            curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL . '/Customers');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $pos = strpos($result, '<message>If-Match or If-None-Match HTTP headers cannot be specified since the URI \'http');
            $this->assertTrue($pos !== false);

	    } catch (\Exception $exception) {
		    $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
		}    
	}

	function in_array($haystack)
	{
	    return (in_array(strtolower($needle), array_map('strtolower', array_keys($haystack)))) ;
	}

	protected function tearDown()
	{
	}
}
?>