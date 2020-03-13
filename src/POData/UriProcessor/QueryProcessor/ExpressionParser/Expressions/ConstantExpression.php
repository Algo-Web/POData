<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

/**
 * Class ConstantExpression.
 */
class ConstantExpression extends AbstractExpression
{
    /**
     * The value hold by the expression.
     *
     * @var string
     */
    protected $value;

    /**
     * Create new instance of ConstantExpression.
     *
     * @param string                                $value The constant value
     * @param \POData\Providers\Metadata\Type\IType $type  The expression node type
     */
    public function __construct($value, $type)
    {
        $this->value    = $value;
        $this->nodeType = ExpressionType::CONSTANT();
        $this->type     = $type;
    }

    /**
     * Get the value associated with the expression.
     *
     * @return string|null
     */
    public function getValue()
    {
        return isset($this->value) ? $this->value : null;
    }

    /**
     * (non-PHPdoc).
     *
     * @see library/POData/QueryProcessor/ExpressionParser/Expressions.AbstractExpression::free()
     * @return void
     */
    public function free()
    {
        unset($this->value);
    }
}
