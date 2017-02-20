<?php

namespace UnitTests\POData\Providers\Expression;

use Mockery as m;
use POData\Common\ODataConstants;
use POData\Providers\Expression\PHPExpressionProvider;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use UnitTests\POData\TestCase;

class PHPExpressionProviderTest extends TestCase
{
    public function testGetIteratorName()
    {
        $foo = new PHPExpressionProvider(null);
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
        $foo = new PHPExpressionProvider('abc');
        $result = $foo->onLogicalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testonLogicalExpressionBadType()
    {
        $foo = new PHPExpressionProvider('abc');

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
        $foo = new PHPExpressionProvider('abc');
        $result = $foo->onArithmeticExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testonArithmeticExpressionBadType()
    {
        $foo = new PHPExpressionProvider('abc');

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
            [ExpressionType::EQUAL, '6', '3', '(6 == 3)'],
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
        $foo = new PHPExpressionProvider('abc');
        $result = $foo->onRelationalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testonRelationalExpressionBadType()
    {
        $foo = new PHPExpressionProvider('abc');

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
        $foo = new PHPExpressionProvider('abc');
        $result = $foo->onUnaryExpression($type, $arg);
        $this->assertEquals($expected, $result);
    }

    public function testonUnaryExpressionBadType()
    {
        $foo = new PHPExpressionProvider('abc');

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
            [ODataConstants::STRFUN_COMPARE, ['eins', 'zwei'], 'strcmp(eins, zwei)'],
            [ODataConstants::STRFUN_ENDSWITH, ['eins', 'zwei'],
                '(strcmp(substr(eins, strlen(eins) - strlen(zwei)), zwei) === 0)', ],
            [ODataConstants::STRFUN_INDEXOF, ['eins', 'zwei'], 'strpos(eins, zwei)'],
            [ODataConstants::STRFUN_REPLACE, ['eins', 'zwei', 'polizei'], 'str_replace(zwei, polizei, eins)'],
            [ODataConstants::STRFUN_STARTSWITH, ['eins', 'zwei'], '(strpos(eins, zwei) === 0)'],
            [ODataConstants::STRFUN_TOLOWER, ['eins'], 'strtolower(eins)'],
            [ODataConstants::STRFUN_TOUPPER, ['eins'], 'strtoupper(eins)'],
            [ODataConstants::STRFUN_TRIM, ['eins'], 'trim(eins)'],
            [ODataConstants::STRFUN_SUBSTRING, ['eins', 'zwei'], 'substr(eins, zwei)'],
            [ODataConstants::STRFUN_SUBSTRING, ['eins', 'zwei', 'polizei'], 'substr(eins, zwei, polizei)'],
            [ODataConstants::STRFUN_SUBSTRINGOF, ['eins', 'zwei'], '(strpos(zwei, eins) !== false)'],
            [ODataConstants::STRFUN_CONCAT, ['eins', 'zwei'], 'eins . zwei'],
            [ODataConstants::STRFUN_LENGTH, ['eins'], 'strlen(eins)'],
            [ODataConstants::GUIDFUN_EQUAL, ['eins', 'zwei'],
                'POData\Providers\Metadata\Type\Guid::guidEqual(eins, zwei)', ],
            [ODataConstants::DATETIME_COMPARE, ['2014-10-11 12:02:02', '2014-10-11 12:02:02'],
                'POData\Providers\Metadata\Type\DateTime::dateTimeCmp(2014-10-11 12:02:02, 2014-10-11 12:02:02)', ],
            [ODataConstants::DATETIME_YEAR, ['2014-10-11 12:02:02'],
                'POData\Providers\Metadata\Type\DateTime::year(2014-10-11 12:02:02)', ],
            [ODataConstants::DATETIME_MONTH, ['2014-10-11 12:02:02'],
                'POData\Providers\Metadata\Type\DateTime::month(2014-10-11 12:02:02)', ],
            [ODataConstants::DATETIME_DAY, ['2014-10-11 12:02:02'],
                'POData\Providers\Metadata\Type\DateTime::day(2014-10-11 12:02:02)', ],
            [ODataConstants::DATETIME_HOUR, ['2014-10-11 12:02:02'],
                'POData\Providers\Metadata\Type\DateTime::hour(2014-10-11 12:02:02)', ],
            [ODataConstants::DATETIME_MINUTE, ['2014-10-11 12:02:02'],
                'POData\Providers\Metadata\Type\DateTime::minute(2014-10-11 12:02:02)', ],
            [ODataConstants::DATETIME_SECOND, ['2014-10-11 12:02:02'],
                'POData\Providers\Metadata\Type\DateTime::second(2014-10-11 12:02:02)', ],
            [ODataConstants::MATHFUN_ROUND, ['42.2'], 'round(42.2)'],
            [ODataConstants::MATHFUN_CEILING, ['42.2'], 'ceil(42.2)'],
            [ODataConstants::BINFUL_EQUAL, ['eins', 'zwei'],
                'POData\Providers\Metadata\Type\Binary::binaryEqual(eins, zwei)', ],
            [ODataConstants::MATHFUN_FLOOR, ['42.2'], 'floor(42.2)'],
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

        $foo = new PHPExpressionProvider('abc');
        $result = $foo->onFunctionCallExpression($descript, $params);
        $this->assertEquals($expected, $result);
    }

    public function testonFunctionalExpressionBadType()
    {
        $descript = m::mock(FunctionDescription::class);
        $descript->shouldReceive('name')->andReturn('Outta my way I\'m runnin...');
        $foo = new PHPExpressionProvider('abc');

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
        $foo = new PHPExpressionProvider('abc');
        $foo->setResourceType($res);

        $expected = 'abc->HAMMER TIME!';
        $result = $foo->onPropertyAccessExpression($property);
        $this->assertEquals($expected, $result);
    }

    public function testonConstantExpressionNullValue()
    {
        $type = m::mock(IType::class);
        $foo = new PHPExpressionProvider('abc');

        $result = $foo->onConstantExpression($type, null);
        $this->assertEquals('NULL', $result);
    }

    public function testonConstantExpressionBoolValue()
    {
        $type = m::mock(IType::class);
        $foo = new PHPExpressionProvider('abc');

        $result = $foo->onConstantExpression($type, false);
        $this->assertEquals('false', $result);
    }

    public function testonConstantExpressionOtherValue()
    {
        $type = m::mock(IType::class);
        $foo = new PHPExpressionProvider('abc');

        $result = $foo->onConstantExpression($type, 'fnord');
        $this->assertEquals('fnord', $result);
    }
}
