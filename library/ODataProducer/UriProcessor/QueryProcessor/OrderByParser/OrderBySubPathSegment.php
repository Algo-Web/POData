<?php
/**
 * A type to represent sub path segment in an order by path segment.
 * Syntax of orderby clause is:
 * 
 * OrderByClause         : OrderByPathSegment [, OrderByPathSegment]*
 * OrderByPathSegment    : OrderBySubPathSegment [/OrderBySubPathSegment]*[asc|desc]?
 * OrderBySubPathSegment : identifier
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
/**
 * A type to represent sub path segment.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class OrderBySubPathSegment
{
    /**
     * Resource property of the property corrosponding to this sub path segment
     * 
     * @var ResourceProperty
     */
    private $_resourceProperty;

    /**
     * Constructs a new instance of OrderBySubPathSegment
     * 
     * @param ResourceProperty $resourceProperty Resource property of the property
     *                                           corrosponding to this sub path 
     *                                           segment
     */
    public function __construct(ResourceProperty $resourceProperty)
    {
        $this->_resourceProperty = $resourceProperty;
    }

    /**
     * Gets name of the sub path segment
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_resourceProperty->getName();
    }

    /**
     * Gets refernece to the ResourceProperty instance corrosponding to this
     * sub path segment
     * 
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->_resourceProperty;
    }

    /**
     * Gets instance type of the ResourceProperty instance corrosponding to 
     * this sub path segment If this sub path segment is last segment then 
     * this function returns 'IType' otherwise 'ReflectionClass'.
     * 
     * @return ReflectionClass/IType
     */
    public function getInstanceType()
    {
        return $this->_resourceProperty->getInstanceType();
    }
}
?>