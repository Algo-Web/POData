<?php

namespace POData\UriProcessor;

use POData\Common\InvalidOperationException;
use POData\IService;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;

class RequestExpander
{
    /**
     * Description of the OData request that a client has submitted.
     *
     * @var RequestDescription
     */
    private $request;

    /**
     * Holds reference to the data service instance.
     *
     * @var IService
     */
    private $service;

    /**
     * Holds reference to the wrapper over IDSMP and IDSQP implementation.
     *
     * @var ProvidersWrapper
     */
    private $providers;

    /**
     * Holds reference to segment stack being processed.
     *
     * @var SegmentStack
     */
    private $stack;

    public function __construct(RequestDescription $request, IService $service, ProvidersWrapper $wrapper)
    {
        $this->request = $request;
        $this->service = $service;
        $this->providers = $wrapper;
        $this->stack = new SegmentStack($request);
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
     * Gets reference to the request submitted by client.
     *
     * @return ProvidersWrapper
     */
    public function getProviders()
    {
        return $this->providers;
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
     * Gets the segment stack instance.
     *
     * @return SegmentStack
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * Perform expansion.
     *
     * @return void
     */
    public function handleExpansion()
    {
        $node = $this->getRequest()->getRootProjectionNode();
        if (null !== $node && $node->isExpansionSpecified()) {
            $result = $this->getRequest()->getTargetResult();
            if (null !== $result && (!is_array($result) || !empty($result))) {
                $needPop = $this->pushSegmentForRoot();
                $this->executeExpansion($result);
                $this->popSegment(true === $needPop);
            }
        }
    }

    /**
     * Execute queries for expansion.
     *
     * @param array|mixed $result Resource(s) whose navigation properties needs to be expanded
     */
    private function executeExpansion($result)
    {
        if ($result instanceof QueryResult) {
            $result = $result->results;
        }

        $originalIsArray = is_array($result);

        if (!$originalIsArray) {
            $result = [$result];
        }

        $expandedProjectionNodes = $this->getExpandedProjectionNodes();
        foreach ($expandedProjectionNodes as $expandedProjectionNode) {
            $resourceType = $expandedProjectionNode->getResourceType();
            $isCollection = ResourcePropertyKind::RESOURCESET_REFERENCE
                            == $expandedProjectionNode->getResourceProperty()->getKind();
            $expandedPropertyName = $expandedProjectionNode->getResourceProperty()->getName();

            foreach ($result as $entry) {
                if ($isCollection) {
                    $result1 = $this->executeCollectionExpansionGetRelated($expandedProjectionNode, $entry);
                    if (!empty($result1)) {
                        $this->executeCollectionExpansionProcessExpansion(
                            $entry,
                            $result1,
                            $expandedProjectionNode,
                            $resourceType,
                            $expandedPropertyName
                        );
                    } else {
                        $resultSet = $originalIsArray ? [] : $result1;
                        $resourceType->setPropertyValue($entry, $expandedPropertyName, $resultSet);
                    }
                } else {
                    $this->executeSingleExpansionGetRelated(
                        $expandedProjectionNode,
                        $entry,
                        $resourceType,
                        $expandedPropertyName
                    );
                }
            }
        }
    }

    /**
     * Resource set wrapper for the resource being retrieved.
     *
     * @return ResourceSetWrapper
     */
    private function getCurrentResourceSetWrapper()
    {
        $wraps = $this->getStack()->getSegmentWrappers();
        $count = count($wraps);

        return 0 == $count ? $this->getRequest()->getTargetResourceSetWrapper() : $wraps[$count - 1];
    }

    /**
     * Pushes a segment for the root of the tree
     * Note: Calls to this method should be balanced with calls to popSegment.
     *
     * @return bool true if the segment was pushed, false otherwise
     */
    private function pushSegmentForRoot()
    {
        $segmentName = $this->getRequest()->getContainerName();
        $segmentResourceSetWrapper = $this->getRequest()->getTargetResourceSetWrapper();

        return $this->pushSegment($segmentName, $segmentResourceSetWrapper);
    }

    /**
     * Pushes a segment for the current navigation property being written out.
     * Note: Refer 'ObjectModelSerializerNotes.txt' for more details about
     * 'Segment Stack' and this method.
     * Note: Calls to this method should be balanced with calls to popSegment.
     *
     * @param ResourceProperty &$resourceProperty Current navigation property
     *                                            being written out
     *
     * @throws InvalidOperationException If this function invoked with non-navigation
     *                                   property instance
     *
     * @return bool true if a segment was pushed, false otherwise
     */
    private function pushSegmentForNavigationProperty(ResourceProperty &$resourceProperty)
    {
        if ($resourceProperty->getTypeKind() == ResourceTypeKind::ENTITY()) {
            if (empty($this->getStack()->getSegmentNames())) {
                throw new InvalidOperationException('!is_empty($this->getStack()->getSegmentNames())');
            }
            $currentResourceSetWrapper = $this->getCurrentResourceSetWrapper();
            $currentResourceType = $currentResourceSetWrapper->getResourceType();
            $currentResourceSetWrapper = $this->getService()
                ->getProvidersWrapper()
                ->getResourceSetWrapperForNavigationProperty(
                    $currentResourceSetWrapper,
                    $currentResourceType,
                    $resourceProperty
                );

            if (null === $currentResourceSetWrapper) {
                throw new InvalidOperationException('!null($currentResourceSetWrapper)');
            }

            return $this->pushSegment(
                $resourceProperty->getName(),
                $currentResourceSetWrapper
            );
        } else {
            throw new InvalidOperationException(
                'pushSegmentForNavigationProperty should not be called with non-entity type'
            );
        }
    }

    /**
     * Gets collection of expanded projection nodes under the current node.
     *
     * @return ExpandedProjectionNode[] List of nodes describing expansions for the current segment
     */
    protected function getExpandedProjectionNodes()
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        $expandedProjectionNodes = [];
        if (null !== $expandedProjectionNode) {
            foreach ($expandedProjectionNode->getChildNodes() as $node) {
                if ($node instanceof ExpandedProjectionNode) {
                    $expandedProjectionNodes[] = $node;
                }
            }
        }

        return $expandedProjectionNodes;
    }

    /**
     * Find a 'ExpandedProjectionNode' instance in the projection tree
     * which describes the current segment.
     *
     * @return ExpandedProjectionNode|null
     */
    private function getCurrentExpandedProjectionNode()
    {
        $expandedProjectionNode = $this->getRequest()->getRootProjectionNode();
        if (null !== $expandedProjectionNode) {
            $names = $this->getStack()->getSegmentNames();
            $depth = count($names);
            if (0 != $depth) {
                for ($i = 1; $i < $depth; ++$i) {
                    $expandedProjectionNode = $expandedProjectionNode->findNode($names[$i]);
                    if (!$expandedProjectionNode instanceof ExpandedProjectionNode) {
                        $msg = '$expandedProjectionNode instanceof ExpandedProjectionNode';
                        throw new InvalidOperationException($msg);
                    }
                }
            }
        }

        return $expandedProjectionNode;
    }

    /**
     * Pushes information about the segment whose instance is going to be
     * retrieved from the IDSQP implementation
     * Note: Calls to this method should be balanced with calls to popSegment.
     *
     * @param string             $segmentName         Name of segment to push
     * @param ResourceSetWrapper &$resourceSetWrapper The resource set wrapper
     *                                                to push
     *
     * @return bool true if the segment was push, false otherwise
     */
    private function pushSegment($segmentName, ResourceSetWrapper &$resourceSetWrapper)
    {
        return $this->getStack()->pushSegment($segmentName, $resourceSetWrapper);
    }

    /**
     * Pops segment information from the 'Segment Stack'
     * Note: Calls to this method should be balanced with previous calls
     * to _pushSegment.
     *
     * @param bool $needPop Is a pop required. Only true if last push
     *                      was successful
     *
     * @throws InvalidOperationException If found un-balanced call
     *                                   with _pushSegment
     */
    private function popSegment($needPop)
    {
        $this->getStack()->popSegment($needPop);
    }

    /**
     * @param ExpandedProjectionNode $expandedProjectionNode
     * @param $entry
     *
     * @return object[]|null
     */
    private function executeCollectionExpansionGetRelated($expandedProjectionNode, $entry)
    {
        $currentResourceSet = $this->getCurrentResourceSetWrapper()->getResourceSet();
        $resourceSetOfProjectedProperty = $expandedProjectionNode
            ->getResourceSetWrapper()
            ->getResourceSet();
        $projectedProperty = $expandedProjectionNode->getResourceProperty();
        $result = $this->getProviders()->getRelatedResourceSet(
            QueryType::ENTITIES(), //it's always entities for an expansion
            $currentResourceSet,
            $entry,
            $resourceSetOfProjectedProperty,
            $projectedProperty,
            null, // $filter
            null, // $orderby
            null, // $top
            null  // $skip
        )->results;

        return $result;
    }

    /**
     * @param ExpandedProjectionNode $expandedProjectionNode
     * @param $entry
     * @param \POData\Providers\Metadata\ResourceType $resourceType
     * @param string                                  $expandedPropertyName
     *
     * @throws InvalidOperationException
     * @throws \POData\Common\ODataException
     */
    private function executeSingleExpansionGetRelated(
        $expandedProjectionNode,
        $entry,
        $resourceType,
        $expandedPropertyName
    ) {
        $currentResourceSet = $this->getCurrentResourceSetWrapper()->getResourceSet();
        $resourceSetOfProjectedProperty = $expandedProjectionNode
            ->getResourceSetWrapper()
            ->getResourceSet();
        $projectedProperty = $expandedProjectionNode->getResourceProperty();
        $result = $this->getProviders()->getRelatedResourceReference(
            $currentResourceSet,
            $entry,
            $resourceSetOfProjectedProperty,
            $projectedProperty
        );
        $resourceType->setPropertyValue($entry, $expandedPropertyName, $result);
        if (null !== $result) {
            $this->pushPropertyToNavigation($result, $expandedProjectionNode);
        }
    }

    /**
     * @param $entry
     * @param $result
     * @param ExpandedProjectionNode                  $expandedProjectionNode
     * @param \POData\Providers\Metadata\ResourceType $resourceType
     * @param string                                  $expandedPropertyName
     *
     * @throws InvalidOperationException
     */
    private function executeCollectionExpansionProcessExpansion(
        $entry,
        $result,
        $expandedProjectionNode,
        $resourceType,
        $expandedPropertyName
    ) {
        $internalOrderByInfo = $expandedProjectionNode->getInternalOrderByInfo();
        if (null !== $internalOrderByInfo) {
            $orderByFunction = $internalOrderByInfo->getSorterFunction();
            usort($result, $orderByFunction);
            unset($internalOrderByInfo);
            $takeCount = $expandedProjectionNode->getTakeCount();
            if (null !== $takeCount) {
                $result = array_slice($result, 0, $takeCount);
            }
        }

        $resourceType->setPropertyValue($entry, $expandedPropertyName, $result);
        $this->pushPropertyToNavigation($result, $expandedProjectionNode);
    }

    /**
     * @param $result
     * @param ExpandedProjectionNode $expandedProjectionNode
     *
     * @throws InvalidOperationException
     */
    private function pushPropertyToNavigation($result, $expandedProjectionNode)
    {
        $projectedProperty = $expandedProjectionNode->getResourceProperty();
        $needPop = $this->pushSegmentForNavigationProperty($projectedProperty);
        $this->executeExpansion($result);
        $this->popSegment(true === $needPop);
    }
}
