<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use POData\Providers\Metadata\Type\Boolean;

/**
 * Class LogicalExpression.
 */
class LogicalExpression extends BinaryExpression
{
    /**
     * Creates new instance of LogicalExpression.
     *
     * @param AbstractExpression $left     left expression
     * @param AbstractExpression $right    right expression
     * @param ExpressionType     $nodeType expression node type
     */
    public function __construct(AbstractExpression $left, AbstractExpression $right, ExpressionType $nodeType)
    {
        $this->nodeType = $nodeType;
        $this->type     = new Boolean();
        parent::__construct($left, $right);
    }
}
