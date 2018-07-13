<?php

namespace UnitTests\POData\OperationContext\Web;

use POData\Common\ODataConstants;
use POData\HttpProcessUtility;
use POData\OperationContext\Web\IncomingRequest;
use UnitTests\POData\TestCase;

class IncomingRequestTest extends TestCase
{
    public function tearDown()
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['QUERY_STRING']);
        unset($_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]);
        unset($_SERVER[ODataConstants::HTTPREQUEST_URI]);
        unset($_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]);
    }

    public function testIncomingRequestDoesNotDecodeTooEarlyInParseProcess()
    {
        //The incoming request parses a PHP Super Globals so let's set those up
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = '$format=json&$expand=PhysicalAddress&$filter=Active%20eq%20true%20and%20(Segments/any(s:%20s/Name%20eq%20\'Cedar%20Rapids-Waterloo-Iowa%20City%26Dubuque\'))';

        $incoming = new IncomingRequest();

        $expectedParameters = [
            [
                '$format' => 'json',
            ],
            [
                '$expand' => 'PhysicalAddress',
            ],
            [
                //Notice the value of this URI component has been decoded
                '$filter' => "Active eq true and (Segments/any(s: s/Name eq 'Cedar Rapids-Waterloo-Iowa City&Dubuque'))",
            ],
        ];

        $this->assertEquals($expectedParameters, $incoming->getQueryParameters());
    }

    public function testIncomingRequestIsNotDoubleDecoded()
    {
        //The incoming request parses a PHP Super Globals so let's set those up
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = '$filter='.rawurlencode('%20');

        $incoming = new IncomingRequest();

        $expectedParameters = [
            [
                //Notice the value of this URI component has been decoded
                '$filter' => '%20',
            ],
        ];

        $this->assertEquals($expectedParameters, $incoming->getQueryParameters());
        $this->assertEquals('GET', $incoming->getMethod());
    }

    public function testIncomingRequestGetHttpsRawUrl()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = 'HTTPS';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost/';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = 'odata.svc';

        $expected = 'https://localhost/odata.svc';
        $actual = null;

        $incoming = new IncomingRequest();
        $actual = $incoming->getRawUrl();
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testIncomingRequestGetHttpRawUrl()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = 'HTTP';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost/';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = 'odata.svc';

        $expected = 'http://localhost/odata.svc';
        $actual = null;

        $incoming = new IncomingRequest();
        $actual = $incoming->getRawUrl();
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetRequestMethodHeader()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = 'HTTPS';
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost/';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = 'odata.svc';

        $expected = 'GET';
        $actual = null;

        $incoming = new IncomingRequest();
        $actual = $incoming->getRequestHeader('REQUEST_METHOD');
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
        $this->assertNull($incoming->getRequestHeader('REQUEST_TYPE'));
    }

    public function testGetEmptyQueryStringParameters()
    {
        $expected = [ ['$filter' => '%20']];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = '$filter='.rawurlencode('%20');
        $incoming = new IncomingRequest();
        $result = $incoming->getQueryParameters();
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result);
    }
}
