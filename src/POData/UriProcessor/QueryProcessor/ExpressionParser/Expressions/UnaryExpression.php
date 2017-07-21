<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

/**
 * Class UnaryExpression.
 */
class UnaryExpression extends AbstractExpression
{
    /**
     * @var AbstractExpression
     */
    protected $child;

    /**
     * Construct a new instance of UnaryExpression.
     *
     * @param AbstractExpression                    $child    Child expression
     * @param ExpressionType                        $nodeType Expression node type
     * @param \POData\Providers\Metadata\Type\IType $type     Expression type
     */
    public function __construct($child, ExpressionType $nodeType, $type)
    {
        $this->child = $child;
        //allowed unary operator are 'not' (ExpressionType::NOT_LOGICAL)
        //and ExpressionType::NEGATE
        $this->nodeType = $nodeType;
        $this->type = $type;
    }

    /**
     * To get the child.
     *
     * @return AbstractExpression|null
     */
    public function getChild()
    {
        return isset($this->child) ? $this->child : null;
    }

    /**
     * (non-PHPdoc).
     *
     * @see library/POData/QueryProcessor/ExpressionParser/Expressions.AbstractExpression::free()
     */
    public function free()
    {
        $this->child->free();
        unset($this->child);
    }
}
