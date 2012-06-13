<?php
/** 
 * The expression provider interface.
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
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\Type\IType;
/**
 * The expression provider interface.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
interface IExpressionProvider
{
    /**
     * Get the name of the iterator
     * 
     * @return string
     */    
    public function getIteratorName();

    /**
     * call-back for setting the resource type.
     * 
     * @param ResourceType $resourceType The resource type on which the filter
     *                                   is going to be applied.
     *
     * @return void
     */
    public function setResourceType(ResourceType $resourceType);
    
    /**
     * Call-back for logical expression
     * 
     * @param ExpressionType $expressionType The type of logical expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     * 
     * @return string
     */
    public function onLogicalExpression($expressionType, $left, $right);

    /**      
     * Call-back for arithmetic expression
     * 
     * @param ExpressionType $expressionType The type of arithmetic expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     * 
     * @return string
     */
    public function onArithmeticExpression($expressionType, $left, $right);

    /**
     * Call-back for relational expression
     * 
     * @param ExpressionType $expressionType The type of relation expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     * 
     * @return string
     */
    public function onRelationalExpression($expressionType, $left, $right);

    /**      
     * Call-back for unary expression
     * 
     * @param ExpressionType $expressionType The type of unary expression
     * @param string         $child          The child expression     
     * 
     * @return string
     */
    public function onUnaryExpression($expressionType, $child);
    
    /**
     * Call-back for constant expression
     * 
     * @param IType  $type  The type of constant
     * @param objetc $value The value of the constant
     * 
     * @return string
     */
    public function onConstantExpression(IType $type, $value);
    
    /**
     * Call-back for property access expression
     * 
     * @param PropertyAccessExpression $expression The property access expression
     * 
     * @return string
     */
    public function onPropertyAccessExpression($expression);

    /**
     * Call-back for function call expression
     * 
     * @param string        $functionDescription Description of the function.
     * @param array<string> $params              Arguments to the functions.
     * 
     * @return string
     */
    public function onFunctionCallExpression($functionDescription, $params);
}
?>