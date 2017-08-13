<?php

namespace POData\ObjectModel;

use Carbon\Carbon;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\Providers\Metadata\ResourceComplexType;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\SegmentStack;

class CynicSerialiser implements IObjectSerialiser
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
     * Holds reference to segment stack being processed.
     *
     * @var SegmentStack
     */
    protected $stack;

    /**
     * Lightweight stack tracking for recursive descent fill.
     */
    private $lightStack = [];

    /*
     * Update time to insert into ODataEntry/ODataFeed fields
     * @var \DateTime;
     */
    private $updated;

    /**
     * @param IService                $service Reference to the data service instance
     * @param RequestDescription|null $request Type instance describing the client submitted request
     */
    public function __construct(IService $service, RequestDescription $request = null)
    {
        $this->service = $service;
        $this->request = $request;
        $this->absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash = rtrim($this->absoluteServiceUri, '/') . '/';
        $this->stack = new SegmentStack($request);
        $this->complexTypeInstanceCollection = [];
        $this->updated = Carbon::now();
    }

    /**
     * Write a top level entry resource.
     *
     * @param QueryResult $entryObject Results property contains reference to the entry object to be written
     *
     * @return ODataEntry|null
     */
    public function writeTopLevelElement(QueryResult $entryObject)
    {
        if (!isset($entryObject->results)) {
            array_pop($this->lightStack);
            return null;
        }

        $this->loadStackIfEmpty();

        $stackCount = count($this->lightStack);
        $topOfStack = $this->lightStack[$stackCount-1];
        $resourceType = $this->getService()->getProvidersWrapper()->resolveResourceType($topOfStack[0]);
        assert($resourceType instanceof ResourceType, get_class($resourceType));
        $rawProp = $resourceType->getAllProperties();
        $relProp = [];
        $nonRelProp = [];
        $last = end($this->lightStack);
        $projNodes = ($last[0] == $last[1]) ? $this->getProjectionNodes() : null;

        foreach ($rawProp as $prop) {
            $propName = $prop->getName();
            if ($prop->getResourceType() instanceof ResourceEntityType) {
                $relProp[$propName] = $prop;
            } else {
                $nonRelProp[$propName] = $prop;
            }
        }
        $rawCount = count($rawProp);
        $relCount = count($relProp);
        $nonRelCount = count($nonRelProp);
        assert(
            $rawCount == $relCount + $nonRelCount,
            'Raw property count '.$rawCount.', does not equal sum of relProp count, '.$relCount
            .', and nonRelPropCount,'.$nonRelCount
        );

        // now mask off against projNodes
        if (null !== $projNodes) {
            $keys = [];
            foreach ($projNodes as $node) {
                $keys[$node->getPropertyName()] = '';
            }

            $relProp = array_intersect_key($relProp, $keys);
            $nonRelProp = array_intersect_key($nonRelProp, $keys);
        }

        $resourceSet = $resourceType->getCustomState();
        assert($resourceSet instanceof ResourceSet);
        $title = $resourceType->getName();
        $type = $resourceType->getFullName();

        $relativeUri = $this->getEntryInstanceKey(
            $entryObject->results,
            $resourceType,
            $resourceSet->getName()
        );
        $absoluteUri = rtrim($this->absoluteServiceUri, '/') . '/' . $relativeUri;

        list($mediaLink, $mediaLinks) = $this->writeMediaData(
            $entryObject->results,
            $type,
            $relativeUri,
            $resourceType
        );

        $propertyContent = $this->writeProperties($entryObject->results, $nonRelProp);

        $links = [];
        foreach ($relProp as $prop) {
            $nuLink = new ODataLink();
            $propKind = $prop->getKind();

            assert(
                ResourcePropertyKind::RESOURCESET_REFERENCE == $propKind
                || ResourcePropertyKind::RESOURCE_REFERENCE == $propKind,
                '$propKind != ResourcePropertyKind::RESOURCESET_REFERENCE &&'
                .' $propKind != ResourcePropertyKind::RESOURCE_REFERENCE'
            );
            $propTail = ResourcePropertyKind::RESOURCE_REFERENCE == $propKind ? 'entry' : 'feed';
            $propType = 'application/atom+xml;type='.$propTail;
            $propName = $prop->getName();
            $nuLink->title = $propName;
            $nuLink->name = ODataConstants::ODATA_RELATED_NAMESPACE . $propName;
            $nuLink->url = $relativeUri . '/' . $propName;
            $nuLink->type = $propType;

            $navProp = new ODataNavigationPropertyInfo($prop, $this->shouldExpandSegment($propName));
            if ($navProp->expanded) {
                $this->expandNavigationProperty($entryObject, $prop, $nuLink, $propKind, $propName);
            }

            $links[] = $nuLink;
        }

        $odata = new ODataEntry();
        $odata->resourceSetName = $resourceSet->getName();
        $odata->id = $absoluteUri;
        $odata->title = new ODataTitle($title);
        $odata->type = $type;
        $odata->propertyContent = $propertyContent;
        $odata->isMediaLinkEntry = true === $resourceType->isMediaLinkEntry() ? true : null;
        $odata->editLink = $relativeUri;
        $odata->mediaLink = $mediaLink;
        $odata->mediaLinks = $mediaLinks;
        $odata->links = $links;
        $odata->updated = $this->getUpdated()->format(DATE_ATOM);

        $newCount = count($this->lightStack);
        assert(
            $newCount == $stackCount,
            'Should have ' . $stackCount . 'elements in stack, have ' . $newCount . 'elements'
        );
        array_pop($this->lightStack);
        return $odata;
    }

    /**
     * Write top level feed element.
     *
     * @param QueryResult &$entryObjects Results property contains array of entry resources to be written
     *
     * @return ODataFeed
     */
    public function writeTopLevelElements(QueryResult &$entryObjects)
    {
        assert(is_array($entryObjects->results), '!is_array($entryObjects->results)');

        $this->loadStackIfEmpty();
        $setName = $this->getRequest()->getTargetResourceSetWrapper()->getName();

        $title = $this->getRequest()->getContainerName();
        $relativeUri = $this->getRequest()->getIdentifier();
        $absoluteUri = $this->getRequest()->getRequestUrl()->getUrlAsString();

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = $title;
        $selfLink->url = $relativeUri;

        $odata = new ODataFeed();
        $odata->title = new ODataTitle($title);
        $odata->id = $absoluteUri;
        $odata->selfLink = $selfLink;
        $odata->updated = $this->getUpdated()->format(DATE_ATOM);

        if ($this->getRequest()->queryType == QueryType::ENTITIES_WITH_COUNT()) {
            $odata->rowCount = $this->getRequest()->getCountValue();
        }
        foreach ($entryObjects->results as $entry) {
            if (!$entry instanceof QueryResult) {
                $query = new QueryResult();
                $query->results = $entry;
            } else {
                $query = $entry;
            }
            $odata->entries[] = $this->writeTopLevelElement($query);
        }

        $resourceSet = $this->getRequest()->getTargetResourceSetWrapper()->getResourceSet();
        $requestTop = $this->getRequest()->getTopOptionCount();
        $pageSize = $this->getService()->getConfiguration()->getEntitySetPageSize($resourceSet);
        $requestTop = (null === $requestTop) ? $pageSize + 1 : $requestTop;

        if (true === $entryObjects->hasMore && $requestTop > $pageSize) {
            $stackSegment = $setName;
            $lastObject = end($entryObjects->results);
            $segment = $this->getNextLinkUri($lastObject);
            $nextLink = new ODataLink();
            $nextLink->name = ODataConstants::ATOM_LINK_NEXT_ATTRIBUTE_STRING;
            $nextLink->url = rtrim($this->absoluteServiceUri, '/') . '/' . $stackSegment . $segment;
            $odata->nextPageLink = $nextLink;
        }

        return $odata;
    }

    /**
     * Write top level url element.
     *
     * @param QueryResult $entryObject Results property contains the entry resource whose url to be written
     *
     * @return ODataURL
     */
    public function writeUrlElement(QueryResult $entryObject)
    {
        $url = new ODataURL();
        if (null !== $entryObject->results) {
            $currentResourceType = $this->getCurrentResourceSetWrapper()->getResourceType();
            $relativeUri = $this->getEntryInstanceKey(
                $entryObject->results,
                $currentResourceType,
                $this->getCurrentResourceSetWrapper()->getName()
            );

            $url->url = rtrim($this->absoluteServiceUri, '/') . '/' . $relativeUri;
        }

        return $url;
    }

    /**
     * Write top level url collection.
     *
     * @param QueryResult $entryObjects Results property contains the array of entry resources whose urls are
     *                                  to be written
     *
     * @return ODataURLCollection
     */
    public function writeUrlElements(QueryResult $entryObjects)
    {
        $urls = new ODataURLCollection();
        if (!empty($entryObjects->results)) {
            $i = 0;
            foreach ($entryObjects->results as $entryObject) {
                $urls->urls[$i] = $this->writeUrlElement($entryObject);
                ++$i;
            }

            if ($i > 0 && true === $entryObjects->hasMore) {
                $stackSegment = $this->getRequest()->getTargetResourceSetWrapper()->getName();
                $lastObject = end($entryObjects->results);
                $segment = $this->getNextLinkUri($lastObject);
                $nextLink = new ODataLink();
                $nextLink->name = ODataConstants::ATOM_LINK_NEXT_ATTRIBUTE_STRING;
                $nextLink->url = rtrim($this->absoluteServiceUri, '/') . '/' . $stackSegment . $segment;
                $nextLink->url = ltrim($nextLink->url, '/');
                $urls->nextPageLink = $nextLink;
            }
        }

        if ($this->getRequest()->queryType == QueryType::ENTITIES_WITH_COUNT()) {
            $urls->count = $this->getRequest()->getCountValue();
        }

        return $urls;
    }

    /**
     * Write top level complex resource.
     *
     * @param QueryResult  &$complexValue Results property contains the complex object to be written
     * @param string       $propertyName  The name of the complex property
     * @param ResourceType &$resourceType Describes the type of complex object
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelComplexObject(QueryResult &$complexValue, $propertyName, ResourceType &$resourceType)
    {
        $result = $complexValue->results;

        $propertyContent = new ODataPropertyContent();
        $odataProperty = new ODataProperty();
        $odataProperty->name = $propertyName;
        $odataProperty->typeName = $resourceType->getFullName();
        if (null !== $result) {
            assert(is_object($result), 'Supplied $customObject must be an object');
            $internalContent = $this->writeComplexValue($resourceType, $result);
            $odataProperty->value = $internalContent;
        }

        $propertyContent->properties[] = $odataProperty;

        return $propertyContent;
    }

    /**
     * Write top level bag resource.
     *
     * @param QueryResult  $bagValue
     * @param string       $propertyName  The name of the bag property
     * @param ResourceType &$resourceType Describes the type of bag object
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelBagObject(QueryResult &$bagValue, $propertyName, ResourceType &$resourceType)
    {
        $result = $bagValue->results;

        $propertyContent = new ODataPropertyContent();
        $odataProperty = new ODataProperty();
        $odataProperty->name = $propertyName;
        $odataProperty->typeName = 'Collection('.$resourceType->getFullName().')';
        $odataProperty->value = $this->writeBagValue($resourceType, $result);

        $propertyContent->properties[] = $odataProperty;
        return $propertyContent;
    }

    /**
     * Write top level primitive value.
     *
     * @param QueryResult      &$primitiveValue   Results property contains the primitive value to be written
     * @param ResourceProperty &$resourceProperty Resource property describing the primitive property to be written
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelPrimitive(QueryResult &$primitiveValue, ResourceProperty &$resourceProperty = null)
    {
        assert(null !== $resourceProperty, 'Resource property must not be null');
        $result = new ODataPropertyContent();
        $property = new ODataProperty();
        $property->name = $resourceProperty->getName();

        $iType = $resourceProperty->getInstanceType();
        assert($iType instanceof IType, get_class($iType));
        $property->typeName = $iType->getFullTypeName();
        if (null !== $primitiveValue->results) {
            $rType = $resourceProperty->getResourceType()->getInstanceType();
            assert($rType instanceof IType, get_class($rType));
            $property->value = $this->primitiveToString($rType, $primitiveValue->results);
        }

        $result->properties[] = $property;
        return $result;
    }

    /**
     * Gets reference to the request submitted by client.
     *
     * @return RequestDescription
     */
    public function getRequest()
    {
        assert(null !== $this->request, 'Request not yet set');

        return $this->request;
    }

    /**
     * Sets reference to the request submitted by client.
     *
     * @param  RequestDescription $request
     * @return void
     */
    public function setRequest(RequestDescription $request)
    {
        $this->request = $request;
        $this->stack->setRequest($request);
    }

    /**
     * Gets the data service instance.
     *
     * @return IService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Sets the data service instance.
     *
     * @param  IService $service
     * @return void
     */
    public function setService(IService $service)
    {
        $this->service = $service;
        $this->absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash = rtrim($this->absoluteServiceUri, '/') . '/';
    }

    /**
     * Gets the segment stack instance.
     *
     * @return SegmentStack
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * Get update timestamp
     *
     * @return Carbon
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param ResourceType $resourceType
     * @param $result
     * @return ODataBagContent|null
     */
    protected function writeBagValue(ResourceType &$resourceType, $result)
    {
        assert(null === $result || is_array($result), 'Bag parameter must be null or array');
        $typeKind = $resourceType->getResourceTypeKind();
        assert(
            ResourceTypeKind::PRIMITIVE() == $typeKind || ResourceTypeKind::COMPLEX() == $typeKind,
            '$bagItemResourceTypeKind != ResourceTypeKind::PRIMITIVE'
            .' && $bagItemResourceTypeKind != ResourceTypeKind::COMPLEX'
        );
        if (null == $result) {
            return null;
        }
        $bag = new ODataBagContent();
        foreach ($result as $value) {
            if (isset($value)) {
                if (ResourceTypeKind::PRIMITIVE() == $typeKind) {
                    $instance = $resourceType->getInstanceType();
                    assert($instance instanceof IType, get_class($instance));
                    $bag->propertyContents[] = $this->primitiveToString($instance, $value);
                } elseif (ResourceTypeKind::COMPLEX() == $typeKind) {
                    $bag->propertyContents[] = $this->writeComplexValue($resourceType, $value);
                }
            }
        }
        return $bag;
    }

    /**
     * @param  ResourceType         $resourceType
     * @param  object               $result
     * @param  string|null          $propertyName
     * @return ODataPropertyContent
     */
    protected function writeComplexValue(ResourceType &$resourceType, &$result, $propertyName = null)
    {
        assert(is_object($result), 'Supplied $customObject must be an object');

        $count = count($this->complexTypeInstanceCollection);
        for ($i = 0; $i < $count; ++$i) {
            if ($this->complexTypeInstanceCollection[$i] === $result) {
                throw new InvalidOperationException(
                    Messages::objectModelSerializerLoopsNotAllowedInComplexTypes($propertyName)
                );
            }
        }

        $this->complexTypeInstanceCollection[$count] = &$result;

        $internalContent = new ODataPropertyContent();
        $resourceProperties = $resourceType->getAllProperties();
        // first up, handle primitive properties
        foreach ($resourceProperties as $prop) {
            $resourceKind = $prop->getKind();
            $propName = $prop->getName();
            $internalProperty = new ODataProperty();
            $internalProperty->name = $propName;
            $raw = $result->$propName;
            if (static::isMatchPrimitive($resourceKind)) {
                $iType = $prop->getInstanceType();
                assert($iType instanceof IType, get_class($iType));
                $internalProperty->typeName = $iType->getFullTypeName();

                $rType = $prop->getResourceType()->getInstanceType();
                assert($rType instanceof IType, get_class($rType));
                if (null !== $raw) {
                    $internalProperty->value = $this->primitiveToString($rType, $raw);
                }
            } elseif (ResourcePropertyKind::COMPLEX_TYPE == $resourceKind) {
                $rType = $prop->getResourceType();
                $internalProperty->typeName = $rType->getFullName();
                if (null !== $raw) {
                    $internalProperty->value = $this->writeComplexValue($rType, $raw, $propName);
                }
            }
            $internalContent->properties[] = $internalProperty;
        }

        unset($this->complexTypeInstanceCollection[$count]);
        return $internalContent;
    }

    /**
     * Check whether to expand a navigation property or not.
     *
     * @param string $navigationPropertyName Name of naviagtion property in question
     *
     * @return bool True if the given navigation should be expanded, otherwise false
     */
    protected function shouldExpandSegment($navigationPropertyName)
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        if (null === $expandedProjectionNode) {
            return false;
        }

        $expandedProjectionNode = $expandedProjectionNode->findNode($navigationPropertyName);

        // null is a valid input to an instanceof call as of PHP 5.6 - will always return false
        return $expandedProjectionNode instanceof ExpandedProjectionNode;
    }

    protected function getEntryInstanceKey($entityInstance, ResourceType $resourceType, $containerName)
    {
        $typeName = $resourceType->getName();
        $keyProperties = $resourceType->getKeyProperties();
        assert(0 != count($keyProperties), 'count($keyProperties) == 0');
        $keyString = $containerName . '(';
        $comma = null;
        foreach ($keyProperties as $keyName => $resourceProperty) {
            $keyType = $resourceProperty->getInstanceType();
            assert($keyType instanceof IType, '$keyType not instanceof IType');
            $keyName = $resourceProperty->getName();
            $keyValue = $entityInstance->$keyName;
            if (!isset($keyValue)) {
                $msg = Messages::badQueryNullKeysAreNotSupported($typeName, $keyName);
                throw ODataException::createInternalServerError($msg);
            }

            $keyValue = $keyType->convertToOData($keyValue);
            $keyString .= $comma . $keyName . '=' . $keyValue;
            $comma = ',';
        }

        $keyString .= ')';

        return $keyString;
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
        $pageSize = $currentResourceSet->getResourceSetPageSize();

        if (1 == $recursionLevel) {
            //presence of $top option affect next link for root container
            $topValueCount = $this->getRequest()->getTopOptionCount();
            if (null !== $topValueCount && ($topValueCount <= $pageSize)) {
                return false;
            }
        }
        return $resultSetCount == $pageSize;
    }

    /**
     * Get next page link from the given entity instance.
     *
     * @param mixed &$lastObject Last object serialized to be
     *                           used for generating $skiptoken
     *
     * @return string for the link for next page
     */
    protected function getNextLinkUri(&$lastObject)
    {
        $currentExpandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        $internalOrderByInfo = $currentExpandedProjectionNode->getInternalOrderByInfo();
        assert(null !== $internalOrderByInfo);
        assert(is_object($internalOrderByInfo));
        assert($internalOrderByInfo instanceof InternalOrderByInfo, get_class($internalOrderByInfo));
        $numSegments = count($internalOrderByInfo->getOrderByPathSegments());
        $queryParameterString = $this->getNextPageLinkQueryParametersForRootResourceSet();

        $skipToken = $internalOrderByInfo->buildSkipTokenValue($lastObject);
        assert(null !== $skipToken, '!is_null($skipToken)');
        $token = (1 < $numSegments) ? '$skiptoken=' : '$skip=';
        $skipToken = '?'.$queryParameterString.$token.$skipToken;

        return $skipToken;
    }

    /**
     * @param $entryObject
     * @param $type
     * @param $relativeUri
     * @param $resourceType
     * @return array<ODataMediaLink|null|array>
     */
    protected function writeMediaData($entryObject, $type, $relativeUri, ResourceType $resourceType)
    {
        $context = $this->getService()->getOperationContext();
        $streamProviderWrapper = $this->getService()->getStreamProviderWrapper();
        assert(null != $streamProviderWrapper, 'Retrieved stream provider must not be null');

        $mediaLink = null;
        if ($resourceType->isMediaLinkEntry()) {
            $eTag = $streamProviderWrapper->getStreamETag2($entryObject, null, $context);
            $mediaLink = new ODataMediaLink($type, '/$value', $relativeUri . '/$value', '*/*', $eTag);
        }
        $mediaLinks = [];
        if ($resourceType->hasNamedStream()) {
            $namedStreams = $resourceType->getAllNamedStreams();
            foreach ($namedStreams as $streamTitle => $resourceStreamInfo) {
                $readUri = $streamProviderWrapper->getReadStreamUri2(
                    $entryObject,
                    $resourceStreamInfo,
                    $context,
                    $relativeUri
                );
                $mediaContentType = $streamProviderWrapper->getStreamContentType2(
                    $entryObject,
                    $resourceStreamInfo,
                    $context
                );
                $eTag = $streamProviderWrapper->getStreamETag2(
                    $entryObject,
                    $resourceStreamInfo,
                    $context
                );

                $nuLink = new ODataMediaLink($streamTitle, $readUri, $readUri, $mediaContentType, $eTag);
                $mediaLinks[] = $nuLink;
            }
        }
        return [$mediaLink, $mediaLinks];
    }

    /**
     * @param $entryObject
     * @param $prop
     * @param $nuLink
     * @param $propKind
     * @param $propName
     */
    private function expandNavigationProperty(QueryResult $entryObject, $prop, $nuLink, $propKind, $propName)
    {
        $nextName = $prop->getResourceType()->getName();
        $nuLink->isExpanded = true;
        $isCollection = ResourcePropertyKind::RESOURCESET_REFERENCE == $propKind;
        $nuLink->isCollection = $isCollection;
        $value = $entryObject->results->$propName;
        $nullResult = null === $value;
        $result = new QueryResult();
        $result->results = $value;
        if (!$nullResult) {
            array_push($this->lightStack, [$nextName, $propName]);
            if (isset($value)) {
                if (!$isCollection) {
                    $expandedResult = $this->writeTopLevelElement($result);
                } else {
                    $expandedResult = $this->writeTopLevelElements($result);
                }
                $nuLink->expandedResult = $expandedResult;
            }
        }
        if (!isset($nuLink->expandedResult)) {
            $nuLink->isCollection = null;
            $nuLink->isExpanded = null;
        } else {
            if (isset($nuLink->expandedResult->selfLink)) {
                $nuLink->expandedResult->selfLink->title = $propName;
                $nuLink->expandedResult->selfLink->url = $nuLink->url;
                $nuLink->expandedResult->title = new ODataTitle($propName);
                $nuLink->expandedResult->id = rtrim($this->absoluteServiceUri, '/') . '/' . $nuLink->url;
            }
        }
    }

    /**
     * Gets collection of projection nodes under the current node.
     *
     * @return ProjectionNode[]|ExpandedProjectionNode[]|null List of nodes describing projections for the current
     *                                                        segment, If this method returns null it means no
     *                                                        projections are to be applied and the entire resource for
     *                                                        the current segment should be serialized, If it returns
     *                                                        non-null only the properties described by the returned
     *                                                        projection segments should be serialized
     */
    protected function getProjectionNodes()
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        if (null === $expandedProjectionNode || $expandedProjectionNode->canSelectAllProperties()) {
            return null;
        }

        return $expandedProjectionNode->getChildNodes();
    }

    /**
     * Find a 'ExpandedProjectionNode' instance in the projection tree
     * which describes the current segment.
     *
     * @return null|RootProjectionNode|ExpandedProjectionNode
     */
    protected function getCurrentExpandedProjectionNode()
    {
        $expandedProjectionNode = $this->getRequest()->getRootProjectionNode();
        if (null === $expandedProjectionNode) {
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
            if (0 != $depth) {
                for ($i = 1; $i < $depth; ++$i) {
                    $expandedProjectionNode = $expandedProjectionNode->findNode($segmentNames[$i]);
                    assert(null !== $expandedProjectionNode, 'is_null($expandedProjectionNode)');
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
                     ODataConstants::HTTPQUERY_STRING_SELECT, ] as $queryOption) {
            $value = $this->getService()->getHost()->getQueryStringItem($queryOption);
            if (null !== $value) {
                if (null !== $queryParameterString) {
                    $queryParameterString = $queryParameterString . '&';
                }

                $queryParameterString .= $queryOption . '=' . $value;
            }
        }

        $topCountValue = $this->getRequest()->getTopOptionCount();
        if (null !== $topCountValue) {
            $remainingCount = $topCountValue - $this->getRequest()->getTopCount();
            if (0 < $remainingCount) {
                if (null !== $queryParameterString) {
                    $queryParameterString .= '&';
                }

                $queryParameterString .= ODataConstants::HTTPQUERY_STRING_TOP . '=' . $remainingCount;
            }
        }

        if (null !== $queryParameterString) {
            $queryParameterString .= '&';
        }

        return $queryParameterString;
    }

    /**
     * @param $entryObject
     * @param $nonRelProp
     * @return ODataPropertyContent
     */
    private function writeProperties($entryObject, $nonRelProp)
    {
        $propertyContent = new ODataPropertyContent();
        foreach ($nonRelProp as $corn => $flake) {
            $resource = $nonRelProp[$corn]->getResourceType();
            if ($resource instanceof ResourceEntityType) {
                continue;
            }
            $result = $entryObject->$corn;
            $isBag = $flake->isKindOf(ResourcePropertyKind::BAG);
            $typePrepend = $isBag ? 'Collection(' : '';
            $typeAppend = $isBag ? ')' : '';
            $nonNull = null !== $result;
            $subProp = new ODataProperty();
            $subProp->name = strval($corn);
            $subProp->typeName = $typePrepend . $resource->getFullName() . $typeAppend;

            if ($nonNull && is_array($result)) {
                $subProp->value = $this->writeBagValue($resource, $result);
            } elseif ($resource instanceof ResourcePrimitiveType && $nonNull) {
                $rType = $resource->getInstanceType();
                assert($rType instanceof IType, get_class($rType));
                $subProp->value = $this->primitiveToString($rType, $result);
            } elseif ($resource instanceof ResourceComplexType && $nonNull) {
                $subProp->value = $this->writeComplexValue($resource, $result, $flake->getName());
            }
            $propertyContent->properties[] = $subProp;
        }
        return $propertyContent;
    }

    /**
     * @return void
     */
    private function loadStackIfEmpty()
    {
        if (0 == count($this->lightStack)) {
            $typeName = $this->getRequest()->getTargetResourceType()->getName();
            array_push($this->lightStack, [$typeName, $typeName]);
        }
    }

    /**
     * Convert the given primitive value to string.
     * Note: This method will not handle null primitive value.
     *
     * @param IType &$primitiveResourceType Type of the primitive property
     *                                      whose value need to be converted
     * @param mixed $primitiveValue         Primitive value to convert
     *
     * @return string
     */
    private function primitiveToString(IType &$type, $primitiveValue)
    {
        if ($type instanceof Boolean) {
            $stringValue = (true === $primitiveValue) ? 'true' : 'false';
        } elseif ($type instanceof Binary) {
            $stringValue = base64_encode($primitiveValue);
        } elseif ($type instanceof DateTime && $primitiveValue instanceof \DateTime) {
            $stringValue = $primitiveValue->format(\DateTime::ATOM);
        } elseif ($type instanceof StringType) {
            $stringValue = utf8_encode($primitiveValue);
        } else {
            $stringValue = strval($primitiveValue);
        }

        return $stringValue;
    }

    public static function isMatchPrimitive($resourceKind)
    {
        if (16 > $resourceKind) {
            return false;
        }
        if (28 < $resourceKind) {
            return false;
        }
        return 0 == ($resourceKind % 4);
    }
}
