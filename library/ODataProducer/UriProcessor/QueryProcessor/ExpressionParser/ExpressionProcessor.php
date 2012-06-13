<?php
/** 
 * Class to process an expression tree and generate specialized 
 * (e.g. PHP) expression using expression provider
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyNullabilityCheckExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;
/**
 * Processor to process expression tree.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ExpressionProcessor
{
    private $_expressionAsString;
    private $_rootExpression;
    private $_expressionProvider;
    
    /**
     * Construct new instance of ExpressionProcessor
     * 
     * @param AbstractExpression  $rootExpression     The root of the expression
     *                                                tree.
     * @param IExpressionProvider $expressionProvider Reference to the language 
     *                                                specific provider.
     */
    public function __construct(AbstractExpression $rootExpression, 
        IExpressionProvider $expressionProvider
    ) {
        $this->_rootExpression = $rootExpression;
        $this->_expressionProvider = $expressionProvider;
    }

    /**
     * Sets the expression root.
     * 
     * @param AbstractExpression $rootExpression The root of the expression
     *                                           tree.
     * 
     * @return void
     */
    public function setExpression(AbstractExpression $rootExpression)
    {
        $this->_rootExpression = $rootExpression;
    }

    /**
     * Sets the language specific provider.
     * 
     * @param IExpressionProvider $expressionProvider The expression provider.
     * 
     * @return void
     */
    public function setExpressionProvider(IExpressionProvider $expressionProvider)
    {
        $this->_expressionProvider = $expressionProvider;
    }

    /**
     * Process the expression tree using expression provider and return the 
     * expression as string
     * 
     * @return string
     */
    public function processExpression()
    {
        $this->_expressionAsString = $this->_processExpressionNode($this->_rootExpression);
        return $this->_expressionAsString;
    }

    /**
     * Recursive function to process each node of the expression
     * 
     * @param AbstractExpression $expression Current node to process.
     * 
     * @return string The language specific expression.
     */
    private function _processExpressionNode(AbstractExpression $expression)
    {
        if ($expression instanceof ArithmeticExpression) {
            $left = $this->_processExpressionNode($expression->getLeft());
            $right = $this->_processExpressionNode($expression->getRight());
            return $this->_expressionProvider->onArithmeticExpression(
                $expression->getNodeType(), 
                $left, 
                $right
            );
        } else if ($expression instanceof LogicalExpression) {
            $left = $this->_processExpressionNode($expression->getLeft());
            $right = $this->_processExpressionNode($expression->getRight());
            return $this->_expressionProvider->onLogicalExpression(
                $expression->getNodeType(), 
                $left, 
                $right
            );
        } else if ($expression instanceof RelationalExpression) {
            $left = $this->_processExpressionNode($expression->getLeft());
            $right = $this->_processExpressionNode($expression->getRight());
            return $this->_expressionProvider->onRelationalExpression(
                $expression->getNodeType(), 
                $left, 
                $right
            );
        } else if ($expression instanceof ConstantExpression) {
            return $this->_expressionProvider->onConstantExpression(
                $expression->getType(), 
                $expression->getValue()
            );
        } else if ($expression instanceof PropertyAccessExpression) {
            return $this->_expressionProvider->onPropertyAccessExpression(
                $expression
            );
        } else if ($expression instanceof FunctionCallExpression) {
            $params = array();
            foreach ($expression->getParamExpressions() as $paramExpression) {
                $params[] = $this->_processExpressionNode($paramExpression);
            }
            return $this->_expressionProvider->onFunctionCallExpression(
                $expression->getFunctionDescription(), 
                $params
            );
        } else if ($expression instanceof UnaryExpression) {
            $child = $this->_processExpressionNode($expression->getChild());
            return $this->_expressionProvider->onUnaryExpression(
                $expression->getNodeType(), 
                $child
            );
        }
    }
}
?>