<?php
/**
 * Base type for nodes in OrderByTree, a node in 'OrderBy Tree' 
 * represents a sub path segment.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\UriProcessor\QueryProcessor\OrderByParser;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\ResourceType;
/**
 * Base type for nodes in OrderByTree
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
abstract class OrderByBaseNode
{
    /**
     * Name of the property corrosponds to the sub path segment 
     * represented by this node.
     * 
     * @var string
     */
    protected $propertyName;

    /**
     * Th resource property of the property corrosponds to the 
     * sub path segment represented by this node.
     * 
     * @var ResourceProperty
     */
    protected $resourceProperty;

    /**
     * Construct a new instance of OrderByBaseNode
     * 
     * @param string           $propertyName     Name of the property corrosponds to
     *                                           the sub path segment represented by 
     *                                           this node, this parameter will be 
     *                                           null if this node is root.
     * @param ResourceProperty $resourceProperty Resource property corrosponds to the
     *                                           sub path segment represented by this
     *                                           node, this parameter will be null if
     *                                           this node is root.
     */
    public function __construct($propertyName, $resourceProperty)
    {
        $this->propertyName = $propertyName;
        $this->resourceProperty = $resourceProperty;
    }

    /**
     * Gets resource type of the property corrosponds to the sub path segment 
     * represented by this node.
     * 
     * @return ResourceType
     */
    abstract public function getResourceType();

    /**
     * Free resource used by this node.
     * 
     * @return void
     */
    abstract public function free();

    /**
     * Gets the name of the property corrosponds to the sub path segment 
     * represented by this node.
     * 
     * @return  string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Gets the resource property of property corrosponds to the sub path 
     * segment represented by this node.
     * 
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->resourceProperty;
    }    
}
?>