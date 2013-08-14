<?php
/** 
 * Binary expression class specialized for an arithmetic expression
 * 
 *
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;
/**
 * Arithmetic expression class.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ArithmeticExpression extends BinaryExpression
{
    /**
     * Creates new instance of ArithmeticExpression
     * 
     * @param AbstractExpression $left     left expression
     * @param AbstractExpression $right    right Expression
     * @param ExpressionType     $nodeType Expression node type
     * @param IType              $type     Expression type 
     */
    public function __construct($left, $right, $nodeType, $type)
    {
        $this->nodeType = $nodeType;
        $this->type = $type; 
        parent::__construct($left, $right);
    }
}
?>