<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Common\Messages;

/**
 * Class FilterInfo
 *
 * Type for holding navigation properties in the $filter clause.
 *
 * @package POData\UriProcessor\QueryProcessor\ExpressionParser
 */
class FilterInfo
{
    /**
     * Collection of navigation properties specified in the filter 
     * clause, if no navigation (resource reference) property used 
     * in the clause then this property will be null.
     * 
     * e.g. $filter=NaviProp1/NaviProp2/PrimitiveProp eq 12 
     *      $filter=NaviPropA/NaviPropB/PrimitiveProp gt 56.3
     * In this case array will be as follows:
     * array(array(NaviProp1, NaviProp2), array(NaviPropA, NaviPropB)) 
     * 
     * @var array(array(ResourceProperty))/NULL
     */
    private $_navigationPropertiesUsedInTheFilterClause;

    /**
     * Creates a new instance of FilterInfo.
     * 
     * @param array(array(ResourceProperty))/NULL $navigationPropertiesUsedInTheFilterClause Collection of navigation properties specified in the filter
     * 
     * @throws \InvalidArgumentException
     */
    public function __construct($navigationPropertiesUsedInTheFilterClause) 
    {
        if (!is_null($navigationPropertiesUsedInTheFilterClause)) {
            if (!is_array($navigationPropertiesUsedInTheFilterClause)) {
                throw new \InvalidArgumentException(
                    Messages::filterInfoNaviUsedArgumentShouldBeNullOrNonEmptyArray()
                ); 
            }
        }

        $this->_navigationPropertiesUsedInTheFilterClause 
            = $navigationPropertiesUsedInTheFilterClause;
    }

    /**
     * Gets collection of navigation properties specified in the filter clause
     * if no navigation (resource reference) properties are used in the clause then
     * this function returns null,
     * IQueryProvider must check this  function and include these resource reference type navigation properties
     * in the result.
     *  
     * @return array(array(ResourceProperty))/NULL
     */
    public function getNavigationPropertiesUsed()
    {
        return $this->_navigationPropertiesUsedInTheFilterClause;
    }
}