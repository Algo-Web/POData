<?php
/** 
 * The specialized expression provider for PHP
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
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use ODataProducer\Providers\Metadata\Type\IType;
use ODataProducer\Common\NotImplementedException;
use ODataProducer\Common\ODataConstants;
/**
 * PHP expression provider.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class PHPExpressionProvider implements IExpressionProvider
{
    const ADD                  = '+';
    const CLOSE_BRACKET        = ')';
    const COMMA                = ',';
    const DIVIDE               = '/';
    const SUBTRACT             = '-';
    const EQUAL                = '==';
    const GREATERTHAN          = '>';
    const GREATERTHAN_OR_EQUAL = '>=';
    const LESSTHAN             = '<';
    const LESSTHAN_OR_EQUAL    = '<=';
    const LOGICAL_AND          = '&&';
    const LOGICAL_NOT          = '!';
    const LOGICAL_OR           = '||';
    const MEMBERACCESS         = '->';
    const MODULO               = '%';
    const MULTIPLY             = '*';
    const NEGATE               = '-';
    const NOTEQUAL             = '!=';
    const OPEN_BRAKET          = '(';
    const TYPE_NAMESPACE       = 'ODataProducer\\Providers\\Metadata\\Type\\'; 

    /**
     * The name of iterator
     * 
     * @var string
     */
    private $_iterName;

    /**
     * The type of the resource pointed by the resource path segement
     * 
     * @var ResourceType
     */
    private $_resourceType;

    /**
     * Constructs new instance of PHPExpressionProvider
     * 
     * @param string $iterName The name of the iterator
     */
    public function __construct($iterName)
    {
        $this->_iterName = $iterName;
    }

    /**
     * Get the name of the iterator
     * 
     * @return string
     */
    public function getIteratorName()
    {
        return $this->_iterName;
    }

    /**
     * call-back for setting the resource type.
     *
     * @param ResourceType $resourceType The resource type on which the filter
     *                                   is going to be applied.
     *
     * @return void
     */
    public function setResourceType(ResourceType $resourceType)
    {
    	$this->_resourceType = $resourceType;
    }

    /**
     * Call-back for logical expression
     * 
     * @param ExpressionType $expressionType The type of logical expression.
     * @param string         $left           The left expression.
     * @param string         $right          The left expression.
     * 
     * @return string
     */
    public function onLogicalExpression($expressionType, $left, $right)
    {
        switch($expressionType) {
        case ExpressionType::AND_LOGICAL:
            return $this->_prepareBinaryExpression(self::LOGICAL_AND, $left, $right);
            break;
        case ExpressionType::OR_LOGICAL:
            return $this->_prepareBinaryExpression(self::LOGICAL_OR, $left, $right);
            break;
        default:
            throw new \InvalidArgumentException('onLogicalExpression');
        }
    }

    /**
     * Call-back for arithmetic expression
     * 
     * @param ExpressionType $expressionType The type of arithmetic expression.
     * @param string         $left           The left expression.
     * @param string         $right          The left expression.
     * 
     * @return string
     */
    public function onArithmeticExpression($expressionType, $left, $right)
    {
        switch($expressionType) {
        case ExpressionType::MULTIPLY:
            return $this->_prepareBinaryExpression(self::MULTIPLY, $left, $right);
            break;
        case ExpressionType::DIVIDE:
            return $this->_prepareBinaryExpression(self::DIVIDE, $left, $right);
            break;
        case ExpressionType::MODULO:
            return $this->_prepareBinaryExpression(self::MODULO, $left, $right);
            break;
        case ExpressionType::ADD:
            return $this->_prepareBinaryExpression(self::ADD, $left, $right);
            break;
        case ExpressionType::SUBTRACT:
            return $this->_prepareBinaryExpression(self::SUBTRACT, $left, $right);
            break;
        default:
            throw new \InvalidArgumentException('onArithmeticExpression');
        }
    }

    /**
     * Call-back for relational expression
     * 
     * @param ExpressionType $expressionType The type of relation expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     * 
     * @return string
     */
    public function onRelationalExpression($expressionType, $left, $right)
    {
        switch($expressionType) {
        case ExpressionType::GREATERTHAN:
            return $this->_prepareBinaryExpression(self::GREATERTHAN, $left, $right);
            break;
        case ExpressionType::GREATERTHAN_OR_EQUAL:
            return $this->_prepareBinaryExpression(
                self::GREATERTHAN_OR_EQUAL, $left, $right
            );
            break;
        case ExpressionType::LESSTHAN:
            return $this->_prepareBinaryExpression(self::LESSTHAN, $left, $right);
            break;
        case ExpressionType::LESSTHAN_OR_EQUAL:
            return $this->_prepareBinaryExpression(
                self::LESSTHAN_OR_EQUAL, $left, $right
            );
            break;
        case ExpressionType::EQUAL:
            return $this->_prepareBinaryExpression(self::EQUAL, $left, $right);
            break;
        case ExpressionType::NOTEQUAL:
            return $this->_prepareBinaryExpression(self::NOTEQUAL, $left, $right);
            break;
        default:
            throw new \InvalidArgumentException('onArithmeticExpression');
        }
    }

    /**
     * Call-back for unary expression
     * 
     * @param ExpressionType $expressionType The type of unary expression
     * @param string         $child          The child expression
     * 
     * @return string
     */
    public function onUnaryExpression($expressionType, $child)
    {
        switch($expressionType) {
        case ExpressionType::NEGATE:
            return $this->_prepareUnaryExpression(self::NEGATE, $child);
            break;
        case ExpressionType::NOT_LOGICAL:
            return $this->_prepareUnaryExpression(self::LOGICAL_NOT, $child);
            break;
        default:
            throw new \InvalidArgumentException('onUnaryExpression');
        }
    }

    /**
     * Call-back for constant expression
     * 
     * @param IType  $type  The type of constant
     * @param objetc $value The value of the constant
     * 
     * @return string
     */
    public function onConstantExpression(IType $type, $value)
    {
        if (is_bool($value)) {
            return var_export($value, true);
        } else if (is_null($value)) {
            return var_export(null, true);
        }
        
        return $value;
    }

    /**
     * Call-back for property access expression
     * 
     * @param PropertyAccessExpression $expression The property access expression
     * 
     * @return string
     */
    public function onPropertyAccessExpression($expression)
    {
        $parent = $expression;
        $variable = null;
        
        do {
            $variable 
                = $parent->getResourceProperty()->getName() 
                . self::MEMBERACCESS 
                . $variable;
            $parent = $parent->getParent();
        } while ($parent != null);
 
        $variable = rtrim($variable, self::MEMBERACCESS);
        $variable = $this->getIteratorName() . self::MEMBERACCESS . $variable;
        return $variable;
    }

    /**
     * Call-back for function call expression
     * 
     * @param FunctionDescription $functionDescription Description of the function.
     * @param array<string>       $params              Paameters to the function.
     * 
     * @return string
     */
    public function onFunctionCallExpression($functionDescription, $params)
    {
        switch($functionDescription->functionName) {
        case ODataConstants::STRFUN_COMPARE:
            return "strcmp($params[0], $params[1])";
            break;
        case ODataConstants::STRFUN_ENDSWITH:
            return "(strcmp(substr($params[0], strlen($params[0]) - strlen($params[1])), $params[1]) === 0)";
            break;
        case ODataConstants::STRFUN_INDEXOF:
            return "strpos($params[0], $params[1])";
            break;
        case ODataConstants::STRFUN_REPLACE:
            return "str_replace($params[1], $params[2], $params[0])";
            break;
        case ODataConstants::STRFUN_STARTSWITH:
            return "(strpos($params[0], $params[1]) === 0)";
            break; 
        case ODataConstants::STRFUN_TOLOWER:
            return "strtolower($params[0])";
            break;
        case ODataConstants::STRFUN_TOUPPER:
            return "strtoupper($params[0])";
            break;
        case ODataConstants::STRFUN_TRIM:
            return "trim($params[0])";
            break;
        case ODataConstants::STRFUN_SUBSTRING:
            return count($params) == 3 ?
                "substr($params[0], $params[1], $params[2])" :
                "substr($params[0], $params[1])";
            break;
        case ODataConstants::STRFUN_SUBSTRINGOF:
            return "(strpos($params[1], $params[0]) !== false)";
            break;
        case ODataConstants::STRFUN_CONCAT:
            return $params[0] . ' . ' . $params[1];
            break;
        case ODataConstants::STRFUN_LENGTH:
            return "strlen($params[0])";
            break;
        case ODataConstants::GUIDFUN_EQUAL:
            return self::TYPE_NAMESPACE . "Guid::guidEqual($params[0], $params[1])";
            break;
        case ODataConstants::DATETIME_COMPARE:
            return 
                self::TYPE_NAMESPACE 
                    . "DateTime::dateTimeCmp($params[0], $params[1])";
            break;
        case ODataConstants::DATETIME_YEAR:
            return self::TYPE_NAMESPACE . "DateTime::year($params[0])";
            break;
        case ODataConstants::DATETIME_MONTH:
            return self::TYPE_NAMESPACE . "DateTime::month($params[0])";
            break;
        case ODataConstants::DATETIME_DAY:
            return self::TYPE_NAMESPACE . "DateTime::day($params[0])";
            break;
        case ODataConstants::DATETIME_HOUR:
            return self::TYPE_NAMESPACE . "DateTime::hour($params[0])";
            break;
        case ODataConstants::DATETIME_MINUTE:
            return self::TYPE_NAMESPACE . "DateTime::minute($params[0])";
            break;
        case ODataConstants::DATETIME_SECOND:
            return self::TYPE_NAMESPACE . "DateTime::second($params[0])";
            break;                
        case ODataConstants::MATHFUN_ROUND:
            return "round($params[0])";
            break;
        case ODataConstants::MATHFUN_CEILING:
            return "ceil($params[0])";
            break;
        case ODataConstants::MATHFUN_FLOOR:
            return "floor($params[0])";
            break;
        case ODataConstants::BINFUL_EQUAL:
            return 
                self::TYPE_NAMESPACE 
                    . "Binary::binaryEqual($params[0], $params[1])";
            break;
        case 'is_null':
            return "is_null($params[0])";
            break;
        default:
            throw new \InvalidArgumentException('onFunctionCallExpression');
        }
    }

    /**
     * To format binary expression
     * 
     * @param string $operator The binary operator.
     * @param string $left     The left operand.
     * @param string $right    The right operand.
     * 
     * @return string
     */
    private function _prepareBinaryExpression($operator, $left, $right)
    {
        return 
            self::OPEN_BRAKET 
            . $left . ' ' . $operator 
            . ' ' . $right . self::CLOSE_BRACKET;
    }

    /**
     * To format unary expression
     * 
     * @param string $operator The unary operator.
     * @param string $child    The operand.
     * 
     * @return string
     */
    private function _prepareUnaryExpression($operator, $child)
    {
        return $operator . self::OPEN_BRAKET . $child . self::CLOSE_BRACKET;
    }
}
?>