<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use POData\Providers\Metadata\Type\Boolean;

/**
 * Class RelationalExpression.
 */
class RelationalExpression extends BinaryExpression
{
    /**
     * Creates new instance of RelationalExpression.
     *
     * @param AbstractExpression $left     left expression
     * @param AbstractExpression $right    right expression
     * @param ExpressionType     $nodeType expression node type
     */
    public function __construct($left, $right, $nodeType)
    {
        $this->nodeType = $nodeType;
        $this->type = new Boolean();
        parent::__construct($left, $right);
    }
}
