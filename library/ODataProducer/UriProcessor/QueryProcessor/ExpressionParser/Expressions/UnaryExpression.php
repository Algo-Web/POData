<?php
/** 
 * Expression class specialized for unary expression
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
 * Expression class for unary expression
 *
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser_Expressions
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class UnaryExpression extends AbstractExpression
{
    /**
     * @var AbstractExpression
     */
    protected $child;

    /**
     * Construct a new instance of UnaryExpression
     * 
     * @param unknown_type $child    Child expression
     * @param unknown_type $nodeType Expression node type
     * @param unknown_type $type     Expression type
     */
    public function __construct($child, $nodeType, $type)
    {
        $this->child = $child;
        //allowed unary operator are 'not' (ExpressionType::NOT_LOGICAL) 
        //and ExpressionType::NEGATE
        $this->nodeType = $nodeType;
        $this->type = $type;
    }

    /**
     * To get the child
     * 
     * @return AbstractExpression
     */
    public function getChild()
    {
        return $this->child;
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
        $this->child->free();
        unset($this->child);
    }
}
?>