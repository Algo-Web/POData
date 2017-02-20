<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\Providers\Expression\IExpressionProvider;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;

class NorthWindExpressionProvider implements IExpressionProvider
{
    /**
     * Get the name of the iterator.
     *
     * @return string
     */
    public function getIteratorName()
    {
        // TODO: Implement getIteratorName() method.
    }

    /**
     * call-back for setting the resource type.
     *
     * @param ResourceType $resourceType The resource type on which the filter
     *                                   is going to be applied
     */
    public function setResourceType(ResourceType $resourceType)
    {
        // TODO: Implement setResourceType() method.
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
        // TODO: Implement onLogicalExpression() method.
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
        // TODO: Implement onArithmeticExpression() method.
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
        // TODO: Implement onRelationalExpression() method.
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
        // TODO: Implement onUnaryExpression() method.
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
        // TODO: Implement onConstantExpression() method.
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
        // TODO: Implement onPropertyAccessExpression() method.
    }

    /**
     * Call-back for function call expression.
     *
     * @param string        $functionDescription Description of the function
     * @param array<string> $params              Arguments to the functions
     *
     * @return string
     */
    public function onFunctionCallExpression($functionDescription, $params)
    {
        // TODO: Implement onFunctionCallExpression() method.
    }
}
