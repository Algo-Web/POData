<?php

namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser;

use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyNullabilityCheckExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;


/**
 * Class ExpressionProcessor
 *
 * Class to process an expression tree and generate specialized
 * (e.g. PHP) expression using expression provider
 *
 * @package ODataProducer\UriProcessor\QueryProcessor\ExpressionParser
 */
class ExpressionProcessor
{
    private $_expressionAsString;
    private $_rootExpression;
    private $_expressionProvider;
    
    /**
     * Construct new instance of ExpressionProcessor
     * 
     * @param AbstractExpression  $rootExpression     The root of the expression
     *                                                tree.
     * @param IExpressionProvider $expressionProvider Reference to the language 
     *                                                specific provider.
     */
    public function __construct(AbstractExpression $rootExpression, 
        IExpressionProvider $expressionProvider
    ) {
        $this->_rootExpression = $rootExpression;
        $this->_expressionProvider = $expressionProvider;
    }

    /**
     * Sets the expression root.
     * 
     * @param AbstractExpression $rootExpression The root of the expression
     *                                           tree.
     * 
     * @return void
     */
    public function setExpression(AbstractExpression $rootExpression)
    {
        $this->_rootExpression = $rootExpression;
    }

    /**
     * Sets the language specific provider.
     * 
     * @param IExpressionProvider $expressionProvider The expression provider.
     * 
     * @return void
     */
    public function setExpressionProvider(IExpressionProvider $expressionProvider)
    {
        $this->_expressionProvider = $expressionProvider;
    }

    /**
     * Process the expression tree using expression provider and return the 
     * expression as string
     * 
     * @return string
     */
    public function processExpression()
    {
        $this->_expressionAsString = $this->_processExpressionNode($this->_rootExpression);
        return $this->_expressionAsString;
    }

    /**
     * Recursive function to process each node of the expression
     * 
     * @param AbstractExpression $expression Current node to process.
     * 
     * @return string The language specific expression.
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
        } else if ($expression instanceof LogicalExpression) {
            $left = $this->_processExpressionNode($expression->getLeft());
            $right = $this->_processExpressionNode($expression->getRight());
            return $this->_expressionProvider->onLogicalExpression(
                $expression->getNodeType(), 
                $left, 
                $right
            );
        } else if ($expression instanceof RelationalExpression) {
            $left = $this->_processExpressionNode($expression->getLeft());
            $right = $this->_processExpressionNode($expression->getRight());
            return $this->_expressionProvider->onRelationalExpression(
                $expression->getNodeType(), 
                $left, 
                $right
            );
        } else if ($expression instanceof ConstantExpression) {
            return $this->_expressionProvider->onConstantExpression(
                $expression->getType(), 
                $expression->getValue()
            );
        } else if ($expression instanceof PropertyAccessExpression) {
            return $this->_expressionProvider->onPropertyAccessExpression(
                $expression
            );
        } else if ($expression instanceof FunctionCallExpression) {
            $params = array();
            foreach ($expression->getParamExpressions() as $paramExpression) {
                $params[] = $this->_processExpressionNode($paramExpression);
            }
            return $this->_expressionProvider->onFunctionCallExpression(
                $expression->getFunctionDescription(), 
                $params
            );
        } else if ($expression instanceof UnaryExpression) {
            $child = $this->_processExpressionNode($expression->getChild());
            return $this->_expressionProvider->onUnaryExpression(
                $expression->getNodeType(), 
                $child
            );
        }
    }
}