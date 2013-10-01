<?php

namespace POData\UriProcessor;

use POData\Providers\ProvidersWrapper;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceProperty;
use POData\UriProcessor\QueryProcessor\QueryProcessor;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\ResourcePathProcessor\ResourcePathProcessor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetSource;
use POData\IService;
use POData\Common\Url;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Common\NotImplementedException;
use POData\Common\InvalidOperationException;
use POData\Common\ODataConstants;

/**
 * Class UriProcessor
 *
 * A type to process client's requets URI
 * The syntax of request URI is:
 *  Scheme Host Port ServiceRoot ResourcePath ? QueryOption
 * For more details refer:
 * http://www.odata.org/developers/protocols/uri-conventions#UriComponents
 *
 * @package POData\UriProcessor
 */
class UriProcessor
{
    /**
     * Description of the OData request that a client has submitted.
     * 
     * @var RequestDescription
     */
    private $_requestDescription;

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
    private $_provider;

    /**
     * Collection of segment names.
     *
     * @var string[]
     */
    private $_segmentNames;

    /**
     * Collection of segment ResourceSetWrapper instances.
     *
     * @var ResourceSetWrapper[]
     */
    private $_segmentResourceSetWrappers;

    /**
     * Constructs a new instance of UriProcessor
     * 
     * @param IService $service Reference to the data service instance.
     */
    private function __construct(IService $service)
    {
        $this->service = $service;
        $this->_provider = $service->getProvidersWrapper();
        $this->_segmentNames = array();
        $this->_segmentResourceSetWrappers = array();
    }

    /**
     * Process the resource path and query options of client's request uri.
     * 
     * @param IService $service Reference to the data service instance.
     * 
     * @return URIProcessor
     * 
     * @throws ODataException
     */
    public static function process(IService $service)
    {
        $absoluteRequestUri = $service->getHost()->getAbsoluteRequestUri();
        $absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri();
        
        if (!$absoluteServiceUri->isBaseOf($absoluteRequestUri)) {
            ODataException::createInternalServerError(
                Messages::uriProcessorRequestUriDoesNotHaveTheRightBaseUri(
                    $absoluteRequestUri->getUrlAsString(), 
                    $absoluteServiceUri->getUrlAsString()
                )
            );
        }

        $uriProcessor = new UriProcessor($service);
        //Parse the resource path part of the request Uri.
		$uriProcessor->_requestDescription = ResourcePathProcessor::process(
			$absoluteRequestUri,
			$service
		);

	    $uriProcessor->_requestDescription->setUriProcessor($uriProcessor);


        //Parse the query string options of the request Uri.
        QueryProcessor::process( $uriProcessor->_requestDescription, $service );

        return $uriProcessor;
    }

    /**
     * Gets reference to the request submitted by client.
     * 
     * @return RequestDescription
     */
    public function getRequestDescription()
    {
        return $this->_requestDescription;
    }

    /**
     * Execute the client submitted request against the data source.
     * 
     * @return void
     */
    public function execute()
    {
        $segmentDescriptors = $this->_requestDescription->getSegmentDescriptors();
        foreach ($segmentDescriptors as $segmentDescriptor) {
            $requestTargetKind = $segmentDescriptor->getTargetKind();
            if ($segmentDescriptor->getTargetSource() == RequestTargetSource::ENTITY_SET) {
                $this->_handleSegmentTargetsToResourceSet($segmentDescriptor);
            } else if ($requestTargetKind == RequestTargetKind::RESOURCE) {
                if (is_null($segmentDescriptor->getPrevious()->getResult())) {
                    ODataException::createResourceNotFoundError(
                        $segmentDescriptor->getPrevious()->getIdentifier()
                    );
                }
                $this->_handleSegmentTargetsToRelatedResource($segmentDescriptor);
            } else if ($requestTargetKind == RequestTargetKind::LINK) {
                $segmentDescriptor->setResult($segmentDescriptor->getPrevious()->getResult());
            } else if ($segmentDescriptor->getIdentifier() == ODataConstants::URI_COUNT_SEGMENT) {
                // we are done, $count will the last segment and 
                // taken care by _applyQueryOptions method
                $segmentDescriptor->setResult($this->_requestDescription->getCountValue());
                break;
            } else {
                if ($requestTargetKind == RequestTargetKind::MEDIA_RESOURCE) {
                    if (is_null($segmentDescriptor->getPrevious()->getResult())) {
                        ODataException::createResourceNotFoundError(
                            $segmentDescriptor->getPrevious()->getIdentifier()
                        );
                    }
                    // For MLE and Named Stream the result of last segment 
                    // should be that of previous segment, this is required 
                    // while retriving content type or stream from IDSSP
                    $segmentDescriptor->setResult($segmentDescriptor->getPrevious()->getResult());
                    // we are done, as named stream property or $value on 
                    // media resource will be the last segment
                    break;
                } else {
                    $value = $segmentDescriptor->getPrevious()->getResult();
                    while (!is_null($segmentDescriptor)) {
                        if (is_null($value)) {
                            $value = null;
                        } else {
                            try {
                                $property = new \ReflectionProperty($value, $segmentDescriptor->getIdentifier());
                                $value = $property->getValue($value);
                            } catch (\ReflectionException $reflectionException) {
                                //throw ODataException::createInternalServerError(Messages::orderByParserFailedToAccessOrInitializeProperty($resourceProperty->getName(), $resourceType->getName()));
                            }
                        }

                        $segmentDescriptor->setResult($value);
                        $segmentDescriptor = $segmentDescriptor->getNext();
                        if (!is_null($segmentDescriptor) 
                            && $segmentDescriptor->getIdentifier() == ODataConstants::URI_VALUE_SEGMENT
                        ) {
                            $segmentDescriptor->setResult($value);
                            $segmentDescriptor = $segmentDescriptor->getNext();
                        }
                    }

                    //done, exit from outer loop as inner while complete traversal.
                    break;
                }
            }

            if (is_null($segmentDescriptor->getNext()) 
                || $segmentDescriptor->getNext()->getIdentifier() == ODataConstants::URI_COUNT_SEGMENT
            ) {
                    $this->_applyQueryOptions($segmentDescriptor);
                    
            }
        }

         // Apply $select and $expand options to result set, this function will be always applied
         // irrespective of return value of IDSQP2::canApplyQueryOptions which means library will
         // not delegate $expand/$select operation to IDSQP2 implementation
        $this->_handleExpansion();
    }

    /**
     * Query for a resource set pointed by the given segment descriptor and update
     * the descriptor with the result.
     *
     * @param SegmentDescriptor &$segmentDescriptor Describes the resource set to query
     * @return void
     *
     */
    private function _handleSegmentTargetsToResourceSet( SegmentDescriptor $segmentDescriptor ) {
        if ($segmentDescriptor->isSingleResult()) {
            $entityInstance = $this->_provider->getResourceFromResourceSet(
                $segmentDescriptor->getTargetResourceSetWrapper()->getResourceSet(),
                $segmentDescriptor->getKeyDescriptor()
            );

            $segmentDescriptor->setResult($entityInstance);
            
        } else {
            $entityInstances = $this->_provider->getResourceSet(
                $segmentDescriptor->getTargetResourceSetWrapper()->getResourceSet(),
                $this->_requestDescription->getInternalFilterInfo(),
                null, // $select :: We will not pass RequestionDescriptio::ProjectionNode which contains 
                // $select and $expand info to IDSQP2 this will be handled by the library::_handleExpansion 
                // function.
                $this->_requestDescription->getInternalOrderByInfo(),
                $this->_requestDescription->getTopCount(),
                $this->_requestDescription->getSkipCount()
            );
            $segmentDescriptor->setResult($entityInstances);
        }
    }

    /**
     * Query for a related resource set or resource set reference pointed by the 
     * given segment descriptor and update the descriptor with the result.
     * 
     * @param SegmentDescriptor &$segmentDescriptor Describes the related resource
     *                                              to query.
     * 
     * @return void
     */
    private function _handleSegmentTargetsToRelatedResource(
        SegmentDescriptor &$segmentDescriptor
    ) {
        $projectedProperty = $segmentDescriptor->getProjectedProperty();
        $projectedPropertyKind = $projectedProperty->getKind();
        if ($projectedPropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE) {
            if ($segmentDescriptor->isSingleResult()) {
                $entityInstance 
                    = $this->_provider->getResourceFromRelatedResourceSet(
                        $segmentDescriptor->getPrevious()->getTargetResourceSetWrapper()->getResourceSet(),
                        $segmentDescriptor->getPrevious()->getResult(),
                        $segmentDescriptor->getTargetResourceSetWrapper()->getResourceSet(),
                        $projectedProperty,
                        $segmentDescriptor->getKeyDescriptor()
                    );

                $segmentDescriptor->setResult($entityInstance);
            } else {
                $entityInstances 
                    = $this->_provider->getRelatedResourceSet(
                        $segmentDescriptor->getPrevious()->getTargetResourceSetWrapper()->getResourceSet(),
                        $segmentDescriptor->getPrevious()->getResult(),
                        $segmentDescriptor->getTargetResourceSetWrapper()->getResourceSet(),
                        $segmentDescriptor->getProjectedProperty(),
                        $this->_requestDescription->getInternalFilterInfo(),
                        null, // $select
                        null, // $orderby
                        null, // $top
                        null  // $skip
                    );

                $segmentDescriptor->setResult($entityInstances);
            }           
        } else if ($projectedPropertyKind == ResourcePropertyKind::RESOURCE_REFERENCE) {
            $entityInstance 
                = $this->_provider->getRelatedResourceReference(
                    $segmentDescriptor->getPrevious()->getTargetResourceSetWrapper()->getResourceSet(),
                    $segmentDescriptor->getPrevious()->getResult(),
                    $segmentDescriptor->getTargetResourceSetWrapper()->getResourceSet(),
                    $segmentDescriptor->getProjectedProperty()
                );

            $segmentDescriptor->setResult($entityInstance);
        } else {
            //Unexpected state
        }
    }

    /**
     * Applies the query options to the resource(s) retrieved from the data source.
     * 
     * @param SegmentDescriptor $segmentDescriptor The descriptor which holds
     *                                              resource(s) on which query
     *                                              options to be applied.
     * 
     * @return void
     */
    private function _applyQueryOptions(SegmentDescriptor $segmentDescriptor)
    {
	    //TODO: much of this will change as #3

        // This function will not set RequestDescription::Count value if IDSQP2::canApplyQueryOptions 
        // returns false, this function assumes IDSQP2 has already set the count value in the global
        // variable named _odata_server_count. temporary fix for Drupal OData Plugin support
        global $_odata_server_count;

        $result = $segmentDescriptor->getResult();


        // $inlinecount=allpages should ignore the query options 
        // $skiptoken, $top and $skip so take count before applying these options
        if ($this->_requestDescription->getRequestCountOption() != RequestCountOption::NONE() && is_array($result)
        ) {
            if ($this->_provider->canApplyQueryOptions()) {
                $this->_requestDescription->setCountValue(count($result));
            } else {
                $this->_requestDescription->setCountValue($_odata_server_count);
            }
        }
        
        // Library applies query options only if the QueryProvider::canApplyQueryOptions returns true.
        $applicableForSetQuery = $this->_provider->canApplyQueryOptions() && is_array($result) && !empty($result);
        if ($applicableForSetQuery) {
            //Apply (implicit and explicit) $orderby option
            $internalOrderByInfo = $this->_requestDescription->getInternalOrderByInfo();
            if (!is_null($internalOrderByInfo)) {
                $orderByFunction = $internalOrderByInfo->getSorterFunction()->getReference();
                usort($result, $orderByFunction);
            }

            //Apply $skiptoken option
            $internalSkipTokenInfo = $this->_requestDescription->getInternalSkipTokenInfo();
            if (!is_null($internalSkipTokenInfo)) {
                $matchingIndex = $internalSkipTokenInfo->getIndexOfFirstEntryInTheNextPage($result);
                $result = array_slice($result, $matchingIndex);
            }
            
            //Apply $top and $skip option
            if (!empty($result)) {
                $top  = $this->_requestDescription->getTopCount();
                $skip = $this->_requestDescription->getSkipCount();
                if (!is_null($top) && !is_null($skip)) {
                    $result = array_slice($result, $skip, $top);
                } else if (is_null($top)) {
                    $result = array_slice($result, $skip);
                } else if (is_null($skip)) {
                    $result = array_slice($result, 0, $top);
                }

                //$skip and $top affects $count so consider here.
                if ($this->_requestDescription->getRequestCountOption() == RequestCountOption::VALUE_ONLY()) {
                    $this->_requestDescription->setCountValue(count($result));
                }
            }
        }

        $segmentDescriptor->setResult($result);
    }

    /**
     * Perfrom expansion.
     * 
     * @return void
     */
    private function _handleExpansion()
    {
        $rootrojectionNode = $this->_requestDescription->getRootProjectionNode();
        if (!is_null($rootrojectionNode) 
            && $rootrojectionNode->isExpansionSpecified()
        ) {
            $result = $this->_requestDescription->getTargetResult();
            if (!is_null($result) || is_array($result) && !empty($result)) {
                $needPop = $this->_pushSegmentForRoot();
                $this->_executeExpansion($result);
                $this->_popSegment($needPop);
            }
        }
    }

    /**
     * Execute queries for expansion.
     * 
     * @param array(mixed)/mixed &$result Resource(s) whose navigation properties needs to be expanded.
     *
     *
     * @return void
     */
    private function _executeExpansion(&$result)
    {
        $expandedProjectionNodes = $this->_getExpandedProjectionNodes();
        foreach ($expandedProjectionNodes as $expandedProjectionNode) {
            $isCollection 
                = $expandedProjectionNode->getResourceProperty()->getKind() == ResourcePropertyKind::RESOURCESET_REFERENCE;
            $expandedPropertyName 
                = $expandedProjectionNode->getResourceProperty()->getName();
            if (is_array($result)) {
                foreach ($result as $entry) {
                    // Check for null entry
                    if ($isCollection) {
                        $currentResourceSet = $this->_getCurrentResourceSetWrapper()->getResourceSet();
                        $resourceSetOfProjectedProperty = $expandedProjectionNode->getResourceSetWrapper()->getResourceSet();
                        $projectedProperty1 = $expandedProjectionNode->getResourceProperty();
                        $result1 = $this->_provider->getRelatedResourceSet(
                            $currentResourceSet,
                            $entry,
                            $resourceSetOfProjectedProperty,
                            $projectedProperty1,
                            null, // $filter
                            null, // $select
                            null, // $orderby
                            null, // $top
                            null  // $skip
                        );
                        if (!empty($result1)) {
                            $internalOrderByInfo 
                                = $expandedProjectionNode->getInternalOrderByInfo();
                            if (!is_null($internalOrderByInfo)) {
                                $orderByFunction 
                                    = $internalOrderByInfo->getSorterFunction()->getReference();
                                usort($result1, $orderByFunction);
                                unset($internalOrderByInfo);
                                $takeCount = $expandedProjectionNode->getTakeCount();
                                if (!is_null($takeCount)) {
                                    $result1 = array_slice($result1, 0, $takeCount);
                                }
                            }

                            $entry->$expandedPropertyName = $result1;
                            $projectedProperty = $expandedProjectionNode->getResourceProperty();
                            $needPop = $this->_pushSegmentForNavigationProperty(
                                $projectedProperty
                            );
                            $this->_executeExpansion($result1);
                            $this->_popSegment($needPop);
                        } else {
                            $entry->$expandedPropertyName = array();
                        }
                    } else {
                        $currentResourceSet1 = $this->_getCurrentResourceSetWrapper()->getResourceSet();
                        $resourceSetOfProjectedProperty1 = $expandedProjectionNode->getResourceSetWrapper()->getResourceSet();
                        $projectedProperty2 = $expandedProjectionNode->getResourceProperty();
                        $result1 = $this->_provider->getRelatedResourceReference(
                            $currentResourceSet1,
                            $entry,
                            $resourceSetOfProjectedProperty1,
                            $projectedProperty2
                        );
                        $entry->$expandedPropertyName = $result1;
                        if (!is_null($result1)) {
                            $projectedProperty3 = $expandedProjectionNode->getResourceProperty();
                            $needPop = $this->_pushSegmentForNavigationProperty(
                                $projectedProperty3
                            );
                            $this->_executeExpansion($result1);
                            $this->_popSegment($needPop);
                        }
                    }
                }
            } else {
                if ($isCollection) {
                    $currentResourceSet2 = $this->_getCurrentResourceSetWrapper()->getResourceSet();
                    $resourceSetOfProjectedProperty2 = $expandedProjectionNode->getResourceSetWrapper()->getResourceSet();
                    $projectedProperty4 = $expandedProjectionNode->getResourceProperty();
                    $result1 = $this->_provider->getRelatedResourceSet(
                        $currentResourceSet2,
                        $result,
                        $resourceSetOfProjectedProperty2,
                        $projectedProperty4,
                        null, // $filter
                        null, // $select
                        null, // $orderby
                        null, // $top
                        null  // $skip
                    );
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

                        $result->$expandedPropertyName = $result1;
                        $projectedProperty7 = $expandedProjectionNode->getResourceProperty();
                        $needPop = $this->_pushSegmentForNavigationProperty(
                            $projectedProperty7
                        );
                        $this->_executeExpansion($result1);
                        $this->_popSegment($needPop);
                    } else {
                        $result->$expandedPropertyName = array();
                    }
                } else {
                    $currentResourceSet3 = $this->_getCurrentResourceSetWrapper()->getResourceSet();
                    $resourceSetOfProjectedProperty3 = $expandedProjectionNode->getResourceSetWrapper()->getResourceSet();
                    $projectedProperty5 = $expandedProjectionNode->getResourceProperty();
                    $result1 = $this->_provider->getRelatedResourceReference(
                        $currentResourceSet3,
                        $result,
                        $resourceSetOfProjectedProperty3,
                        $projectedProperty5
                    );
                    $result->$expandedPropertyName = $result1;
                    if (!is_null($result1)) {
                        $projectedProperty6 = $expandedProjectionNode->getResourceProperty();
                        $needPop = $this->_pushSegmentForNavigationProperty(
                            $projectedProperty6
                        );
                        $this->_executeExpansion($result1);
                        $this->_popSegment($needPop);
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
    private function _getCurrentResourceSetWrapper()
    {
        $count = count($this->_segmentResourceSetWrappers);
        if ($count == 0) {
            return $this->_requestDescription->getTargetResourceSetWrapper();
        } else {
            return $this->_segmentResourceSetWrappers[$count - 1];
        }
    }

    /**
     * Pushes a segment for the root of the tree 
     * Note: Calls to this method should be balanced with calls to popSegment.
     *
     * @return bool true if the segment was pushed, false otherwise.
     */
    private function _pushSegmentForRoot()
    {
        $segmentName = $this->_requestDescription->getContainerName();
        $segmentResourceSetWrapper 
            = $this->_requestDescription->getTargetResourceSetWrapper();
        return $this->_pushSegment($segmentName, $segmentResourceSetWrapper);
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
     *                                   property instance.
     */
    private function _pushSegmentForNavigationProperty(ResourceProperty &$resourceProperty)
    {
        if ($resourceProperty->getTypeKind() == ResourceTypeKind::ENTITY) {
            $this->assert(
                !empty($this->_segmentNames), 
                '!is_empty($this->_segmentNames'
            );
            $currentResourceSetWrapper = $this->_getCurrentResourceSetWrapper();
            $currentResourceType = $currentResourceSetWrapper->getResourceType();
            $currentResourceSetWrapper = $this->service
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
            return $this->_pushSegment(
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
     *
     */
    private function _getExpandedProjectionNodes()
    {
        $expandedProjectionNode = $this->_getCurrentExpandedProjectionNode();
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
    private function _getCurrentExpandedProjectionNode()
    {
        $expandedProjectionNode 
            = $this->_requestDescription->getRootProjectionNode();
        if (!is_null($expandedProjectionNode)) {
            $depth = count($this->_segmentNames);
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
     * Pushes information about the segment whose instance is going to be
     * retrieved from the IDSQP implementation
     * Note: Calls to this method should be balanced with calls to popSegment.
     *
     * @param string             $segmentName         Name of segment to push.
     * @param ResourceSetWrapper &$resourceSetWrapper The resource set wrapper 
     *                                                to push.
     *
     * @return bool true if the segment was push, false otherwise
     */
    private function _pushSegment($segmentName, ResourceSetWrapper &$resourceSetWrapper)
    {
        $rootProjectionNode = $this->_requestDescription->getRootProjectionNode();
        if (!is_null($rootProjectionNode) 
            && $rootProjectionNode->isExpansionSpecified()
        ) {
            array_push($this->_segmentNames, $segmentName);
            array_push($this->_segmentResourceSetWrappers, $resourceSetWrapper);
            return true;
        }

        return false;
    }

    /**
     * Pops segment information from the 'Segment Stack'
     * Note: Calls to this method should be balanced with previous calls 
     * to _pushSegment.
     * 
     * @param boolean $needPop Is a pop required. Only true if last push 
     *                         was successful.
     * 
     * @return void
     * 
     * @throws InvalidOperationException If found un-balanced call 
     *                                   with _pushSegment
     */
    private function _popSegment($needPop)
    {
        if ($needPop) {
            if (!empty($this->_segmentNames)) {
                array_pop($this->_segmentNames);
                array_pop($this->_segmentResourceSetWrappers);
            } else {
                throw new InvalidOperationException(
                    'Found non-balanced call to _pushSegment and popSegment'
                );
            }
        }
    }
    
    /**
     * Assert that the given condition is true.
     * 
     * @param boolean $condition         Constion to assert.
     * @param string  $conditionAsString Message to show incase assertion fails.
     * 
     * @return void
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