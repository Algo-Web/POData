<?php

namespace UnitTests\POData\Common;

use Illuminate\Http\Request;
use Mockery as m;
use POData\BaseService;
use POData\BatchProcessor\BatchProcessor;
use POData\BatchProcessor\ChangeSetParser;
use POData\BatchProcessor\QueryParser;
use POData\Common\ErrorHandler;
use POData\Common\HttpStatus;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\OutgoingResponse;
use POData\UriProcessor\RequestDescription;
use UnitTests\POData\BatchProcessor\ChangeSetParserDummy;
use UnitTests\POData\TestCase;

class ChangeSetParserTest extends TestCase
{
    public function testGetters()
    {
        $service = m::mock(BaseService::class);
        $body = 'body';

        $foo = new QueryParser($service, $body);

        $this->assertTrue($foo->getService() instanceof BaseService);
        $this->assertTrue(is_string($foo->getData()));
        $this->assertEquals('', $foo->getBoundary());
    }

    public function testHandleData()
    {
        $service = m::mock(BaseService::class);
        $body = ' 
Content-Type: multipart/mixed; boundary=changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621 
Content-Length: ###       

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621 
Content-Type: application/http 
Content-Transfer-Encoding: binary 

POST /service/Customers HTTP/1.1 
Host: host  
Content-Type: application/atom+xml;type=entry 
Content-Length: ### 

<AtomPub representation of a new Customer> 

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621 
Content-Type: application/http 
Content-Transfer-Encoding:binary 

PUT /service/Customers(\'ALFKI\') HTTP/1.1 
Host: host 
Content-Type: application/json 
If-Match: xxxxx 
Content-Length: ### 

<JSON representation of Customer ALFKI> 

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621-- 

';
        $first = (object) [
            'RequestVerb' => 'POST',
            'RequestURL' => '/service/Customers',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/atom+xml;type=entry',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<AtomPub representation of a new Customer>',
            'Request' => null,
            'Response' => null];

        $second = (object) [
            'RequestVerb' => 'PUT',
            'RequestURL' => '/service/Customers(\'ALFKI\')',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_IF_MATCH' => 'xxxxx',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<JSON representation of Customer ALFKI>',
            'Request' => null,
            'Response' => null];


        $foo = new ChangeSetParser($service, $body);
        $foo->handleData();
        $result = $foo->getRawRequests();
        $this->assertEquals(2, count($result));
        $this->assertTrue(array_key_exists(-1, $result));
        $this->assertTrue(array_key_exists(-2, $result));
        $this->assertEquals($first, $result[-1]);
        $this->assertEquals($second, $result[-2]);
    }

    public function testHandleDataWithContentID()
    {
        $service = m::mock(BaseService::class);
        $body = ' 
Content-Type: multipart/mixed; boundary=changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621 
Content-Length: ###       

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621 
Content-Type: application/http 
Content-Transfer-Encoding: binary

POST /service/Customers HTTP/1.1 
Host: host  
Content-Type: application/atom+xml;type=entry 
Content-Length: ### 

<AtomPub representation of a new Customer> 

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621 
Content-Type: application/http 
Content-Transfer-Encoding:binary

PUT /service/Customers(\'ALFKI\') HTTP/1.1 
Host: host 
Content-Type: application/json 
If-Match: xxxxx 
Content-Length: ###
Content-ID: 2

<JSON representation of Customer ALFKI> 

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621-- 

';

        $first = (object) [
            'RequestVerb' => 'POST',
            'RequestURL' => '/service/Customers',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/atom+xml;type=entry',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<AtomPub representation of a new Customer>',
            'Request' => null,
            'Response' => null];

        $second = (object) [
            'RequestVerb' => 'PUT',
            'RequestURL' => '/service/Customers(\'ALFKI\')',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_IF_MATCH' => 'xxxxx',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<JSON representation of Customer ALFKI>',
            'Request' => null,
            'Response' => null];


        $foo = new ChangeSetParser($service, $body);
        $foo->handleData();
        $result = $foo->getRawRequests();
        $this->assertEquals(2, count($result));
        $this->assertTrue(array_key_exists(-1, $result));
        $this->assertTrue(array_key_exists(2, $result));
        $this->assertEquals($first, $result[-1]);
        $this->assertEquals($second, $result[2]);
    }

    public function testHandleDataWithMalformedHeaderLine()
    {
        $service = m::mock(BaseService::class);
        $body = ' 
Content-Type: multipart/mixed; boundary=changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621 
Content-Length: ###       

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621 
Content-Type: application/http 
Content-Transfer-Encoding: binary

POST /service/Customers HTTP/1.1 
Host: host  
Content-Type: application/atom+xml:type=entry 
Content-Length: ### 

<AtomPub representation of a new Customer> 

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd621-- 

';

        $expected = 'Malformed header line: Content-Type: application/atom+xml:type=entry ';
        $actual = null;

        $foo = new ChangeSetParser($service, $body);
        try {
            $foo->handleData();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResponse()
    {
        $headers = ['X-Swedish-Chef' => 'bork bork bork!', 'Status' => '202 Updated'];
        $response = m::mock(OutgoingResponse::class);
        $response->shouldReceive('getStream')->andReturn('Stream II: ELECTRIC BOOGALOO');
        $response->shouldReceive('getHeaders')->andReturn($headers);

        $second = (object) [
            'RequestVerb' => 'PUT',
            'RequestURL' => '/service/Customers(\'ALFKI\')',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_IF_MATCH' => 'xxxxx',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<JSON representation of Customer ALFKI>',
            'Request' => null,
            'Response' => $response];

        $foo = m::mock(ChangeSetParser::class)->makePartial();
        $foo->shouldReceive('getRawRequests')->andReturn([-1 => $second])->once();

        $expected = '--
Content-Type: application/http
Content-Transfer-Encoding: binary

X-Swedish-Chef: bork bork bork!

Stream II: ELECTRIC BOOGALOO--
';

        $actual = $foo->getResponse();
        $this->assertTrue(false !== stripos($actual, 'Content-Type: application/http'));
        $this->assertTrue(false !== stripos($actual, 'Content-Transfer-Encoding: binary'));
        $this->assertTrue(false !== stripos($actual, 'X-Swedish-Chef: bork bork bork!'));
        $this->assertTrue(false !== stripos($actual, 'Stream II: ELECTRIC BOOGALOO--'));
    }

    public function testProcess()
    {
        $response = m::mock(OutgoingResponse::class);
        $response->shouldReceive('getHeaders')->andReturn(['Location' => 'Location Location'])->times(4);

        $second = (object) [
            'RequestVerb' => 'PUT',
            'RequestURL' => '/service/Customers(\'ALFKI\')',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_IF_MATCH' => 'xxxxx',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<JSON representation of Customer ALFKI>',
            'Request' => null,
            'Response' => $response];

        $first = (object) [
            'RequestVerb' => 'POST',
            'RequestURL' => '/service/Customers',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/atom+xml;type=entry',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<AtomPub representation of a new Customer>',
            'Request' => null,
            'Response' => $response];

        $foo = m::mock(ChangeSetParser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRawRequests')->andReturn([-1 => $second, 1 => $first])->once();
        $foo->shouldReceive('processSubRequest')->with($second)->andReturnNull()->once();
        $foo->shouldReceive('processSubRequest')->with($first)->andReturnNull()->once();

        $foo->process();
    }

    public function testProcessWithBadLocation()
    {
        $response = m::mock(OutgoingResponse::class);
        $response->shouldReceive('getHeaders')->andReturn(['Location' => null])->times(1);

        $second = (object) [
            'RequestVerb' => 'PUT',
            'RequestURL' => '/service/Customers(\'ALFKI\')',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_IF_MATCH' => 'xxxxx',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<JSON representation of Customer ALFKI>',
            'Request' => null,
            'Response' => $response];

        $first = (object) [
            'RequestVerb' => 'POST',
            'RequestURL' => '/service/Customers',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/atom+xml;type=entry',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<AtomPub representation of a new Customer>',
            'Request' => null,
            'Response' => $response];

        $foo = m::mock(ChangeSetParser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRawRequests')->andReturn([-1 => $second, 1 => $first])->once();
        $foo->shouldReceive('processSubRequest')->with($second)->andReturnNull()->once();
        $foo->shouldReceive('processSubRequest')->with($first)->andReturnNull()->never();

        $expected = 'Location header not set in subrequest response for PUT request url /service/Customers(\'ALFKI\')';
        $actual = null;

        try {
            $foo->process();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testProcessSubRequest()
    {
        $service = m::mock(BaseService::class);
        $service->shouldReceive('setHost')->andReturnNull()->atLeast(1);
        $service->shouldReceive('handleRequest')->andReturnNull()->atLeast(1);
        $body = 'foo';
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('POST');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/service.svc/Customers');

        $first = (object) [
            'RequestVerb' => 'POST',
            'RequestURL' => '/service/Customers',
            'ServerParams' =>
                ['HTTP_HOST' => 'host',
                    'CONTENT_TYPE' => 'application/atom+xml;type=entry',
                    'HTTP_CONTENT_LENGTH' => '###'],
            'Content' => '<AtomPub representation of a new Customer>',
            'Request' => $request,
            'Response' => null];

        $foo = new ChangeSetParserDummy($service, $body);
        $foo->processSubRequest($first);
        $this->assertNotNull($first->Response);
    }
}
