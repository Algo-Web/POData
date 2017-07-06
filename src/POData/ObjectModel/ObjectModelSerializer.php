<?php

namespace POData\ObjectModel;

use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;

/**
 * Class ObjectModelSerializer.
 */
class ObjectModelSerializer extends ObjectModelSerializerBase implements IObjectSerialiser
{
    /**
     * Creates new instance of ObjectModelSerializer.
     *
     * @param IService           $service
     * @param RequestDescription $request the  request submitted by the client
     */
    public function __construct(IService $service, RequestDescription $request = null)
    {
        parent::__construct($service, $request);
    }

    /**
     * Write a top level entry resource.
     *
     * @param QueryResult $entryObject      Results property contains reference to the entry object to be written
     *
     * @return ODataEntry
     */
    public function writeTopLevelElement(QueryResult $entryObject)
    {
        $requestTargetSource = $this->getRequest()->getTargetSource();

        if (TargetSource::ENTITY_SET == $requestTargetSource) {
            $resourceType = $this->getRequest()->getTargetResourceType();
        } else {
            assert(TargetSource::PROPERTY == $requestTargetSource, '$requestTargetSource != TargetSource::PROPERTY');
            $resourceProperty = $this->getRequest()->getProjectedProperty();
            $resourceType = $resourceProperty->getResourceType();
        }

        $needPop = $this->pushSegmentForRoot();
        $entry = $this->writeEntryElement(
            $entryObject->results,
            $resourceType,
            $this->getRequest()->getRequestUrl()->getUrlAsString(),
            $this->getRequest()->getContainerName()
        );
        $this->popSegment($needPop);

        return $entry;
    }

    /**
     * Write top level feed element.
     *
     * @param QueryResult &$entryObjects    Results property contains array of entry resources to be written
     *
     * @return ODataFeed
     */
    public function writeTopLevelElements(QueryResult &$entryObjects)
    {
        assert(is_array($entryObjects->results), '!is_array($entryObjects->results)');
        $requestTargetSource = $this->getRequest()->getTargetSource();
        if (TargetSource::ENTITY_SET == $requestTargetSource) {
            $title = $this->getRequest()->getContainerName();
        } else {
            assert(TargetSource::PROPERTY == $requestTargetSource, '$requestTargetSource != TargetSource::PROPERTY');
            $resourceProperty = $this->getRequest()->getProjectedProperty();
            assert(
                ResourcePropertyKind::RESOURCESET_REFERENCE == $resourceProperty->getKind(),
                '$resourceProperty->getKind() != ResourcePropertyKind::RESOURCESET_REFERENCE'
            );
            $title = $resourceProperty->getName();
        }

        $relativeUri = $this->getRequest()->getIdentifier();
        $feed = new ODataFeed();

        if ($this->getRequest()->queryType == QueryType::ENTITIES_WITH_COUNT()) {
            $feed->rowCount = $this->getRequest()->getCountValue();
        }

        $needPop = $this->pushSegmentForRoot();
        $targetResourceType = $this->getRequest()->getTargetResourceType();
        assert(null != $targetResourceType, 'Target resource type must not be null');

        $resourceSet = $this->getRequest()->getTargetResourceSetWrapper()->getResourceSet();
        $requestTop = $this->getRequest()->getTopOptionCount();
        $pageSize = $this->getService()->getConfiguration()->getEntitySetPageSize($resourceSet);
        $requestTop = (null == $requestTop) ? $pageSize + 1 : $requestTop;
        $needLink = $entryObjects->hasMore && ($requestTop > $pageSize);

        $this->writeFeedElements(
            $entryObjects->results,
            $targetResourceType,
            $title,
            $this->getRequest()->getRequestUrl()->getUrlAsString(),
            $relativeUri,
            $feed,
            $needLink
        );
        $this->popSegment($needPop);

        return $feed;
    }

    /**
     * Write top level url element.
     *
     * @param QueryResult $entryObject      Results property contains the entry resource whose url to be written
     *
     * @return ODataURL
     */
    public function writeUrlElement(QueryResult $entryObject)
    {
        $url = new ODataURL();
        if (!is_null($entryObject->results)) {
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
     * @param QueryResult $entryObjects     Results property contains the array of entry resources whose urls are
     *                                      to be written
     *
     * @return ODataURLCollection
     */
    public function writeUrlElements(QueryResult $entryObjects)
    {
        $urls = new ODataURLCollection();
        $results = $entryObjects->results;
        if (!empty($results)) {
            $i = 0;
            foreach ($results as $entryObject) {
                $urls->urls[$i] = $this->writeUrlElement($entryObject);
                ++$i;
            }

            //if ($i > 0 && $this->needNextPageLink(count($results))) {
            if ($i > 0 && true === $entryObjects->hasMore) {
                $urls->nextPageLink = $this->getNextLinkUri(
                    $results[$i - 1],
                    $this->getRequest()->getRequestUrl()->getUrlAsString()
                );
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
    public function writeTopLevelComplexObject(
        QueryResult &$complexValue,
        $propertyName,
        ResourceType & $resourceType
    ) {
        $propertyContent = new ODataPropertyContent();
        $this->writeComplexValue(
            $complexValue->results,
            $propertyName,
            $resourceType,
            null,
            $propertyContent
        );

        return $propertyContent;
    }

    /**
     * Write top level bag resource.
     *
     * @param QueryResult  &$BagValue     Results property contains the bag object to be written
     * @param string       $propertyName  The name of the bag property
     * @param ResourceType &$resourceType Describes the type of bag object
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelBagObject(
        QueryResult &$BagValue,
        $propertyName,
        ResourceType &$resourceType
    ) {
        $propertyContent = new ODataPropertyContent();
        $this->writeBagValue(
            $BagValue->results,
            $propertyName,
            $resourceType,
            null,
            $propertyContent
        );

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
    public function writeTopLevelPrimitive(
        QueryResult &$primitiveValue,
        ResourceProperty &$resourceProperty = null
    ) {
        assert(null != $resourceProperty, "Resource property must not be null");
        $propertyContent = new ODataPropertyContent();
        $propertyContent->properties[] = new ODataProperty();
        $this->writePrimitiveValue(
            $primitiveValue->results,
            $propertyContent->properties[0],
            $resourceProperty
        );

        return $propertyContent;
    }

    /**
     * Write an entry element.
     *
     * @param mixed        $entryObject  Object representing entry element
     * @param ResourceType $resourceType Expected type of the entry object
     * @param string       $absoluteUri  Absolute uri of the entry element
     * @param string       $relativeUri  Relative uri of the entry element
     *
     * @return ODataEntry
     */
    private function writeEntryElement(
        $entryObject,
        ResourceType $resourceType,
        $absoluteUri,
        $relativeUri
    ) {
        $entry = new ODataEntry();
        $entry->resourceSetName = $this->getCurrentResourceSetWrapper()->getName();

        if (is_null($entryObject)) {
            //According to atom standard an empty entry must have an Author
            //node.
        } else {
            $relativeUri = $this->getEntryInstanceKey(
                $entryObject,
                $resourceType,
                $this->getCurrentResourceSetWrapper()->getName()
            );

            $absoluteUri = rtrim($this->absoluteServiceUri, '/') . '/' . $relativeUri;
            $title = $resourceType->getName();
            //TODO Resolve actual resource type
            $actualResourceType = $resourceType;
            $this->writeMediaResourceMetadata(
                $entryObject,
                $actualResourceType,
                $title,
                $relativeUri,
                $entry
            );

            $entry->id = $absoluteUri;
            $entry->eTag = $this->getETagForEntry($entryObject, $resourceType);
            $entry->title = $title;
            $entry->editLink = $relativeUri;
            $entry->type = $actualResourceType->getFullName();
            $odataPropertyContent = new ODataPropertyContent();
            $this->writeObjectProperties(
                $entryObject,
                $actualResourceType,
                $absoluteUri,
                $relativeUri,
                $entry,
                $odataPropertyContent
            );
            $entry->propertyContent = $odataPropertyContent;
        }

        return $entry;
    }

    /**
     * Writes the feed elements.
     *
     * @param array        &$entryObjects Array of entries in the feed element
     * @param ResourceType &$resourceType The resource type of the elements in the collection
     * @param string       $title         Title of the feed element
     * @param string       $absoluteUri   Absolute uri representing the feed element
     * @param string       $relativeUri   Relative uri representing the feed element
     * @param ODataFeed    &$feed         Feed to write to
     * @param bool|null    $needLink      Has query provider already determined next-page link is needed?
     */
    private function writeFeedElements(
        &$entryObjects,
        ResourceType &$resourceType,
        $title,
        $absoluteUri,
        $relativeUri,
        ODataFeed &$feed,
        $needLink = false
    ) {
        assert(is_array($entryObjects), '!_writeFeedElements::is_array($entryObjects)');
        $feed->id = $absoluteUri;
        $feed->title = $title;
        $feed->selfLink = new ODataLink();
        $feed->selfLink->name = ODataConstants::ATOM_SELF_RELATION_ATTRIBUTE_VALUE;
        $feed->selfLink->title = $title;
        $feed->selfLink->url = $relativeUri;

        if (empty($entryObjects)) {
            //TODO // ATOM specification: if a feed contains no entries,
            //then the feed should have at least one Author tag
        } else {
            foreach ($entryObjects as $entryObject) {
                if ($entryObject instanceof QueryResult) {
                    $entryObject = $entryObject->results;
                }
                $feed->entries[] = $this->writeEntryElement($entryObject, $resourceType, null, null);
            }

            if (true === $needLink) {
                if ($entryObjects instanceof QueryResult) {
                    $entryObjects = $entryObjects->results;
                }
                $lastObject = end($entryObjects);
                $feed->nextPageLink = $this->getNextLinkUri($lastObject, $absoluteUri);
            }
        }
    }

    /**
     * Write values of properties of given entry (resource) or complex object.
     *
     * @param mixed        $customObject  Entity or complex object
     *                                    with properties
     *                                    to write out
     * @param ResourceType &$resourceType Resource type describing
     *                                    the metadata of
     *                                    the custom object
     * @param string       $absoluteUri   Absolute uri for the given
     *                                    entry object
     *                                    NULL for complex object
     * @param string       $relativeUri   Relative uri for the given
     *                                    custom object
     * @param ODataEntry           ODataEntry|null           ODataEntry instance to
     *                                                    place links and
     *                                                    expansion of the
     *                                                    entry object,
     *                                                    NULL for complex object
     * @param ODataPropertyContent &$odataPropertyContent ODataPropertyContent
     *                                                    instance in which
     *                                                    to place the values
     */
    private function writeObjectProperties(
        $customObject,
        ResourceType &$resourceType,
        $absoluteUri,
        $relativeUri,
        &$odataEntry,
        ODataPropertyContent &$odataPropertyContent
    ) {
        $resourceTypeKind = $resourceType->getResourceTypeKind();
        if (is_null($absoluteUri) == (ResourceTypeKind::ENTITY == $resourceTypeKind)
        ) {
            throw ODataException::createInternalServerError(
                Messages::badProviderInconsistentEntityOrComplexTypeUsage(
                    $resourceType->getName()
                )
            );
        }

        assert(
            ((ResourceTypeKind::ENTITY == $resourceTypeKind) && ($odataEntry instanceof ODataEntry))
            || ((ResourceTypeKind::COMPLEX == $resourceTypeKind) && is_null($odataEntry)),
            '!(($resourceTypeKind == ResourceTypeKind::ENTITY) && ($odataEntry instanceof ODataEntry))'
            .' && !(($resourceTypeKind == ResourceTypeKind::COMPLEX) && is_null($odataEntry))'
        );
        $projectionNodes = null;
        $navigationProperties = null;
        if (ResourceTypeKind::ENTITY == $resourceTypeKind) {
            $projectionNodes = $this->getProjectionNodes();
            $navigationProperties = [];
        }

        if (is_null($projectionNodes)) {
            list($odataPropertyContent, $navigationProperties) = $this->writeObjectPropertiesUnexpanded(
                $customObject,
                $resourceType,
                $relativeUri,
                $odataPropertyContent,
                $resourceTypeKind,
                $navigationProperties
            );
        } else { //This is the code path to handle projected properties of Entry
            list($odataPropertyContent, $navigationProperties) = $this->writeObjectPropertiesExpanded(
                $customObject,
                $resourceType,
                $relativeUri,
                $odataPropertyContent,
                $projectionNodes,
                $navigationProperties
            );
        }

        if (!is_null($navigationProperties)) {
            //Write out navigation properties (deferred or inline)
            foreach ($navigationProperties as $navigationPropertyInfo) {
                $propertyName = $navigationPropertyInfo->resourceProperty->getName();
                $type = ResourcePropertyKind::RESOURCE_REFERENCE == $navigationPropertyInfo->resourceProperty->getKind() ?
                    'application/atom+xml;type=entry' : 'application/atom+xml;type=feed';
                $link = new ODataLink();
                $link->name = ODataConstants::ODATA_RELATED_NAMESPACE . $propertyName;
                $link->title = $propertyName;
                $link->type = $type;
                $link->url = $relativeUri . '/' . $propertyName;

                if ($navigationPropertyInfo->expanded) {
                    $propertyRelativeUri = $relativeUri . '/' . $propertyName;
                    $propertyAbsoluteUri = trim($absoluteUri, '/') . '/' . $propertyName;
                    $needPop = $this->pushSegmentForNavigationProperty($navigationPropertyInfo->resourceProperty);
                    $navigationPropertyKind = $navigationPropertyInfo->resourceProperty->getKind();
                    assert(
                        ResourcePropertyKind::RESOURCESET_REFERENCE == $navigationPropertyKind
                        || ResourcePropertyKind::RESOURCE_REFERENCE == $navigationPropertyKind,
                        '$navigationPropertyKind != ResourcePropertyKind::RESOURCESET_REFERENCE 
                        && $navigationPropertyKind != ResourcePropertyKind::RESOURCE_REFERENCE'
                    );
                    $currentResourceSetWrapper = $this->getCurrentResourceSetWrapper();
                    assert(!is_null($currentResourceSetWrapper), 'is_null($currentResourceSetWrapper)');
                    $link->isExpanded = true;
                    if (!is_null($navigationPropertyInfo->value)) {
                        $currentResourceType = $currentResourceSetWrapper->getResourceType();
                        if (ResourcePropertyKind::RESOURCESET_REFERENCE == $navigationPropertyKind) {
                            $inlineFeed = new ODataFeed();
                            $link->isCollection = true;

                            //TODO: Robustise need-next-link determination
                            $this->writeFeedElements(
                                $navigationPropertyInfo->value,
                                $currentResourceType,
                                $propertyName,
                                $propertyAbsoluteUri,
                                $propertyRelativeUri,
                                $inlineFeed,
                                false
                            );
                            $link->expandedResult = $inlineFeed;
                        } else {
                            $link->isCollection = false;
                            $link->expandedResult = $this->writeEntryElement(
                                $navigationPropertyInfo->value,
                                $currentResourceType,
                                $propertyAbsoluteUri,
                                $propertyRelativeUri
                            );
                        }
                    } else {
                        $link->expandedResult = null;
                    }

                    $this->popSegment($needPop);
                }

                $odataEntry->links[] = $link;
            }
        }
    }

    /**
     * Writes a primitive value and related information to the given
     * ODataProperty instance.
     *
     * @param mixed &$primitiveValue The primitive value to write
     * @param ODataProperty &$odataProperty ODataProperty instance to which
     *                                            the primitive value and related
     *                                            information to write out
     *
     * @param ResourceProperty|null &$resourceProperty The metadata of the primitive
     *                                            property value
     */
    private function writePrimitiveValue(
        &$primitiveValue,
        ODataProperty &$odataProperty,
        ResourceProperty &$resourceProperty
    ) {
        if (is_object($primitiveValue)) {
            //TODO ERROR: The property 'PropertyName'
            //is defined as primitive type but value is an object
        }

        $odataProperty->name = $resourceProperty->getName();
        $odataProperty->typeName = $resourceProperty->getInstanceType()->getFullTypeName();
        if (is_null($primitiveValue)) {
            $odataProperty->value = null;
        } else {
            $resourceType = $resourceProperty->getResourceType();
            $odataProperty->value = $this->primitiveToString($resourceType, $primitiveValue);
        }
    }

    /**
     * Write value of a complex object.
     *
     * @param mixed                &$complexValue         Complex object to write
     * @param string               $propertyName          Name of the
     *                                                    complex property
     *                                                    whose value need
     *                                                    to be written
     * @param ResourceType         &$resourceType         Expected type
     *                                                    of the property
     * @param string               $relativeUri           Relative uri for the
     *                                                    complex type element
     * @param ODataPropertyContent &$odataPropertyContent Content to write to
     */
    private function writeComplexValue(
        &$complexValue,
        $propertyName,
        ResourceType &$resourceType,
        $relativeUri,
        ODataPropertyContent &$odataPropertyContent
    ) {
        $odataProperty = new ODataProperty();
        $odataProperty->name = $propertyName;
        if (is_null($complexValue)) {
            $odataProperty->value = null;
            $odataProperty->typeName = $resourceType->getFullName();
        } else {
            $content = new ODataPropertyContent();
            $actualType = $this->complexObjectToContent(
                $complexValue,
                $propertyName,
                $resourceType,
                $relativeUri,
                $content
            );

            $odataProperty->typeName = $actualType->getFullName();
            $odataProperty->value = $content;
        }

        $odataPropertyContent->properties[] = $odataProperty;
    }

    /**
     * Write value of a bag instance.
     *
     * @param array/NULL           &$BagValue             Bag value to write
     * @param string               $propertyName          Property name of the bag
     * @param ResourceType         &$resourceType         Type describing the
     *                                                    bag value
     * @param string               $relativeUri           Relative Url to the bag
     * @param ODataPropertyContent &$odataPropertyContent On return, this object
     *                                                    will hold bag value which
     *                                                    can be used by writers
     */
    private function writeBagValue(
        &$BagValue,
        $propertyName,
        ResourceType &$resourceType,
        $relativeUri,
        ODataPropertyContent &$odataPropertyContent
    ) {
        assert(null == $BagValue || is_array($BagValue), 'Bag parameter must be null or array');
        $bagItemResourceTypeKind = $resourceType->getResourceTypeKind();
        assert(
            ResourceTypeKind::PRIMITIVE == $bagItemResourceTypeKind
            || ResourceTypeKind::COMPLEX == $bagItemResourceTypeKind,
            '$bagItemResourceTypeKind != ResourceTypeKind::PRIMITIVE'
            .' && $bagItemResourceTypeKind != ResourceTypeKind::COMPLEX'
        );

        $odataProperty = new ODataProperty();
        $odataProperty->name = $propertyName;
        $odataProperty->typeName = 'Collection(' . $resourceType->getFullName() . ')';

        if (is_null($BagValue) || (is_array($BagValue) && empty($BagValue))) {
            $odataProperty->value = null;
        } else {
            $odataBagContent = new ODataBagContent();
            foreach ($BagValue as $itemValue) {
                // strip out null elements
                if (isset($itemValue)) {
                    if (ResourceTypeKind::PRIMITIVE == $bagItemResourceTypeKind) {
                        $odataBagContent->propertyContents[] = $this->primitiveToString($resourceType, $itemValue);
                    } elseif (ResourceTypeKind::COMPLEX == $bagItemResourceTypeKind) {
                        $complexContent = new ODataPropertyContent();
                        $this->complexObjectToContent(
                            $itemValue,
                            $propertyName,
                            $resourceType,
                            $relativeUri,
                            $complexContent
                        );
                        //TODO add type in case of base type
                        $odataBagContent->propertyContents[] = $complexContent;
                    }
                }
            }

            $odataProperty->value = $odataBagContent;
        }

        $odataPropertyContent->properties[] = $odataProperty;
    }

    /**
     * Write media resource metadata (for MLE and Named Streams).
     *
     * @param mixed        $entryObject   The entry instance being serialized
     * @param ResourceType &$resourceType Resource type of the entry instance
     * @param string       $title         Title for the current
     *                                    current entry instance
     * @param string       $relativeUri   Relative uri for the
     *                                    current entry instance
     * @param ODataEntry   &$odataEntry   OData entry to write to
     */
    private function writeMediaResourceMetadata(
        $entryObject,
        ResourceType &$resourceType,
        $title,
        $relativeUri,
        ODataEntry &$odataEntry
    ) {
        $streamProviderWrapper = $this->getService()->getStreamProviderWrapper();
        assert(null != $streamProviderWrapper, "Retrieved stream provider must not be null");
        $context = $this->getService()->getOperationContext();
        if ($resourceType->isMediaLinkEntry()) {
            $odataEntry->isMediaLinkEntry = true;
            $eTag = $streamProviderWrapper->getStreamETag2($entryObject, null, $context);
            $readStreamUri = $streamProviderWrapper->getReadStreamUri2($entryObject, null, $context, $relativeUri);
            $mediaContentType = $streamProviderWrapper->getStreamContentType2($entryObject, null, $context);
            $mediaLink = new ODataMediaLink(
                $title,
                $streamProviderWrapper->getDefaultStreamEditMediaUri(
                    $relativeUri,
                    $resourceType,
                    null,
                    $context
                ),
                $readStreamUri,
                $mediaContentType,
                $eTag
            );

            $odataEntry->mediaLink = $mediaLink;
        }

        if ($resourceType->hasNamedStream()) {
            foreach ($resourceType->getAllNamedStreams() as $title => $resourceStreamInfo) {
                $eTag = $streamProviderWrapper->getStreamETag2(
                    $entryObject,
                    $resourceStreamInfo,
                    $context
                );
                $readStreamUri = $streamProviderWrapper->getReadStreamUri2(
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
                $mediaLink = new ODataMediaLink(
                    $title,
                    $streamProviderWrapper->getReadStreamUri2(
                        $entryObject,
                        $resourceStreamInfo,
                        $context,
                        $relativeUri
                    ),
                    $readStreamUri,
                    $mediaContentType,
                    $eTag
                );

                $odataEntry->mediaLinks[] = $mediaLink;
            }
        }
    }

    /**
     * Convert the given primitive value to string.
     * Note: This method will not handle null primitive value.
     *
     * @param ResourceType &$primitiveResourceType Type of the primitive property
     *                                             whose value need to be converted
     * @param mixed        $primitiveValue         Primitive value to convert
     *
     * @return string
     */
    private function primitiveToString(
        ResourceType &$primitiveResourceType,
        $primitiveValue
    ) {
        $type = $primitiveResourceType->getInstanceType();
        if ($type instanceof Boolean) {
            $stringValue = ($primitiveValue === true) ? 'true' : 'false';
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

    /**
     * Write value of a complex object.
     * Note: This method will not handle null complex value.
     *
     * @param mixed                &$complexValue         Complex object to write
     * @param string               $propertyName          Name of the
     *                                                    complex property
     *                                                    whose value
     *                                                    need to be written
     * @param ResourceType         &$resourceType         Expected type of the
     *                                                    property
     * @param string               $relativeUri           Relative uri for the
     *                                                    complex type element
     * @param ODataPropertyContent &$odataPropertyContent Content to write to
     *
     * @return ResourceType The actual type of the complex object
     */
    private function complexObjectToContent(
        &$complexValue,
        $propertyName,
        ResourceType &$resourceType,
        $relativeUri,
        ODataPropertyContent &$odataPropertyContent
    ) {
        $count = count($this->complexTypeInstanceCollection);
        for ($i = 0; $i < $count; ++$i) {
            if ($this->complexTypeInstanceCollection[$i] === $complexValue) {
                throw new InvalidOperationException(
                    Messages::objectModelSerializerLoopsNotAllowedInComplexTypes($propertyName)
                );
            }
        }

        $this->complexTypeInstanceCollection[$count] = &$complexValue;

        //TODO function to resolve actual type from $resourceType
        $actualType = $resourceType;
        $odataEntry = null;
        $this->writeObjectProperties(
            $complexValue,
            $actualType,
            null,
            $relativeUri,
            $odataEntry,
            $odataPropertyContent
        );
        unset($this->complexTypeInstanceCollection[$count]);

        return $actualType;
    }

    /**
     * @param object $customObject
     * @param ResourceType $resourceType
     * @param string $relativeUri
     * @param ODataPropertyContent $odataPropertyContent
     * @param ResourceTypeKind $resourceTypeKind
     * @param $navigationProperties
     *
     * @throws ODataException
     *
     * @return array
     */
    private function writeObjectPropertiesUnexpanded(
        $customObject,
        ResourceType &$resourceType,
        $relativeUri,
        ODataPropertyContent &$odataPropertyContent,
        $resourceTypeKind,
        $navigationProperties
    ) {
        assert(is_object($customObject), 'Supplied $customObject must be an object');
        //This is the code path to handle properties of Complex type
        //or Entry without projection (i.e. no expansion or selection)
        if (ResourceTypeKind::ENTITY == $resourceTypeKind) {
            // If custom object is an entry then it can contain navigation
            // properties which are invisible (because the corresponding
            // resource set is invisible).
            // IDSMP::getResourceProperties will give collection of properties
            // which are visible.
            $currentResourceSetWrapper1 = $this->getCurrentResourceSetWrapper();
            $resourceProperties = $this->getService()
                ->getProvidersWrapper()
                ->getResourceProperties(
                    $currentResourceSetWrapper1,
                    $resourceType
                );
        } else {
            $resourceProperties = $resourceType->getAllProperties();
        }

        $nonPrimitiveProperties = [];
        //First write out primitive types
        foreach ($resourceProperties as $name => $resourceProperty) {
            $resourceKind = $resourceProperty->getKind();
            if (ObjectModelSerializer::isMatchPrimitive($resourceKind)) {
                $odataProperty = new ODataProperty();
                $primitiveValue = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
                $this->writePrimitiveValue($primitiveValue, $odataProperty, $resourceProperty);
                $odataPropertyContent->properties[] = $odataProperty;
            } else {
                $nonPrimitiveProperties[] = $resourceProperty;
            }
        }

        //Write out bag and complex type
        $i = 0;
        foreach ($nonPrimitiveProperties as $resourceProperty) {
            if ($resourceProperty->isKindOf(ResourcePropertyKind::BAG)) {
                //Handle Bag Property (Bag of Primitive or complex)
                $propertyValue = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
                $resourceType2 = $resourceProperty->getResourceType();
                $this->writeBagValue(
                    $propertyValue,
                    $resourceProperty->getName(),
                    $resourceType2,
                    $relativeUri . '/' . $resourceProperty->getName(),
                    $odataPropertyContent
                );
            } else {
                $resourceKind = $resourceProperty->getKind();
                if (ResourcePropertyKind::COMPLEX_TYPE == $resourceKind) {
                    $propertyValue = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
                    $resourceType1 = $resourceProperty->getResourceType();
                    $this->writeComplexValue(
                        $propertyValue,
                        $resourceProperty->getName(),
                        $resourceType1,
                        $relativeUri . '/' . $resourceProperty->getName(),
                        $odataPropertyContent
                    );
                } else {
                    assert(
                        (ResourcePropertyKind::RESOURCE_REFERENCE == $resourceKind)
                        || (ResourcePropertyKind::RESOURCESET_REFERENCE == $resourceKind),
                        '($resourceKind != ResourcePropertyKind::RESOURCE_REFERENCE)'
                        .'&& ($resourceKind != ResourcePropertyKind::RESOURCESET_REFERENCE)'
                    );

                    $navigationProperties[$i] = new ODataNavigationPropertyInfo(
                        $resourceProperty,
                        $this->shouldExpandSegment($resourceProperty->getName())
                    );
                    if ($navigationProperties[$i]->expanded) {
                        $navigationProperties[$i]->value = $this->getPropertyValue(
                            $customObject,
                            $resourceType,
                            $resourceProperty
                        );
                    }

                    ++$i;
                }
            }
        }

        return [$odataPropertyContent, $navigationProperties];
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


    /**
     * @param object $customObject
     * @param ResourceType $resourceType
     * @param string $relativeUri
     * @param ODataPropertyContent $odataPropertyContent
     * @param \POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode[] $projectionNodes
     * @param $navigationProperties
     *
     * @throws ODataException
     *
     * @return array
     */
    private function writeObjectPropertiesExpanded(
        $customObject,
        ResourceType &$resourceType,
        $relativeUri,
        ODataPropertyContent &$odataPropertyContent,
        $projectionNodes,
        $navigationProperties
    ) {
        assert(is_object($customObject), 'Supplied $customObject must be an object');
        $i = 0;
        foreach ($projectionNodes as $projectionNode) {
            $propertyName = $projectionNode->getPropertyName();
            $resourceProperty = $resourceType->resolveProperty($propertyName);
            assert(!is_null($resourceProperty), 'is_null($resourceProperty)');

            if (ResourceTypeKind::ENTITY == $resourceProperty->getTypeKind()) {
                $currentResourceSetWrapper2 = $this->getCurrentResourceSetWrapper();
                $resourceProperties = $this->getService()
                    ->getProvidersWrapper()
                    ->getResourceProperties(
                        $currentResourceSetWrapper2,
                        $resourceType
                    );
                //Check for the visibility of this navigation property
                if (array_key_exists($resourceProperty->getName(), $resourceProperties)) {
                    $navigationProperties[$i] = new ODataNavigationPropertyInfo(
                        $resourceProperty,
                        $this->shouldExpandSegment($propertyName)
                    );
                    if ($navigationProperties[$i]->expanded) {
                        $navigationProperties[$i]->value = $this->getPropertyValue(
                            $customObject,
                            $resourceType,
                            $resourceProperty
                        );
                    }

                    ++$i;
                    continue;
                }
            }

            //Primitive, complex or bag property
            $propertyValue = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
            $propertyTypeKind = $resourceProperty->getKind();
            $propertyResourceType = $resourceProperty->getResourceType();
            assert(!is_null($propertyResourceType), 'is_null($propertyResourceType)');
            if ($resourceProperty->isKindOf(ResourcePropertyKind::BAG)) {
                $bagResourceType = $resourceProperty->getResourceType();
                $this->writeBagValue(
                    $propertyValue,
                    $propertyName,
                    $bagResourceType,
                    $relativeUri . '/' . $propertyName,
                    $odataPropertyContent
                );
            } elseif ($resourceProperty->isKindOf(ResourcePropertyKind::PRIMITIVE)) {
                $odataProperty = new ODataProperty();
                $this->writePrimitiveValue($propertyValue, $odataProperty, $resourceProperty);
                $odataPropertyContent->properties[] = $odataProperty;
            } elseif (ResourcePropertyKind::COMPLEX_TYPE == $propertyTypeKind) {
                $complexResourceType = $resourceProperty->getResourceType();
                $this->writeComplexValue(
                    $propertyValue,
                    $propertyName,
                    $complexResourceType,
                    $relativeUri . '/' . $propertyName,
                    $odataPropertyContent
                );
            } else {
                //unexpected
                assert(false, '$propertyTypeKind != Primitive or Bag or ComplexType');
            }
        }

        return [$odataPropertyContent, $navigationProperties];
    }
}
