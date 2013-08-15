<?php

namespace ODataProducer\UriProcessor\QueryProcessor\OrderByParser;
use ODataProducer\Common\Messages;
use ODataProducer\Common\InvalidOperationException;

/**
 * Class OrderByInfo
 *
 * Type to hold information about the navigation properties used
 * in the orderby clause (if any) and orderby path if IDSQP implementor
 * want to perform sorting.
 *
 * @package ODataProducer\UriProcessor\QueryProcessor\OrderByParser
 */
class OrderByInfo
{
    /**
     * Collection of orderby path segments
     * 
     * @var OrderByPathSegment[]
     */
    private $_orderByPathSegments;

    /**
     * The DataServiceQueryProvider implementor will set this to true
     * using 'setSorted' function if implementor is going to perform 
     * the sorting, a false value for this flag means the library is 
     * responsible for sorting. 
     * 
     * @var boolean
     */
    private $_isSorted;

    /**
     * Collection of navigation properties specified in the orderby 
     * clause, if no navigation (resource reference) property used 
     * in the clause then this property will be null.
     * 
     * e.g. $orderby=NaviProp1/NaviProp2/PrimitiveProp, 
     *      NaviPropA/NaviPropB/PrimitiveProp
     * In this case array will be as follows:
     * array(array(NaviProp1, NaviProp2), array(NaviPropA, NaviPropB)) 
     * 
     * @var array(array(ResourceProperty))/NULL
     */
    private $_navigationPropertiesUsedInTheOrderByClause;

    /**
     * Constructs new instance of OrderByInfo
     * 
     * @param OrderByPathSegment[] $orderByPathSegments  Order by path segments
     *
     * @param array(array(ResourceProperty))|null $navigationPropertiesUsedInTheOrderByClause navigation properties used in the order by clause
     *
     * @throws InvalidArgumentException
     */
    public function __construct($orderByPathSegments, $navigationPropertiesUsedInTheOrderByClause) 
    {
        if (!is_array($orderByPathSegments)) {
            throw new \InvalidArgumentException(
                Messages::orderByInfoPathSegmentsArgumentShouldBeNonEmptyArray()
            );
        }

        if (empty($orderByPathSegments)) {
            throw new \InvalidArgumentException(
                Messages::orderByInfoPathSegmentsArgumentShouldBeNonEmptyArray()
            );
        }

        if (!is_null($navigationPropertiesUsedInTheOrderByClause)) {
            if (!is_array($navigationPropertiesUsedInTheOrderByClause)) {
                throw new \InvalidArgumentException(
                    Messages::orderByInfoNaviUSedArgumentShouldBeNullOrNonEmptyArray()
                );
            }

            if (empty($navigationPropertiesUsedInTheOrderByClause)) {
                throw new \InvalidArgumentException(
                    Messages::orderByInfoNaviUSedArgumentShouldBeNullOrNonEmptyArray()
                );
            }
        }

        $this->_orderByPathSegments = $orderByPathSegments;
        $this->_navigationPropertiesUsedInTheOrderByClause 
            = $navigationPropertiesUsedInTheOrderByClause;
    }

    /**
     * Gets collection of path segments which made up the orderby clause
     * 
     * @return OrderByPathSegment[]
     */
    public function getOrderByPathSegments()
    {
        return $this->_orderByPathSegments;
    }

    /**
     * Gets collection of navigation properties specified in the orderby clause
     * if no navigation (resource reference) properties are used in the clause then
     * this function returns null, DataServiceQueryProvider must check this
     * function and include these resource reference type navigation properties
     * in the result.
     *  
     * @return array(array(ResourceProperty))/NULL
     */
    public function getNavigationPropertiesUsed()
    {
        return $this->_navigationPropertiesUsedInTheOrderByClause;
    }

    /**
     * DataServiceQueryProvder implementor should use this function to let the
     * library know that whether implementor will be performing the sorting
     * or not, if not library will perform the sorting.
     * 
     * @param boolean $isSorted Set the flag so indicate that the result has
     *                          been sorted.
     * 
     * @return void
     */
    public function setSorted($isSorted = true)
    {
        $this->_isSorted = $isSorted;
    }

    /**
     * Whether library should do the sorting or not, if the QueryProvider 
     * implementor already sort the entities then library will not perform 
     * the sorting.
     * 
     * @return boolean
     */
    public function requireInternalSorting()
    {
        return !$this->_isSorted;
    }
}