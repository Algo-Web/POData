<?php
/**  
 * A type to hold information about the navigation properties
 * used in the filter clause
 * 
 *
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser;
use ODataProducer\Common\Messages;
/**
 * Type for holding navigation properties in the $filter clause.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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
     * @throws InvalidArgumentException
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
     * this function returns null, DataServiceQueryProvider must check this
     * function and include these resource reference type navigation properties
     * in the result.
     *  
     * @return array(array(ResourceProperty))/NULL
     */
    public function getNavigationPropertiesUsed()
    {
        return $this->_navigationPropertiesUsedInTheFilterClause;
    }
}
?>