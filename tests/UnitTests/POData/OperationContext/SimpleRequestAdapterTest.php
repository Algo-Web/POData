<?php

namespace UnitTests\POData\OperationContext;

use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\SimpleRequestAdapter;
use UnitTests\POData\TestCase;
use Mockery as m;

class SimpleRequestAdapterTest extends TestCase
{
    public function testGetRawUrl()
    {
        // set up required superglobals
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = 'odata.svc';

        $foo = new SimpleRequestAdapter([]);
        $expected = 'https://localhost/odata.svc';
        $actual = $foo->getRawUrl();
        $this->assertEquals($expected, $actual);
    }

    public function testGetRequestMethod()
    {
        // set up required superglobals
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $foo = new SimpleRequestAdapter([]);
        $expected = HTTPRequestMethod::PUT();
        $actual = $foo->getMethod();
        $this->assertEquals($expected, $actual);
    }

    public function testGetQueryParms()
    {
        $expected = ['key' => 'secret'];
        $foo = new SimpleRequestAdapter($expected);
        $actual = $foo->getQueryParameters();
        $this->assertEquals([$expected], $actual);
    }
}
