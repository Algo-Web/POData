<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use POData\Providers\Metadata\Type\IType;

/**
 * Class ConstantExpression.
 */
class ConstantExpression extends AbstractExpression
{
    /**
     * The value held by the expression.
     *
     * @var string|bool|null
     */
    protected $value;

    /**
     * Create new instance of ConstantExpression.
     *
     * @param string|bool $value The constant value
     * @param IType $type The expression node type
     */
    public function __construct($value, $type)
    {
        $this->value = $value;
        $this->nodeType = ExpressionType::CONSTANT();
        $this->type = $type;
    }

    /**
     * Get the value associated with the expression.
     *
     * @return string|bool|null
     */
    public function getValue()
    {
        return isset($this->value) ? $this->value : null;
    }

    /**
     * (non-PHPdoc).
     *
     * @return void
     * @see library/POData/QueryProcessor/ExpressionParser/Expressions.AbstractExpression::free()
     */
    public function free()
    {
        unset($this->value);
    }
}
