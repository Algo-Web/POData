<?php

/**
 * Note:
 * 1. This test case requires the service 'NorthWind' to be
 *    accessed using http://localhost:8086/NorthWind.svc.
 */
use POData\Common\HttpStatus;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;

class TestETag extends PHPUnit_Framework_TestCase
{
    const BASE_SERIVE_URL = 'http://localhost:8089/NorthWind.svc';

    protected function setUp()
    {
    }

    /**
     * Test content ype header for service document.
     */
    public function testContentTypeHeader_ServiceDocument()
    {

        // Atom service document
        $ch = curl_init();
        $headers = [];
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
        $headers = [];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'?$format=json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('content_type', $info));
        $this->assertEquals($info['content_type'], 'application/json;charset=utf-8');
    }

    /**
     * Test content type header for metadata.
     */
    public function testContentTypeHeader_Metadata()
    {

        // Atom metadata document
        $ch = curl_init();
        $headers = [];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/$metadata');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('content_type', $info));
        $this->assertEquals($info['content_type'], 'application/xml;charset=utf-8');

        // request unsupported content type (metadata cannot be in json format)
        $ch = curl_init();
        $headers = [ODataConstants::HTTP_REQUEST_ACCEPT.':'.MimeTypes::MIME_APPLICATION_JSON];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/$metadata');
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
    }

    /**
     * Test content type of feed response.
     */
    public function testContentType_Feed()
    {

        // Request feed in atom format
        $ch = curl_init();
        $headers = [];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('content_type', $info));
        $this->assertEquals($info['content_type'], 'application/atom+xml;charset=utf-8');

        // Request feed in json format
        $ch = curl_init();
        $headers = [ODataConstants::HTTP_REQUEST_ACCEPT.':'.MimeTypes::MIME_APPLICATION_JSON];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('content_type', $info));
        $this->assertEquals($info['content_type'], 'application/json;charset=utf-8');
    }

    /**
     * Test content type of entry response.
     */
    public function testContentType_Entry()
    {

        // Request feed in atom format
        $ch = curl_init();
        $headers = [];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers(\'ALFKI\')');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('content_type', $info));
        $this->assertEquals($info['content_type'], 'application/atom+xml;charset=utf-8');

        // Request feed in json format
        $ch = curl_init();
        $headers = [ODataConstants::HTTP_REQUEST_ACCEPT.':'.MimeTypes::MIME_APPLICATION_JSON];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers(\'ALFKI\')');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('content_type', $info));
        $this->assertEquals($info['content_type'], 'application/json;charset=utf-8');
    }

    /**
     * Test content type of primitive value.
     */
    public function testContentType_Count_Value()
    {

        // Request for count
        $ch = curl_init();
        $headers = [];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers/$count');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('content_type', $info));
        $this->assertEquals($info['content_type'], 'text/plain;charset=utf-8');

        // Request for count in json format, this iwll cause service to thow error
        $ch = curl_init();
        $headers = [ODataConstants::HTTP_REQUEST_ACCEPT.':'.MimeTypes::MIME_APPLICATION_JSON];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers/$count');
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
        $headers = [ODataConstants::HTTP_REQUEST_ACCEPT.':'.MimeTypes::MIME_APPLICATION_ATOM];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers/$count');
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
    }

    /**
     * Test content type for $links response.
     */
    public function testContentType_Links()
    {

        // Request for count
        $ch = curl_init();
        $headers = [];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers(\'ALFKI\')/$links/Orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('content_type', $info));
        $this->assertEquals($info['content_type'], 'application/xml;charset=utf-8');

        // Request for $links in json format, is allowed
        $ch = curl_init();
        $headers = [ODataConstants::HTTP_REQUEST_ACCEPT.':'.MimeTypes::MIME_APPLICATION_JSON];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers(\'ALFKI\')/$links/Orders');
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
        $headers = [ODataConstants::HTTP_REQUEST_ACCEPT.':'.MimeTypes::MIME_APPLICATION_ATOM];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers(\'ALFKI\')/$links/Orders');
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
    }

    /**
     * Test content tyoe for complex value.
     */
    public function testContentType_Complex()
    {

        // Request for complex
        $ch = curl_init();
        $headers = [];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers(\'ALFKI\')/Address');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('content_type', $info));
        $this->assertEquals($info['content_type'], 'application/xml;charset=utf-8');

        // Request for complex in json format, is allowed
        $ch = curl_init();
        $headers = [ODataConstants::HTTP_REQUEST_ACCEPT.':'.MimeTypes::MIME_APPLICATION_JSON];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers(\'ALFKI\')/Address');
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
        $headers = [ODataConstants::HTTP_REQUEST_ACCEPT.':'.MimeTypes::MIME_APPLICATION_ATOM];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers(\'ALFKI\')/Address');
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
    }

    // Test if-Match
    public function testETag_For_Entry1()
    {

        // Get ETag of an entry
        $responseHeaders = get_headers(self::BASE_SERIVE_URL.'/Orders(OrderID=10248)', 1);
        $this->assertTrue(array_key_exists('ETag', $responseHeaders));
        $eTag = $responseHeaders['ETag'];

        //Get the same entry without if-Match or If-None-Match header
        $ch = curl_init();
        $headers = [];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Orders(OrderID=10248)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $order_entry1 = curl_exec($ch);
        curl_close($ch);

        //Get the same entry with correct if-Match value
        $ch = curl_init();
        $headers = ['If-Match:'.$eTag];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Orders(OrderID=10248)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $order_entry2 = curl_exec($ch);
        curl_close($ch);

        $this->assertNotEmpty($order_entry1);
        $this->assertNotEmpty($order_entry2);
        $this->assertContains('<id>'.self::BASE_SERIVE_URL.'/Orders(OrderID=10248)</id>', $order_entry1);
        $this->assertContains('<id>'.self::BASE_SERIVE_URL.'/Orders(OrderID=10248)</id>', $order_entry2);
        $order_entry1 = preg_replace('/<updated>.*<\/updated>/', '', $order_entry1);
        $order_entry2 = preg_replace('/<updated>.*<\/updated>/', '', $order_entry2);
        $this->assertEquals($order_entry1, $order_entry2);

        //Try to get the same entry with incorrect if-Match value
        $ch = curl_init();
        $headers = ['If-Match:'.'ABC'];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Orders(OrderID=10248)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        $pos = strpos($result, '<message>The etag value in the request header does not match with the current etag value of the object.</message>');
        $this->assertTrue($pos !== false);
    }

    //test If-None-Match
    public function testETag_For_Entry2()
    {

        // Get ETag of an entry
        $responseHeaders = get_headers(self::BASE_SERIVE_URL.'/Orders(OrderID=10248)', 1);
        $this->assertTrue(array_key_exists('ETag', $responseHeaders));
        $eTag = $responseHeaders['ETag'];

        // Server will give entry only if eTag does not match, we are trying wih
        // matching etag so server should not give response body.
        $ch = curl_init();
        $headers = ['If-None-Match:'.$eTag];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Orders(OrderID=10248)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $this->assertTrue(array_key_exists('http_code', $info));
        $this->assertEquals($info['http_code'], HttpStatus::CODE_NOT_MODIFIED);
    }

    // eTag headers are not allowed if the requested entry doed not have etag property defined
    public function testETag_For_Entry3()
    {

        // If-Match/If-None-Match allowed only for entry with etag properties
        $ch = curl_init();
        $headers = ['If-None-Match:'.'AAA'];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers(\'ALFKI\')');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $pos = strpos($result, '<message>If-Match or If-None-Match headers cannot ');
        $this->assertTrue($pos !== false);
    }

    // ETag headrs are not allowed for feed
    public function testETag_For_Feed()
    {

        // If-Match/If-None-Match allowed only for entry with etag properties
        $ch = curl_init();
        $headers = ['If-None-Match:'.'AAA'];
        curl_setopt($ch, CURLOPT_URL, self::BASE_SERIVE_URL.'/Customers');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $pos = strpos($result, '<message>If-Match or If-None-Match HTTP headers cannot be specified since the URI \'http');
        $this->assertTrue($pos !== false);
    }

    public function in_array($haystack)
    {
        return in_array(strtolower($needle), array_map('strtolower', array_keys($haystack)));
    }

    protected function tearDown()
    {
    }
}
