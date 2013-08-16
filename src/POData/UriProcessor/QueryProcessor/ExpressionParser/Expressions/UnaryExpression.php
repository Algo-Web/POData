<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

/**
 * Class UnaryExpression
 * @package POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions
 */
class UnaryExpression extends AbstractExpression
{
    /**
     * @var AbstractExpression
     */
    protected $child;

    /**
     * Construct a new instance of UnaryExpression
     * 
     * @param unknown_type $child    Child expression
     * @param unknown_type $nodeType Expression node type
     * @param unknown_type $type     Expression type
     */
    public function __construct($child, $nodeType, $type)
    {
        $this->child = $child;
        //allowed unary operator are 'not' (ExpressionType::NOT_LOGICAL) 
        //and ExpressionType::NEGATE
        $this->nodeType = $nodeType;
        $this->type = $type;
    }

    /**
     * To get the child
     * 
     * @return AbstractExpression
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * (non-PHPdoc)
     * 
     * @see library/POData/QueryProcessor/ExpressionParser/Expressions.AbstractExpression::free()
     * 
     * @return void
     */
    public function free()
    {
        $this->child->free();
        unset($this->child);
    }
}