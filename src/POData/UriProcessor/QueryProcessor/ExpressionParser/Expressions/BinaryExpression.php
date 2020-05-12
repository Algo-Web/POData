<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

/**
 * Class BinaryExpression.
 *
 * Abstract base class for binary expressions (arithmetic, logical or relational)
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
     * Create new instance of BinaryExpression.
     *
     * @param AbstractExpression $left  The left expression
     * @param AbstractExpression $right The right expression
     */
    public function __construct($left, $right)
    {
        $this->left  = $left;
        $this->right = $right;
    }

    /**
     * Get left operand (expression) of binary expression.
     *
     * @return AbstractExpression|null
     */
    public function getLeft(): ?AbstractExpression
    {
        return isset($this->left) ? $this->left : null;
    }

    /**
     * Set left operand (expression) of binary expression.
     *
     * @param AbstractExpression $expression Expression to set as left operand
     */
    public function setLeft($expression): void
    {
        $this->left = $expression;
    }

    /**
     * Get right operand (expression) of binary expression.
     *
     * @return AbstractExpression|null
     */
    public function getRight(): ?AbstractExpression
    {
        return isset($this->right) ? $this->right : null;
    }

    /**
     * Set right operand (expression) of binary expression.
     *
     * @param AbstractExpression $expression Expression to set as right operand
     */
    public function setRight($expression): void
    {
        $this->right = $expression;
    }

    /**
     * (non-PHPdoc).
     *
     * @see library/POData/QueryProcessor/ExpressionParser/Expressions.AbstractExpression::free()
     */
    public function free(): void
    {
        $this->left->free();
        $this->right->free();
        unset($this->left);
        unset($this->right);
    }
}
