<?php
/**
 * Type to hold information about the navigation properties used 
 * in the orderby clause (if any) and orderby path if IDSQP implementor
 * want to perform sorting.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\OrderByParser;
use ODataProducer\Common\Messages;
use ODataProducer\Common\InvalidOperationException;
/**
 * Type to hold information about the navigation properties used 
 * in the orderby clause.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class OrderByInfo
{
    /**
     * Collection of orderby path segments
     * 
     * @var array(OrderByPathSegment)
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
     * @param array $orderByPathSegments                        Order by 
     *                                                          path segments
     * array(OrderByPathSegment)
     * @param array $navigationPropertiesUsedInTheOrderByClause navigation
     *                                                          properties used
     *                                                          in the order 
     *                                                          by clause
     * array(array(ResourceProperty))/NULL
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
     * @return array(OrderByPathSegment)
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
?>