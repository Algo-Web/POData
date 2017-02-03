<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

/**
 * Class ExpressionType.
 *
 * Enumeration for expression language operators, function call and literal
 * used in $filter option
 */
class ExpressionType
{
    /**
     * Arithmetic expression with 'add' operator.
     */
    const ADD = 1;

    /**
     * Logical expression with 'and' operator.
     */
    const AND_LOGICAL = 2;

    /**
     * Funcation call expression
     * e.g. substringof('Alfreds', CompanyName).
     */
    const CALL = 3;

    /**
     * Constant expression. e.g. In the expression
     * OrderID ne null and OrderID add 2 gt 5432
     * 2, null, 5432 are candicate for constant expression.
     */
    const CONSTANT = 4;

    /**
     * Arithmetic expression with 'div' operator.
     */
    const DIVIDE = 5;

    /**
     * Comparison expression with 'eq' operator.
     */
    const EQUAL = 6;

    /**
     * Comparison expression with 'gt' operator.
     */
    const GREATERTHAN = 7;

    /**
     * Comparison expression with 'ge' operator.
     */
    const GREATERTHAN_OR_EQUAL = 8;

    /**
     * Comparison expression with 'lt' operator.
     */
    const LESSTHAN = 9;

    /**
     * Comparison expression with 'le' operator.
     */
    const LESSTHAN_OR_EQUAL = 10;

    /**
     * Arithmetic expression with 'mod' operator.
     */
    const MODULO = 11;

    /**
     * Arithmetic expression with 'mul' operator.
     */
    const MULTIPLY = 12;

    /**
     * Unary expression with '-' operator.
     */
    const NEGATE = 13;

    /**
     * Unary Logical expression with 'not' operator.
     */
    const NOT_LOGICAL = 14;

    /**
     * Comparison expression with 'ne' operator.
     */
    const NOTEQUAL = 15;

    /**
     * Logical expression with 'or' operator.
     */
    const OR_LOGICAL = 16;

    /**
     * Property expression. e.g. In the expression
     * OrderID add 2 gt 5432
     * OrderID is candicate for PropertyAccessExpression.
     */
    const PROPERTYACCESS = 17;

    /**
     * Same as property expression but for nullabilty check.
     */
    const PROPERTY_NULLABILITY_CHECK = 18;

    /**
     * Arithmetic expression with 'sub' operator.
     */
    const SUBTRACT = 19;
}
