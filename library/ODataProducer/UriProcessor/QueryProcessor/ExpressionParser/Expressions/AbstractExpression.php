<?php
/** 
 * Abstract base class for all expressions
 * 
 *
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;
use ODataProducer\Providers\Metadata\Type\IType;
/**
 * Abstract expression class.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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