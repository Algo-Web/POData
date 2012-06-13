<?php
/** 
 * Enumeration for expression language operators, function call and literal 
 * used in $filter option
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser_Expressions
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
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;
/**
 * Enumeration for operators.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser_Expressions
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ExpressionType
{
    /**
     * Arithmetic expression with 'add' operator
     */
    const ADD                   = 1;

    /**
     * Logical expression with 'and' operator
     */
    const AND_LOGICAL           = 2;

    /**
     * Funcation call expression 
     * e.g. substringof('Alfreds', CompanyName)
     */
    const CALL                  = 3;

    /**
     * Constant expression. e.g. In the expression
     * OrderID ne null and OrderID add 2 gt 5432
     * 2, null, 5432 are candicate for constant expression
     */
    const CONSTANT              = 4;

    /**
     * Arithmetic expression with 'div' operator
     */
    const DIVIDE                = 5;

    /**
     * Comparison expression with 'eq' operator
     */
    const EQUAL                 = 6;

    /**
     * Comparison expression with 'gt' operator
     */
    const GREATERTHAN           = 7;

    /**
     * Comparison expression with 'ge' operator
     */
    const GREATERTHAN_OR_EQUAL  = 8;

    /**
     * Comparison expression with 'lt' operator
     */
    const LESSTHAN              = 9;

    /**
     * Comparison expression with 'le' operator
     */
    const LESSTHAN_OR_EQUAL     = 10;

    /**
     * Arithmetic expression with 'mod' operator
     */
    const MODULO                = 11;

    /**
     * Arithmetic expression with 'mul' operator
     */
    const MULTIPLY              = 12;

    /**
     * Unary expression with '-' operator
     */
    const NEGATE                 = 13;

    /**
     * Unary Logical expression with 'not' operator
     */
    const NOT_LOGICAL           = 14;

    /**
     * Comparison expression with 'ne' operator
     */
    const NOTEQUAL              = 15;

    /**
     * Logical expression with 'or' operator
     */
    const OR_LOGICAL            = 16;

    /**
     * Property expression. e.g. In the expression
     * OrderID add 2 gt 5432 
     * OrderID is candicate for PropertyAccessExpression
     */
    const PROPERTYACCESS        = 17;

    /** 
     * Same as property expression but for nullabilty check
     */
    const PROPERTY_NULLABILITY_CHECK = 18;
    
    /**
     * 
     * Arithmetic expression with 'sub' operator
     */
    const SUBTRACT              = 19;
    
    
}
?>