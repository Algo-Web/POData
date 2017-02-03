<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

/**
 * Class ArithmeticExpression.
 */
class ArithmeticExpression extends BinaryExpression
{
    /**
     * Creates new instance of ArithmeticExpression.
     *
     * @param AbstractExpression                    $left     left expression
     * @param AbstractExpression                    $right    right Expression
     * @param ExpressionType                        $nodeType Expression node type
     * @param \POData\Providers\Metadata\Type\IType $type     Expression type
     */
    public function __construct($left, $right, $nodeType, $type)
    {
        $this->nodeType = $nodeType;
        $this->type = $type;
        parent::__construct($left, $right);
    }
}
