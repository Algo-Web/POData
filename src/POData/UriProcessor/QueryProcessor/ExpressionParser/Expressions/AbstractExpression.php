<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use POData\Providers\Metadata\Type\IType;

/**
 * Class AbstractExpression.
 */
abstract class AbstractExpression
{
    /**
     * The expression node type.
     *
     * @var ExpressionType
     */
    protected $nodeType;

    /**
     * The type of expression.
     *
     * @var IType
     */
    protected $type;

    /**
     * Get the node type.
     *
     * @return ExpressionType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * Get the expression type.
     *
     * @return IType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the expression type.
     *
     * @param IType $type The type to set as expression type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Checks expression is a specific type.
     *
     * @param IType $type The type to check
     *
     * @return bool
     */
    public function typeIs(IType $type)
    {
        return $this->type->getTypeCode() == $type->getTypeCode();
    }

    /**
     * Frees the resources hold by this expression.
     */
    abstract public function free();
}
