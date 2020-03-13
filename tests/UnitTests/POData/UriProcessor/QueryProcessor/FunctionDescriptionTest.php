<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor\QueryProcessor;

use Mockery as m;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\Guid;
use POData\Providers\Metadata\Type\Null1;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionToken;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use UnitTests\POData\TestCase;

class FunctionDescriptionTest extends TestCase
{
    public function testEqualityofAddAndSubtractOperationFunctions()
    {
        $add = FunctionDescription::addOperationFunctions();
        $sub = FunctionDescription::subtractOperationFunctions();

        $this->assertEquals($add, $sub);
    }

    public function testBinaryEqualityFunctions()
    {
        $foo = FunctionDescription::binaryEqualityFunctions();
        $this->assertEquals(1, count($foo));
        $foo = $foo[0];
        $this->assertEquals('binaryEqual', $foo->name);
        $this->assertTrue($foo->returnType instanceof Boolean);
        $this->assertTrue($foo->argumentTypes[0] instanceof Binary);
        $this->assertTrue($foo->argumentTypes[1] instanceof Binary);
    }

    public function testVerifyRelationalArgsWithGuidAndLesserThan()
    {
        $token           = new ExpressionToken();
        $token->Text     = ODataConstants::KEYWORD_LESSTHAN;
        $token->Position = 0;
        $left            = m::mock(AbstractExpression::class);
        $left->shouldReceive('typeIs')->with(m::type(Guid::class))->andReturn(true);
        $left->shouldReceive('typeIs')->with(m::type(Null1::class))->andReturn(false);
        $right = m::mock(AbstractExpression::class);
        $right->shouldReceive('typeIs')->with(m::type(Guid::class))->andReturn(true);
        $right->shouldReceive('typeIs')->with(m::type(Null1::class))->andReturn(false);

        $expected = 'The operator \'lt\' at position 0 is not supported for the Edm.Guid; only'
                    . ' equality checks are supported';
        $actual = null;

        try {
            FunctionDescription::verifyRelationalOpArguments($token, $left, $right);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testVerifyRelationalArgsWithBinaryAndLesserThan()
    {
        $token           = new ExpressionToken();
        $token->Text     = ODataConstants::KEYWORD_LESSTHAN;
        $token->Position = 0;
        $left            = m::mock(AbstractExpression::class);
        $left->shouldReceive('typeIs')->with(m::type(Binary::class))->andReturn(true)->once();
        $left->shouldReceive('typeIs')->with(m::type(Guid::class))->andReturn(false);
        $left->shouldReceive('typeIs')->with(m::type(Null1::class))->andReturn(false);
        $right = m::mock(AbstractExpression::class);
        $right->shouldReceive('typeIs')->with(m::type(Binary::class))->andReturn(true)->once();
        $right->shouldReceive('typeIs')->with(m::type(Guid::class))->andReturn(true);
        $right->shouldReceive('typeIs')->with(m::type(Null1::class))->andReturn(false);

        $expected = 'The operator \'lt\' at position 0 is not supported for the Edm.Binary; only'
                    . ' equality checks are supported';
        $actual = null;

        try {
            FunctionDescription::verifyRelationalOpArguments($token, $left, $right);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testVerifyRelationalArgsWithBinaryAndEqualTo()
    {
        $token           = new ExpressionToken();
        $token->Text     = ODataConstants::KEYWORD_NOT_EQUAL;
        $token->Position = 0;
        $left            = m::mock(AbstractExpression::class);
        $left->shouldReceive('typeIs')->with(m::type(Binary::class))->andReturn(true)->once();
        $left->shouldReceive('typeIs')->with(m::type(Guid::class))->andReturn(false);
        $left->shouldReceive('typeIs')->with(m::type(Null1::class))->andReturn(false);
        $right = m::mock(AbstractExpression::class);
        $right->shouldReceive('typeIs')->with(m::type(Binary::class))->andReturn(true)->once();
        $right->shouldReceive('typeIs')->with(m::type(Guid::class))->andReturn(true);
        $right->shouldReceive('typeIs')->with(m::type(Null1::class))->andReturn(false);

        FunctionDescription::verifyRelationalOpArguments($token, $left, $right);
    }
}
