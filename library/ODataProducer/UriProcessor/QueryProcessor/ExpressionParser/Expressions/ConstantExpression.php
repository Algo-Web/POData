<?php

namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

/**
 * Class ConstantExpression
 * @package ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions
 */
class ConstantExpression extends AbstractExpression
{
    /**
     * The value hold by the expression
     * @var string
     */
    protected $value;

    /**
     * Create new inatnce of ConstantExpression.
     * 
     * @param string $value The constant value
     * @param IType  $type  The expression node type
     */
    public function __construct($value, $type)
    {
        $this->value = $value;
        $this->nodeType = ExpressionType::CONSTANT;
        $this->type = $type;
    }

    /**
     * Get the value associated with the expression
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->value;
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
        unset($this->value);
    }
}