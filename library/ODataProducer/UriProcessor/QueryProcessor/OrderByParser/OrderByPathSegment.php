<?php
/**
 * A type to represent path segment in an order by clause
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
use ODataProducer\Common\Messages;
/**
 * Type to represent path segment in an $orderby clause
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class OrderByPathSegment
{
    /**
     * Collection of sub path in this path segment
     * 
     * @var array(OrderBySubPathSegment)
     */
    private $_orderBySubPathSegments;

    /**
     * Flag indicates order of sorting, ascending or desending, default is ascending
     * 
     * @var boolean
     */
    private $_isAscending;

    /**
     * Constructs a new instance of OrderByPathSegment
     * 
     * @param array(OrderBySubPathSegment) $orderBySubPathSegments Collection of 
     *                                                             orderby sub path 
     *                                                             segments for
     *                                                             this path segment.
     * @param boolean                      $isAscending            sort order, 
     *                                                             True for 
     *                                                             ascending and 
     *                                                             false
     *                                                             for desending.
     */
    public function __construct($orderBySubPathSegments, $isAscending = true) 
    {
        if (!is_array($orderBySubPathSegments)) {
            throw new \InvalidArgumentException(
                Messages::orderByPathSegmentOrderBySubPathSegmentArgumentShouldBeNonEmptyArray()
            );
        }

        if (empty($orderBySubPathSegments)) {
            throw new \InvalidArgumentException(
                Messages::orderByPathSegmentOrderBySubPathSegmentArgumentShouldBeNonEmptyArray()
            );
        }

        $this->_orderBySubPathSegments = $orderBySubPathSegments;
        $this->_isAscending = $isAscending;
    }

    /**
     * Gets collection of sub path segments that made up this path segment
     * 
     * @return array(OrderBySubPathSegment)
     */
    public function getSubPathSegments()
    {
        return $this->_orderBySubPathSegments;
    }

    /**
     * To check sorting order is ascending or descending
     * 
     * @return boolean Return true for ascending sort order else false
     */
    public function isAscending()
    {
        return $this->_isAscending;
    }
}
?>