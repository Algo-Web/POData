<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\Int64;
use POData\Providers\Metadata\Type\Double;
use POData\Providers\Metadata\Type\Single;
use POData\Providers\Metadata\Type\Decimal;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\String;
use POData\Providers\Metadata\Type\Navigation;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\Null1;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser;
use POData\Common\ODataException;
use POData\Providers\Metadata\IMetadataProvider;

use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;

class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var IMetadataProvider  */
    private $_northWindMetadata;
    
    protected function setUp()
    {        
        $this->_northWindMetadata = NorthWindMetadata::Create();
    }

    public function testConstantExpression()
    {
        $expression = '123';
        $parser = new ExpressionParser($expression,
                     $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                     false);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertTrue($expr->getType() instanceof Int32);
        $this->assertEquals($expr->getValue(), 123);

        $expression = '-127';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertTrue($expr->getType() instanceof Int32);
        $this->assertEquals($expr->getValue(), -127);

        $expression = '125L';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertTrue($expr->getType() instanceof Int64);
        $this->assertEquals($expr->getValue(), '125');

        $expression = '122.3';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertEquals($expr->getValue(), 122.3);

        $expression = '126E2';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertEquals($expr->getValue(), '126E2');

        $expression = '121D';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertEquals('121', $expr->getValue());

        $expression = '126.3F';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertTrue($expr->getType() instanceof Single);
        $this->assertEquals('126.3', $expr->getValue());

        $expression = '126.3M';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertEquals($expr->getType() instanceof Decimal, true);
        $this->assertEquals('126.3', $expr->getValue());

        $expression = '126E2m';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertEquals($expr->getType() instanceof Decimal, true);
        $this->assertEquals($expr->getValue(), '126E2');

        $expression = 'datetime\'1990-12-23\'';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof ConstantExpression, true);
        $this->assertEquals($expr->getType() instanceof DateTime, true);
        $this->assertEquals($expr->getValue(), '\'1990-12-23\'');

        $expression = 'datetime\'11990-12-23\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'dateime\' validation has not been raised');
        }
        catch(ODataException $ex)
        {
            $this->assertEquals('Unrecognized \'Edm.DateTime\' literal \'datetime\'11990-12-23\'\' in position \'0\'.', $ex->getMessage());
        }


        //$expression = 'binary\'ABCD\'';
        //$parser->resetParser($expression);
        //$expr = $parser->parseFilter();
        //$this->assertEquals($expr instanceof ConstantExpression, true);
        //$this->assertEquals($expr->getType() instanceof Binary, true);

        //$expression = 'X\'123F\'';
        //$parser->resetParser($expression);
        //$expr = $parser->parseFilter();
        //$this->assertEquals($expr instanceof ConstantExpression, true);
        //$this->assertEquals($expr->getType() instanceof Binary, true);
            
    }
    
    public function testPropertyAccessExpression()
    {
        $expression = 'CustomerID';
        $parser = new ExpressionParser($expression,
                     $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                     false);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof PropertyAccessExpression, true);
        $this->assertEquals($expr->getType() instanceof String, true);

        $expression = 'Rating';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof PropertyAccessExpression, true);
        $this->assertTrue($expr->getType() instanceof Int32);

        $expression = 'Address';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof PropertyAccessExpression, true);
        $this->assertEquals($expr->getType() instanceof Navigation, true);
        $this->assertEquals($expr->getResourceType()->getFullName(), 'Address');

        $expression = 'Address/LineNumber';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof PropertyAccessExpression, true);
        $this->assertTrue($expr->getType() instanceof Int32);
        $this->assertEquals($expr->getResourceType()->getFullName(), 'Edm.Int32');
        $this->assertEquals($expr->getParent()->getResourceType()->getFullName(), 'Address');

        $expression = 'Address\LineNumber';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'invalid chatacter\' has not been thrown');
        } catch(ODataException $exception) {
            $this->assertEquals('Invalid character \'\\\' at position 7', $exception->getMessage());
        }

        $expression = 'CustomerID1';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No property exists\' has not been thrown');
        } catch(ODataException $exception) {
           $this->assertEquals('No property \'CustomerID1\' exists in type \'Customer\' at position 0', $exception->getMessage());
        }

        $expression = 'Address/InvalidLineNumber';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No property exists\' has not been thrown');
        } catch(ODataException $exception) {
           $this->assertEquals('No property \'InvalidLineNumber\' exists in type \'Address\' at position 8', $exception->getMessage());
        }

        $expression = 'Orders/OrderID';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for collection property navigation was not thrown');
        } catch(ODataException $exception) {
           $this->assertStringStartsWith('The \'Orders\' is an entity collection property of \'Customer\'', $exception->getMessage());
        }

        $expression = 'Customer/CustomerID';
        $parser = new ExpressionParser($expression,
                     $this->_northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
                     false);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof PropertyAccessExpression, true);
        $this->assertEquals($expr->getType() instanceof String, true);
        $this->assertEquals($expr->getResourceType()->getFullName(), 'Edm.String');

        $expression = 'Customer/Orders/OrderID';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for collection property navigation was not thrown');
        } catch(ODataException $exception) {
           $this->assertStringStartsWith('The \'Orders\' is an entity collection property of \'Customer\'', $exception->getMessage());
        }


    }

    public function testArithmeticExpressionAndOperandPromotion()
    {
        $expression = "1 add 2";
        $parser = new ExpressionParser($expression,
                     $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                     false);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Int32);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getLeft()->getValue(), 1);

        $expression = "1 sub 2.5";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getLeft()->getType() instanceof Double, true);
        $this->assertEquals($expr->getRight()->getType() instanceof Double, true);

        $expression = "1.1F sub 2";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Single);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getLeft()->getType() instanceof Single, true);
        $this->assertEquals($expr->getRight()->getType() instanceof Single, true);

        $expression = "1.1F mul 2.7";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getLeft()->getType() instanceof Double, true);
        $this->assertEquals($expr->getRight()->getType() instanceof Double, true);

        $expression = "1 add 2 sub 4";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr->getNodeType(), ExpressionType::SUBTRACT);
        $this->assertEquals($expr->getLeft() instanceof ArithmeticExpression, true);
        $this->assertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getLeft()->getNodeType(), ExpressionType::ADD);
        $this->assertEquals($expr->getRight()->getNodeType(), ExpressionType::CONSTANT);
        $this->assertEquals($expr->getLeft()->getLeft() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getLeft()->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getLeft()->getLeft()->getValue(), 1);

        $expression = "1 add (2 sub 4)";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr->getNodeType(), ExpressionType::ADD);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertEquals($expr->getRight() instanceof ArithmeticExpression, true);
        $this->assertEquals($expr->getRight()->getNodeType(), ExpressionType::SUBTRACT);
        $this->assertEquals($expr->getLeft()->getNodeType(), ExpressionType::CONSTANT);
        $this->assertEquals($expr->getRight()->getLeft() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getRight()->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getRight()->getLeft()->getValue(), 2);

        $expression = "1 add (2 sub 4)";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr->getNodeType(), ExpressionType::ADD);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertEquals($expr->getRight() instanceof ArithmeticExpression, true);
        $this->assertEquals($expr->getRight()->getNodeType(), ExpressionType::SUBTRACT);
        $this->assertEquals($expr->getLeft()->getNodeType(), ExpressionType::CONSTANT);
        $this->assertEquals($expr->getRight()->getLeft() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getRight()->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getRight()->getLeft()->getValue(), 2);

        $expression = "1 add 2 mul 4";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr->getNodeType(), ExpressionType::ADD);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertEquals($expr->getRight() instanceof ArithmeticExpression, true);
        $this->assertEquals($expr->getRight()->getNodeType(), ExpressionType::MULTIPLY);
        $this->assertEquals($expr->getLeft()->getNodeType(), ExpressionType::CONSTANT);
        $this->assertEquals($expr->getRight()->getLeft() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getRight()->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getRight()->getRight()->getValue(), 4);

        $expression = "Rating add 2.5 mul 3.4F";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof ArithmeticExpression);
        $this->assertTrue($expr->getType() instanceof Double);
        $this->assertEquals($expr->getNodeType(), ExpressionType::ADD);
        $this->assertEquals($expr->getLeft() instanceof PropertyAccessExpression, true);
        $this->assertEquals($expr->getLeft()->getType() instanceof Double, true);

        $expression = "5.2 mul true";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Double and Boolean was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'mul\' incompatible with operand types Edm.Double and Edm.Boolean', $exception->getMessage());
        }


        $expression = "1F add 2M";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Single and Decimal was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Single and Edm.Decimal', $exception->getMessage());
        }


        $expression = "1 add 'MyString'";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Int32 and String was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Int32 and Edm.String', $exception->getMessage());
        }


        $expression = "1F add 2M";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between Single and Decimal was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Single and Edm.Decimal', $exception->getMessage());
        }


        $expression = "datetime'1990-12-12' add datetime'1991-11-11'";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for incompatible between DateTime types was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.DateTime and Edm.DateTime', $exception->getMessage());
        }


    }

    public function testRelationalExpression()
    {
        $expression = '2.5 gt 2';
        $parser = new ExpressionParser($expression, $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(), true);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof RelationalExpression);
        $this->assertTrue($expr->getType() instanceof Boolean);

        $expression = 'true le false';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof RelationalExpression);
        $this->assertTrue($expr->getType() instanceof Boolean);
        $this->assertTrue($expr->getLeft() instanceof ConstantExpression);
        $this->assertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getLeft()->getValue(), 'true');
        $this->assertEquals($expr->getRight()->getType() instanceof Boolean, true);

        $expression = 'Country eq null';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof FunctionCallExpression);
        $this->assertEquals('is_null', $expr->getFunctionDescription()->functionName);
        $this->assertTrue($expr->getType() instanceof Boolean);
        $paramExpressions = $expr->getParamExpressions();
        $this->assertEquals($paramExpressions[0] instanceof PropertyAccessExpression, true);

        $expression = 'Country ge \'India\'';
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof RelationalExpression);
        $this->assertEquals($expr->getNodeType(), ExpressionType::GREATERTHAN_OR_EQUAL);
        $this->assertEquals($expr->getLeft() instanceof FunctionCallExpression, true);
        $this->assertEquals($expr->getLeft()->getFunctionDescription()->functionName, 'strcmp');
        $paramExpression = $expr->getLeft()->getParamExpressions();
        $this->assertEquals($paramExpression[0] instanceof PropertyAccessExpression, true);
        $this->assertEquals($paramExpression[1] instanceof ConstantExpression, true);
        $this->assertEquals($paramExpression[0]->getType() instanceof String, true);
        $this->assertEquals($paramExpression[1]->getType() instanceof String, true);
        $this->assertEquals($expr->getRight() instanceof ConstantExpression, true);
        $this->assertEquals($expr->getRight()->getValue(), 0);

        $expression = "1F gt 2M";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for gt operator\'s incompatible between Edm.Single and Edm.Decimal was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('Operator \'gt\' incompatible with operand types Edm.Single and Edm.Decimal', $exception->getMessage());
        }


        $expression = "Rating lt null";
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for lt operator\'s incompatible for null was not thrown');
        } catch(ODataException $exception)
        {
            $this->assertStringStartsWith('The operator \'lt\' at position 7 is not supported for the \'null\' literal; only equality checks', $exception->getMessage());
        }


    }

    public function testLogicalExpression()
    {
        $expression = 'true or false';
        $parser = new ExpressionParser($expression,
                      $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                      false);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof LogicalExpression, true);
        $this->assertTrue($expr->getType() instanceof Boolean);

        $expression = "1 add 2 gt 5 and 5 le 8";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof LogicalExpression, true);
        $this->assertEquals($expr->getNodeType(), ExpressionType::AND_LOGICAL);
        $this->assertEquals($expr->getLeft() instanceof RelationalExpression, true);
        $this->assertEquals($expr->getRight() instanceof RelationalExpression, true);
        $this->assertEquals($expr->getLeft()->getNodeType(), ExpressionType::GREATERTHAN);
        $this->assertEquals($expr->getRight()->getNodeType(), ExpressionType::LESSTHAN_OR_EQUAL);
        $this->assertEquals($expr->getLeft()->getLeft() instanceof ArithmeticExpression, true);
        $this->assertEquals($expr->getLeft()->getRight() instanceof ConstantExpression, true);

        $expression = '1 add (2 gt 5) and 5 le 8';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for add operator\'s incompatible between Edm.Int32 and Edm.Boolean was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'add\' incompatible with operand types Edm.Int32 and Edm.Boolean', $exception->getMessage());
        }

        $expression = '12 or 34.5';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for or operator\'s incompatible between Edm.Int32 and Edm.Double was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'or\' incompatible with operand types Edm.Int32 and Edm.Double', $exception->getMessage());
        }

        $expression = '12.6F and true';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for and operator\'s incompatible between Edm.Single and Edm.Boolean was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'and\' incompatible with operand types Edm.Single and Edm.Boolean', $exception->getMessage());
        }

        $expression = '\'string1\' and \'string2\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for and operator\'s incompatible between Edm.String and Edm.String was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'and\' incompatible with operand types Edm.String and Edm.String', $exception->getMessage());
        }

    }

    public function testUnaryExpression()
    {
        $expression = "-Rating";
        $parser = new ExpressionParser($expression,
                      $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                      false);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof UnaryExpression, true);
        $this->assertEquals($expr->getNodeType(), ExpressionType::NEGATE);
        $this->assertEquals($expr->getChild() instanceof PropertyAccessExpression, true);

        $expression = "not(1 gt 4)";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertEquals($expr instanceof UnaryExpression, true);
        $this->assertEquals($expr->getNodeType(), ExpressionType::NOT_LOGICAL);
        $this->assertTrue($expr->getType() instanceof Boolean);
        $this->assertEquals($expr->getChild() instanceof RelationalExpression, true);

        $expression = '-\'myString\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for negate operator\'s incompatible with Edm.String was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'-\' incompatible with operand types Edm.String at position 0', $exception->getMessage());
        }


        $expression = 'not(1 mul 3)';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for not operator\'s incompatible with Edm.Int32 was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Operator \'not\' incompatible with operand types Edm.Int32 at position 0', $exception->getMessage());
        }

    }

    public function testFunctionCallExpression()
    {
        $expression = 'year(datetime\'1988-11-11\')';
        $parser = new ExpressionParser($expression,
                      $this->_northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
                      false);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof FunctionCallExpression);
        $this->assertTrue($expr->getType() instanceof Int32);

        $expression = "substring('pspl', 1) eq 'pl'";
        $parser->resetParser($expression);
        $expr = $parser->parseFilter();
        $this->assertTrue($expr instanceof RelationalExpression);
        $this->assertEquals($expr->getLeft() instanceof FunctionCallExpression, true);
        $this->assertEquals($expr->getLeft()->getFunctionDescription()->functionName, 'strcmp');
        $paramExpressions = $expr->getLeft()->getParamExpressions();
        $this->assertEquals(count($paramExpressions), 2);
        $this->assertEquals($paramExpressions[0] instanceof FunctionCallExpression, true);
        $this->assertEquals($paramExpressions[0]->getFunctionDescription()->functionName, 'substring');
        $paramExpressions1 = $paramExpressions[0]->getParamExpressions();
        $this->assertEquals(count($paramExpressions1), 2);
        $this->assertEquals($paramExpressions1[0] instanceof ConstantExpression, true);
        $this->assertEquals($paramExpressions1[0]->getValue(), '\'pspl\'');

        $expression = 'unknownFun(1, 3)';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for and unknown function was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Unknown function \'unknownFun\' at position 0', $exception->getMessage());
        }

        $expression = 'endswith(\'mystring\'';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for function without closing bracket was not thrown');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('Close parenthesis expected', $exception->getMessage());
        }

        $expression = 'trim()';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No applicable function found\' was not thrown for trim');

        } catch(ODataException $exception) {
            $this->assertStringStartsWith('No applicable function found for \'trim\' at position 0 with the specified arguments. The functions considered are: Edm.String trim(Edm.String)', $exception->getMessage());
        }


        $expression = 'month(123.4)';
        $parser->resetParser($expression);
        try {
            $expr = $parser->parseFilter();
            $this->fail('An expected ODataException for \'No applicable function found\' was not thrown for month');
        } catch(ODataException $exception) {
            $this->assertStringStartsWith('No applicable function found for \'month\' at position', $exception->getMessage());
        }

    }

    protected function tearDown()
    {    
    }
}

