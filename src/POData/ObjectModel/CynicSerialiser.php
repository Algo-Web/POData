<?php

namespace POData\ObjectModel;

use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
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
     * @param IService                  $service    Reference to the data service instance
     * @param RequestDescription|null   $request    Type instance describing the client submitted request
     */
    public function __construct(IService $service, RequestDescription $request = null)
    {
        $this->service = $service;
        $this->request = $request;
        $this->absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash = rtrim($this->absoluteServiceUri, '/') . '/';
        $this->stack = new SegmentStack($request);
        $this->complexTypeInstanceCollection = [];
    }

    /**
     * Write a top level entry resource.
     *
     * @param QueryResult $entryObject Results property contains reference to the entry object to be written
     *
     * @return ODataEntry
     */
    public function writeTopLevelElement(QueryResult $entryObject)
    {
        // TODO: Implement writeTopLevelElement() method.
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
        // TODO: Implement writeTopLevelElements() method.
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
     * @param QueryResult &$complexValue Results property contains the complex object to be written
     * @param string $propertyName The name of the complex property
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
            $internalContent = $this->writeComplexValue($resourceType, $result);
            $odataProperty->value = $internalContent;
        }

        $propertyContent->properties[] = $odataProperty;

        return $propertyContent;
    }

    /**
     * Write top level bag resource.
     *
     * @param  QueryResult $bagValue
     * @param  string $propertyName The name of the bag property
     * @param  ResourceType &$resourceType Describes the type of bag object
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
     * @param QueryResult &$primitiveValue Results property contains the primitive value to be written
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
     * @param RequestDescription $request
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
     * @param IService $service
     * @return IService
     */
    public function setService(IService $service)
    {
        // TODO: Implement setService() method.
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
     * @param ResourceType $resourceType
     * @param $result
     * @return ODataBagContent|null
     */
    protected function writeBagValue(ResourceType &$resourceType, $result)
    {
        assert(null == $result || is_array($result), 'Bag parameter must be null or array');
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
     * @param ResourceType  $resourceType
     * @param object        $result
     * @param string|null   $propertyName
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
            if (static::isMatchPrimitive($resourceKind)) {
                $iType = $prop->getInstanceType();
                assert($iType instanceof IType, get_class($iType));
                $internalProperty->typeName = $iType->getFullTypeName();

                $rType = $prop->getResourceType()->getInstanceType();
                assert($rType instanceof IType, get_class($rType));
                $internalProperty->value = $this->primitiveToString($rType, $result->$propName);

                $internalContent->properties[] = $internalProperty;
            } elseif (ResourcePropertyKind::COMPLEX_TYPE == $resourceKind) {
                $rType = $prop->getResourceType();
                $internalProperty->typeName = $rType->getFullName();
                $internalProperty->value = $this->writeComplexValue($rType, $result->$propName, $propName);

                $internalContent->properties[] = $internalProperty;
            }
        }

        unset($this->complexTypeInstanceCollection[$count]);
        return $internalContent;
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
     * Get next page link from the given entity instance.
     *
     * @param mixed  &$lastObject Last object serialized to be
     *                            used for generating $skiptoken
     *
     * @return string for the link for next page
     */
    protected function getNextLinkUri(&$lastObject)
    {
        $currentExpandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        $internalOrderByInfo = $currentExpandedProjectionNode->getInternalOrderByInfo();
        assert(null != $internalOrderByInfo);
        assert(is_object($internalOrderByInfo));
        assert($internalOrderByInfo instanceof InternalOrderByInfo, get_class($internalOrderByInfo));
        $numSegments = count($internalOrderByInfo->getOrderByPathSegments());
        $queryParameterString = $this->getNextPageLinkQueryParametersForRootResourceSet();

        $skipToken = $internalOrderByInfo->buildSkipTokenValue($lastObject);
        assert(!is_null($skipToken), '!is_null($skipToken)');
        $token = (1 < $numSegments) ? '$skiptoken=' : '$skip=';
        $skipToken = '?'.$queryParameterString.$token.$skipToken;

        return $skipToken;
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
        if (is_null($expandedProjectionNode) || $expandedProjectionNode->canSelectAllProperties()) {
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
            if (0 != $depth) {
                for ($i = 1; $i < $depth; ++$i) {
                    $expandedProjectionNode = $expandedProjectionNode->findNode($segmentNames[$i]);
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
            if (0 < $remainingCount) {
                if (!is_null($queryParameterString)) {
                    $queryParameterString .= '&';
                }

                $queryParameterString .= ODataConstants::HTTPQUERY_STRING_TOP . '=' . $remainingCount;
            }
        }

        if (!is_null($queryParameterString)) {
            $queryParameterString .= '&';
        }

        return $queryParameterString;
    }

    /**
     * Convert the given primitive value to string.
     * Note: This method will not handle null primitive value.
     *
     * @param IType &$primitiveResourceType        Type of the primitive property
     *                                             whose value need to be converted
     * @param mixed        $primitiveValue         Primitive value to convert
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
