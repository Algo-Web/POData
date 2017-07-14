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
        $foo = new WebOperationContext();
        $this->assertTrue($foo->incomingRequest() instanceof IHTTPRequest);
        $this->assertTrue($foo->outgoingResponse() instanceof OutgoingResponse);
    }
}
