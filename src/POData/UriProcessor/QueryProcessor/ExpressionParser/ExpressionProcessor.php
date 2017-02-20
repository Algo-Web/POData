<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Providers\Expression\IExpressionProvider;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;

/**
 * Class ExpressionProcessor.
 *
 * Class to process an expression tree and generate specialized
 * (e.g. PHP) expression using expression provider
 */
class ExpressionProcessor
{
    private $_expressionProvider;

    /**
     * Construct new instance of ExpressionProcessor.
     *
     * @param IExpressionProvider $expressionProvider Reference to the language specific provider
     */
    public function __construct(IExpressionProvider $expressionProvider)
    {
        $this->_expressionProvider = $expressionProvider;
    }

    /**
     * Process the expression tree using expression provider and return the
     * expression as string.
     *
     * @param AbstractExpression $rootExpression The root of the expression tree
     *
     * @return string
     */
    public function processExpression(AbstractExpression $rootExpression)
    {
        return $this->_processExpressionNode($rootExpression);
    }

    /**
     * Recursive function to process each node of the expression.
     *
     * @param AbstractExpression $expression Current node to process
     *
     * @return string The language specific expression
     */
    private function _processExpressionNode(AbstractExpression $expression)
    {
        if ($expression instanceof ArithmeticExpression) {
            $left = $this->_processExpressionNode($expression->getLeft());
            $right = $this->_processExpressionNode($expression->getRight());

            return $this->_expressionProvider->onArithmeticExpression(
                $expression->getNodeType(),
                $left,
                $right
            );
        }

        if ($expression instanceof LogicalExpression) {
            $left = $this->_processExpressionNode($expression->getLeft());
            $right = $this->_processExpressionNode($expression->getRight());

            return $this->_expressionProvider->onLogicalExpression(
                $expression->getNodeType(),
                $left,
                $right
            );
        }

        if ($expression instanceof RelationalExpression) {
            $left = $this->_processExpressionNode($expression->getLeft());
            $right = $this->_processExpressionNode($expression->getRight());

            return $this->_expressionProvider->onRelationalExpression(
                $expression->getNodeType(),
                $left,
                $right
            );
        }

        if ($expression instanceof ConstantExpression) {
            return $this->_expressionProvider->onConstantExpression(
                $expression->getType(),
                $expression->getValue()
            );
        }

        if ($expression instanceof PropertyAccessExpression) {
            return $this->_expressionProvider->onPropertyAccessExpression(
                $expression
            );
        }

        if ($expression instanceof FunctionCallExpression) {
            $params = [];
            foreach ($expression->getParamExpressions() as $paramExpression) {
                $params[] = $this->_processExpressionNode($paramExpression);
            }

            return $this->_expressionProvider->onFunctionCallExpression(
                $expression->getFunctionDescription(),
                $params
            );
        }

        if ($expression instanceof UnaryExpression) {
            $child = $this->_processExpressionNode($expression->getChild());

            return $this->_expressionProvider->onUnaryExpression(
                $expression->getNodeType(),
                $child
            );
        }
    }
}
