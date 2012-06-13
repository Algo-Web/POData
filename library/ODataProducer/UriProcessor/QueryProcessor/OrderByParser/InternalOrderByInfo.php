<?php
/** 
 * Class to hold parsed orderby infromation.
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
use ODataProducer\UriProcessor\QueryProcessor\AnonymousFunction;
/**
 * Type to hold parsed orderby information.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class InternalOrderByInfo
{
    /**
     * The structure holds information about the navigation properties used in the 
     * orderby clause (if any) and orderby path if IDSQP implementor want to perform
     * sorting.
     * 
     * @var OrderByInfo
     */
    private $_orderByInfo;

    /**
     * Collection of sub sorter functions corrosponding to each orderby path segment.
     * 
     * @var array(AnonymousFunction)
     */
    private $_subSorterFunctions;

    /**
     * The top level anonymous sorter function
     * 
     * @var AnonymousFunction
     */
    private $_sorterFunction;

    /**
     * This object will be of type of the resource set identified by the request uri.
     * 
     * @var mixed
     */
    private $_dummyObject;

    /**
     * Creates new instance of InternalOrderByInfo
     * 
     * @param OrderByInfo              $orderByInfo        The structure holds
     *                                                     information about the 
     *                                                     navigation properties 
     *                                                     used in the orderby clause
     *                                                     (if any) and orderby path 
     *                                                     if IDSQP implementor 
     *                                                     want to perform sorting.
     * @param array(AnonymousFunction) $subSorterFunctions Collection of sub sorter
     *                                                     functions corrosponding 
     *                                                     to each orderby 
     *                                                     path segment
     * @param AnonymousFunction        $sorterFunction     The top level anonymous
     *                                                     sorter function.
     * @param mixed                    $dummyObject        A dummy object of type
     *                                                     of the resource set 
     *                                                     identified by the
     *                                                     request uri.
     */
    public function __construct(OrderByInfo $orderByInfo, $subSorterFunctions, 
        AnonymousFunction $sorterFunction, $dummyObject
    ) {
        $this->_orderByInfo = $orderByInfo;
        $this->_sorterFunction = $sorterFunction;
        $this->_subSorterFunctions = $subSorterFunctions;
        $this->_dummyObject = $dummyObject;
    }

    /**
     * Get reference to order information to pe passed to IDSQP implementation calls
     * 
     * @return OrderByInfo
     */
    public function getOrderByInfo()
    {
        return $this->_orderByInfo;
    }

    /**
     * Get reference to the orderby path segment information.
     * 
     * @return array(OrderByPathSegment)
     */
    public function getOrderByPathSegments()
    {
        return $this->_orderByInfo->getOrderByPathSegments();
    }

    /**
     * Gets reference to the top level sorter function. 
     * 
     * @return AnonymousFunction
     */
    public function getSorterFunction()
    {
        return $this->_sorterFunction;
    }

    /**
     * Gets collection of sub sorter functions
     * 
     * @return array(AnonymousFunction)
     */
    public function getSubSorterFunctions()
    {
        return $this->_subSorterFunctions;  
    }

    /**
     * Gets a dummy object of type of the resource set identified by the request uri.
     * 
     * @return mixed
     */
    public function &getDummyObject()
    {
        return $this->_dummyObject;
    }

    /**
     * Build value of $skiptoken from the given object which will be the 
     * last object in the page.
     * 
     * @param mixed $lastObject entity instance from which skiptoken needs 
     *                          to be built.
     * 
     * @return string
     * 
     * @throws ODataException If reflection exception occurs while accessing 
     *                        property.
     */
    public function buildSkipTokenValue($lastObject)
    {
        $nextPageLink = null;
        foreach ($this->getOrderByPathSegments() as $orderByPathSegment) {
            $index = 0;
            $currentObject = $lastObject;
            $subPathSegments = $orderByPathSegment->getSubPathSegments();
            $subPathCount = count($subPathSegments);
            foreach ($subPathSegments as &$subPathSegment) {
                $isLastSegment = ($index == $subPathCount - 1);
                try {
                    $dummyProperty = new \ReflectionProperty(
                        $currentObject, $subPathSegment->getName()
                    );
                    $currentObject = $dummyProperty->getValue($currentObject);
                    if (is_null($currentObject)) {                        
                            $nextPageLink .= 'null, ';
                            break;
                    } else if ($isLastSegment) {
                        $type = $subPathSegment->getInstanceType();
                        // assert($type implements IType)
                        // If this is a string then do utf8_encode to convert 
                        // utf8 decoded characters to
                        // corrospoding utf8 char (e.g. � to í), then do a 
                        // urlencode to convert í to %C3%AD
                        // urlencode is needed for datetime and guid too
                        // if ($type instanceof String || $type instanceof DateTime 
                        //     || $type instanceof Guid) {
                        //    if ($type instanceof String) {
                        //        $currentObject = utf8_encode($currentObject);
                        //    } 

                        //    $currentObject = urlencode($currentObject);
                        //}

                        // call IType::convertToOData to attach reuqired suffix 
                        // and prepfix.
                        // e.g. $valueM, $valueF, datetime'$value', guid'$value', 
                        // '$value' etc..
                        // Also we can think about moving above urlencode to this 
                        // function
                        $value = $type->convertToOData($currentObject);
                        $nextPageLink .= $value . ', ';
                    }
                } catch (\ReflectionException $reflectionException) {
                    throw ODataException::createInternalServerError(
                        Messages::internalSkipTokenInfoFailedToAccessOrInitializeProperty(
                            $subPathSegment->getName()
                        )
                    );
                }

                $index++;
            }
        }

        return rtrim($nextPageLink, ", ");
    }
}