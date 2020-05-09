<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\ObjectModel\ObjectModelSerializer;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\Web\IncomingRequest;
use ReflectionException;
use UnitTests\POData\TestCase;

class SerialiserTestBase extends TestCase
{
    /**
     * @param  string              $method
     * @throws ReflectionException
     * @return IncomingRequest
     */
    protected function setUpRequest(string $method = 'GET')
    {
        $verb = new HTTPRequestMethod($method);

        $request          = m::mock(IncomingRequest::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $request->shouldReceive('getMethod')->andReturn($verb);
        $request->shouldReceive('getBaseUrl')->andReturn('http://localhost/');
        //$request->shouldReceive('getQueryString')->andReturn('');
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('isSecure')->andReturn(false);
        $request->shouldReceive('getPort')->andReturn(80);
        $request->shouldReceive('getRequestHeader')
            ->withArgs([ODataConstants::HTTPREQUEST_HEADER_DATA_SERVICE_VERSION])->andReturn('3.0');

        return $request;
    }
}
