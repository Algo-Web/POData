<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use MyCLabs\Enum\Enum;

/**
 * Class ExpressionType.
 *
 * Enumeration for expression language operators, function call and literal
 * used in $filter option
 *
 * @method static ExpressionType ADD()
 * @method static ExpressionType AND_LOGICAL()
 * @method static ExpressionType CALL()
 * @method static ExpressionType CONSTANT()
 * @method static ExpressionType DIVIDE()
 * @method static ExpressionType EQUAL()
 * @method static ExpressionType GREATERTHAN()
 * @method static ExpressionType GREATERTHAN_OR_EQUAL()
 * @method static ExpressionType LESSTHAN()
 * @method static ExpressionType LESSTHAN_OR_EQUAL()
 * @method static ExpressionType MODULO()
 * @method static ExpressionType MULTIPLY()
 * @method static ExpressionType NEGATE()
 * @method static ExpressionType NOT_LOGICAL()
 * @method static ExpressionType NOTEQUAL()
 * @method static ExpressionType OR_LOGICAL()
 * @method static ExpressionType PROPERTYACCESS()
 * @method static ExpressionType PROPERTY_NULLABILITY_CHECK()
 * @method static ExpressionType SUBTRACT()
 */
class ExpressionType extends Enum
{
    /**
     * Arithmetic expression with 'add' operator.
     */
    protected const ADD = 1;

    /**
     * Logical expression with 'and' operator.
     */
    protected const AND_LOGICAL = 2;

    /**
     * Function call expression
     * e.g. substringof('Alfreds', CompanyName).
     */
    protected const CALL = 3;

    /**
     * Constant expression. e.g. In the expression
     * OrderID ne null and OrderID add 2 gt 5432
     * 2, null, 5432 are candidate for constant expression.
     */
    protected const CONSTANT = 4;

    /**
     * Arithmetic expression with 'div' operator.
     */
    protected const DIVIDE = 5;

    /**
     * Comparison expression with 'eq' operator.
     */
    protected const EQUAL = 6;

    /**
     * Comparison expression with 'gt' operator.
     */
    protected const GREATERTHAN = 7;

    /**
     * Comparison expression with 'ge' operator.
     */
    protected const GREATERTHAN_OR_EQUAL = 8;

    /**
     * Comparison expression with 'lt' operator.
     */
    protected const LESSTHAN = 9;

    /**
     * Comparison expression with 'le' operator.
     */
    protected const LESSTHAN_OR_EQUAL = 10;

    /**
     * Arithmetic expression with 'mod' operator.
     */
    protected const MODULO = 11;

    /**
     * Arithmetic expression with 'mul' operator.
     */
    protected const MULTIPLY = 12;

    /**
     * Unary expression with '-' operator.
     */
    protected const NEGATE = 13;

    /**
     * Unary Logical expression with 'not' operator.
     */
    protected const NOT_LOGICAL = 14;

    /**
     * Comparison expression with 'ne' operator.
     */
    protected const NOTEQUAL = 15;

    /**
     * Logical expression with 'or' operator.
     */
    protected const OR_LOGICAL = 16;

    /**
     * Property expression. e.g. In the expression
     * OrderID add 2 gt 5432
     * OrderID is candidate for PropertyAccessExpression.
     */
    protected const PROPERTYACCESS = 17;

    /**
     * Same as property expression but for nullability check.
     */
    protected const PROPERTY_NULLABILITY_CHECK = 18;

    /**
     * Arithmetic expression with 'sub' operator.
     */
    protected const SUBTRACT = 19;
}
