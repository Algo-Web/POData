<?php

namespace UnitTests\POData\ObjectModel;

use POData\IService;
use POData\ObjectModel\ObjectModelSerializerBase;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\SegmentStack;

class ObjectModelSerializerDummy extends ObjectModelSerializerBase
{
    /**
     * Creates new instance of ObjectModelSerializerTest.
     *
     * @param IService           $service
     * @param RequestDescription $request the  request submitted by the client
     */
    public function __construct(IService $service, RequestDescription $request)
    {
        parent::__construct($service, $request);
    }

    public function setStack(SegmentStack $stack)
    {
        $this->stack = $stack;
    }

    public function getEntryInstanceKey($entityInstance, ResourceType $resourceType, $containerName)
    {
        return parent::getEntryInstanceKey($entityInstance, $resourceType, $containerName);
    }

    public function getCurrentResourceSetWrapper()
    {
        return parent::getCurrentResourceSetWrapper();
    }

    public function isRootResourceSet()
    {
        return parent::isRootResourceSet();
    }

    public function getPropertyValue($entity, ResourceType $resourceType, ResourceProperty $resourceProperty)
    {
        return parent::getPropertyValue($entity, $resourceType, $resourceProperty);
    }

    public function getCurrentExpandedProjectionNode()
    {
        return parent::getCurrentExpandedProjectionNode();
    }

    public function getETagForEntry($entryObject, ResourceType $resourceType)
    {
        return parent::getETagForEntry($entryObject, $resourceType);
    }

    public function pushSegmentForNavigationProperty(ResourceProperty &$resourceProperty)
    {
        return parent::pushSegmentForNavigationProperty($resourceProperty);
    }

    public function shouldExpandSegment($navigationPropertyName)
    {
        return parent::shouldExpandSegment($navigationPropertyName);
    }

    public function getNextPageLinkQueryParametersForRootResourceSet()
    {
        return parent::getNextPageLinkQueryParametersForRootResourceSet();
    }

    public function getNextPageLinkQueryParametersForExpandedResourceSet()
    {
        return parent::getNextPageLinkQueryParametersForExpandedResourceSet();
    }

    public function getNextLinkUri(&$lastObject, $absoluteUri)
    {
        return parent::getNextLinkUri($lastObject, $absoluteUri);
    }

    public function needNextPageLink($resultSetCount)
    {
        return parent::needNextPageLink($resultSetCount);
    }
}
