<?php

namespace UnitTests\POData\Facets\NorthWind4;

use POData\Common\NotImplementedException;
use POData\Common\ODataConstants;
use POData\Providers\Expression\IExpressionProvider;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\FunctionDescription;

class NorthWindDSExpressionProvider4 implements IExpressionProvider
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
    const LOGICAL_AND = 'AND';
    const LOGICAL_NOT = 'not';
    const LOGICAL_OR = 'OR';
    const MEMBERACCESS = '';
    const MODULO = '%';
    const MULTIPLY = '*';
    const NEGATE = '-';
    const NOTEQUAL = '!=';
    const OPEN_BRAKET = '(';
    // The default parameter for ROUND sql function-call
    private $_default_round = 0;

    /**
     * The type of the resource pointed by the resource path segement.
     *
     * @var ResourceType
     */
    private $_resourceType;

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
     * @param IType $type  The type of constant
     * @param mixed $value The value of the constant
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
        $isFirstLevelPrimitive = is_null($parent->getParent());
        if (!$isFirstLevelPrimitive) {
            // This propery access sub-expression in the $filter need access
            // to level 2 or greater property of a complex or resource reference
            // property.
            // e.g. Customers?$filter=Address/City eq 'Kottayam'
            //          Level_2 property access [Complex]
            //      Customers?$filter=Address/AltAddress/City eq 'Seattle'
            //          Level_3 property access [Complex]
            //      Orders?$filter=Customer/CustomerID eq 'ALFKI'
            //          Level_2 property access [Resource Reference]
            $parent2 = null;
            do {
                $parent2 = $parent;
                $parent = $parent->getParent();
            } while ($parent != null);

            $resourceProperty = $parent2->getResourceProperty();
            if ($resourceProperty->isKindOf(ResourcePropertyKind::RESOURCE_REFERENCE)) {
                // Orders?$filter=Customer/CustomerID eq 'ALFKI'
                throw new NotImplementedException(
                    'This implementation not supports Resource reference in the filter',
                    500,
                    null
                );
            } else {
                // Customers?$filter=Address/AltAddress/City eq 'Seattle'
                // Customers?$filter=Address/City eq 'Seattle'
                $propertyName = $parent2->getResourceProperty()->getName();
                if ('Address' == $propertyName) {
                    $child = $parent2->getChild();
                    $propertyName = $child->getResourceProperty()->getName();
                    if ('AltAddress' != $propertyName) {
                        return $propertyName;
                    }

                    throw new NotImplementedException(
                        'This implementation not supports Customer::Address::AltAddress in the filter',
                        500,
                        null
                    );
                }
            }
        } else {
            // This is a first level property access
            $resourceProperty = $parent->getResourceProperty();
            if ($resourceProperty->isKindOf(ResourcePropertyKind::COMPLEX_TYPE)
                || $resourceProperty->isKindOf(ResourcePropertyKind::RESOURCE_REFERENCE)) {
                // Customers?$filter=Address eq null
                // Orders?$filter=Customer ne null
                // First level property access to a complex or resource reference
                // which is not supported by $this [this implementation of IDSQP2]
                throw new NotImplementedException(
                    'First level complex and Resource reference are not supported in the filter',
                    500,
                    null
                );
            } else {
                // First level property access to primitive property
                return $parent->getResourceProperty()->getName();
            }
        }
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
                return "STRCMP($params[0]; $params[1])";
            break;
            case ODataConstants::STRFUN_ENDSWITH:
                return "(($params[1]) = RIGHT(($params[0]), LEN($params[1])))";
            break;
            case ODataConstants::STRFUN_INDEXOF:
                // In SQLServer the index of string starts from 1, but in OData
                // the string start with index 0, so the below subtraction of 1
                return "(CHARINDEX($params[1], $params[0]) - 1)";
            break;
            case ODataConstants::STRFUN_REPLACE:
                return "REPLACE($params[0], $params[1], $params[2])";
            break;
            case ODataConstants::STRFUN_STARTSWITH:
                return "(($params[1]) = LEFT(($params[0]), LEN($params[1])))";
            break;
            case ODataConstants::STRFUN_TOLOWER:
                return "LOWER($params[0])";
            break;
            case ODataConstants::STRFUN_TOUPPER:
                return "UPPER($params[0])";
            break;
            case ODataConstants::STRFUN_TRIM:
                // OData supports trim function
                // We don't have the same function SQL Server, so use SQL functions LTRIM and RTRIM together
                // to achieve TRIM functionality.
                return "RTRIM(LTRIM($params[0]))";
            break;
            case ODataConstants::STRFUN_SUBSTRING:
                if (count($params) == 3) {
                    // 3 Param version of OData substring
                    return "SUBSTRING($params[0], $params[1] + 1, $params[2])";
                } else {
                    // 2 Params version of OData substring
                    // We don't have the same function for SQL Server, we have only:

                    // SUBSTRING ( value_expression , start_expression , length_expression )
                    // http://msdn.microsoft.com/en-us/library/ms187748.aspx

                    // If the sum of start_expression and length_expression is greater than the number of characters
                    // in value_expression, the whole value expression beginning at start_expression is returned
                    // In OData substring function the index start from 0, in SQL Server its from 1
                    return "SUBSTRING($params[0], $params[1] + 1, LEN($params[0]))";
                }
                break;
            case ODataConstants::STRFUN_SUBSTRINGOF:
                return "(CHARINDEX($params[0], $params[1]) != 0)";
            break;
            case ODataConstants::STRFUN_CONCAT:
                return "$params[0] + $params[1]";
            break;
            case ODataConstants::STRFUN_LENGTH:
                return "LEN($params[0])";
            break;
            case ODataConstants::GUIDFUN_EQUAL:
                return "($params[0] = $params[1])";
            break;
            case ODataConstants::DATETIME_COMPARE:
                return "DATETIMECMP($params[0]; $params[1])";
            break;
            case ODataConstants::DATETIME_YEAR:
                return "YEAR($params[0])";
            break;
            case ODataConstants::DATETIME_MONTH:
                return "MONTH($params[0])";
            break;
            case ODataConstants::DATETIME_DAY:
                return "DAY($params[0])";
            break;
            case ODataConstants::DATETIME_HOUR:
                return "DATENAME(HOUR, $params[0])";
            break;
            case ODataConstants::DATETIME_MINUTE:
                return "DATENAME(MINUTE, $params[0])";
            break;
            case ODataConstants::DATETIME_SECOND:
                return "DATENAME(SECOND, $params[0])";
            break;
            case ODataConstants::MATHFUN_ROUND:
                return "ROUND($params[0], $this->_default_round)";
            break;
            case ODataConstants::MATHFUN_CEILING:
                return "CEILING($params[0])";
            break;
            case ODataConstants::MATHFUN_FLOOR:
                return "FLOOR($params[0])";
            break;
            case ODataConstants::BINFUL_EQUAL:
                return "($params[0] = $params[1])";
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
        if (!substr_compare($left, 'STRCMP', 0, 6)) {
            $str = explode(';', $left, 2);
            $str[0] = str_replace('STRCMP', '', $str[0]);
            if ($right == 'false' and $right != '0') {
                if (!substr_compare($operator, '!', 0, 1)) {
                    $operator = str_replace('!', '', $operator);
                } elseif ($operator == '>=') {
                    $operator = '<';
                } elseif ($operator == '<=') {
                    $operator = '>';
                } else {
                    $operator = '!'.$operator;
                }

                return self::OPEN_BRAKET
                    .$str[0].' '.$operator
                    .' '.$str[1].self::CLOSE_BRACKET;
            } else {
                return self::OPEN_BRAKET
                    .$str[0].' '.$operator
                    .' '.$str[1].self::CLOSE_BRACKET;
            }
        }

        //DATETIMECMP
        if (!substr_compare($left, 'DATETIMECMP', 0, 11)) {
            $str = explode(';', $left, 2);
            $str[0] = str_replace('DATETIMECMP', '', $str[0]);
            if ($right == 'false' and $right != '0') {
                if (!substr_compare($operator, '!', 0, 1)) {
                    $operator = str_replace('!', '', $operator);
                } elseif ($operator == '>=') {
                    $operator = '<';
                } elseif ($operator == '<=') {
                    $operator = '>';
                } else {
                    $operator = '!'.$operator;
                }

                return self::OPEN_BRAKET
                .$str[0].' '.$operator
                .' '.$str[1].self::CLOSE_BRACKET;
            } else {
                return self::OPEN_BRAKET
                .$str[0].' '.$operator
                .' '.$str[1].self::CLOSE_BRACKET;
            }
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
