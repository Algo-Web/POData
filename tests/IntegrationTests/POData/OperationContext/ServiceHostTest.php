<?php
/**
 * Mainly test ServiceHost class.
 */
ob_start();
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\HttpProcessUtility;
use POData\OperationContext\ServiceHost;

class ServiceHostTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        ob_start();
    }

    /**
     * If RequestURI is proper URI and does not contain QueryParam then it will return proper o/p.
     */
    public function testServiceDispatchingUriRawUrlWithoutQueryParam()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = null;
        try {
            $exceptionThrown = false;
            $dispatcher = new Dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $this->assertContains('<feed xml:base="http://localhost:8086/NorthWind.svc', $contents);
            $this->assertContains('<id>http://localhost:8086/NorthWind.svc/Customers</id>', $contents);
            $absoluteUri = $dispatcher->getHost()->getAbsoluteRequestUriAsString();
            $this->assertEquals('http://localhost:8086/NorthWind.svc/Customers', $absoluteUri);
            $rawUrl = $dispatcher->getHost()->getWebOperationContext()->IncomingRequest()->getRawUrl();
            $this->assertEquals('http://localhost:8086/NorthWind.svc/Customers', $rawUrl);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Without Query Params - An unexpected exception  has been thrown:'.$exception->getMessage());
        }
    }

    /**
     * If RequestURI is proper URI and does not contain QueryParam then it will return proper o/p.
     */
    public function testServiceDispatchingUriRawUrlWithQueryParam()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = "/NorthWind.svc/Customers('AROUT')?\$select=CompanyName";
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$select=CompanyName';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $this->assertContains("<id>http://localhost:8086/NorthWind.svc/Customers(CustomerID='AROUT')</id>", $contents);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('With Query Params - An unexpected exception  has been thrown:'.$exception->getMessage());
        }
    }

    public function testInvalidServiceUri()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers1';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $this->assertContains('Resource not found for the segment', $contents);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('InvalidServiceUri - An unexpected exception  has been thrown:'.$exception->getMessage());
        }
    }

    public function testInvalidServiceUriContainsFragments()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = "/NorthWind.svc/Customers('AROUT')";
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $serviceUri = $dispatcher->getHost()->getAbsoluteServiceUriAsString();
            $this->assertEquals('http://localhost:8086/NorthWind.svc', $serviceUri);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('FragmentsInServiceUri - An unexpected exception  has been thrown:'.$exception->getMessage());
        }
    }

    public function testServiceUriContainsQueryParams()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = "/NorthWind.svc/Customers('AROUT')?\$select=CompanyName";
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$select=CompanyName';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $serviceUri = $dispatcher->getHost()->getAbsoluteServiceUriAsString();
            $this->assertEquals('http://localhost:8086/NorthWind.svc', $serviceUri);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('QueryStringInServiceUri - An unexpected exception  has been thrown:'.$exception->getMessage());
        }
    }

    public function testValidateQueryParametersFormatAtom()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers?$format=atom';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$format=atom';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $format = $dispatcher->getHost()->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FORMAT);
            $this->assertEquals('atom', $format);
            //print "Contents:".$contents;
            //$dispatcher->getHost()->validateQueryParameters();
            $requestAccept = $dispatcher->getHost()->getWebOperationContext()->IncomingRequest()->getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_ACCEPT);
            $this->assertEquals(MimeTypes::MIME_APPLICATION_ATOM.';q=1.0', $requestAccept);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Format=ATOM - An unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testValidateQueryParametersFormatJson()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers?$format=json';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$format=json';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $format = $dispatcher->getHost()->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FORMAT);
            $this->assertEquals('json', $format);
            //print "Contents:".$contents;
            //$dispatcher->getHost()->validateQueryParameters();
            $requestAccept = $dispatcher->getHost()->getWebOperationContext()->IncomingRequest()->getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_ACCEPT);
            $this->assertEquals(MimeTypes::MIME_APPLICATION_JSON.';q=1.0', $requestAccept);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Format=JSON - An unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testValidateQueryParameterEmptyTOP()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers?$top';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$top';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $this->assertContains("<message>Query parameter '\$top' is specified, but it should be specified with value.</message>", $contents);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('An unexpected exception has been occured for empty $top :', $exception->getMessage());
        }
    }

    public function testValidateQueryParameterEmptyFORMAT()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers?$format';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$format';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $this->assertContains("<message>Query parameter '\$format' is specified, but it should be specified with value.</message>", $contents);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('An unexpected exception has been occured for empty $format :', $exception->getMessage());
        }
    }

    public function testValidateQueryParameterUnknownOdataQueryOptionIsEmpty()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers?$my';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$my';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $this->assertContains("<message>The query parameter '\$my' begins with a system-reserved '$' character but is not recognized.</message>", $contents);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('An unexpected exception has been occured for empty $my :', $exception->getMessage());
        }
    }

    public function testValidateQueryParameterMoreThanOnce()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers?$format=atom&$format=atom';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$format=atom&$format=atom';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $this->assertContains("<message>Query parameter '\$format' is specified, but it should be specified exactly once.</message>", $contents);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('An unexpected exception has been occured : ', $exception->getMessage());
        }
    }

    public function testValidateQueryParameterEmptyOptionValueSystemParam()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers?$top=';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$top=';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            //print $contents;
            $this->assertContains("<message>Query parameter '\$top' is specified, but it should be specified with value.</message>", $contents);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('An unexpected exception has been occured : ', $exception->getMessage());
        }
    }

    public function testValidateQueryParameterEmptyOptionValueOnknownOdataOption()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers?$my=';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$my=';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            //print $contents;
            $this->assertContains("<message>The query parameter '\$my' begins with a system-reserved '$' character but is not recognized.</message>", $contents);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('An unexpected exception has been occured : ', $exception->getMessage());
        }
    }

    public function testValidateQueryParameterEmptyOptionValueMoreThanOnce()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers?$top=&$top=';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$top=&$top=';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            //print $contents;
            $this->assertContains(" <message>Query parameter '\$top' is specified, but it should be specified with value.</message>", $contents);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('An unexpected exception has been occured : ', $exception->getMessage());
        }
    }

    public function testRequestVersion()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_DATA_SERVICE_VERSION)] = '1.0';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $serviceVersion = $dispatcher->getHost()->getRequestVersion();
            $this->assertEquals('1.0', $serviceVersion);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('RequestServiceVersion - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testRequestMaxVersion()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_MAX_DATA_SERVICE_VERSION)] = '2.0';

        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $serviceMaxVersion = $dispatcher->getHost()->getRequestMaxVersion();
            $this->assertEquals('2.0', $serviceMaxVersion);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('RequestMaxServiceVersion - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testRequestAcceptCharSet()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_URI)] = '/NorthWind.svc/Customers';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_ACCEPT_CHARSET)] = 'ISO-8859-1';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $acceptCharSet = $dispatcher->getHost()->getRequestAcceptCharSet();
            $this->assertEquals('ISO-8859-1', $acceptCharSet);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('AcceptCharSet - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testRequestContentType()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        $_SERVER[ODataConstants::HTTPREQUEST_CONTENT_TYPE] = 'text/comma-separated-values';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $contentType = $dispatcher->getHost()->getRequestContentType();
            $this->assertEquals('text/comma-separated-values', $contentType);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('ContentType - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testRequestContentLength()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        $_SERVER[ODataConstants::HTTPREQUEST_CONTENT_LENGTH] = '1000';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $contentLength = $dispatcher->getHost()->getRequestContentLength();
            $this->assertEquals('1000', $contentLength);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('ContentLength - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testRequestMethod()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        //$_SERVER[ODataConstants::HttpRequestHeaderMethod]		= ODataConstants::oDataMethodInsert;
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $requestMethod = $dispatcher->getHost()->getRequestHttpMethod();
            $this->assertEquals('GET', $requestMethod);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('HTTP Request Method - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testRequestIfMatch()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_IF_MATCH)] = '123';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            //print "Contents".$contents;
            $ifMatch = $dispatcher->getHost()->getRequestIfMatch();
            $this->assertContains('<message>If-Match or If-None-Match HTTP headers cannot be specified', $contents);
            $this->assertContains('refers to a collection of resources', $contents);
            $this->assertEquals('123', $ifMatch);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('IfMatch - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testRequestIfNoneMatch()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_IF_NONE)] = '456';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $ifNoneMatch = $dispatcher->getHost()->getRequestIfNoneMatch();
            $this->assertContains('<message>If-Match or If-None-Match HTTP headers cannot be specified', $contents);
            $this->assertContains('refers to a collection of resources', $contents);
            $this->assertEquals('456', $ifNoneMatch);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('IfNoneMatch - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testSetResponseCacheControl()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseCacheControl('no-cache');
            $headers = &$dispatcher->getHost()->getWebOperationContext()->OutgoingResponse()->getHeaders();
            $this->assertEquals('no-cache', $headers[ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL]);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Cache-Control - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testSetResponseContentType()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseContentType('text/html');
            $this->assertEquals('text/html', $dispatcher->getHost()->getResponseContentType());
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Content-Type - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testSetResponseContentLength()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseContentLength('5000');
            $headers = &$dispatcher->getHost()->getWebOperationContext()->OutgoingResponse()->getHeaders();
            $this->assertEquals('5000', $headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTLENGTH]);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Content-Length - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testETag()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseETag('W/"123456789"');
            $this->assertEquals('W/"123456789"', $dispatcher->getHost()->getResponseETag());
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('ETag - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testSetResponseLocation()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseLocation('http://testing.com');
            $headers = &$dispatcher->getHost()->getWebOperationContext()->OutgoingResponse()->getHeaders();
            $this->assertEquals('http://testing.com', $headers[ODataConstants::HTTPRESPONSE_HEADER_LOCATION]);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Location - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testSetResponseStatusCodeValidCodeRange()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseStatusCode(404);
            $headers = &$dispatcher->getHost()->getWebOperationContext()->OutgoingResponse()->getHeaders();
            $this->assertEquals('404 Not Found', $headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS]);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Valid StatusCode Range - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testSetResponseStatusCodeValidCodeRangeInvalidCode()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseStatusCode(485);
            $headers = &$dispatcher->getHost()->getWebOperationContext()->OutgoingResponse()->getHeaders();
            $this->assertEquals('485', $headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS]);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Invalid Status Code - Some Unexpected exception has been occured:', $exception->getMessage());
        }
    }

    public function testSetResponseStatusCodeInvalidCodeRange()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            $dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseStatusCode(601);
            $headers = &$dispatcher->getHost()->getWebOperationContext()->OutgoingResponse()->getHeaders();
            $this->assertStringStartsWith('601', $headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE]);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->assertTrue(true);
        }
    }

    public function testSetResponseStatusDescription()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            //$dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseStatusDescription('Not Found');
            $headers = &$dispatcher->getHost()->getWebOperationContext()->OutgoingResponse()->getHeaders();
            $this->assertEquals('Not Found', $headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_DESC]);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Some unexpected exception has been thrown:', $exception->getMessage());
        }
    }

    public function testSetResponseVersion()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD] = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        try {
            $exceptionThrown = false;
            $dispatcher = new dispatcher();
            //Service dispatched
            //$dispatcher->dispatch();
            $contents = ob_get_contents();
            ob_end_clean();
            $dispatcher->getHost()->setResponseVersion('2.0');
            $headers = &$dispatcher->getHost()->getWebOperationContext()->OutgoingResponse()->getHeaders();
            $this->assertEquals('2.0', $headers[ODataConstants::ODATAVERSIONHEADER]);
        } catch (\Exception $exception) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $exceptionThrown = true;
            $this->fail('Some unexpected exception has been thrown:', $exception->getMessage());
        }
    }
}
