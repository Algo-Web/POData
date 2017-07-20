<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Decimal;
use POData\Providers\Metadata\Type\Double;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\Int64;
use POData\Providers\Metadata\Type\Navigation;
use POData\Providers\Metadata\Type\Single;
use POData\Providers\Metadata\Type\StringType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionToken;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionTokenId;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\TestCase;

class ExpressionTokenTest extends TestCase
{
    public function testGetIdentifierOnNewCreationThrowsException()
    {
        $expected = 'Identifier expected at position ';
        $actual = null;

        $foo = new ExpressionToken();

        try {
            $foo->getIdentifier();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testIsEqualityOperatorNewCreation()
    {
        $foo = new ExpressionToken();
        $this->assertFalse($foo->isEqualityOperator());
    }

    public function testIsEqualityOperatorWithGoodIdentifierAndBadText()
    {
        $foo = new ExpressionToken();
        $foo->Id = ExpressionTokenId::IDENTIFIER;
        $foo->Text = [];
        $this->assertFalse($foo->isEqualityOperator());
    }

    public function testIsComparisonOperatorWithGoodIdentifierAndBadText()
    {
        $foo = new ExpressionToken();
        $foo->Id = ExpressionTokenId::IDENTIFIER;
        $foo->Text = [];
        $this->assertFalse($foo->isComparisonOperator());
    }

    public function testIsEqualityOperatorWithGoodIdentifierAndNullText()
    {
        $foo = new ExpressionToken();
        $foo->Id = ExpressionTokenId::IDENTIFIER;
        $foo->Text = null;
        $this->assertFalse($foo->isEqualityOperator());
    }

    public function testIsEqualityOperatorWithGoodIdentifierAndNotEqualText()
    {
        $foo = new ExpressionToken();
        $foo->Id = ExpressionTokenId::IDENTIFIER;
        $foo->Text = 'ne';
        $this->assertTrue($foo->isEqualityOperator());
    }

    public function testIsEqualityOperatorWithGoodIdentifierAndEqualText()
    {
        $foo = new ExpressionToken();
        $foo->Id = ExpressionTokenId::IDENTIFIER;
        $foo->Text = 'eq';
        $this->assertTrue($foo->isEqualityOperator());
    }
}
