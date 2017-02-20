<?php

namespace UnitTests\POData\Providers\Expression;

use Mockery as m;
use POData\Common\ODataConstants;
use POData\Providers\Expression\MySQLExpressionProvider;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use UnitTests\POData\TestCase;

class MySQLExpressionProviderTest extends TestCase
{
    public function testGetIteratorName()
    {
        $foo = new MySQLExpressionProvider();
        $this->assertNull($foo->getIteratorName());
    }

    public function onLogicalExpressionProvider()
    {
        return [
            [ExpressionType::AND_LOGICAL, "Title = 'PHB'", 'Clue > 0', "(Title = 'PHB' && Clue > 0)"],
            [ExpressionType::OR_LOGICAL, "BeardColour = 'Grey'", 'WizardFlag = TRUE',
                "(BeardColour = 'Grey' || WizardFlag = TRUE)", ],
        ];
    }

    /**
     * @dataProvider onLogicalExpressionProvider
     */
    public function testonLogicalExpression($type, $left, $right, $expected)
    {
        $foo = new MySQLExpressionProvider();
        $result = $foo->onLogicalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testonLogicalExpressionBadType()
    {
        $foo = new MySQLExpressionProvider();

        $expected = 'onLogicalExpression';
        $actual = null;
        try {
            $foo->onLogicalExpression(ExpressionType::CONSTANT, '', '');
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function onArithmeticExpressionProvider()
    {
        return [
            [ExpressionType::MULTIPLY, '2', '1', '(2 * 1)'],
            [ExpressionType::DIVIDE, '4', '2', '(4 / 2)'],
            [ExpressionType::MODULO, '6', '3', '(6 % 3)'],
            [ExpressionType::ADD, '8', '4', '(8 + 4)'],
            [ExpressionType::SUBTRACT, '10', '5', '(10 - 5)'],
        ];
    }

    /**
     * @dataProvider onArithmeticExpressionProvider
     */
    public function testonArithmeticExpression($type, $left, $right, $expected)
    {
        $foo = new MySQLExpressionProvider();
        $result = $foo->onArithmeticExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testonArithmeticExpressionBadType()
    {
        $foo = new MySQLExpressionProvider();

        $expected = 'onArithmeticExpression';
        $actual = null;
        try {
            $foo->onArithmeticExpression(ExpressionType::NOT_LOGICAL, '', '');
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function onRelationalExpressionProvider()
    {
        return [
            [ExpressionType::GREATERTHAN, '2', '1', '(2 > 1)'],
            [ExpressionType::GREATERTHAN_OR_EQUAL, '4', '2', '(4 >= 2)'],
            [ExpressionType::EQUAL, '6', '3', '(6 = 3)'],
            [ExpressionType::NOTEQUAL, '6', '3', '(6 != 3)'],
            [ExpressionType::LESSTHAN, '8', '4', '(8 < 4)'],
            [ExpressionType::LESSTHAN_OR_EQUAL, '10', '5', '(10 <= 5)'],
        ];
    }

    /**
     * @dataProvider onRelationalExpressionProvider
     */
    public function testonRelationalExpression($type, $left, $right, $expected)
    {
        $foo = new MySQLExpressionProvider();
        $result = $foo->onRelationalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testonRelationalExpressionBadType()
    {
        $foo = new MySQLExpressionProvider();

        $expected = 'onRelationalExpression';
        $actual = null;
        try {
            $foo->onRelationalExpression(ExpressionType::NOT_LOGICAL, '', '');
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function onUnaryExpressionProvider()
    {
        return [
            [ExpressionType::NEGATE, '4 - 2', '-(4 - 2)'],
            [ExpressionType::NOT_LOGICAL, '4', '!(4)'],
        ];
    }

    /**
     * @dataProvider onUnaryExpressionProvider
     */
    public function testonUnaryExpression($type, $arg, $expected)
    {
        $foo = new MySQLExpressionProvider();
        $result = $foo->onUnaryExpression($type, $arg);
        $this->assertEquals($expected, $result);
    }

    public function testonUnaryExpressionBadType()
    {
        $foo = new MySQLExpressionProvider();

        $expected = 'onUnaryExpression';
        $actual = null;
        try {
            $foo->onUnaryExpression(ExpressionType::ADD, '');
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function onFunctionCallExpressionProvider()
    {
        return [
            [ODataConstants::STRFUN_COMPARE, ['eins', 'zwei'], 'STRCMP(eins, zwei)'],
            [ODataConstants::STRFUN_ENDSWITH, ['eins', 'zwei'], '(STRCMP(zwei,RIGHT(eins,LENGTH(zwei))) = 0)'],
            [ODataConstants::STRFUN_INDEXOF, ['eins', 'zwei'], 'INSTR(eins, zwei) - 1'],
            [ODataConstants::STRFUN_REPLACE, ['eins', 'zwei', 'polizei'], 'REPLACE(eins,zwei,polizei)'],
            [ODataConstants::STRFUN_STARTSWITH, ['eins', 'zwei'], '(STRCMP(zwei,LEFT(eins,LENGTH(zwei))) = 0)'],
            [ODataConstants::STRFUN_TOLOWER, ['eins'], 'LOWER(eins)'],
            [ODataConstants::STRFUN_TOUPPER, ['eins'], 'UPPER(eins)'],
            [ODataConstants::STRFUN_TRIM, ['eins'], 'TRIM(eins)'],
            [ODataConstants::STRFUN_SUBSTRING, ['eins', 'zwei'], 'SUBSTRING(eins, zwei + 1)'],
            [ODataConstants::STRFUN_SUBSTRING, ['eins', 'zwei', 'polizei'], 'SUBSTRING(eins, zwei + 1, polizei)'],
            [ODataConstants::STRFUN_SUBSTRINGOF, ['eins', 'zwei'], '(LOCATE(eins, zwei) > 0)'],
            [ODataConstants::STRFUN_CONCAT, ['eins', 'zwei'], 'CONCAT(eins,zwei)'],
            [ODataConstants::STRFUN_LENGTH, ['eins'], 'LENGTH(eins)'],
            [ODataConstants::GUIDFUN_EQUAL, ['eins', 'zwei'], 'STRCMP(eins, zwei)'],
            [ODataConstants::DATETIME_COMPARE, ['2014-10-11 12:02:02', '2014-10-11 12:02:02'],
                'DATETIMECMP(2014-10-11 12:02:02; 2014-10-11 12:02:02)', ],
            [ODataConstants::DATETIME_YEAR, ['2014-10-11 12:02:02'], 'EXTRACT(YEAR from 2014-10-11 12:02:02)'],
            [ODataConstants::DATETIME_MONTH, ['2014-10-11 12:02:02'], 'EXTRACT(MONTH from 2014-10-11 12:02:02)'],
            [ODataConstants::DATETIME_DAY, ['2014-10-11 12:02:02'], 'EXTRACT(DAY from 2014-10-11 12:02:02)'],
            [ODataConstants::DATETIME_HOUR, ['2014-10-11 12:02:02'], 'EXTRACT(HOUR from 2014-10-11 12:02:02)'],
            [ODataConstants::DATETIME_MINUTE, ['2014-10-11 12:02:02'], 'EXTRACT(MINUTE from 2014-10-11 12:02:02)'],
            [ODataConstants::DATETIME_SECOND, ['2014-10-11 12:02:02'], 'EXTRACT(SECOND from 2014-10-11 12:02:02)'],
            [ODataConstants::MATHFUN_ROUND, ['42.2'], 'ROUND(42.2)'],
            [ODataConstants::MATHFUN_CEILING, ['42.2'], 'CEIL(42.2)'],
            [ODataConstants::BINFUL_EQUAL, ['eins', 'zwei'], '(eins = zwei)'],
            [ODataConstants::MATHFUN_FLOOR, ['42.2'], 'FLOOR(42.2)'],
            ['is_null', ['42.2'], 'is_null(42.2)'],
        ];
    }

    /**
     * @dataProvider onFunctionCallExpressionProvider
     */
    public function testonFunctionCallExpression($type, $params, $expected)
    {
        $descript = m::mock(FunctionDescription::class)->makePartial();
        $descript->name = $type;

        $foo = new MySQLExpressionProvider();
        $result = $foo->onFunctionCallExpression($descript, $params);
        $this->assertEquals($expected, $result);
    }

    public function testonFunctionalExpressionBadType()
    {
        $descript = m::mock(FunctionDescription::class);
        $descript->shouldReceive('name')->andReturn('Outta my way I\'m runnin...');
        $foo = new MySQLExpressionProvider();

        $expected = 'onFunctionCallExpression';
        $actual = null;
        try {
            $foo->onFunctionCallExpression($descript, []);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testonPropertyAccessExpression()
    {
        $property = m::mock(PropertyAccessExpression::class)->makePartial();
        $property->shouldReceive('getResourceProperty->getName')->andReturn('HAMMER TIME!');
        $res = m::mock(ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn('OH NOES!');
        $foo = new MySQLExpressionProvider();
        $foo->setResourceType($res);

        $expected = 'HAMMER TIME!';
        $result = $foo->onPropertyAccessExpression($property);
        $this->assertEquals($expected, $result);
    }

    public function testonConstantExpressionNullValue()
    {
        $type = m::mock(IType::class);
        $foo = new MySQLExpressionProvider();

        $result = $foo->onConstantExpression($type, null);
        $this->assertEquals('NULL', $result);
    }

    public function testonConstantExpressionBoolValue()
    {
        $type = m::mock(IType::class);
        $foo = new MySQLExpressionProvider();

        $result = $foo->onConstantExpression($type, false);
        $this->assertEquals('false', $result);
    }

    public function testonConstantExpressionOtherValue()
    {
        $type = m::mock(IType::class);
        $foo = new MySQLExpressionProvider();

        $result = $foo->onConstantExpression($type, 'fnord');
        $this->assertEquals('fnord', $result);
    }
}
