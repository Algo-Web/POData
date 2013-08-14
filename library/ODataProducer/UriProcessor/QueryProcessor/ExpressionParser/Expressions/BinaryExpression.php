<?php
/** 
 * Abstract base class for binary expressions (arithmetic, logical or relational)
 * 
 *
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;
/**
 * Abstract base class for binary expression.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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
?>