<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 7/05/20
 * Time: 9:32 PM.
 */
namespace UnitTests\POData\OperationContext\Web;

use AlgoWeb\ODataMetadata\Tests\TestCase;
use Illuminate\Http\Request;
use Mockery as m;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext;

class IlluminateOperationContextTest extends TestCase
{
    public function testIncomingRequest()
    {
        $req = m::mock(Request::class);
        $req->shouldReceive('getMethod')->andReturn('GET');
        $context = new IlluminateOperationContext($req);

        $this->assertNotNull($context->incomingRequest());
    }

    public function testOutgoingResponse()
    {
        $req = m::mock(Request::class);
        $req->shouldReceive('getMethod')->andReturn('GET');
        $context = new IlluminateOperationContext($req);

        $this->assertNotNull($context->outgoingResponse());
    }
}
