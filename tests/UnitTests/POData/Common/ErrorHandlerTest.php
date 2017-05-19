<?php

namespace UnitTests\POData\Common;

use Mockery as m;
use POData\Common\ErrorHandler;
use POData\Common\HttpStatus;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\OutgoingResponse;
use UnitTests\POData\TestCase;

class ErrorHandlerTest extends TestCase
{
    public function testHandleODataException()
    {
        $exception = new ODataException('FAIL', 500);

        $outgoing = m::mock(OutgoingResponse::class);
        $outgoing->shouldReceive('setServiceVersion')
            ->withArgs([ODataConstants::DATASERVICEVERSION_1_DOT_0.';'])->andReturnNull()->once();
        $outgoing->shouldReceive('setStatusCode')->withArgs(['500 Internal Server Error'])->andReturnNull()->once();
        $outgoing->shouldReceive('setContentType')->withArgs(['application/xml'])->andReturnNull()->once();
        $outgoing->shouldReceive('setStream')->passthru();
        $outgoing->shouldReceive('getStream')->passthru();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse')->andReturn($outgoing);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getRequestAccept')->andReturn([MimeTypes::MIME_APPLICATION_HTTP]);
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        ErrorHandler::handleException($exception, $service);

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<error xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
 <code>500</code>
 <message>strlen() expects parameter 1 to be string, array given</message>
</error>
';
        $actual = $outgoing->getStream();
        $this->assertEquals($expected, $actual);
    }

    public function testHandleODataExceptionJson()
    {
        $exception = new ODataException('FAIL', 500);

        $outgoing = m::mock(OutgoingResponse::class);
        $outgoing->shouldReceive('setServiceVersion')
            ->withArgs([ODataConstants::DATASERVICEVERSION_1_DOT_0.';'])->andReturnNull()->once();
        $outgoing->shouldReceive('setStatusCode')->withArgs(['500 Internal Server Error'])->andReturnNull()->once();
        $outgoing->shouldReceive('setContentType')->withArgs(['application/json'])->andReturnNull()->once();
        $outgoing->shouldReceive('setStream')->passthru();
        $outgoing->shouldReceive('getStream')->passthru();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse')->andReturn($outgoing);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getRequestAccept')->andReturn(MimeTypes::MIME_APPLICATION_JSON);
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        ErrorHandler::handleException($exception, $service);

        $expected = '{
    "error":{
        "message":{
            "lang":"en-US","value":"FAIL"
        }
    }
}';
        $actual = $outgoing->getStream();
        $this->assertEquals($expected, $actual);
    }

    public function testHandleODataExceptionStatusCodeNotModified()
    {
        $exception = new ODataException('FAIL', HttpStatus::CODE_NOT_MODIFIED);

        $outgoing = m::mock(OutgoingResponse::class);
        $outgoing->shouldReceive('setServiceVersion')
            ->withArgs([ODataConstants::DATASERVICEVERSION_1_DOT_0.';'])->andReturnNull()->once();
        $outgoing->shouldReceive('setStatusCode')->withArgs(['304 Not Modified'])->andReturnNull()->never();
        $outgoing->shouldReceive('setContentType')->withArgs(['application/json'])->andReturnNull()->never();
        $outgoing->shouldReceive('setStream')->passthru();
        $outgoing->shouldReceive('getStream')->passthru();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse')->andReturn($outgoing);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getRequestAccept')->andReturn(MimeTypes::MIME_APPLICATION_JSON);
        $host->shouldReceive('getOperationContext')->andReturn($context);
        $host->shouldReceive('setResponseStatusCode')
            ->withArgs([HttpStatus::CODE_NOT_MODIFIED])->andReturnNull()->once();

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        ErrorHandler::handleException($exception, $service);
    }
}
