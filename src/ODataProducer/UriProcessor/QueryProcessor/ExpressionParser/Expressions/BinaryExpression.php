<?php

namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

/**
 * Class BinaryExpression
 *
 * Abstract base class for binary expressions (arithmetic, logical or relational)
 *
 * @package ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions
 */
abstract class BinaryExpression extends AbstractExpression
{
    /**
     * @var AbstractExpression
     */
    protected $left;
    
    /**
     * @var AbstractExpression
     */
    protected $right;

    /**
     * Create new inatnce of BinaryExpression.
     * 
     * @param AbstractExpression $left  The left expression
     * @param AbstractExpression $right The right expression
     */
    public function __construct($left, $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    /**
     * Get left operand (expression) of binary expression
     * 
     * @return AbstractExpression
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * Get right operand (expression) of binary expression
     * 
     * @return AbstractExpression
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * Set left operand (expression) of binary expression
     * 
     * @param AbstractExpression $expression Expression to set as left operand
     * 
     * @return void
     */
    public function setLeft($expression)
    {
        $this->left = $expression;
    }

    /**
     * Set right operand (expression) of binary expression
     * 
     * @param AbstractExpression $expression Expression to set as right operand
     * 
     * @return void
     */
    public function setRight($expression)
    {
        $this->right = $expression;
    }

    /**
     * (non-PHPdoc)
     * 
     * @see library/ODataProducer/QueryProcessor/ExpressionParser/Expressions/ODataProducer\QueryProcessor\ExpressionParser\Expressions.AbstractExpression::free()
     * 
     * @return void
     */
    public function free()
    {
        $this->left->free();
        $this->right->free();
        unset($this->left);
        unset($this->right);
    }
}