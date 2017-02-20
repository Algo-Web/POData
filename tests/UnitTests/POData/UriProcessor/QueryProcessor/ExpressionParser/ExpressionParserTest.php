<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser;

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
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\TestCase;

class ExpressionParserTest extends TestCase
{
    /** @var IMetadataProvider */
    private $northWindMetadata;

    /**
     * @var ResourceType
     */
    private $customersResourceType;

    protected function setUp()
    {
        $this->northWindMetadata = NorthWindMetadata::Create();

        $this->customersResourceType = $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType();
    }

    public function testConstantExpression()
    {
        $expression = '123';
        $parser = new ExpressionParser($expression, $this->customersResourceType, false);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof Int32);
        $this->assertEquals(123, $expr->getValue());

        $expression = '-127';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof Int32);
        $this->assertEquals(-127, $expr->getValue());

        $expression = '125L';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof Int64);
        $this->assertEquals('125', $expr->getValue());

        $expression = '122.3';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertEquals(122.3, $expr->getValue());

        $expression = '126E2';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertEquals('126E2', $expr->getValue());

        $expression = '121D';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertEquals('121', $expr->getValue());

        $expression = '126.3F';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof Single);
        $this->assertEquals('126.3', $expr->getValue());

        $expression = '126.3M';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof Decimal);
        $this->assertEquals('126.3', $expr->getValue());

        $expression = '126E2m';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof Decimal);
        $this->assertEquals('126E2', $expr->getValue());

        $expression = 'datetime\'1990-12-23\'';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ConstantExpression);
        $this->assertTrue($expr->getType() instanceof DateTime);
        $this->assertEquals("'1990-12-23'", $expr->getValue());

        $expression = 'datetime\'11990-12-23\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'dateime\' validation has not been raised');
        } catch (ODataException $ex) {
            $this->assertEquals('Unrecognized \'Edm.DateTime\' literal \'datetime\'11990-12-23\'\' in position \'0\'.', $ex->getMessage());
        }

        //$expression = 'binary\'ABCD\'';
        //$parser->resetParser($expression);
        //$expr = $parser->parseFilter();
        //$this->assertTrue($expr instanceof ConstantExpression);
        //$this->assertEquals($expr->getType() instanceof Binary, true);

        //$expression = 'X\'123F\'';
        //$parser->resetParser($expression);
        //$expr = $parser->parseFilter();
        //$this->assertTrue($expr instanceof ConstantExpression);
        //$this->assertEquals($expr->getType() instanceof Binary, true);
    }

    public function testPropertyAccessExpression()
    {
        $expression = 'CustomerID';
        $parser = new ExpressionParser($expression, $this->customersResourceType, false);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof PropertyAccessExpression);
        $this->assertTrue($expr->getType() instanceof StringType);

        $expression = 'Rating';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof PropertyAccessExpression);
        $this->assertTrue($expr->getType() instanceof Int32);

        $expression = 'Address';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof PropertyAccessExpression);
        $this->assertTrue($expr->getType() instanceof Navigation);
        $this->assertEquals('Address', $expr->getResourceType()->getFullName());

        $expression = 'Address/LineNumber';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof PropertyAccessExpression);
        $this->assertTrue($expr->getType() instanceof Int32);
        $this->assertEquals('Edm.Int32', $expr->getResourceType()->getFullName());
        $this->assertEquals('Address', $expr->getParent()->getResourceType()->getFullName());

        $expression = 'Address\LineNumber';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'invalid chatacter\' has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals('Invalid character \'\\\' at position 7', $exception->getMessage());
        }

        $expression = 'CustomerID1';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No property exists\' has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals('No property \'CustomerID1\' exists in type \'Customer\' at position 0', $exception->getMessage());
        }

        $expression = 'Address/InvalidLineNumber';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No property exists\' has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals('No property \'InvalidLineNumber\' exists in type \'Address\' at position 8', $exception->getMessage());
        }

        $expression = 'Orders/OrderID';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for collection property navigation was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('The \'Orders\' is an entity collection property of \'Customer\'', $exception->getMessage());
        }

        $expression = 'Customer/CustomerID';
        $parser = new ExpressionParser(
            $expression,
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            false
        );
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof PropertyAccessExpression);
        $this->assertTrue($expr->getType() instanceof StringType);
        $this->assertEquals('Edm.String', $expr->getResourceType()->getFullName());

        $expression = 'Customer/Orders/OrderID';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for collection property navigation was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('The \'Orders\' is an entity collection property of \'Customer\'', $exception->getMessage());
        }
    }

    public function testArithmeticExpressionAndOperandPromotion()
    {
        $expression = '1 add 2';
        $parser = new ExpressionParser($expression, $this->customersResourceType, false);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Int32);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight() instanceof ConstantExpression);
        $this->assertEquals(1, $expr->getLeft()->getValue());

        $expression = '1 sub 2.5';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight() instanceof ConstantExpression);
        $this->assertTrue($expr->getLeft()->getType() instanceof Double);
        $this->assertTrue($expr->getRight()->getType() instanceof Double);

        $expression = '1.1F sub 2';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Single);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight() instanceof ConstantExpression);
        $this->assertTrue($expr->getLeft()->getType() instanceof Single);
        $this->assertTrue($expr->getRight()->getType() instanceof Single);

        $expression = '1.1F mul 2.7';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight() instanceof ConstantExpression);
        $this->assertTrue($expr->getLeft()->getType() instanceof Double);
        $this->assertTrue($expr->getRight()->getType() instanceof Double);

        $expression = '1 add 2 sub 4';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals(ExpressionType::SUBTRACT, $expr->getNodeType());
        $this->assertTrue($expr->getLeft() instanceof ArithmeticExpression);
        $this->assertTrue($expr->getRight() instanceof ConstantExpression);
        $this->assertEquals(ExpressionType::ADD, $expr->getLeft()->getNodeType());
        $this->assertEquals(ExpressionType::CONSTANT, $expr->getRight()->getNodeType());
        $this->assertTrue($expr->getLeft()->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getLeft()->getRight() instanceof ConstantExpression);
        $this->assertEquals(1, $expr->getLeft()->getLeft()->getValue());

        $expression = '1 add (2 sub 4)';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals(ExpressionType::ADD, $expr->getNodeType());
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight() instanceof ArithmeticExpression);
        $this->assertEquals(ExpressionType::SUBTRACT, $expr->getRight()->getNodeType());
        $this->assertEquals(ExpressionType::CONSTANT, $expr->getLeft()->getNodeType());
        $this->assertTrue($expr->getRight()->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight()->getRight() instanceof ConstantExpression);
        $this->assertEquals(2, $expr->getRight()->getLeft()->getValue());

        $expression = '1 add (2 sub 4)';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals(ExpressionType::ADD, $expr->getNodeType());
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight() instanceof ArithmeticExpression);
        $this->assertEquals(ExpressionType::SUBTRACT, $expr->getRight()->getNodeType());
        $this->assertEquals(ExpressionType::CONSTANT, $expr->getLeft()->getNodeType());
        $this->assertTrue($expr->getRight()->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight()->getRight() instanceof ConstantExpression);
        $this->assertEquals(2, $expr->getRight()->getLeft()->getValue());

        $expression = '1 add 2 mul 4';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals(ExpressionType::ADD, $expr->getNodeType());
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight() instanceof ArithmeticExpression);
        $this->assertEquals(ExpressionType::MULTIPLY, $expr->getRight()->getNodeType());
        $this->assertEquals(ExpressionType::CONSTANT, $expr->getLeft()->getNodeType());
        $this->assertTrue($expr->getRight()->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight()->getRight() instanceof ConstantExpression);
        $this->assertEquals(4, $expr->getRight()->getRight()->getValue());

        $expression = 'Rating add 2.5 mul 3.4F';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertEquals(ExpressionType::ADD, $expr->getNodeType());
        $this->assertTrue($expr->getLeft() instanceof PropertyAccessExpression);
        $this->assertTrue($expr->getLeft()->getType() instanceof Double);

        $expression = '5.2 mul true';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Double and Boolean was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'mul\' incompatible with operand types Edm.Double and Edm.Boolean', $exception->getMessage());
        }

        $expression = '1F add 2M';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Single and Decimal was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Single and Edm.Decimal', $exception->getMessage());
        }

        $expression = "1 add 'MyString'";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Int32 and EdmString was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Int32 and Edm.String', $exception->getMessage());
        }

        $expression = '1F add 2M';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Single and Decimal was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Single and Edm.Decimal', $exception->getMessage());
        }

        $expression = "datetime'1990-12-12' add datetime'1991-11-11'";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between DateTime types was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.DateTime and Edm.DateTime', $exception->getMessage());
        }
    }

    public function testRelationalExpression()
    {
        $expression = '2.5 gt 2';
        $parser = new ExpressionParser($expression, $this->customersResourceType, true);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof RelationalExpression);
        $this->assertTrue($expr->getType() instanceof Boolean);

        $expression = 'true le false';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof RelationalExpression);
        $this->assertTrue($expr->getType() instanceof Boolean);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertTrue($expr->getRight() instanceof ConstantExpression);
        $this->assertEquals('true', $expr->getLeft()->getValue());
        $this->assertTrue($expr->getRight()->getType() instanceof Boolean);

        $expression = 'Country eq null';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof FunctionCallExpression);
        $this->assertEquals('is_null', $expr->getFunctionDescription()->name);
        $this->assertTrue($expr->getType() instanceof Boolean);
        $paramExpressions = $expr->getParamExpressions();
        $this->assertTrue($paramExpressions[0] instanceof PropertyAccessExpression);

        $expression = 'Country ge \'India\'';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof RelationalExpression);
        $this->assertEquals($expr->getNodeType(), ExpressionType::GREATERTHAN_OR_EQUAL);
        $this->assertTrue($expr->getLeft() instanceof FunctionCallExpression);
        $this->assertEquals('strcmp', $expr->getLeft()->getFunctionDescription()->name);
        $paramExpression = $expr->getLeft()->getParamExpressions();
        $this->assertTrue($paramExpression[0] instanceof PropertyAccessExpression);
        $this->assertTrue($paramExpression[1] instanceof ConstantExpression);
        $this->assertTrue($paramExpression[0]->getType() instanceof StringType);
        $this->assertTrue($paramExpression[1]->getType() instanceof StringType);
        $this->assertTrue($expr->getRight() instanceof ConstantExpression);
        $this->assertEquals($expr->getRight()->getValue(), 0);

        $expression = '1F gt 2M';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for gt operator\'s incompatible between Edm.Single and Edm.Decimal was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'gt\' incompatible with operand types Edm.Single and Edm.Decimal', $exception->getMessage());
        }

        $expression = 'Rating lt null';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for lt operator\'s incompatible for null was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('The operator \'lt\' at position 7 is not supported for the \'null\' literal; only equality checks', $exception->getMessage());
        }
    }

    public function testLogicalExpression()
    {
        $expression = 'true or false';
        $parser = new ExpressionParser($expression, $this->customersResourceType, false);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof LogicalExpression);
        $this->assertTrue($expr->getType() instanceof Boolean);

        $expression = '1 add 2 gt 5 and 5 le 8';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof LogicalExpression);
        $this->assertEquals(ExpressionType::AND_LOGICAL, $expr->getNodeType());
        $this->assertTrue($expr->getLeft() instanceof RelationalExpression);
        $this->assertTrue($expr->getRight() instanceof RelationalExpression);
        $this->assertEquals(ExpressionType::GREATERTHAN, $expr->getLeft()->getNodeType());
        $this->assertEquals(ExpressionType::LESSTHAN_OR_EQUAL, $expr->getRight()->getNodeType());
        $this->assertTrue($expr->getLeft()->getLeft() instanceof ArithmeticExpression);
        $this->assertTrue($expr->getLeft()->getRight() instanceof ConstantExpression);

        $expression = '1 add (2 gt 5) and 5 le 8';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for add operator\'s incompatible between Edm.Int32 and Edm.Boolean was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Int32 and Edm.Boolean', $exception->getMessage());
        }

        $expression = '12 or 34.5';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for or operator\'s incompatible between Edm.Int32 and Edm.Double was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'or\' incompatible with operand types Edm.Int32 and Edm.Double', $exception->getMessage());
        }

        $expression = '12.6F and true';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for and operator\'s incompatible between Edm.Single and Edm.Boolean was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'and\' incompatible with operand types Edm.Single and Edm.Boolean', $exception->getMessage());
        }

        $expression = '\'string1\' and \'string2\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for and operator\'s incompatible between Edm.String and Edm.String was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'and\' incompatible with operand types Edm.String and Edm.String', $exception->getMessage());
        }
    }

    public function testUnaryExpression()
    {
        $expression = '-Rating';
        $parser = new ExpressionParser($expression, $this->customersResourceType, false);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof UnaryExpression);
        $this->assertEquals(ExpressionType::NEGATE, $expr->getNodeType());
        $this->assertTrue($expr->getChild() instanceof PropertyAccessExpression);

        $expression = 'not(1 gt 4)';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof UnaryExpression);
        $this->assertEquals(ExpressionType::NOT_LOGICAL, $expr->getNodeType());
        $this->assertTrue($expr->getType() instanceof Boolean);
        $this->assertTrue($expr->getChild() instanceof RelationalExpression);

        $expression = '-\'myString\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for negate operator\'s incompatible with Edm.String was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'-\' incompatible with operand types Edm.String at position 0', $exception->getMessage());
        }

        $expression = 'not(1 mul 3)';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for not operator\'s incompatible with Edm.Int32 was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Operator \'not\' incompatible with operand types Edm.Int32 at position 0', $exception->getMessage());
        }
    }

    public function testFunctionCallExpression()
    {
        $expression = 'year(datetime\'1988-11-11\')';
        $parser = new ExpressionParser($expression, $this->customersResourceType, false);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof FunctionCallExpression);
        $this->assertTrue($expr->getType() instanceof Int32);

        $expression = "substring('pspl', 1) eq 'pl'";

        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof RelationalExpression);
        /* @var RelationalExpression $expr */
        $this->assertTrue($expr->getLeft() instanceof FunctionCallExpression);
        $this->assertEquals('strcmp', $expr->getLeft()->getFunctionDescription()->name);
        $paramExpressions = $expr->getLeft()->getParamExpressions();
        $this->assertEquals(2, count($paramExpressions));
        $this->assertTrue($paramExpressions[0] instanceof FunctionCallExpression);
        $this->assertEquals('substring', $paramExpressions[0]->getFunctionDescription()->name);
        $paramExpressions1 = $paramExpressions[0]->getParamExpressions();
        $this->assertEquals(2, count($paramExpressions1));
        $this->assertTrue($paramExpressions1[0] instanceof ConstantExpression);
        $this->assertEquals("'pspl'", $paramExpressions1[0]->getValue());

        $expression = 'unknownFun(1, 3)';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for and unknown function was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Unknown function \'unknownFun\' at position 0', $exception->getMessage());
        }

        $expression = 'endswith(\'mystring\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for function without closing bracket was not thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('Close parenthesis expected', $exception->getMessage());
        }

        $expression = 'trim()';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No applicable function found\' was not thrown for trim');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('No applicable function found for \'trim\' at position 0 with the specified arguments. The functions considered are: Edm.String trim(Edm.String)', $exception->getMessage());
        }

        $expression = 'month(123.4)';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No applicable function found\' was not thrown for month');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('No applicable function found for \'month\' at position', $exception->getMessage());
        }
    }
}
