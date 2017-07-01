<?php

namespace UnitTests\POData\OperationContext\Web;

use Illuminate\Http\Request;
use POData\OperationContext\Web\Illuminate\IncomingIlluminateRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use UnitTests\POData\TestCase;
use Mockery as m;

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
        $request->shouldReceive('header')->withAnyArgs()->andReturn("");
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
}
