<?php

namespace UnitTests\POData\OperationContext\Web;

use POData\OperationContext\Web\IncomingRequest;

class IncomingRequestTest extends \PHPUnit_Framework_TestCase {

    public function testIncomingRequestDoesNotDecodeTooEarlyInParseProcess()
    {
        //The incoming request parses a PHP Super Globals so let's set those up
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = '$format=json&$expand=PhysicalAddress&$filter=Active%20eq%20true%20and%20(Segments/any(s:%20s/Name%20eq%20\'Cedar%20Rapids-Waterloo-Iowa%20City%26Dubuque\'))';

        $incoming = new IncomingRequest();
        $qs = $incoming->getQueryString();

        $this->assertEquals('$format=json&$expand=PhysicalAddress&$filter=Active%20eq%20true%20and%20(Segments/any(s:%20s/Name%20eq%20\'Cedar%20Rapids-Waterloo-Iowa%20City%26Dubuque\'))', $qs, "Query string should not be decoded yet");

        $expectedCount = array(
            '$format' => 1,
            '$expand' => 1,
            '$filter' => 1,
        );

        $this->assertEquals($expectedCount, $incoming->getQueryParametersCount());

        $expectedParameters = array(
            array(
                '$format' => 'json'
            ),
            array(
                '$expand' => 'PhysicalAddress'
            ),
            array(
                //Notice the value of this URI component has been decoded
                '$filter' => "Active eq true and (Segments/any(s: s/Name eq 'Cedar Rapids-Waterloo-Iowa City&Dubuque'))"
            ),
        );

        $this->assertEquals($expectedParameters, $incoming->getQueryParameters());

    }


    public function testIncomingRequestIsNotDoubleDecoded()
    {
        //The incoming request parses a PHP Super Globals so let's set those up
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = '$filter=' . rawurlencode('%20');

        $incoming = new IncomingRequest();
        $qs = $incoming->getQueryString();

        $this->assertEquals('$filter=%2520', $qs, "Query string should not be decoded yet");

        $expectedCount = array(
            '$filter' => 1,
        );

        $this->assertEquals($expectedCount, $incoming->getQueryParametersCount());

        $expectedParameters = array(
            array(
                //Notice the value of this URI component has been decoded
                '$filter' => "%20"
            ),
        );

        $this->assertEquals($expectedParameters, $incoming->getQueryParameters());

    }

}