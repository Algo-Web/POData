<?php
/**
 * Type which holds information about processed skiptoken value, this type
 * also provide method to search the given result set for the skiptoken
 * and to build skiptoken from an entry object. 
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_SkipTokenParser
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
namespace ODataProducer\UriProcessor\QueryProcessor\SkipTokenParser;
use ODataProducer\Providers\Metadata\Type\Guid;
use ODataProducer\Providers\Metadata\Type\Null1;
use ODataProducer\Providers\Metadata\Type\DateTime;
use ODataProducer\Providers\Metadata\Type\String;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use ODataProducer\Common\Messages;
use ODataProducer\Common\ODataException;
/**
 * Type to hold information about processed skiptoken value.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_SkipTokenParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class InternalSkipTokenInfo
{
    /**
     * Reference to an instance of InternalOrderByInfo which holds 
     * sorter function(s) generated from orderby clause.
     * 
     * @var InternalOrderByInfo
     */
    private $_internalOrderByInfo;

    /**
     * Holds collection of values in the skiptoken corrosponds to the orderby
     * path segments.
     * 
     * @var array(int (array(string, IType))
     */
    private $_orderByValuesInSkipToken;

    /**
     * Holds reference to the type of the resource pointed by the request uri.
     * 
     * @var ResourceType
     */
    private $_resourceType;

    /**
     * Reference to the object holding parsed skiptoken value, this information
     * can be used by the IDSQP implementor for custom paging.
     * 
     * @var SkipTokenInfo
     */
    private $_skipTokenInfo;

    /**
     * Object which is used as a key for searching the sorted result, this object
     * will be an instance of type described by the resource type pointed by the 
     * request uri.
     * 
     * @var mixed
     */
    private $_keyObject;
    

    /**
     * Creates a new instance of InternalSkipTokenInfo
     * 
     * @param InternalOrderByInfo           &$internalOrderByInfo     Reference to an instance of InternalOrderByInfo which holds
     *                                                                sorter function(s) generated from orderby clause.
     * @param array(int(array(string,IType) $orderByValuesInSkipToken Collection of values in the skiptoken corrosponds to the 
     *                                                                orderby path segments.
     * @param ResourceType                  &$resourceType            Reference to the type of the resource pointed by the request uri.
     */
    public function __construct(InternalOrderByInfo &$internalOrderByInfo, 
        $orderByValuesInSkipToken, ResourceType &$resourceType
    ) {
        $this->_internalOrderByInfo = $internalOrderByInfo;        
        $this->_orderByValuesInSkipToken = $orderByValuesInSkipToken;
        $this->_resourceType = $resourceType;
        $this->_skipTokenInfo = null;
        $this->_keyObject = null;
    }

    /**
     * Gets reference to the SkipTokenInfo object holding result of 
     * skiptoken parsing, which used by the IDSQP implementor for 
     * custom paging.
     * 
     * @return SkipTokenInfo
     */
    public function getSkipTokenInfo()
    {
        if (is_null($this->_skipTokenInfo)) {
            $orderbyInfo = $this->_internalOrderByInfo->getOrderByInfo();
            $this->_skipTokenInfo = new SkipTokenInfo(
                $orderbyInfo, 
                $this->_orderByValuesInSkipToken
            );
        }

        return $this->_skipTokenInfo;
    }

    /**
     * Search the sorted array of result set for key object created from the
     * skip token key values and returns index of first entry in the next
     * page.
     * 
     * @param array(mixed) &$searchArray The sorted array to search.
     * 
     * @return int  (1) If the array is empty then return -1, 
     *              (2) If the key object found then return index of first record 
     *                  in the next page, 
     *              (3) If partial matching found (means found matching for first 
     *                  m keys where m < n, where n is total number of positional 
     *                  keys, then return the index of the object which has most 
     *                  matching.
     * 
     * @throws InvalidArgumentException
     */
    public function getIndexOfFirstEntryInTheNextPage(&$searchArray)
    {
        if (!is_array($searchArray)) {
            throw new \InvalidArgumentException(
                Messages::internalSkipTokenInfoBinarySearchRequireArray(
                    'searchArray'
                )
            );
        }

        if (empty($searchArray)) {
            return -1;
        }

        $comparer 
            = $this->_internalOrderByInfo->getSorterFunction()->getReference();
        //Gets the key object initialized from skiptoken
        $keyObject = $this->getKeyObject();
        $low = 0;
        $searcArraySize = count($searchArray) - 1;
        $mid = 0;
        $high = $searcArraySize;
        do {
            $matchLevel = 0;
            $mid = $low + round(($high - $low)/2);
            $result = $comparer($keyObject, $searchArray[$mid]);
            if ($result > 0) {
                $low = $mid + 1;
            } else if ($result < 0) {
                $high = $mid - 1;
            } else {
                //Now we found record the matches with skiptoken value, 
                //so first record of next page will at $mid + 1
                if ($mid == $searcArraySize) {
                    //Check skiptoken points to last record, in this 
                    //case no more records available for next page
                    return -1;
                }

                return $mid + 1;
            }
        } while ($low <= $high);
 
        if ($mid >= $searcArraySize) {
            //If key object does not match with last object, then 
            //no more page
            return -1;
        } else if ($mid <= 0) {
            //If key object is less than first object, then paged 
            //result start from 0
            return 0;
        }
        
        //return index of the most matching object
        return $mid;        
    }

    /**
     * Gets the key object for searching, if the object is not initialized, 
     * then do it from skiptoken positional values.
     * 
     * @return mixed
     * 
     * @throws ODataException If reflection exception occurs while accessing 
     *                        or setting property.
     */
    public function getKeyObject()
    {
        if (is_null($this->_keyObject)) {
            $this->_keyObject = $this->_internalOrderByInfo->getDummyObject();
            $i = 0;
            foreach ($this->_internalOrderByInfo->getOrderByPathSegments() 
                as $orderByPathSegment) {
                $index = 0;
                $currentObject = $this->_keyObject;
                $subPathSegments = $orderByPathSegment->getSubPathSegments();
                $subPathCount = count($subPathSegments);
                foreach ($subPathSegments as &$subPathSegment) {
                    $isLastSegment = ($index == $subPathCount - 1);
                    $dummyProperty = null;
                    try {
                        // if currentObject = null means, previous iteration did a 
                        // ReflectionProperty::getValue where ReflectionProperty 
                        // represents a complex/navigation, but its null, which means
                        // the property is not set in the dummy object by OrderByParser, 
                        // an unexpected state.
                        if (!$isLastSegment) {
                            $dummyProperty = new \ReflectionProperty(
                                $currentObject, 
                                $subPathSegment->getName()
                            );
                            $currentObject 
                                = $dummyProperty->getValue($currentObject);
                        } else {
                            $dummyProperty = new \ReflectionProperty(
                                $currentObject, 
                                $subPathSegment->getName()
                            );
                            if ($this->_orderByValuesInSkipToken[$i][1] instanceof Null1) {
                                $dummyProperty->setValue($currentObject, null);
                            } else {
                                // The Lexer's Token::Text value will be always 
                                // string, convert the string to 
                                // required type i.e. int, float, double etc..
                                $value 
                                    = $this->_orderByValuesInSkipToken[$i][1]->convert(
                                        $this->_orderByValuesInSkipToken[$i][0]
                                    );
                                $dummyProperty->setValue($currentObject, $value);
                            }
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

                $i++;
            }
        }

        return $this->_keyObject;
    }
    
    /**
     * Build nextpage link from the given object which will be the last object
     * in the page.
     * 
     * @param mixed $lastObject Entity instance to build next page link from.
     * 
     * @return string
     * 
     * @throws ODataException If reflection exception occurs while accessing 
     *                        property.
     */
    public function buildNextPageLink($lastObject)
    {
        $nextPageLink = null;
        foreach ($this->_internalOrderByInfo->getOrderByPathSegments() 
        as $orderByPathSegment) {
            $index = 0;
            $currentObject = $lastObject;
            $subPathSegments = $orderByPathSegment->getSubPathSegments();
            $subPathCount = count($subPathSegments);
            foreach ($subPathSegments as &$subPathSegment) {
                $isLastSegment = ($index == $subPathCount - 1);
                try {
                    $dummyProperty = new \ReflectionProperty(
                        $currentObject, 
                        $subPathSegment->getName()
                    );
                    $currentObject = $dummyProperty->getValue($currentObject);
                    if (is_null($currentObject)) {
                            $nextPageLink .= 'null, ';
                            break;
                    } else if ($isLastSegment) {
                        $type = $subPathSegment->getInstanceType();
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
?>