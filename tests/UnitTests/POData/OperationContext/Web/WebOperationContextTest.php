<?php

namespace UnitTests\POData\OperationContext\Web;

use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\Web\OutgoingResponse;
use POData\OperationContext\Web\WebOperationContext;
use UnitTests\POData\TestCase;

class WebOperationContextTest extends TestCase
{
    public function testWebOperationContextCtor()
    {
        // set up required superglobal
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $foo = new WebOperationContext();
        $this->assertTrue($foo->incomingRequest() instanceof IHTTPRequest, get_class($foo->incomingRequest()));
        $this->assertTrue($foo->outgoingResponse() instanceof OutgoingResponse, get_class($foo->outgoingResponse()));
    }
}
