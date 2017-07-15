<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use Mockery as m;
use POData\Common\ODataException;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use UnitTests\POData\TestCase;

class FunctionCallExpressionTest extends TestCase
{
    public function testFree()
    {
        $descript = m::mock(FunctionDescription::class);
        $expr1 = m::mock(AbstractExpression::class);
        $expr1->shouldReceive('free')->andReturnNull()->once();
        $expr2 = m::mock(AbstractExpression::class);
        $expr2->shouldReceive('free')->andReturnNull()->once();

        $foo = new FunctionCallExpression($descript, [$expr1, $expr2]);
        $this->assertEquals(2, count($foo->getParamExpressions()));
        $foo->free();
        $this->assertEquals(0, count($foo->getParamExpressions()));
    }
}
