<?php

declare(strict_types=1);

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
    private $expressionProvider;

    /**
     * Construct new instance of ExpressionProcessor.
     *
     * @param IExpressionProvider $expressionProvider Reference to the language specific provider
     */
    public function __construct(IExpressionProvider $expressionProvider)
    {
        $this->expressionProvider = $expressionProvider;
    }

    /**
     * Process the expression tree using expression provider and return the
     * expression as string.
     *
     * @param AbstractExpression $rootExpression The root of the expression tree
     *
     * @return string|null
     */
    public function processExpression(AbstractExpression $rootExpression): ?string
    {
        return $this->processExpressionNode($rootExpression);
    }

    /**
     * Recursive function to process each node of the expression.
     *
     * @param AbstractExpression|null $expression Current node to process
     *                                            TODO: Figure out why return type is b0rked
     *
     * @return string|null The language specific expression
     */
    private function processExpressionNode(AbstractExpression $expression = null)
    {
        if (null === $expression) {
            return null;
        }
        $funcName = null;
        if ($expression instanceof ArithmeticExpression) {
            $funcName = 'onArithmeticExpression';
        } elseif ($expression instanceof LogicalExpression) {
            $funcName = 'onLogicalExpression';
        } elseif ($expression instanceof RelationalExpression) {
            $funcName = 'onRelationalExpression';
        }

        if (null !== $funcName) {
            $left  = $this->processExpressionNode($expression->getLeft());
            $right = $this->processExpressionNode($expression->getRight());

            return $this->expressionProvider->{$funcName}(
                $expression->getNodeType(),
                $left,
                $right
            );
        }

        if ($expression instanceof ConstantExpression) {
            return $this->expressionProvider->onConstantExpression(
                $expression->getType(),
                $expression->getValue()
            );
        }

        if ($expression instanceof PropertyAccessExpression) {
            return $this->expressionProvider->onPropertyAccessExpression(
                $expression
            );
        }

        if ($expression instanceof FunctionCallExpression) {
            $params = [];
            foreach ($expression->getParamExpressions() as $paramExpression) {
                $params[] = $this->processExpressionNode($paramExpression);
            }

            return $this->expressionProvider->onFunctionCallExpression(
                $expression->getFunctionDescription(),
                $params
            );
        }

        if ($expression instanceof UnaryExpression) {
            $child = $this->processExpressionNode($expression->getChild());

            return $this->expressionProvider->onUnaryExpression(
                $expression->getNodeType(),
                $child
            );
        }

        return null;
    }
}
