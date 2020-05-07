<?php

declare(strict_types=1);

namespace UnitTests\POData\OperationContext\Web;

use Illuminate\Http\Request;
use Mockery as m;
use POData\OperationContext\Web\Illuminate\IncomingIlluminateRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use UnitTests\POData\TestCase;

class IncomingIlluminateRequestTest extends TestCase
{
    public function testHandlingHtmlExpansionDebrisInParmNames()
    {
        $rawParm = [
            '$orderBy' => 'CustomerTitle desc,id desc',
            'amp;$top' => '1',
            'amp;$skipToken' => "'University+of+Loamshire', 1, 1"
        ];

        $expectedParm = [
            [ '$orderby' => 'CustomerTitle desc,id desc'],
            [ '$top' => '1'],
            [ '$skiptoken' => "'University+of+Loamshire', 1, 1"]
        ];

        $request = new Request($rawParm, $rawParm);
        $request->setMethod('GET');

        $foo = new IncomingIlluminateRequest($request);

        $actualParm = $foo->getQueryParameters();
        $this->assertEquals($expectedParm, $actualParm);
    }

    public function testHandlingHtmlExpansionCapitalsInParmNames()
    {
        $rawParm = [
            '$orderBy' => 'CustomerTitle desc,id desc',
            '$Top' => '1',
            '$skipToken' => "'University+of+Loamshire', 1, 1"
        ];

        $expectedParm = [
            [ '$orderby' => 'CustomerTitle desc,id desc'],
            [ '$top' => '1'],
            [ '$skiptoken' => "'University+of+Loamshire', 1, 1"]
        ];

        $request = new Request($rawParm, $rawParm);
        $request->setMethod('GET');

        $foo = new IncomingIlluminateRequest($request);

        $actualParm = $foo->getQueryParameters();
        $this->assertEquals($expectedParm, $actualParm);
    }

    public function testFalseRequestHeaderReturnsNull()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('header')->withAnyArgs()->andReturn(false);
        $request->shouldReceive('getMethod')->andReturn('GET');

        $foo = new IncomingIlluminateRequest($request);
        $this->assertNull($foo->getRequestHeader('header'));
    }

    public function testEmptyRequestHeaderReturnsNull()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('header')->withAnyArgs()->andReturn('');
        $request->shouldReceive('getMethod')->andReturn('GET');

        $foo = new IncomingIlluminateRequest($request);
        $this->assertNull($foo->getRequestHeader('header'));
    }

    public function testNullRequestHeaderReturnsNull()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('header')->withAnyArgs()->andReturn(null);
        $request->shouldReceive('getMethod')->andReturn('GET');

        $foo = new IncomingIlluminateRequest($request);
        $this->assertNull($foo->getRequestHeader('header'));
    }

    public function testRawUrl()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('fullUrl')->andReturn('/odata.svc/bork?bork=true');

        $foo = new IncomingIlluminateRequest($request);
        $expected = '/odata.svc/bork?bork=true';
        $actual = $foo->getRawUrl();

        $this->assertEquals($expected, $actual);
    }

    public function testGetMethod()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');

        $foo = new IncomingIlluminateRequest($request);
        $expected = 'GET';
        $actual = $foo->getMethod();

        $this->assertEquals($expected, $actual);
    }

    public function testGetAllInputFromAll()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn('foo');
        $request->shouldReceive('content')->never();

        $foo = new IncomingIlluminateRequest($request);
        $expected = 'foo';
        $actual = $foo->getAllInput();
        $this->assertEquals($expected, $actual);
    }

    public function testGetAllInputFromContent()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([]);
        $request->shouldReceive('getContent')->andReturn('foo')->once();

        $foo = new IncomingIlluminateRequest($request);
        $expected = 'foo';
        $actual = $foo->getAllInput();
        $this->assertEquals($expected, $actual);
    }
}
