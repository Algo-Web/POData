<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use Mockery as m;
use POData\Common\ODataException;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use UnitTests\POData\TestCase;

class UnaryExpressionTest extends TestCase
{
    public function testFree()
    {
        $expr1 = m::mock(AbstractExpression::class);
        $expr1->shouldReceive('free')->andReturnNull()->once();
        $expression = m::mock(ExpressionType::class);
        $iType = m::mock(IType::class);

        $foo = new UnaryExpression($expr1, $expression, $iType);
        $this->assertNotNull($foo->getChild());
        $foo->free();
        $this->assertNull($foo->getChild());
    }
}
