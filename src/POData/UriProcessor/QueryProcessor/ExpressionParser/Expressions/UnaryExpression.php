<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use POData\Providers\Metadata\Type\IType;

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
     * @param AbstractExpression $child    Child expression
     * @param ExpressionType     $nodeType Expression node type
     * @param IType              $type     Expression type
     */
    public function __construct(AbstractExpression $child, ExpressionType $nodeType, IType $type)
    {
        $this->child = $child;
        //allowed unary operator are 'not' (ExpressionType::NOT_LOGICAL)
        //and ExpressionType::NEGATE
        $this->nodeType = $nodeType;
        $this->type     = $type;
    }

    /**
     * To get the child.
     *
     * @return AbstractExpression|null
     */
    public function getChild(): ?AbstractExpression
    {
        return isset($this->child) ? $this->child : null;
    }

    /**
     * (non-PHPdoc).
     *
     * @see library/POData/QueryProcessor/ExpressionParser/Expressions.AbstractExpression::free()
     */
    public function free(): void
    {
        $this->child->free();
        unset($this->child);
    }
}
