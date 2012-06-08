<?php
/**
 * A type to represent root node of 'OrderBy Tree', the root node includes
 * details of resource set pointed by the request resource path uri.
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
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourceSetWrapper;
/**
 * A type to represent root node of 'OrderBy Tree'.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class OrderByRootNode extends OrderByNode
{
    /**
     * The resource type resource set pointed by the request resource
     * path uri.
     * 
     * @var ResourceType
     */
    private $_baseResourceType;

    /**
     * Constructs a new instance of 'OrderByRootNode' representing 
     * root of 'OrderBy Tree'
     * 
     * @param ResourceSetWrapper $resourceSetWrapper The resource set pointed by 
     *                                               the request resource path uri.
     * @param ResourceType       $baseResourceType   The resource type resource set
     *                                               pointed by the request resource
     *                                               path uri.
     */
    public function __construct(ResourceSetWrapper $resourceSetWrapper, 
        ResourceType $baseResourceType
    ) {
        parent::__construct(null, null, $resourceSetWrapper);
        $this->_baseResourceType = $baseResourceType;
    }

    /**
     * (non-PHPdoc)
     * 
     * @see library/ODataProducer/QueryProcessor/OrderByParser/ODataProducer\QueryProcessor\OrderByParser.OrderByNode::getResourceType()
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_baseResourceType;
    }
}
?>