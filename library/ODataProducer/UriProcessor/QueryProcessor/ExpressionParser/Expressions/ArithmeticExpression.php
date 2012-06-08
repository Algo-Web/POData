<?php
/** 
 * Binary expression class specialized for an arithmetic expression
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser_Expressions
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;
/**
 * Arithmetic expression class.
 *
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser_Expressions
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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