<?php
/** 
 * Base class for object model serializer.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_ObjectModel
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
namespace ODataProducer\ObjectModel;
use ODataProducer\Common\ODataConstants;
use ODataProducer\DataService;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Providers\Metadata\ResourceSetWrapper;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\Type\IType;
use ODataProducer\UriProcessor\RequestDescription;
use ODataProducer\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use ODataProducer\Common\InvalidOperationException;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\Messages;
/**
 * Base class for object model serializer.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_ObjectModel
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ObjectModelSerializerBase
{
    /**
     * Holds refernece to the data service instance.
     * 
     * @var DataService
     */
    protected $dataService;

    /**
     * Request description instance describes OData request the
     * the client has submitted and result of the request.
     * 
     * @var RequestDescription
     */
    protected $requestDescription;

    /**
     * Collection of segment names
     * Remark: Read 'ObjectModelSerializerNotes.txt' for more
     * details about segment.
     * 
     * @var array(string)
     */
    private $_segmentNames;

    /**
     * Collection of segment ResourceSetWrapper instances
     * Remark: Read 'ObjectModelSerializerNotes.txt' for more
     * details about segment.
     * 
     * @var array(ResourceSetWrapper)
     */
    private $_segmentResourceSetWrappers;

    /**
     * Result counts for segments
     * Remark: Read 'ObjectModelSerializerNotes.txt' for more
     * details about segment.
     * 
     * @var array(int)
     */
    private $_segmentResultCounts;

    /**
     * Collection of complex type instances used for cycle detection.
     * 
     * @var array(mixed)
     */
    protected $complexTypeInstanceCollection;

    /**
     * Absolute service Uri.
     * 
     * @var string
     */
    protected $absoluteServiceUri;

    /**
     * Absolute service Uri with slash.
     * 
     * @var string
     */
    protected $absoluteServiceUriWithSlash;

    /**
     * Constructs a new instance of ObjectModelSerializerBase.
     * 
     * @param DataService        &$dataService        Reference to the 
     *                                                data service instance.
     * @param RequestDescription &$requestDescription Type instance describing 
     *                                                the client submitted
     *                                                request.
     */
    protected function __construct(DataService &$dataService, RequestDescription &$requestDescription)
    {
        $this->dataService = $dataService;
        $this->requestDescription = $requestDescription;
        $this->absoluteServiceUri = $dataService->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash = rtrim($this->absoluteServiceUri, '/') . '/';
        $this->_segmentNames = array();
        $this->_segmentResourceSetWrappers = array();
        $this->_segmentResultCounts = array();
        $this->complexTypeInstanceCollection = array();
    }

    /**
     * Builds the key for the given entity instance.
     * Note: The generated key can be directly used in the uri, 
     * this function will perform
     * required escaping of characters, for example:
     * Ships(ShipName='Antonio%20Moreno%20Taquer%C3%ADa',ShipID=123),
     * Note to method caller: Don't do urlencoding on 
     * return value of this method as it already encoded.
     * 
     * @param mixed        &$entityInstance Entity instance for which 
     *                                      key value needs to be prepared.
     * @param ResourceType &$resourceType   Resource type instance containing 
     *                                      metadata about the instance.
     * @param string       $containerName   Name of the entity set that 
     *                                      the entity instance belongs to.
     * 
     * @return string      Key for the given resource, with values 
     * encoded for use in a URI.
     */
    protected function getEntryInstanceKey(&$entityInstance, ResourceType &$resourceType, $containerName)
    {
        $keyProperties = $resourceType->getKeyProperties();
        $this->assert(count($keyProperties) != 0, 'count($keyProperties) != 0');
        $keyString = $containerName . '(';
        $comma = null;
        foreach ($keyProperties as $keyName => $resourceProperty) {
            $keyType = $resourceProperty->getInstanceType();            
            $this->assert(
                array_search('ODataProducer\Providers\Metadata\Type\IType', class_implements($keyType)) !== false, 
                'array_search(\'ODataProducer\Providers\Metadata\Type\IType\', class_implements($keyType)) !== false'
            );

            $keyValue = $this->getPropertyValue($entityInstance, $resourceType, $resourceProperty);
            if (is_null($keyValue)) {
                ODataException::createInternalServerError(Messages::badQueryNullKeysAreNotSupported($resourceType->getName(), $keyName));
            }
            
            $keyValue = $keyType->convertToOData($keyValue);
            $keyString .= $comma . $keyName.'='.$keyValue;
            $comma = ',';
        }

        $keyString .= ')';
        return $keyString;
    }

    /**
     * Get the value of a given property from an instance.
     * 
     * @param mixed            &$object           Instance of a type which 
     *                                            contains this property. 
     * @param ResourceType     &$resourceType     Resource type instance 
     *                                            containing metadata about 
     *                                            the instance.
     * @param ResourceProperty &$resourceProperty Resource property instance 
     *                                            containing metadata about the 
     *                                            property whose value 
     *                                            to be retrieved.
     * 
     * @return mixed The value of the given property.
     * 
     * @throws ODataException If reflection exception occured 
     * while trying to access the property.
     */
    protected function getPropertyValue(&$object, ResourceType &$resourceType, ResourceProperty &$resourceProperty)
    {
        try {
                $reflectionProperty = new \ReflectionProperty($object, $resourceProperty->getName());
                $propertyValue = $reflectionProperty->getValue($object);
                return $propertyValue;                   
        } catch (\ReflectionException $reflectionException) {
            throw ODataException::createInternalServerError(
                Messages::objectModelSerializerFailedToAccessProperty(
                    $resourceProperty->getName(), 
                    $resourceType->getName()
                )
            );                
        }
    }

    /**
     * Resource set wrapper for the resource being serialized.
     * 
     * @return ResourceSetWrapper
     */
    protected function getCurrentResourceSetWrapper()
    {
        $count = count($this->_segmentResourceSetWrappers);
        if ($count == 0) {
            return $this->requestDescription->getTargetResourceSetWrapper();
        } else {
            return $this->_segmentResourceSetWrappers[$count - 1];
        }
    }

    /**
     * Whether the current resource set is root resource set.
     * 
     * @return boolean true if the current resource set root container else
     *                 false.
     */
    protected function isRootResourceSet()
    {
        return empty($this->_segmentResourceSetWrappers) 
                || count($this->_segmentResourceSetWrappers) == 1;
    }

    /**
     * Returns the etag for the given resource.
     * 
     * @param mixed        &$entryObject  Resource for which etag value 
     *                                    needs to be returned
     * @param ResourceType &$resourceType Resource type of the $entryObject
     * 
     * @return string/NULL ETag value for the given resource 
     * (with values encoded for use in a URI)
     * if there are etag properties, NULL if there is no etag property.
     */
    protected function getETagForEntry(&$entryObject, ResourceType &$resourceType)
    {
        $eTag = null;
        $comma = null;
        foreach ($resourceType->getETagProperties() as $eTagProperty) {
            $type = $eTagProperty->getInstanceType();
            $this->assert(
                !is_null($type) 
                && array_search('ODataProducer\Providers\Metadata\Type\IType', class_implements($type)) !== false,
                '!is_null($type) 
                && array_search(\'ODataProducer\Providers\Metadata\Type\IType\', class_implements($type)) !== false'
            );
            $value = $this->getPropertyValue($entryObject, $resourceType, $eTagProperty);
            if (is_null($value)) {
                $eTag = $eTag . $comma. 'null';
            } else {
                $eTag = $eTag . $comma . $type->convertToOData($value);
            }

            $comma = ',';
        }

        if (!is_null($eTag)) {
            // If eTag is made up of datetime or string properties then the above
            // IType::converToOData will perform utf8 and url encode. But we don't
            // want this for eTag value.
            $eTag = urldecode(utf8_decode($eTag));
            return ODataConstants::HTTP_WEAK_ETAG_PREFIX . rtrim($eTag, ',') . '"';
        }

        return null;
    }

    /**
     * Pushes a segment for the root of the tree being written out
     * Note: Refer 'ObjectModelSerializerNotes.txt' for more details about
     * 'Segment Stack' and this method.
     * Note: Calls to this method should be balanced with calls to popSegment.
     * 
     * @return true if the segment was pushed, false otherwise.
     */
    protected function pushSegmentForRoot()
    {
        $segmentName = $this->requestDescription->getContainerName();
        $segmentResourceSetWrapper = $this->requestDescription->getTargetResourceSetWrapper();
        return $this->_pushSegment($segmentName, $segmentResourceSetWrapper);
    }

    /**
     * Pushes a segment for the current navigation property being written out.
     * Note: Refer 'ObjectModelSerializerNotes.txt' for more details about
     * 'Segment Stack' and this method.
     * Note: Calls to this method should be balanced with calls to popSegment.
     * 
     * @param ResourceProperty &$resourceProperty The current navigation property
     * being written out.
     * 
     * @return true if a segment was pushed, false otherwise
     * 
     * @throws InvalidOperationException If this function invoked with non-navigation
     *                                   property instance.
     */
    protected function pushSegmentForNavigationProperty(ResourceProperty &$resourceProperty)
    {
        if ($resourceProperty->getTypeKind() == ResourceTypeKind::ENTITY) {
            $this->assert(!empty($this->_segmentNames), '!is_empty($this->_segmentNames');
            $currentResourceSetWrapper = $this->getCurrentResourceSetWrapper();
            $currentResourceType = $currentResourceSetWrapper->getResourceType();
            $currentResourceSetWrapper = $this->dataService
                ->getMetadataQueryProviderWrapper()
                ->getResourceSetWrapperForNavigationProperty(
                    $currentResourceSetWrapper, 
                    $currentResourceType, 
                    $resourceProperty
                );

            $this->assert(!is_null($currentResourceSetWrapper), '!null($currentResourceSetWrapper)');
            return $this->_pushSegment($resourceProperty->getName(), $currentResourceSetWrapper);
        } else {
            throw new InvalidOperationException('pushSegmentForNavigationProperty should not be called with non-entity type');
        }
    }

    /**
     * Gets collection of projection nodes under the current node.
     * 
     * @return array(ProjectionNode/ExpandedProjectionNode)/NULL List of nodes 
     * describing projections for the current segment, If this method returns 
     * null it means no projections are to be applied and the entire resource
     * for the current segment should be serialized, If it returns non-null 
     * only the properties described by the returned projection segments should 
     * be serialized.
     */
    protected function getProjectionNodes()
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        if (is_null($expandedProjectionNode) 
            || $expandedProjectionNode->canSelectAllProperties()
        ) {
            return null;
        }

        return $expandedProjectionNode->getChildNodes();
    }

    /**
     * Find a 'ExpandedProjectionNode' instance in the projection tree 
     * which describes the current segment.
     * 
     * @return ExpandedProjectionNode/NULL
     */
    protected function getCurrentExpandedProjectionNode()
    {
        $expandedProjectionNode = $this->requestDescription->getRootProjectionNode();
        if (is_null($expandedProjectionNode)) {
            return null;
        } else {
            $depth = count($this->_segmentNames);
            // $depth == 1 means serialization of root entry 
            //(the resource identified by resource path) is going on, 
            //so control won't get into the below for loop. 
            //we will directly return the root node, 
            //which is 'ExpandedProjectionNode'
            // for resource identified by resource path.
            if ($depth != 0) {
                for ($i = 1; $i < $depth; $i++) {
                    $expandedProjectionNode 
                        = $expandedProjectionNode->findNode($this->_segmentNames[$i]);
                        $this->assert(
                            !is_null($expandedProjectionNode), 
                            '!is_null($expandedProjectionNode)'
                        );
                        $this->assert(
                            $expandedProjectionNode instanceof ExpandedProjectionNode, 
                            '$expandedProjectionNode instanceof ExpandedProjectionNode'
                        );
                }
            } 
        }

        return $expandedProjectionNode;
    }

    /**
     * Check whether to expand a navigation property or not.
     * 
     * @param string $navigationPropertyName Name of naviagtion property in question.
     * 
     * @return boolean True if the given navigation should be 
     * explanded otherwise false.
     */
    protected function shouldExpandSegment($navigationPropertyName)
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        if (is_null($expandedProjectionNode)) {
            return false;
        }

        $expandedProjectionNode = $expandedProjectionNode->findNode($navigationPropertyName);
        return !is_null($expandedProjectionNode) && ($expandedProjectionNode instanceof ExpandedProjectionNode);
    }

    /**
     * Pushes information about the segment that is going to be serialized 
     * to the 'Segment Stack'.
     * Note: Refer 'ObjectModelSerializerNotes.txt' for more details about
     * 'Segment Stack' and this method.
     * Note: Calls to this method should be balanced with calls to popSegment.
     * 
     * @param string             $segmentName         Name of segment to push.
     * @param ResourceSetWrapper &$resourceSetWrapper The resource set 
     *                                                wrapper to push.
     * 
     * @return true if the segment was push, false otherwise
     */
    private function _pushSegment($segmentName, ResourceSetWrapper &$resourceSetWrapper)
    {
        $rootProjectionNode = $this->requestDescription->getRootProjectionNode();
        // Even though there is no expand in the request URI, still we need to push
        // the segment information if we need to count 
        //the number of entities written.
        // After serializing each entity we should check the count to see whether  
        // we serialized more entities than configured 
        //(page size, maxResultPerCollection).
        // But we will not do this check since library is doing paging and never 
        // accumulate entities more than configured.
        //
        // if ((!is_null($rootProjectionNode) && $rootProjectionNode->isExpansionSpecified()) 
        //    || ($resourceSetWrapper->getResourceSetPageSize() != 0)
        //    || ($this->dataService->getServiceConfiguration()->getMaxResultsPerCollection() != PHP_INT_MAX)            
        //) {}

        if (!is_null($rootProjectionNode) 
            && $rootProjectionNode->isExpansionSpecified()
        ) {
            array_push($this->_segmentNames, $segmentName);
            array_push($this->_segmentResourceSetWrappers, $resourceSetWrapper);
            array_push($this->_segmentResultCounts, 0);
            return true;
        }

        return false;
    }

    /**
     * Get next page link from the given entity instance.
     * 
     * @param mixed  &$lastObject Last object serialized to be 
     *                            used for generating $skiptoken.
     * @param string $absoluteUri Absolute response URI.
     * 
     * @return URI for the link for next page.
     */
    protected function getNextLinkUri(&$lastObject, $absoluteUri)
    {
        $currentExpandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        $internalOrderByInfo = $currentExpandedProjectionNode->getInternalOrderByInfo();
        $skipToken = $internalOrderByInfo->buildSkipTokenValue($lastObject);
        $this->assert(!is_null($skipToken), '!is_null($skipToken)');
        $queryParameterString = null;
        if ($this->isRootResourceSet()) {
            $queryParameterString = $this->getNextPageLinkQueryParametersForRootResourceSet();
        } else {
            $queryParameterString = $this->getNextPageLinkQueryParametersForExpandedResourceSet();
        }

        $queryParameterString .= '$skiptoken=' . $skipToken;        
        $odalaLink = new ODataLink();
        $odalaLink->name = ODataConstants::ATOM_LINK_NEXT_ATTRIBUTE_STRING;
        $odalaLink->url = rtrim($absoluteUri, '/') . '?' . $queryParameterString;
        return $odalaLink;
    }

    /**
     * Builds the string corresponding to query parameters for top level results 
     * (result set identified by the resource path) to be put in next page link.
     * 
     * @return string/NULL string representing the query parameters in the URI 
     *                     query parameter format, NULL if there 
     *                     is no query parameters
     *                     required for the next link of top level result set.
     */
    protected function getNextPageLinkQueryParametersForRootResourceSet()
    {
        $queryParameterString = null;
        foreach (array(ODataConstants::HTTPQUERY_STRING_FILTER, 
            ODataConstants::HTTPQUERY_STRING_EXPAND, 
            ODataConstants::HTTPQUERY_STRING_ORDERBY, 
            ODataConstants::HTTPQUERY_STRING_INLINECOUNT, 
            ODataConstants::HTTPQUERY_STRING_SELECT) as $queryOption
        ) {
            $value = $this->dataService->getHost()->getQueryStringItem($queryOption);
            if (!is_null($value)) {
                if (!is_null($queryParameterString)) {
                    $queryParameterString = $queryParameterString . '&';
                }

                $queryParameterString .= $queryOption . '=' . $value;
            }            
        }

        $topCountValue = $this->requestDescription->getTopOptionCount();
        if (!is_null($topCountValue)) {
            $remainingCount  = $topCountValue - $this->requestDescription->getTopCount();
            if (!is_null($queryParameterString)) {
                $queryParameterString .= '&';
            }

            $queryParameterString .= ODataConstants::HTTPQUERY_STRING_TOP . '=' . $remainingCount;
        }

        if (!is_null($queryParameterString)) {
            $queryParameterString .= '&';
        }

        return $queryParameterString;
    }

    /**
     * Builds the string corresponding to query parameters for current expanded
     * results to be put in next page link.
     * 
     * @return string/NULL string representing the $select and $expand parameters 
     *                     in the URI query parameter format, NULL if there is no 
     *                     query parameters ($expand and/select) required for the 
     *                     next link of expanded result set.
     */
    protected function getNextPageLinkQueryParametersForExpandedResourceSet()
    {
        $queryParameterString = null;
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        if (!is_null($expandedProjectionNode)) {
            $pathSegments = array();
            $selectionPaths = null;
            $expansionPaths = null;            
            $foundSelections = false;
            $foundExpansions = false;
            $this->_buildSelectionAndExpansionPathsForNode(
                $pathSegments, 
                $selectionPaths, 
                $expansionPaths, 
                $expandedProjectionNode, 
                $foundSelections, 
                $foundExpansions
            );

            if ($foundSelections && $expandedProjectionNode->canSelectAllProperties()) {
                $this->_appendSelectionOrExpandPath($selectionPaths, $pathSegments, '*');
            }

            if (!is_null($selectionPaths)) {
                $queryParameterString = '$select=' . $selectionPaths;
            }

            if (!is_null($expansionPaths)) {
                if (!is_null($queryParameterString)) {
                    $queryParameterString .= '&';
                }

                $queryParameterString = '$expand=' . $expansionPaths;
            }

            if (!is_null($queryParameterString)) {
                    $queryParameterString .= '&';
            }
        }

        return $queryParameterString;
    }

    /**
     * Wheter next link is needed for the current resource set (feed) 
     * being serialized.
     * 
     * @param int $resultSetCount Number of entries in the current 
     *                            resource set.
     * 
     * @return boolean true if the feed must have a next page link
     */
    protected function needNextPageLink($resultSetCount)
    {
        $currentResourceSet = $this->getCurrentResourceSetWrapper();
        $recursionLevel = count($this->_segmentNames);
        //$this->assert($recursionLevel != 0, '$recursionLevel != 0');
        $pageSize = $currentResourceSet->getResourceSetPageSize();       

        if ($recursionLevel == 1) {
            //presence of $top option affect next link for root container
            $topValueCount = $this->requestDescription->getTopOptionCount();
            if (!is_null($topValueCount) && ($topValueCount <= $pageSize)) {
                 return false;
            }            
        }

        return $resultSetCount == $pageSize;
    }

    /**
     * Pops segment information from the 'Segment Stack'
     * Note: Refer 'ObjectModelSerializerNotes.txt' for more details about
     * 'Segment Stack' and this method.
     * Note: Calls to this method should be balanced with previous 
     * calls to _pushSegment.
     * 
     * @param boolean $needPop Is a pop required. Only true if last 
     *                         push was successful.
     * 
     * @return void
     * 
     * @throws InvalidOperationException If found un-balanced call with _pushSegment
     */
    protected function popSegment($needPop)
    {
        if ($needPop) {
            if (!empty($this->_segmentNames)) {
                array_pop($this->_segmentNames);
                array_pop($this->_segmentResourceSetWrappers);
                array_pop($this->_segmentResultCounts);
            } else {
                throw new InvalidOperationException('Found non-balanced call to _pushSegment and popSegment');
            }
        }
    }

    /**
     * Recursive metod to build $expand and $select paths for a specified node.
     * 
     * @param array(string)          &$parentPathSegments     Array of path 
     *                                                        segments which leads
     *                                                        up to (including) 
     *                                                        the segment 
     *                                                        represented by 
     *                                                        $expandedProjectionNode.
     * @param array(string)          &$selectionPaths         The string which 
     *                                                        holds projection
     *                                                        path segment 
     *                                                        seperated by comma,
     *                                                        On return this argument
     *                                                        will be updated with 
     *                                                        the selection path
     *                                                        segments under 
     *                                                        this node. 
     * @param array(string)          &$expansionPaths         The string which holds
     *                                                        expansion path segment
     *                                                        seperated by comma.
     *                                                        On return this argument
     *                                                        will be updated with 
     *                                                        the expand path
     *                                                        segments under 
     *                                                        this node. 
     * @param ExpandedProjectionNode &$expandedProjectionNode The expanded node for 
     *                                                        which expansion 
     *                                                        and selection path 
     *                                                        to be build.
     * @param boolean                &$foundSelections        On return, this 
     *                                                        argument will hold
     *                                                        true if any selection
     *                                                        defined under this node
     *                                                        false otherwise.
     * @param boolean                &$foundExpansions        On return, this 
     *                                                        argument will hold 
     *                                                        true if any expansion
     *                                                        defined under this node
     *                                                        false otherwise.
     *
     * @return void
     */
    private function _buildSelectionAndExpansionPathsForNode(&$parentPathSegments, 
        &$selectionPaths, &$expansionPaths, 
        ExpandedProjectionNode &$expandedProjectionNode, 
        &$foundSelections, &$foundExpansions
    ) {
        $foundSelections = false;
        $foundExpansions = false;
        $foundSelectionOnChild = false;
        $foundExpansionOnChild = false;
        $expandedChildrenNeededToBeSelected = array();
        foreach ($expandedProjectionNode->getChildNodes() as $childNode) {
            if (!($childNode instanceof ExpandedProjectionNode)) {
                $foundSelections = true;
                $this->_appendSelectionOrExpandPath(
                    $selectionPaths, 
                    $parentPathSegments, 
                    $childNode->getPropertyName()
                );
            } else {
                $foundExpansions = true;
                array_push($parentPathSegments, $childNode->getPropertyName());
                $this->_buildSelectionAndExpansionPathsForNode(
                    $parentPathSegments, 
                    $selectionPaths, $expansionPaths, 
                    $childNode, $foundSelectionOnChild, 
                    $foundExpansionOnChild
                );
                array_pop($parentPathSegments);
                if ($childNode->canSelectAllProperties()) {
                    if ($foundSelectionOnChild) {
                        $this->_appendSelectionOrExpandPath(
                            $selectionPaths, 
                            $parentPathSegments, 
                            $childNode->getPropertyName() . '/*'
                        );
                    } else {
                        $expandedChildrenNeededToBeSelected[] = $childNode;
                    }
                }
            }

            $foundSelections |= $foundSelectionOnChild;
            if (!$foundExpansionOnChild) {
                $this->_appendSelectionOrExpandPath(
                    $expansionPaths, 
                    $parentPathSegments, 
                    $childNode->getPropertyName()
                );
            }
        }

        if (!$expandedProjectionNode->canSelectAllProperties() || $foundSelections) {
            foreach ($expandedChildrenNeededToBeSelected as $childToProject) {
                $this->_appendSelectionOrExpandPath(
                    $selectionPaths, 
                    $parentPathSegments, 
                    $childNode->getPropertyName()
                );
                $foundSelections = true;
            }
        }
    }

    /**
     * Append the given path to $expand or $select path list.
     * 
     * @param string        &$path               The $expand or $select path list
     *                                           to which to append the given path.
     * @param array(string) &$parentPathSegments The list of path upto the 
     *                                           $segmentToAppend.
     * @param string        $segmentToAppend     The last segment of the path.
     * 
     * @return void
     */
    private function _appendSelectionOrExpandPath(&$path, &$parentPathSegments, $segmentToAppend)
    {
        if (!is_null($path)) {
            $path .= ', ';
        }

        foreach ($parentPathSegments as $parentPathSegment) {
            $path .= $parentPathSegment . '/';
        }

        $path .= $segmentToAppend;
    }

    /**
     * Assert that the given condition is true.
     * 
     * @param boolean $condition         Condition to be asserted.
     * @param string  $conditionAsString String containing message incase
     *                                   if assertion fails.
     * 
     * @throws InvalidOperationException Incase if assertion failes.
     * 
     * @return void
     */
    protected function assert($condition, $conditionAsString)
    {
        if (!$condition) {
            throw new InvalidOperationException("Unexpected state, expecting $conditionAsString");
        }
    }
}
?>