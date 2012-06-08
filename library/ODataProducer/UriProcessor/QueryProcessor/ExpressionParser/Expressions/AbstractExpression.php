<?php
/** 
 * Abstract base class for all expressions
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
use ODataProducer\Providers\Metadata\Type\IType;
/**
 * Abstract expression class.
 *
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser_Expressions
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
abstract class AbstractExpression
{
    /**
     * The expression node type
     * 
     * @var ExpressionType
     */
    protected $nodeType;

    /**
     * The type of expression 
     * @var ODataProducer\Provider\Metadata\Type\IType
     */
    protected $type;

    /**
     * Get the node type
     * 
     * @return ExpressionType
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * Get the expression type
     * 
     * @return IType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the expression type
     * 
     * @param IType $type The type to set as expression type
     * 
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Checks expression is a specific type
     * 
     * @param IType $type The type to check
     * 
     * @return boolean
     */
    public function typeIs(IType $type)
    {
        return $this->type->getTypeCode() == $type->getTypeCode();
    }

    /**
     * Frees the resources hold by this expression
     * 
     * @return void
     */
    abstract public function free();
}
?>