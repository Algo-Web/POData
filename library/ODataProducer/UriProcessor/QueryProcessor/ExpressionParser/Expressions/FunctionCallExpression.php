<?php
/** 
 * Expression class specialized for a function call expression
 * 
 *
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;
use ODataProducer\UriProcessor\QueryProcessor\FunctionDescription\FunctionDescription;
/**
 * Expression class for function call.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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
     * Creates new instance of FunctionCallExpression
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
     * To get the array of expressions represents arguments of function
     * 
     * @return array<ParamExpression>
     */
    public function getParamExpressions()
    {
        return $this->paramExpressions;
    }

    /**
     * To get description of the function this expression represents
     * 
     * @return FunctionDescription
     */
    public function getFunctionDescription()
    {
        return $this->functionDescription;
    }

    /**
     * (non-PHPdoc)
     * 
     * @see library/ODataProducer/QueryProcessor/ExpressionParser/Expressions/ODataProducer\QueryProcessor\ExpressionParser\Expressions.AbstractExpression::free()
     * 
     * @return void
     */
    public function free()
    {
        foreach ($this->paramExpressions as $paramExpression) {
            $paramExpression->free();
            unset($paramExpression);
        }
    }
}
?>