<?php

namespace UnitTests\POData\Facets\WordPress2;

/*
 * The specialized expression provider for MySQL
 *
 */

use POData\Common\ODataConstants;
use POData\Providers\Expression\IExpressionProvider;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\FunctionDescription;

class WordPressDSExpressionProvider implements IExpressionProvider
{
    const ADD = '+';
    const CLOSE_BRACKET = ')';
    const COMMA = ',';
    const DIVIDE = '/';
    const SUBTRACT = '-';
    const EQUAL = '=';
    const GREATERTHAN = '>';
    const GREATERTHAN_OR_EQUAL = '>=';
    const LESSTHAN = '<';
    const LESSTHAN_OR_EQUAL = '<=';
    const LOGICAL_AND = '&&';
    const LOGICAL_NOT = '!';
    const LOGICAL_OR = '||';
    const MEMBERACCESS = '';
    const MODULO = '%';
    const MULTIPLY = '*';
    const NEGATE = '-';
    const NOTEQUAL = '!=';
    const OPEN_BRAKET = '(';

    /**
     * The type of the resource pointed by the resource path segement.
     *
     * @var ResourceType
     */
    private $_resourceType;

    private $_entityMapping;

    /**
     * Constructs new instance of MySQLExpressionProvider.
     */
    public function __construct()
    {
        $this->_entityMapping = WordPressMetadata::getEntityMapping();
    }

    /**
     * Get the name of the iterator.
     *
     * @return string
     */
    public function getIteratorName()
    {
    }

    /**
     * call-back for setting the resource type.
     *
     * @param ResourceType $resourceType The resource type on which the filter
     *                                   is going to be applied
     */
    public function setResourceType(ResourceType $resourceType)
    {
        $this->_resourceType = $resourceType;
    }

    /**
     * Call-back for logical expression.
     *
     * @param ExpressionType $expressionType The type of logical expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     *
     * @return string
     */
    public function onLogicalExpression($expressionType, $left, $right)
    {
        switch ($expressionType) {
            case ExpressionType::AND_LOGICAL:
                return $this->_prepareBinaryExpression(self::LOGICAL_AND, $left, $right);
            break;
            case ExpressionType::OR_LOGICAL:
                return $this->_prepareBinaryExpression(self::LOGICAL_OR, $left, $right);
            break;
            default:
                throw new \InvalidArgumentException('onLogicalExpression');
        }
    }

    /**
     * Call-back for arithmetic expression.
     *
     * @param ExpressionType $expressionType The type of arithmetic expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     *
     * @return string
     */
    public function onArithmeticExpression($expressionType, $left, $right)
    {
        switch ($expressionType) {
            case ExpressionType::MULTIPLY:
                return $this->_prepareBinaryExpression(self::MULTIPLY, $left, $right);
            break;
            case ExpressionType::DIVIDE:
                return $this->_prepareBinaryExpression(self::DIVIDE, $left, $right);
            break;
            case ExpressionType::MODULO:
                return $this->_prepareBinaryExpression(self::MODULO, $left, $right);
            break;
            case ExpressionType::ADD:
                return $this->_prepareBinaryExpression(self::ADD, $left, $right);
            break;
            case ExpressionType::SUBTRACT:
                return $this->_prepareBinaryExpression(self::SUBTRACT, $left, $right);
            break;
            default:
                throw new \InvalidArgumentException('onArithmeticExpression');
        }
    }

    /**
     * Call-back for relational expression.
     *
     * @param ExpressionType $expressionType The type of relation expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     *
     * @return string
     */
    public function onRelationalExpression($expressionType, $left, $right)
    {
        switch ($expressionType) {
            case ExpressionType::GREATERTHAN:
                return $this->_prepareBinaryExpression(self::GREATERTHAN, $left, $right);
            break;
            case ExpressionType::GREATERTHAN_OR_EQUAL:
                return $this->_prepareBinaryExpression(
                    self::GREATERTHAN_OR_EQUAL,
                    $left,
                    $right
                );
            break;
            case ExpressionType::LESSTHAN:
                return $this->_prepareBinaryExpression(self::LESSTHAN, $left, $right);
            break;
            case ExpressionType::LESSTHAN_OR_EQUAL:
                return $this->_prepareBinaryExpression(
                    self::LESSTHAN_OR_EQUAL,
                    $left,
                    $right
                );
            break;
            case ExpressionType::EQUAL:
                return $this->_prepareBinaryExpression(self::EQUAL, $left, $right);
            break;
            case ExpressionType::NOTEQUAL:
                return $this->_prepareBinaryExpression(self::NOTEQUAL, $left, $right);
            break;
            default:
                throw new \InvalidArgumentException('onArithmeticExpression');
        }
    }

    /**
     * Call-back for unary expression.
     *
     * @param ExpressionType $expressionType The type of unary expression
     * @param string         $child          The child expression
     *
     * @return string
     */
    public function onUnaryExpression($expressionType, $child)
    {
        switch ($expressionType) {
            case ExpressionType::NEGATE:
                return $this->_prepareUnaryExpression(self::NEGATE, $child);
            break;
            case ExpressionType::NOT_LOGICAL:
                return $this->_prepareUnaryExpression(self::LOGICAL_NOT, $child);
            break;
            default:
                throw new \InvalidArgumentException('onUnaryExpression');
        }
    }

    /**
     * Call-back for constant expression.
     *
     * @param IType  $type  The type of constant
     * @param objetc $value The value of the constant
     *
     * @return string
     */
    public function onConstantExpression(IType $type, $value)
    {
        if (is_bool($value)) {
            return var_export($value, true);
        } elseif (is_null($value)) {
            return var_export(null, true);
        }

        return $value;
    }

    /**
     * Call-back for property access expression.
     *
     * @param PropertyAccessExpression $expression The property access expression
     *
     * @return string
     */
    public function onPropertyAccessExpression($expression)
    {
        $parent = $expression;
        $variable = null;
        $entityTypeName = $this->_resourceType->getName();
        $propertyName = $parent->getResourceProperty()->getName();
        if (is_array($this->_entityMapping)) {
            if (array_key_exists($entityTypeName, $this->_entityMapping)) {
                if (array_key_exists($propertyName, $this->_entityMapping[$entityTypeName])) {
                    return $this->_entityMapping[$entityTypeName][$propertyName];
                }
            }
        }

        return $propertyName;
    }

    /**
     * Call-back for function call expression.
     *
     * @param FunctionDescription $functionDescription Description of the function
     * @param array<string>       $params              Paameters to the function
     *
     * @return string
     */
    public function onFunctionCallExpression($functionDescription, $params)
    {
        switch ($functionDescription->name) {
            case ODataConstants::STRFUN_COMPARE:
                return "STRCMP($params[0], $params[1])";
            break;
            case ODataConstants::STRFUN_ENDSWITH:
                return "(STRCMP($params[1],RIGHT($params[0],LENGTH($params[1]))) = 0)";
            break;
            case ODataConstants::STRFUN_INDEXOF:
                return "INSTR($params[0], $params[1]) - 1";
            break;
            case ODataConstants::STRFUN_REPLACE:
                return "REPLACE($params[0],$params[1],$params[2])";
            break;
            case ODataConstants::STRFUN_STARTSWITH:
                return "(STRCMP($params[1],LEFT($params[0],LENGTH($params[1]))) = 0)";
            break;
            case ODataConstants::STRFUN_TOLOWER:
                return "LOWER($params[0])";
            break;
            case ODataConstants::STRFUN_TOUPPER:
                return "UPPER($params[0])";
            break;
            case ODataConstants::STRFUN_TRIM:
                return "TRIM($params[0])";
            break;
            case ODataConstants::STRFUN_SUBSTRING:
                return count($params) == 3 ?
                "SUBSTRING($params[0], $params[1] + 1, $params[2])" :
                "SUBSTRING($params[0], $params[1] + 1)";
            break;
            case ODataConstants::STRFUN_SUBSTRINGOF:
                return "(LOCATE($params[0], $params[1]) > 0)";
            break;
            case ODataConstants::STRFUN_CONCAT:
                return "CONCAT($params[0],$params[1])";
            break;
            case ODataConstants::STRFUN_LENGTH:
                return "LENGTH($params[0])";
            break;
            case ODataConstants::GUIDFUN_EQUAL:
                return self::TYPE_NAMESPACE."Guid::guidEqual($params[0], $params[1])";
            break;
            case ODataConstants::DATETIME_COMPARE:
                return "DATETIMECMP($params[0]; $params[1])";
            break;
            case ODataConstants::DATETIME_YEAR:
                return 'EXTRACT(YEAR from '.$params[0].')';
            break;
            case ODataConstants::DATETIME_MONTH:
                return 'EXTRACT(MONTH from '.$params[0].')';
            break;
            case ODataConstants::DATETIME_DAY:
                return 'EXTRACT(DAY from '.$params[0].')';
            break;
            case ODataConstants::DATETIME_HOUR:
                return 'EXTRACT(HOUR from '.$params[0].')';
            break;
            case ODataConstants::DATETIME_MINUTE:
                return 'EXTRACT(MINUTE from '.$params[0].')';
            break;
            case ODataConstants::DATETIME_SECOND:
                return 'EXTRACT(SECOND from '.$params[0].')';
            break;
            case ODataConstants::MATHFUN_ROUND:
                return "ROUND($params[0])";
            break;
            case ODataConstants::MATHFUN_CEILING:
                return "CEIL($params[0])";
            break;
            case ODataConstants::MATHFUN_FLOOR:
                return "FLOOR($params[0])";
            break;
            case ODataConstants::BINFUL_EQUAL:
                return
                self::TYPE_NAMESPACE
                    ."Binary::binaryEqual($params[0], $params[1])";
            break;
            case 'is_null':
                return "is_null($params[0])";
            break;

            default:
                throw new \InvalidArgumentException('onFunctionCallExpression');
        }
    }

    /**
     * To format binary expression.
     *
     * @param string $operator The binary operator
     * @param string $left     The left operand
     * @param string $right    The right operand
     *
     * @return string
     */
    private function _prepareBinaryExpression($operator, $left, $right)
    {
        //DATETIMECMP
        if (!substr_compare($left, 'DATETIMECMP', 0, 11)) {
            $str = explode(';', $left, 2);
            $str[0] = str_replace('DATETIMECMP', '', $str[0]);

            return self::OPEN_BRAKET
                .$str[0].' '.$operator
                .' '.$str[1].self::CLOSE_BRACKET;
        }

        return
            self::OPEN_BRAKET
            .$left.' '.$operator
            .' '.$right.self::CLOSE_BRACKET;
    }

    /**
     * To format unary expression.
     *
     * @param string $operator The unary operator
     * @param string $child    The operand
     *
     * @return string
     */
    private function _prepareUnaryExpression($operator, $child)
    {
        return $operator.self::OPEN_BRAKET.$child.self::CLOSE_BRACKET;
    }
}
