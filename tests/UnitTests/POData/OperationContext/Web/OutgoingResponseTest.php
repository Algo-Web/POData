<?php

namespace UnitTests\POData\OperationContext\Web;

use POData\Common\ODataConstants;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\Web\OutgoingResponse;
use POData\OperationContext\Web\WebOperationContext;
use UnitTests\POData\TestCase;

class OutgoingResponseTest extends TestCase
{
    public function testEtagRoundTrip()
    {
        $foo = new OutgoingResponse();
        $foo->setETag('etag');
        $this->assertEquals('etag', $foo->getETag());
    }

    public function testAddHeader()
    {
        $foo = new OutgoingResponse();
        $foo->addHeader('MO-DO', 'Eins Zwei Polizei');
        $this->assertEquals('Eins Zwei Polizei', $foo->getHeaders()['MO-DO']);
    }

    public function testContentLengthRoundTrip()
    {
        $foo = new OutgoingResponse();
        $foo->setContentLength('100');
        $this->assertEquals('100', $foo->getHeaders()[ODataConstants::HTTPRESPONSE_HEADER_CONTENTLENGTH]);
    }

    public function testLastModifiedRoundTrip()
    {
        $foo = new OutgoingResponse();
        $foo->setLastModified('100');
        $this->assertEquals('100', $foo->getHeaders()[ODataConstants::HTTPRESPONSE_HEADER_LASTMODIFIED]);
    }

    public function testLocationRoundTrip()
    {
        $foo = new OutgoingResponse();
        $foo->setLocation('100');
        $this->assertEquals('100', $foo->getHeaders()[ODataConstants::HTTPRESPONSE_HEADER_LOCATION]);
    }

    public function testStatusDescriptionRoundTrip()
    {
        $foo = new OutgoingResponse();
        $foo->setStatusDescription('100');
        $this->assertEquals('100', $foo->getHeaders()[ODataConstants::HTTPRESPONSE_HEADER_STATUS_DESC]);
    }
}
