<?php
/** 
 * Binary expression class specialized for an logical expression
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
use ODataProducer\Providers\Metadata\Type\Boolean;
/**
 * Expression class for logical expression.
 *
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser_Expressions
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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