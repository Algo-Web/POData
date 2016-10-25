<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use POData\UriProcessor\QueryProcessor\FunctionDescription;

/**
 * Class FunctionCallExpression.
 */
class FunctionCallExpression extends AbstractExpression
{
    /**
     * @var FunctionDescription
     */
    protected $functionDescription;

    /**
     * @var array<AbstractExpression>
     */
    protected $paramExpressions;

    /**
     * Creates new instance of FunctionCallExpression.
     *
     * @param FunctionDescription       $functionDescription The signature of function-call
     * @param array<AbstractExpression> $paramExpressions    The parameters to the function
     */
    public function __construct(FunctionDescription $functionDescription, $paramExpressions)
    {
        $this->functionDescription = $functionDescription;
        $this->paramExpressions = $paramExpressions;
        $this->nodeType = ExpressionType::CALL;
        $this->type = $functionDescription->returnType;
    }

    /**
     * To get the array of expressions represents arguments of function.
     *
     * @return array<ParamExpression>
     */
    public function getParamExpressions()
    {
        return $this->paramExpressions;
    }

    /**
     * To get description of the function this expression represents.
     *
     * @return FunctionDescription
     */
    public function getFunctionDescription()
    {
        return $this->functionDescription;
    }

    /**
     * (non-PHPdoc).
     *
     * @see library/POData/QueryProcessor/ExpressionParser/Expressions.AbstractExpression::free()
     */
    public function free()
    {
        foreach ($this->paramExpressions as $paramExpression) {
            $paramExpression->free();
            unset($paramExpression);
        }
    }
}
