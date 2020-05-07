<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use POData\Providers\Metadata\Type\IType;

/**
 * Class ArithmeticExpression.
 */
class ArithmeticExpression extends BinaryExpression
{
    /**
     * Creates new instance of ArithmeticExpression.
     *
     * @param AbstractExpression $left     left expression
     * @param AbstractExpression $right    right Expression
     * @param ExpressionType     $nodeType Expression node type
     * @param IType              $type     Expression type
     */
    public function __construct($left, $right, ExpressionType $nodeType, $type)
    {
        $this->nodeType = $nodeType;
        $this->type     = $type;
        parent::__construct($left, $right);
    }
}
