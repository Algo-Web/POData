<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use Mockery as m;
use POData\Common\ODataException;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use UnitTests\POData\TestCase;

class ConstantExpressionTest extends TestCase
{
    public function testFree()
    {
        $iType = m::mock(IType::class);
        $value = '240';

        $foo = new ConstantExpression($value, $iType);
        $this->assertNotNull($foo->getValue());
        $foo->free();
        $this->assertNull($foo->getValue());
    }
}
