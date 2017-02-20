<?php

namespace UnitTests\POData\OperationContext\Web;

use POData\OperationContext\Web\IncomingRequest;
use UnitTests\POData\TestCase;

class IncomingRequestTest extends TestCase
{
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
    }
}
