<?php

namespace POData\ObjectModel;

use POData\Common\ODataConstants;
use POData\IService;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Common\Messages;
use POData\UriProcessor\SegmentStack;

/**
 * Class ObjectModelSerializerBase.
 */
class ObjectModelSerializerBase
{
    /**
     * The service implementation.
     *
     * @var IService
     */
    protected $service;

    /**
     * Request description instance describes OData request the
     * the client has submitted and result of the request.
     *
     * @var RequestDescription
     */
    protected $request;

    /**
     * Collection of complex type instances used for cycle detection.
     *
     * @var array
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
     * Holds reference to segment stack being processed
     *
     * @var SegmentStack
     */
    protected $stack;

    /**
     * @param IService           $service Reference to the data service instance
     * @param RequestDescription $request Type instance describing the client submitted request
     */
    protected function __construct(IService $service, RequestDescription $request)
    {
        $this->service = $service;
        $this->request = $request;
        $this->absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash = rtrim($this->absoluteServiceUri, '/') . '/';
        $this->stack = new SegmentStack($request);
        $this->complexTypeInstanceCollection = array();
    }

    /**
     * Gets reference to the request submitted by client.
     *
     * @return RequestDescription
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Gets the data service instance
     *
     * @return IService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Gets the segment stack instance
     *
     * @return SegmentStack
     */
    public function getStack()
    {
        return $this->stack;
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
     * @param mixed        $entityInstance Entity instance for which key value needs to be prepared
     * @param ResourceType $resourceType   Resource type instance containing metadata about the instance
     * @param string       $containerName  Name of the entity set that the entity instance belongs to
     *
     * @return string Key for the given resource, with values encoded for use in a URI
     */
    protected function getEntryInstanceKey($entityInstance, ResourceType $resourceType, $containerName)
    {
        $keyProperties = $resourceType->getKeyProperties();
        assert(count($keyProperties) != 0, 'count($keyProperties) == 0');
        $keyString = $containerName . '(';
        $comma = null;
        foreach ($keyProperties as $keyName => $resourceProperty) {
            $keyType = $resourceProperty->getInstanceType();
            assert($keyType instanceof IType, '$keyType not instanceof IType');
            $keyValue = $this->getPropertyValue($entityInstance, $resourceType, $resourceProperty);
            if (is_null($keyValue)) {
                throw ODataException::createInternalServerError(
                    Messages::badQueryNullKeysAreNotSupported($resourceType->getName(), $keyName)
                );
            }

            $keyValue = $keyType->convertToOData($keyValue);
            $keyString .= $comma . $keyName . '=' . $keyValue;
            $comma = ',';
        }

        $keyString .= ')';

        return $keyString;
    }

    /**
     * Get the value of a given property from an instance.
     *
     * @param mixed            $entity           Instance of a type which contains this property
     * @param ResourceType     $resourceType     Resource type instance containing metadata about the instance
     * @param ResourceProperty $resourceProperty Resource property instance containing metadata about the property whose value to be retrieved
     *
     * @return mixed The value of the given property
     *
     * @throws ODataException If reflection exception occurred while trying to access the property
     */
    protected function getPropertyValue($entity, ResourceType $resourceType, ResourceProperty $resourceProperty)
    {
        try {
            //Is this slow?  See #88
                // If a magic method for properties exists (eg Eloquent), dive into it directly and return value
            if (method_exists($entity, '__get')) {
                $targProperty = $resourceProperty->getName();

                return $entity->$targProperty;
            }
            $reflectionClass = new \ReflectionClass(get_class($entity));
            $reflectionProperty = $reflectionClass->getProperty($resourceProperty->getName());
            $reflectionProperty->setAccessible(true);

            return $reflectionProperty->getValue($entity);
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
        $segmentWrappers = $this->getStack()->getSegmentWrappers();
        $count = count($segmentWrappers);
        return 0 == $count ? $this->getRequest()->getTargetResourceSetWrapper() : $segmentWrappers[$count - 1];
    }

    /**
     * Whether the current resource set is root resource set.
     *
     * @return bool true if the current resource set root container else
     *              false
     */
    protected function isRootResourceSet()
    {
        $segmentWrappers = $this->getStack()->getSegmentWrappers();
        return empty($segmentWrappers) || 1 == count($segmentWrappers);
    }

    /**
     * Returns the etag for the given resource.
     *
     * @param mixed        $entryObject  Resource for which etag value
     *                                   needs to be returned
     * @param ResourceType $resourceType Resource type of the $entryObject
     *
     * @return string|null ETag value for the given resource
     *                     (with values encoded for use in a URI)
     *                     if there are etag properties, NULL if there is no etag property
     */
    protected function getETagForEntry($entryObject, ResourceType $resourceType)
    {
        $eTag = null;
        $comma = null;
        foreach ($resourceType->getETagProperties() as $eTagProperty) {
            $type = $eTagProperty->getInstanceType();
            assert(!is_null($type) && $type instanceof IType, 'is_null($type) || $type not instanceof IType');
            $value = $this->getPropertyValue($entryObject, $resourceType, $eTagProperty);
            if (is_null($value)) {
                $eTag = $eTag . $comma . 'null';
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
     * @return bool true if the segment was pushed, false otherwise
     */
    protected function pushSegmentForRoot()
    {
        $segmentName = $this->getRequest()->getContainerName();
        $segmentResourceSetWrapper = $this->getRequest()->getTargetResourceSetWrapper();
        assert(null != $segmentResourceSetWrapper, "Segment resource set wrapper must not be null");

        return $this->_pushSegment($segmentName, $segmentResourceSetWrapper);
    }

    /**
     * Pushes a segment for the current navigation property being written out.
     * Note: Refer 'ObjectModelSerializerNotes.txt' for more details about
     * 'Segment Stack' and this method.
     * Note: Calls to this method should be balanced with calls to popSegment.
     *
     * @param ResourceProperty &$resourceProperty The current navigation property
     *                                            being written out
     *
     * @return bool true if a segment was pushed, false otherwise
     *
     * @throws InvalidOperationException If this function invoked with non-navigation
     *                                   property instance
     */
    protected function pushSegmentForNavigationProperty(ResourceProperty & $resourceProperty)
    {
        if (ResourceTypeKind::ENTITY == $resourceProperty->getTypeKind()) {
            assert(!empty($this->getStack()->getSegmentNames()), 'Segment names should not be empty');
            $currentResourceSetWrapper = $this->getCurrentResourceSetWrapper();
            $currentResourceType = $currentResourceSetWrapper->getResourceType();
            $currentResourceSetWrapper = $this->getService()
                ->getProvidersWrapper()
                ->getResourceSetWrapperForNavigationProperty(
                    $currentResourceSetWrapper,
                    $currentResourceType,
                    $resourceProperty
                );

            assert(!is_null($currentResourceSetWrapper), 'is_null($currentResourceSetWrapper)');

            return $this->_pushSegment($resourceProperty->getName(), $currentResourceSetWrapper);
        }
        throw new InvalidOperationException('pushSegmentForNavigationProperty should not be called with non-entity type');
    }

    /**
     * Gets collection of projection nodes under the current node.
     *
     * @return ProjectionNode[]|ExpandedProjectionNode[]|null List of nodes
     *                                                        describing projections for the current segment, If this method returns
     *                                                        null it means no projections are to be applied and the entire resource
     *                                                        for the current segment should be serialized, If it returns non-null
     *                                                        only the properties described by the returned projection segments should
     *                                                        be serialized
     */
    protected function getProjectionNodes()
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        if (is_null($expandedProjectionNode) || $expandedProjectionNode->canSelectAllProperties()) {
            return null;
        }

        return $expandedProjectionNode->getChildNodes();
    }

    /**
     * Find a 'ExpandedProjectionNode' instance in the projection tree
     * which describes the current segment.
     *
     * @return ExpandedProjectionNode|null
     */
    protected function getCurrentExpandedProjectionNode()
    {
        $expandedProjectionNode = $this->getRequest()->getRootProjectionNode();
        if (is_null($expandedProjectionNode)) {
            return null;
        } else {
            $segmentNames = $this->getStack()->getSegmentNames();
            $depth = count($segmentNames);
            // $depth == 1 means serialization of root entry
            //(the resource identified by resource path) is going on,
            //so control won't get into the below for loop.
            //we will directly return the root node,
            //which is 'ExpandedProjectionNode'
            // for resource identified by resource path.
            if ($depth != 0) {
                for ($i = 1; $i < $depth; ++$i) {
                    $expandedProjectionNode
                        = $expandedProjectionNode->findNode($segmentNames[$i]);
                    assert(!is_null($expandedProjectionNode), 'is_null($expandedProjectionNode)');
                    assert(
                        $expandedProjectionNode instanceof ExpandedProjectionNode,
                        '$expandedProjectionNode not instanceof ExpandedProjectionNode'
                    );
                }
            }
        }

        return $expandedProjectionNode;
    }

    /**
     * Check whether to expand a navigation property or not.
     *
     * @param string $navigationPropertyName Name of naviagtion property in question
     *
     * @return bool True if the given navigation should be
     *              explanded otherwise false
     */
    protected function shouldExpandSegment($navigationPropertyName)
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        if (is_null($expandedProjectionNode)) {
            return false;
        }

        $expandedProjectionNode = $expandedProjectionNode->findNode($navigationPropertyName);

        // null is a valid input to an instanceof call as of PHP 5.6 - will always return false
        return $expandedProjectionNode instanceof ExpandedProjectionNode;
    }

    /**
     * Pushes information about the segment that is going to be serialized
     * to the 'Segment Stack'.
     * Note: Refer 'ObjectModelSerializerNotes.txt' for more details about
     * 'Segment Stack' and this method.
     * Note: Calls to this method should be balanced with calls to popSegment.
     *
     * @param string             $segmentName         Name of segment to push
     * @param ResourceSetWrapper &$resourceSetWrapper The resource set
     *                                                wrapper to push
     *
     * @return bool true if the segment was push, false otherwise
     */
    private function _pushSegment($segmentName, ResourceSetWrapper & $resourceSetWrapper)
    {
        // Even though there is no expand in the request URI, still we need to push
        // the segment information if we need to count
        //the number of entities written.
        // After serializing each entity we should check the count to see whether
        // we serialized more entities than configured
        //(page size, maxResultPerCollection).
        // But we will not do this check since library is doing paging and never
        // accumulate entities more than configured.

        return $this->getStack()->pushSegment($segmentName, $resourceSetWrapper);
    }

    /**
     * Get next page link from the given entity instance.
     *
     * @param mixed  &$lastObject Last object serialized to be
     *                            used for generating $skiptoken
     * @param string $absoluteUri Absolute response URI
     *
     * @return ODataLink for the link for next page
     */
    protected function getNextLinkUri(&$lastObject, $absoluteUri)
    {
        $currentExpandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        $internalOrderByInfo = $currentExpandedProjectionNode->getInternalOrderByInfo();
        $skipToken = $internalOrderByInfo->buildSkipTokenValue($lastObject);
        assert(!is_null($skipToken), '!is_null($skipToken)');
        $queryParameterString = null;
        if ($this->isRootResourceSet()) {
            $queryParameterString = $this->getNextPageLinkQueryParametersForRootResourceSet();
        } else {
            $queryParameterString = $this->getNextPageLinkQueryParametersForExpandedResourceSet();
        }

        $queryParameterString .= '$skip=' . $skipToken;
        $odataLink = new ODataLink();
        $odataLink->name = ODataConstants::ATOM_LINK_NEXT_ATTRIBUTE_STRING;
        $odataLink->url = rtrim($absoluteUri, '/') . '?' . $queryParameterString;

        return $odataLink;
    }

    /**
     * Builds the string corresponding to query parameters for top level results
     * (result set identified by the resource path) to be put in next page link.
     *
     * @return string|null string representing the query parameters in the URI
     *                     query parameter format, NULL if there
     *                     is no query parameters
     *                     required for the next link of top level result set
     */
    protected function getNextPageLinkQueryParametersForRootResourceSet()
    {
        $queryParameterString = null;
        foreach ([ODataConstants::HTTPQUERY_STRING_FILTER,
            ODataConstants::HTTPQUERY_STRING_EXPAND,
            ODataConstants::HTTPQUERY_STRING_ORDERBY,
            ODataConstants::HTTPQUERY_STRING_INLINECOUNT,
            ODataConstants::HTTPQUERY_STRING_SELECT] as $queryOption) {
            $value = $this->getService()->getHost()->getQueryStringItem($queryOption);
            if (!is_null($value)) {
                if (!is_null($queryParameterString)) {
                    $queryParameterString = $queryParameterString . '&';
                }

                $queryParameterString .= $queryOption . '=' . $value;
            }
        }

        $topCountValue = $this->getRequest()->getTopOptionCount();
        if (!is_null($topCountValue)) {
            $remainingCount = $topCountValue - $this->getRequest()->getTopCount();
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
     * @return string|null string representing the $select and $expand parameters
     *                     in the URI query parameter format, NULL if there is no
     *                     query parameters ($expand and/select) required for the
     *                     next link of expanded result set
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
     *                            resource set
     *
     * @return bool true if the feed must have a next page link
     */
    protected function needNextPageLink($resultSetCount)
    {
        $currentResourceSet = $this->getCurrentResourceSetWrapper();
        $recursionLevel = count($this->getStack()->getSegmentNames());
        //$this->assert($recursionLevel != 0, '$recursionLevel != 0');
        $pageSize = $currentResourceSet->getResourceSetPageSize();

        if ($recursionLevel == 1) {
            //presence of $top option affect next link for root container
            $topValueCount = $this->getRequest()->getTopOptionCount();
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
     * @param bool $needPop Is a pop required. Only true if last
     *                      push was successful
     *
     * @throws InvalidOperationException If found un-balanced call with _pushSegment
     */
    protected function popSegment($needPop)
    {
        $this->getStack()->popSegment($needPop);
    }

    /**
     * Recursive metod to build $expand and $select paths for a specified node.
     *
     * @param string[]               &$parentPathSegments     Array of path
     *                                                        segments which leads
     *                                                        up to (including)
     *                                                        the segment
     *                                                        represented by
     *                                                        $expandedProjectionNode
     * @param string[]               &$selectionPaths         The string which
     *                                                        holds projection
     *                                                        path segment
     *                                                        seperated by comma,
     *                                                        On return this argument
     *                                                        will be updated with
     *                                                        the selection path
     *                                                        segments under
     *                                                        this node
     * @param string[]               &$expansionPaths         The string which holds
     *                                                        expansion path segment
     *                                                        seperated by comma.
     *                                                        On return this argument
     *                                                        will be updated with
     *                                                        the expand path
     *                                                        segments under
     *                                                        this node
     * @param ExpandedProjectionNode &$expandedProjectionNode The expanded node for
     *                                                        which expansion
     *                                                        and selection path
     *                                                        to be build
     * @param bool                   &$foundSelections        On return, this
     *                                                        argument will hold
     *                                                        true if any selection
     *                                                        defined under this node
     *                                                        false otherwise
     * @param bool                   &$foundExpansions        On return, this
     *                                                        argument will hold
     *                                                        true if any expansion
     *                                                        defined under this node
     *                                                        false otherwise
     * @param bool                   $foundSelections
     * @param bool                   $foundExpansions
     */
    private function _buildSelectionAndExpansionPathsForNode(
        &$parentPathSegments,
        &$selectionPaths,
        &$expansionPaths,
        ExpandedProjectionNode & $expandedProjectionNode,
        &$foundSelections,
        &$foundExpansions
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
                    $selectionPaths,
                    $expansionPaths,
                    $childNode,
                    $foundSelectionOnChild,
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
     * @param string   &$path               The $expand or $select path list to which to append the given path
     * @param string[] &$parentPathSegments The list of path up to the $segmentToAppend
     * @param string   $segmentToAppend     The last segment of the path
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
}
