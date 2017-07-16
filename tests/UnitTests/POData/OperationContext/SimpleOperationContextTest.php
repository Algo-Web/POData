<?php

namespace UnitTests\POData\OperationContext;

use Mockery as m;
use POData\OperationContext\SimpleOperativeContext;
use POData\OperationContext\SimpleRequestAdapter;
use POData\OperationContext\Web\OutgoingResponse;
use UnitTests\POData\TestCase;

class SimpleOperationContextTest extends TestCase
{
    public function testCreateSimpleOperationContext()
    {
        $request = new SimpleRequestAdapter([]);
        
        $foo = new SimpleOperativeContext($request);
        $this->assertTrue($foo->incomingRequest() instanceof SimpleRequestAdapter);
        $this->assertTrue($foo->outgoingResponse() instanceof OutgoingResponse);
    }
}
