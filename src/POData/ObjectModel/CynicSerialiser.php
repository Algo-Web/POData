<?php

declare(strict_types=1);

namespace POData\ObjectModel;

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
use ReflectionException;

/**
 * Class CynicSerialiser.
 * @package POData\ObjectModel
 */
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

    /**
     * Update time to insert into ODataEntry/ODataFeed fields.
     * @var \DateTime;
     */
    private $updated;

    /**
     * Has base URI already been written out during serialisation?
     * @var bool;
     */
    private $isBaseWritten = false;

    /**
     * @param  IService                $service Reference to the data service instance
     * @param  RequestDescription|null $request Type instance describing the client submitted request
     * @throws \Exception
     */
    public function __construct(IService $service, RequestDescription $request = null)
    {
        $this->service                       = $service;
        $this->request                       = $request;
        $this->absoluteServiceUri            = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash   = rtrim($this->absoluteServiceUri, '/') . '/';
        $this->stack                         = new SegmentStack($request);
        $this->complexTypeInstanceCollection = [];
        $this->updated                       = DateTime::now();
    }

    /**
     * Write top level feed element.
     *
     * @param QueryResult &$entryObjects Results property contains array of entry resources to be written
     *
     * @throws ODataException
     * @throws InvalidOperationException
     * @return ODataFeed
     */
    public function writeTopLevelElements(QueryResult &$entryObjects)
    {
        $res = $entryObjects->results;
        if (!(is_array($res))) {
            throw new InvalidOperationException('!is_array($entryObjects->results)');
        }

        if (is_array($res) && 0 == count($entryObjects->results)) {
            $entryObjects->hasMore = false;
        }

        $this->loadStackIfEmpty();
        $setName = $this->getRequest()->getTargetResourceSetWrapper()->getName();

        $title       = $this->getRequest()->getContainerName();
        $relativeUri = $this->getRequest()->getIdentifier();
        $absoluteUri = $this->getRequest()->getRequestUrl()->getUrlAsString();

        $selfLink        = new ODataLink('self', $title, null, $relativeUri);

        $odata               = new ODataFeed();
        $odata->title        = new ODataTitle($title);
        $odata->id           = $absoluteUri;
        $odata->setSelfLink($selfLink);
        $odata->updated      = $this->getUpdated()->format(DATE_ATOM);
        $odata->baseURI      = $this->isBaseWritten ? null : $this->absoluteServiceUriWithSlash;
        $this->isBaseWritten = true;

        if ($this->getRequest()->queryType == QueryType::ENTITIES_WITH_COUNT()) {
            $odata->rowCount = $this->getRequest()->getCountValue();
        }
        foreach ($res as $entry) {
            if (!$entry instanceof QueryResult) {
                $query          = new QueryResult();
                $query->results = $entry;
            } else {
                $query = $entry;
            }
            $odata->entries[] = $this->writeTopLevelElement($query);
        }

        $resourceSet = $this->getRequest()->getTargetResourceSetWrapper()->getResourceSet();
        $requestTop  = $this->getRequest()->getTopOptionCount();
        $pageSize    = $this->getService()->getConfiguration()->getEntitySetPageSize($resourceSet);
        $requestTop  = (null === $requestTop) ? $pageSize + 1 : $requestTop;

        if (true === $entryObjects->hasMore && $requestTop > $pageSize) {
            $stackSegment        = $setName;
            $lastObject          = end($entryObjects->results);
            $segment             = $this->getNextLinkUri($lastObject);
            $nextLink            = new ODataLink(
                ODataConstants::ATOM_LINK_NEXT_ATTRIBUTE_STRING,
                null,
                null,
                rtrim($this->absoluteServiceUri, '/') . '/' . $stackSegment . $segment
            );
            $odata->nextPageLink = $nextLink;
        }

        return $odata;
    }

    /**
     * Load processing stack if it's currently empty.
     *
     * @return void
     */
    private function loadStackIfEmpty()
    {
        if (0 == count($this->lightStack)) {
            $typeName = $this->getRequest()->getTargetResourceType()->getName();
            array_push($this->lightStack, ['type' => $typeName, 'property' => $typeName, 'count' => 1]);
        }
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
     * @param RequestDescription $request
     */
    public function setRequest(RequestDescription $request)
    {
        $this->request = $request;
        $this->stack->setRequest($request);
    }

    /**
     * Get update timestamp.
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Write a top level entry resource.
     *
     * @param QueryResult $entryObject Results property contains reference to the entry object to be written
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return ODataEntry|null
     */
    public function writeTopLevelElement(QueryResult $entryObject)
    {
        if (!isset($entryObject->results)) {
            array_pop($this->lightStack);

            return null;
        }

        assert(is_object($entryObject->results));
        $this->loadStackIfEmpty();

        $baseURI             = $this->isBaseWritten ? null : $this->absoluteServiceUriWithSlash;
        $this->isBaseWritten = true;

        $stackCount   = count($this->lightStack);
        $topOfStack   = $this->lightStack[$stackCount - 1];
        $resourceType = $this->getService()->getProvidersWrapper()->resolveResourceType($topOfStack['type']);
        assert($resourceType instanceof ResourceType, get_class($resourceType));
        $rawProp    = $resourceType->getAllProperties();
        $relProp    = [];
        $nonRelProp = [];
        $last       = end($this->lightStack);
        $projNodes  = ($last['type'] == $last['property']) ? $this->getProjectionNodes() : null;

        foreach ($rawProp as $prop) {
            $propName = $prop->getName();
            if ($prop->getResourceType() instanceof ResourceEntityType) {
                $relProp[$propName] = $prop;
            } else {
                $nonRelProp[$propName] = $prop;
            }
        }
        $rawCount    = count($rawProp);
        $relCount    = count($relProp);
        $nonRelCount = count($nonRelProp);
        assert(
            $rawCount == $relCount + $nonRelCount,
            'Raw property count ' . $rawCount . ', does not equal sum of relProp count, ' . $relCount
            . ', and nonRelPropCount,' . $nonRelCount
        );

        // now mask off against projNodes
        if (null !== $projNodes) {
            $keys = [];
            foreach ($projNodes as $node) {
                $keys[$node->getPropertyName()] = '';
            }

            $relProp    = array_intersect_key($relProp, $keys);
            $nonRelProp = array_intersect_key($nonRelProp, $keys);
        }

        $resourceSet = $resourceType->getCustomState();
        assert($resourceSet instanceof ResourceSet);
        $title = $resourceType->getName();
        $type  = $resourceType->getFullName();

        $relativeUri = $this->getEntryInstanceKey(
            $entryObject->results,
            $resourceType,
            $resourceSet->getName()
        );
        $absoluteUri = rtrim(strval($this->absoluteServiceUri), '/') . '/' . $relativeUri;

        list($mediaLink, $mediaLinks) = $this->writeMediaData(
            $entryObject->results,
            $type,
            $relativeUri,
            $resourceType
        );

        $propertyContent = $this->writeProperties($entryObject->results, $nonRelProp);

        $links = [];
        foreach ($relProp as $prop) {
            $propKind = $prop->getKind();

            assert(
                ResourcePropertyKind::RESOURCESET_REFERENCE() == $propKind
                || ResourcePropertyKind::RESOURCE_REFERENCE() == $propKind,
                '$propKind != ResourcePropertyKind::RESOURCESET_REFERENCE &&'
                . ' $propKind != ResourcePropertyKind::RESOURCE_REFERENCE'
            );
            $propTail             = ResourcePropertyKind::RESOURCE_REFERENCE() == $propKind ? 'entry' : 'feed';
            $propType             = 'application/atom+xml;type=' . $propTail;
            $propName             = $prop->getName();
            $nuLink               = new ODataLink(
                ODataConstants::ODATA_RELATED_NAMESPACE . $propName,
                $propName,
                $propType,
                $relativeUri . '/' . $propName,
                'feed' === $propTail
            );


            $shouldExpand = $this->shouldExpandSegment($propName);

            $navProp = new ODataNavigationPropertyInfo($prop, $shouldExpand);
            if ($navProp->expanded) {
                $this->expandNavigationProperty($entryObject, $prop, $nuLink, $propKind, $propName);
            }
            $nuLink->setIsExpanded(null !== $nuLink->getExpandedResult() && null !== $nuLink->getExpandedResult()->getData());
            assert(null !== $nuLink->isCollection());

            $links[] = $nuLink;
        }

        $odata                   = new ODataEntry();
        $odata->resourceSetName  = $resourceSet->getName();
        $odata->id               = $absoluteUri;
        $odata->title            = new ODataTitle($title);
        $odata->type             = new ODataCategory($type);
        $odata->propertyContent  = $propertyContent;
        $odata->isMediaLinkEntry = true === $resourceType->isMediaLinkEntry() ? true : null;
        $odata->editLink         = new ODataLink('edit', $title, null, $relativeUri);
        $odata->mediaLink        = $mediaLink;
        $odata->mediaLinks       = $mediaLinks;
        $odata->links            = $links;
        $odata->updated          = $this->getUpdated()->format(DATE_ATOM);
        $odata->baseURI          = $baseURI;

        $newCount = count($this->lightStack);
        assert(
            $newCount == $stackCount,
            'Should have ' . $stackCount . 'elements in stack, have ' . $newCount . 'elements'
        );
        --$this->lightStack[$newCount - 1]['count'];
        if (0 == $this->lightStack[$newCount - 1]['count']) {
            array_pop($this->lightStack);
        }

        return $odata;
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
     * @param IService $service
     */
    public function setService(IService $service)
    {
        $this->service                     = $service;
        $this->absoluteServiceUri          = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash = rtrim($this->absoluteServiceUri, '/') . '/';
    }

    /**
     * Gets collection of projection nodes under the current node.
     *
     * @throws InvalidOperationException
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
     * @throws InvalidOperationException
     * @return RootProjectionNode|ExpandedProjectionNode|null
     */
    protected function getCurrentExpandedProjectionNode()
    {
        /** @var RootProjectionNode|null $expandedProjectionNode */
        $expandedProjectionNode = $this->getRequest()->getRootProjectionNode();
        if (null === $expandedProjectionNode) {
            return null;
        } else {
            $segmentNames = $this->getStack()->getSegmentNames();
            $depth        = count($segmentNames);
            // $depth == 1 means serialization of root entry
            //(the resource identified by resource path) is going on,
            //so control won't get into the below for loop.
            //we will directly return the root node,
            //which is 'ExpandedProjectionNode'
            // for resource identified by resource path.
            if (0 != $depth) {
                for ($i = 1; $i < $depth; ++$i) {
                    $expandedProjectionNode = $expandedProjectionNode->findNode($segmentNames[$i]);
                    if (null === $expandedProjectionNode) {
                        throw new InvalidOperationException('is_null($expandedProjectionNode)');
                    }
                    if (!$expandedProjectionNode instanceof ExpandedProjectionNode) {
                        $msg = '$expandedProjectionNode not instanceof ExpandedProjectionNode';
                        throw new InvalidOperationException($msg);
                    }
                }
            }
        }

        return $expandedProjectionNode;
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
     * @param  object              $entityInstance
     * @param  ResourceType        $resourceType
     * @param  string              $containerName
     * @throws ReflectionException
     * @throws ODataException
     * @return string
     */
    protected function getEntryInstanceKey($entityInstance, ResourceType $resourceType, $containerName)
    {
        assert(is_object($entityInstance));
        $typeName      = $resourceType->getName();
        $keyProperties = $resourceType->getKeyProperties();
        assert(0 != count($keyProperties), 'count($keyProperties) == 0');
        $keyString = $containerName . '(';
        $comma     = null;
        foreach ($keyProperties as $keyName => $resourceProperty) {
            $keyType = $resourceProperty->getInstanceType();
            assert($keyType instanceof IType, '$keyType not instanceof IType');
            $keyName  = $resourceProperty->getName();
            $keyValue = $entityInstance->{$keyName};
            if (!isset($keyValue)) {
                $msg = Messages::badQueryNullKeysAreNotSupported($typeName, $keyName);
                throw ODataException::createInternalServerError($msg);
            }

            $keyValue = $keyType->convertToOData(strval($keyValue));
            $keyString .= $comma . $keyName . '=' . $keyValue;
            $comma = ',';
        }

        $keyString .= ')';

        return $keyString;
    }

    /**
     * @param $entryObject
     * @param $type
     * @param $relativeUri
     * @param $resourceType
     *
     * @return array<ODataMediaLink|array|null>
     */
    protected function writeMediaData($entryObject, $type, $relativeUri, ResourceType $resourceType)
    {
        $context               = $this->getService()->getOperationContext();
        $streamProviderWrapper = $this->getService()->getStreamProviderWrapper();
        assert(null != $streamProviderWrapper, 'Retrieved stream provider must not be null');

        $mediaLink = null;
        if ($resourceType->isMediaLinkEntry()) {
            $eTag      = $streamProviderWrapper->getStreamETag2($entryObject, $context, null);
            $mediaLink = new ODataMediaLink($type, '/$value', $relativeUri . '/$value', '*/*', $eTag, 'edit-media');
        }
        $mediaLinks = [];
        if ($resourceType->hasNamedStream()) {
            $namedStreams = $resourceType->getAllNamedStreams();
            foreach ($namedStreams as $streamTitle => $resourceStreamInfo) {
                $readUri = $streamProviderWrapper->getReadStreamUri2(
                    $entryObject,
                    $context,
                    $resourceStreamInfo,
                    $relativeUri
                );
                $mediaContentType = $streamProviderWrapper->getStreamContentType2(
                    $entryObject,
                    $context,
                    $resourceStreamInfo
                );
                $eTag = $streamProviderWrapper->getStreamETag2(
                    $entryObject,
                    $context,
                    $resourceStreamInfo
                );

                $nuLink       = new ODataMediaLink($streamTitle, $readUri, $readUri, $mediaContentType, $eTag);
                $mediaLinks[] = $nuLink;
            }
        }

        return [$mediaLink, $mediaLinks];
    }

    /**
     * @param $entryObject
     * @param array<string, ResourceProperty> $nonRelProp
     *
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return ODataPropertyContent
     */
    private function writeProperties($entryObject, $nonRelProp)
    {
        $properties = [];
        foreach ($nonRelProp as $corn => $flake) {
            /** @var ResourceType $resource */
            $resource = $nonRelProp[$corn]->getResourceType();
            if ($resource instanceof ResourceEntityType) {
                continue;
            }
            $result            = $entryObject->{$corn};
            $isBag             = $flake->isKindOf(ResourcePropertyKind::BAG());
            $typePrepend       = $isBag ? 'Collection(' : '';
            $typeAppend        = $isBag ? ')' : '';
            $nonNull           = null !== $result;
            $name     = strval($corn);
            $typeName = $typePrepend . $resource->getFullName() . $typeAppend;
            $value = null;
            if ($nonNull && is_array($result)) {
                $value = $this->writeBagValue($resource, $result);
            } elseif ($resource instanceof ResourcePrimitiveType && $nonNull) {
                $rType = $resource->getInstanceType();
                if (!$rType instanceof IType) {
                    throw new InvalidOperationException(get_class($rType));
                }
                $value = $this->primitiveToString($rType, $result);
            } elseif ($resource instanceof ResourceComplexType && $nonNull) {
                $value = $this->writeComplexValue($resource, $result, $flake->getName());
            }
            $properties[$corn] = new ODataProperty($name, $typeName, $value);
        }

        return new ODataPropertyContent($properties);
    }

    /**
     * @param ResourceType $resourceType
     * @param $result
     *
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return ODataBagContent|null
     */
    protected function writeBagValue(ResourceType &$resourceType, $result)
    {
        $bagNullOrArray = null === $result || is_array($result);
        if (!$bagNullOrArray) {
            throw new InvalidOperationException('Bag parameter must be null or array');
        }
        $typeKind               = $resourceType->getResourceTypeKind();
        $typePrimitiveOrComplex = ResourceTypeKind::PRIMITIVE() == $typeKind
            || ResourceTypeKind::COMPLEX() == $typeKind;
        if (!$typePrimitiveOrComplex) {
            throw new InvalidOperationException('$bagItemResourceTypeKind != ResourceTypeKind::PRIMITIVE'
                . ' && $bagItemResourceTypeKind != ResourceTypeKind::COMPLEX');
        }
        if (null == $result) {
            return null;
        }
        $bag = new ODataBagContent();
        foreach ($result as $value) {
            if (isset($value)) {
                if (ResourceTypeKind::PRIMITIVE() == $typeKind) {
                    $instance = $resourceType->getInstanceType();
                    if (!$instance instanceof IType) {
                        throw new InvalidOperationException(get_class($instance));
                    }
                    $bag->addPropertyContent($this->primitiveToString($instance, $value));
                } elseif (ResourceTypeKind::COMPLEX() == $typeKind) {
                    $bag->addPropertyContent($this->writeComplexValue($resourceType, $value));
                }
            }
        }

        return $bag;
    }

    /**
     * Convert the given primitive value to string.
     * Note: This method will not handle null primitive value.
     *
     * @param IType &$type          Type of the primitive property
     *                              whose value need to be converted
     * @param mixed $primitiveValue Primitive value to convert
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
            $stringValue = mb_convert_encoding(strval($primitiveValue), 'UTF-8');
        } else {
            $stringValue = strval($primitiveValue);
        }

        return $stringValue;
    }

    /**
     * @param ResourceType $resourceType
     * @param object       $result
     * @param string|null  $propertyName
     *
     * @throws ReflectionException
     * @throws InvalidOperationException
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

        $properties         = [];
        $resourceProperties = $resourceType->getAllProperties();
        // first up, handle primitive properties
        foreach ($resourceProperties as $prop) {
            $resourceKind           = $prop->getKind();
            $propName               = $prop->getName();
            $name = $propName;
            $typeName = null;
            $value = null;
            $raw                    = $result->{$propName};
            if (static::isMatchPrimitive($resourceKind)) {
                $iType = $prop->getInstanceType();
                if (!$iType instanceof IType) {
                    throw new InvalidOperationException(get_class($iType));
                }

                $typeName = $iType->getFullTypeName();

                $rType = $prop->getResourceType()->getInstanceType();
                if (!$rType instanceof IType) {
                    throw new InvalidOperationException(get_class($rType));
                }
                if (null !== $raw) {
                    $value = $this->primitiveToString($rType, $raw);
                }
            } elseif (ResourcePropertyKind::COMPLEX_TYPE() == $resourceKind) {
                $rType                      = $prop->getResourceType();
                $typeName = $rType->getFullName();
                if (null !== $raw) {
                    $value = $this->writeComplexValue($rType, $raw, $propName);
                }
            }
            $properties[$propName] = new ODataProperty($name, $typeName, $value);
        }

        unset($this->complexTypeInstanceCollection[$count]);

        return new ODataPropertyContent($properties);
    }

    /**
     * Is the supplied resourceKind representing a primitive value?
     *
     * @param  int|ResourcePropertyKind $resourceKind
     * @return bool
     */
    public static function isMatchPrimitive($resourceKind): bool
    {
        $value = $resourceKind instanceof ResourcePropertyKind ? $resourceKind->getValue() : $resourceKind;
        if (16 > $value) {
            return false;
        }
        if (28 < $value) {
            return false;
        }

        return 0 == ($value % 4);
    }

    /**
     * Check whether to expand a navigation property or not.
     *
     * @param string $navigationPropertyName Name of navigation property in question
     *
     * @throws InvalidOperationException
     * @return bool                      True if the given navigation should be expanded, otherwise false
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

    /**
     * @param  QueryResult               $entryObject
     * @param  ResourceProperty          $prop
     * @param  ODataLink                 $nuLink
     * @param  ResourcePropertyKind      $propKind
     * @param  string                    $propName
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws ReflectionException
     */
    private function expandNavigationProperty(
        QueryResult $entryObject,
        ResourceProperty $prop,
        ODataLink $nuLink,
        ResourcePropertyKind $propKind,
        string $propName
    ) {
        $nextName             = $prop->getResourceType()->getName();
        $nuLink->setIsExpanded(true);
        $value                = $entryObject->results->{$propName};
        $isCollection         = ResourcePropertyKind::RESOURCESET_REFERENCE() == $propKind;
        $nuLink->setIsCollection($isCollection);
        $nullResult           = null === $value;
        $object               = (is_object($value));
        $resultCount          = ($nullResult) ? 0 : ($object ? 1 : count($value));

        if (0 < $resultCount) {
            $result          = new QueryResult();
            $result->results = $value;
            if (!$nullResult) {
                $newStackLine = ['type' => $nextName, 'property' => $propName, 'count' => $resultCount];
                array_push($this->lightStack, $newStackLine);
                if (isset($value)) {
                    if (!$isCollection) {
                        $nuLink->setType('application/atom+xml;type=entry');
                        $expandedResult = $this->writeTopLevelElement($result);
                    } else {
                        $nuLink->setType('application/atom+xml;type=feed');
                        $expandedResult = $this->writeTopLevelElements($result);
                    }
                    $nuLink->setExpandedResult(new ODataExpandedResult($expandedResult));
                }
            }
        } else {
            $type = $this->getService()->getProvidersWrapper()->resolveResourceType($nextName);
            if (!$isCollection) {
                $result                  = new ODataEntry();
                $result->resourceSetName = $type->getName();
            } else {
                $result                 = new ODataFeed();
                $result->setSelfLink(new ODataLink(ODataConstants::ATOM_SELF_RELATION_ATTRIBUTE_VALUE));
            }
            $nuLink->setExpandedResult(new ODataExpandedResult($result));
        }
        if (null !== $nuLink->getExpandedResult() && null !== $nuLink->getExpandedResult()->getData() && null !== $nuLink->getExpandedResult()->getData()->getSelfLink()) {
            $nuLink->getExpandedResult()->getData()->getSelfLink()->setTitle($propName);
            $nuLink->getExpandedResult()->getData()->getSelfLink()->setUrl($nuLink->getUrl());
            $nuLink->getExpandedResult()->getData()->title           = new ODataTitle($propName);
            $nuLink->getExpandedResult()->getData()->id              = rtrim($this->absoluteServiceUri, '/') . '/' . $nuLink->getUrl();
        }
    }

    /**
     * Get next page link from the given entity instance.
     *
     * @param mixed &$lastObject Last object serialized to be
     *                           used for generating $skiptoken
     *
     * @throws ODataException
     * @throws InvalidOperationException
     * @return string                    for the link for next page
     */
    protected function getNextLinkUri($lastObject)
    {
        $currentExpandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        $internalOrderByInfo           = $currentExpandedProjectionNode->getInternalOrderByInfo();
        assert(null !== $internalOrderByInfo);
        assert(is_object($internalOrderByInfo));
        assert($internalOrderByInfo instanceof InternalOrderByInfo, get_class($internalOrderByInfo));
        $numSegments          = count($internalOrderByInfo->getOrderByPathSegments());
        $queryParameterString = $this->getNextPageLinkQueryParametersForRootResourceSet();

        $skipToken = $internalOrderByInfo->buildSkipTokenValue($lastObject);
        assert(null !== $skipToken, '!is_null($skipToken)');
        $token     = (1 < $numSegments) ? '$skiptoken=' : '$skip=';
        $skipToken = '?' . $queryParameterString . $token . $skipToken;

        return $skipToken;
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
            ODataConstants::HTTPQUERY_STRING_SELECT,] as $queryOption) {
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
     * Write top level url collection.
     *
     * @param QueryResult $entryObjects Results property contains the array of entry resources whose urls are
     *                                  to be written
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return ODataURLCollection
     */
    public function writeUrlElements(QueryResult $entryObjects)
    {
        $urls         = [];
        $count        = null;
        $nextPageLink = null;
        if (!empty($entryObjects->results)) {
            $i = 0;
            foreach ($entryObjects->results as $entryObject) {
                if (!$entryObject instanceof QueryResult) {
                    $query          = new QueryResult();
                    $query->results = $entryObject;
                } else {
                    $query = $entryObject;
                }
                $urls[$i] = $this->writeUrlElement($query);
                ++$i;
            }

            if ($i > 0 && true === $entryObjects->hasMore) {
                $stackSegment       = $this->getRequest()->getTargetResourceSetWrapper()->getName();
                $lastObject         = end($entryObjects->results);
                $segment            = $this->getNextLinkUri($lastObject);
                $nextLink           = new ODataLink(
                    ODataConstants::ATOM_LINK_NEXT_ATTRIBUTE_STRING,
                    null,
                    null,
                    ltrim(rtrim(strval($this->absoluteServiceUri), '/') . '/' . $stackSegment . $segment, '/')
                );
                $nextPageLink = $nextLink;
            }
        }

        if ($this->getRequest()->queryType == QueryType::ENTITIES_WITH_COUNT()) {
            $count = $this->getRequest()->getCountValue();
        }

        return new ODataURLCollection($urls, $nextPageLink, $count);
    }

    /**
     * Write top level url element.
     *
     * @param QueryResult $entryObject Results property contains the entry resource whose url to be written
     *
     * @throws ReflectionException
     * @throws ODataException
     * @return ODataURL
     */
    public function writeUrlElement(QueryResult $entryObject)
    {
        $url = null;

        /** @var object|null $results */
        $results = $entryObject->results;
        if (null !== $results) {
            $currentResourceType = $this->getCurrentResourceSetWrapper()->getResourceType();
            $relativeUri         = $this->getEntryInstanceKey(
                $results,
                $currentResourceType,
                $this->getCurrentResourceSetWrapper()->getName()
            );

            $url = new ODataURL(rtrim(strval($this->absoluteServiceUri), '/') . '/' . $relativeUri);
        }

        return $url;
    }

    /**
     * Resource set wrapper for the resource being serialized.
     *
     * @return ResourceSetWrapper
     */
    protected function getCurrentResourceSetWrapper()
    {
        $segmentWrappers = $this->getStack()->getSegmentWrappers();
        $count           = count($segmentWrappers);

        return 0 == $count ? $this->getRequest()->getTargetResourceSetWrapper() : $segmentWrappers[$count - 1];
    }

    /**
     * Write top level complex resource.
     *
     * @param QueryResult  &$complexValue Results property contains the complex object to be written
     * @param string       $propertyName  The name of the complex property
     * @param ResourceType &$resourceType Describes the type of complex object
     *
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return ODataPropertyContent
     */
    public function writeTopLevelComplexObject(QueryResult &$complexValue, $propertyName, ResourceType &$resourceType)
    {
        $result = $complexValue->results;

        $name     = $propertyName;
        $typeName = $resourceType->getFullName();
        $value = null;
        if (null !== $result) {
            if (!is_object($result)) {
                throw new InvalidOperationException('Supplied $customObject must be an object');
            }
            $internalContent      = $this->writeComplexValue($resourceType, $result);
            $value = $internalContent;
        }

        return new ODataPropertyContent(
            [
                $propertyName => new ODataProperty($name, $typeName, $value)
            ]
        );
    }

    /**
     * Write top level bag resource.
     *
     * @param QueryResult  $bagValue
     * @param string       $propertyName  The name of the bag property
     * @param ResourceType &$resourceType Describes the type of bag object
     *
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return ODataPropertyContent
     */
    public function writeTopLevelBagObject(QueryResult &$bagValue, $propertyName, ResourceType &$resourceType)
    {
        $result = $bagValue->results;

        $odataProperty           = new ODataProperty(
            $propertyName,
            'Collection(' . $resourceType->getFullName() . ')',
            $this->writeBagValue($resourceType, $result)
        );

        return new ODataPropertyContent([$propertyName => $odataProperty]);
    }

    /**
     * Write top level primitive value.
     *
     * @param QueryResult      &$primitiveValue   Results property contains the primitive value to be written
     * @param ResourceProperty &$resourceProperty Resource property describing the primitive property to be written
     *
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return ODataPropertyContent
     */
    public function writeTopLevelPrimitive(QueryResult &$primitiveValue, ResourceProperty &$resourceProperty = null)
    {
        if (null === $resourceProperty) {
            throw new InvalidOperationException('Resource property must not be null');
        }
        $name = $resourceProperty->getName();
        $typeName = null;
        $value = null;
        $iType = $resourceProperty->getInstanceType();
        if (!$iType instanceof IType) {
            throw new InvalidOperationException(get_class($iType));
        }
        $typeName = $iType->getFullTypeName();
        if (null !== $primitiveValue->results) {
            $rType = $resourceProperty->getResourceType()->getInstanceType();
            if (!$rType instanceof IType) {
                throw new InvalidOperationException(get_class($rType));
            }
            $value = $this->primitiveToString($rType, $primitiveValue->results);
        }

        return new ODataPropertyContent(
            [
                $name => new ODataProperty($name, $typeName, $value)
            ]
        );
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
        $recursionLevel     = count($this->getStack()->getSegmentNames());
        $pageSize           = $currentResourceSet->getResourceSetPageSize();

        if (1 == $recursionLevel) {
            //presence of $top option affect next link for root container
            $topValueCount = $this->getRequest()->getTopOptionCount();
            if (null !== $topValueCount && ($topValueCount <= $pageSize)) {
                return false;
            }
        }

        return $resultSetCount == $pageSize;
    }
}
