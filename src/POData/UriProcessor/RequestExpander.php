<?php

namespace POData\UriProcessor;

use POData\Common\InvalidOperationException;
use POData\IService;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
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
     * Holds reference to segment stack being processed
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
     * Perform expansion.
     */
    public function handleExpansion()
    {
        $node = $this->getRequest()->getRootProjectionNode();
        if (!is_null($node) && $node->isExpansionSpecified()) {
            $result = $this->getRequest()->getTargetResult();
            if (!is_null($result) && (!is_array($result) || !empty($result))) {
                $needPop = $this->pushSegmentForRoot();
                $this->executeExpansion($result);
                $this->popSegment($needPop);
            }
        }
    }

    /**
     * Execute queries for expansion.
     *
     * @param array(mixed)/mixed $result Resource(s) whose navigation properties needs to be expanded
     */
    private function executeExpansion($result)
    {
        $expandedProjectionNodes = $this->getExpandedProjectionNodes();
        foreach ($expandedProjectionNodes as $expandedProjectionNode) {
            $resourceType = $expandedProjectionNode->getResourceType();
            $isCollection = ResourcePropertyKind::RESOURCESET_REFERENCE
                            == $expandedProjectionNode->getResourceProperty()->getKind();
            $expandedPropertyName = $expandedProjectionNode->getResourceProperty()->getName();
            if (is_array($result)) {
                foreach ($result as $entry) {
                    // Check for null entry
                    if ($isCollection) {
                        $currentResourceSet = $this->getCurrentResourceSetWrapper()->getResourceSet();
                        $resourceSetOfProjectedProperty = $expandedProjectionNode
                            ->getResourceSetWrapper()
                            ->getResourceSet();
                        $projectedProperty1 = $expandedProjectionNode->getResourceProperty();
                        $result1 = $this->getProviders()->getRelatedResourceSet(
                            QueryType::ENTITIES(), //it's always entities for an expansion
                            $currentResourceSet,
                            $entry,
                            $resourceSetOfProjectedProperty,
                            $projectedProperty1,
                            null, // $filter
                            null, // $orderby
                            null, // $top
                            null  // $skip
                        )->results;
                        if (!empty($result1)) {
                            $internalOrderByInfo = $expandedProjectionNode->getInternalOrderByInfo();
                            if (!is_null($internalOrderByInfo)) {
                                $orderByFunction = $internalOrderByInfo->getSorterFunction()->getReference();
                                usort($result1, $orderByFunction);
                                unset($internalOrderByInfo);
                                $takeCount = $expandedProjectionNode->getTakeCount();
                                if (!is_null($takeCount)) {
                                    $result1 = array_slice($result1, 0, $takeCount);
                                }
                            }

                            $resourceType->setPropertyValue($entry, $expandedPropertyName, $result1);
                            $projectedProperty = $expandedProjectionNode->getResourceProperty();
                            $needPop = $this->pushSegmentForNavigationProperty($projectedProperty);
                            $this->executeExpansion($result1);
                            $this->popSegment($needPop);
                        } else {
                            $resourceType->setPropertyValue($entry, $expandedPropertyName, array());
                        }
                    } else {
                        $currentResourceSet1 = $this->getCurrentResourceSetWrapper()->getResourceSet();
                        $resourceSetOfProjectedProperty1 = $expandedProjectionNode
                            ->getResourceSetWrapper()
                            ->getResourceSet();
                        $projectedProperty2 = $expandedProjectionNode->getResourceProperty();
                        $result1 = $this->getProviders()->getRelatedResourceReference(
                            $currentResourceSet1,
                            $entry,
                            $resourceSetOfProjectedProperty1,
                            $projectedProperty2
                        );
                        $resourceType->setPropertyValue($entry, $expandedPropertyName, $result1);
                        if (!is_null($result1)) {
                            $projectedProperty3 = $expandedProjectionNode->getResourceProperty();
                            $needPop = $this->pushSegmentForNavigationProperty($projectedProperty3);
                            $this->executeExpansion($result1);
                            $this->popSegment($needPop);
                        }
                    }
                }
            } else {
                if ($isCollection) {
                    $currentResourceSet2 = $this->getCurrentResourceSetWrapper()->getResourceSet();
                    $resourceSetOfProjectedProperty2 = $expandedProjectionNode
                        ->getResourceSetWrapper()
                        ->getResourceSet();
                    $projectedProperty4 = $expandedProjectionNode->getResourceProperty();
                    $result1 = $this->getProviders()->getRelatedResourceSet(
                        QueryType::ENTITIES(), //it's always entities for an expansion
                        $currentResourceSet2,
                        $result,
                        $resourceSetOfProjectedProperty2,
                        $projectedProperty4,
                        null, // $filter
                        null, // $orderby
                        null, // $top
                        null  // $skip
                    )->results;
                    if (!empty($result1)) {
                        $internalOrderByInfo = $expandedProjectionNode->getInternalOrderByInfo();
                        if (!is_null($internalOrderByInfo)) {
                            $orderByFunction = $internalOrderByInfo->getSorterFunction()->getReference();
                            usort($result1, $orderByFunction);
                            unset($internalOrderByInfo);
                            $takeCount = $expandedProjectionNode->getTakeCount();
                            if (!is_null($takeCount)) {
                                $result1 = array_slice($result1, 0, $takeCount);
                            }
                        }
                        $resourceType->setPropertyValue($result, $expandedPropertyName, $result1);
                        $projectedProperty7 = $expandedProjectionNode->getResourceProperty();
                        $needPop = $this->pushSegmentForNavigationProperty($projectedProperty7);
                        $this->executeExpansion($result1);
                        $this->popSegment($needPop);
                    } else {
                        $resourceType->setPropertyValue($result, $expandedPropertyName, $result1);
                    }
                } else {
                    $currentResourceSet3 = $this->getCurrentResourceSetWrapper()->getResourceSet();
                    $resourceSetOfProjectedProperty3 = $expandedProjectionNode
                        ->getResourceSetWrapper()
                        ->getResourceSet();
                    $projectedProperty5 = $expandedProjectionNode->getResourceProperty();
                    $result1 = $this->getProviders()->getRelatedResourceReference(
                        $currentResourceSet3,
                        $result,
                        $resourceSetOfProjectedProperty3,
                        $projectedProperty5
                    );
                    $resourceType->setPropertyValue($result, $expandedPropertyName, $result1);
                    if (!is_null($result1)) {
                        $projectedProperty6 = $expandedProjectionNode->getResourceProperty();
                        $needPop = $this->pushSegmentForNavigationProperty($projectedProperty6);
                        $this->executeExpansion($result1);
                        $this->popSegment($needPop);
                    }
                }
            }
        }
    }

    /**
     * Resource set wrapper for the resource being retireved.
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
     * @return bool true if a segment was pushed, false otherwise
     *
     * @throws InvalidOperationException If this function invoked with non-navigation
     *                                   property instance
     */
    private function pushSegmentForNavigationProperty(ResourceProperty &$resourceProperty)
    {
        if ($resourceProperty->getTypeKind() == ResourceTypeKind::ENTITY) {
            $this->assert(
                !empty($this->getStack()->getSegmentNames()),
                '!is_empty($this->getStack()->getSegmentNames())'
            );
            $currentResourceSetWrapper = $this->getCurrentResourceSetWrapper();
            $currentResourceType = $currentResourceSetWrapper->getResourceType();
            $currentResourceSetWrapper = $this->getService()
                ->getProvidersWrapper()
                ->getResourceSetWrapperForNavigationProperty(
                    $currentResourceSetWrapper,
                    $currentResourceType,
                    $resourceProperty
                );

            $this->assert(
                !is_null($currentResourceSetWrapper),
                '!null($currentResourceSetWrapper)'
            );

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
    private function getExpandedProjectionNodes()
    {
        $expandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        $expandedProjectionNodes = array();
        if (!is_null($expandedProjectionNode)) {
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
        if (!is_null($expandedProjectionNode)) {
            $names = $this->getStack()->getSegmentNames();
            $depth = count($names);
            if ($depth != 0) {
                for ($i = 1; $i < $depth; ++$i) {
                    $expandedProjectionNode = $expandedProjectionNode->findNode($names[$i]);
                    $this->assert(!is_null($expandedProjectionNode), '!is_null($expandedProjectionNode)');
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
        $this->getStack()->pushSegment($segmentName, $resourceSetWrapper);
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
     * Assert that the given condition is true.
     *
     * @param bool   $condition         Condition to assert
     * @param string $conditionAsString Message to show if assertion fails
     *
     * @throws InvalidOperationException
     */
    protected function assert($condition, $conditionAsString)
    {
        if (!$condition) {
            throw new InvalidOperationException(
                "Unexpected state, expecting $conditionAsString"
            );
        }
    }
}
