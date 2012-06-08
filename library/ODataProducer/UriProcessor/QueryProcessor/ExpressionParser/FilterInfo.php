<?php
/**  
 * A type to hold information about the navigation properties
 * used in the filter clause
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser;
use ODataProducer\Common\Messages;
/**
 * Type for holding navigation properties in the $filter clause.
 *
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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