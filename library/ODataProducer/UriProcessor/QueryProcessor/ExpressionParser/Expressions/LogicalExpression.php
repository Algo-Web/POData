<?php
/** 
 * Binary expression class specialized for an logical expression
 * 
 *
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;
use ODataProducer\Providers\Metadata\Type\Boolean;
/**
 * Expression class for logical expression.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class LogicalExpression extends BinaryExpression
{
    /**
     * Creates new instance of LogicalExpression
     * 
     * @param AbstractExpression $left     left expression
     * @param AbstractExpression $right    right expression
     * @param ExpressionType     $nodeType expression node type 
     */
    public function __construct($left, $right, $nodeType)
    {
        $this->nodeType = $nodeType;
        $this->type = new Boolean();
        parent::__construct($left, $right);
    }
}
?>