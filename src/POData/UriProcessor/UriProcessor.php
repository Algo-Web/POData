<?php

namespace POData\UriProcessor;

use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\QueryProcessor;
use POData\UriProcessor\ResourcePathProcessor\ResourcePathProcessor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;

/**
 * Class UriProcessor.
 *
 * A type to process client's requets URI
 * The syntax of request URI is:
 *  Scheme Host Port ServiceRoot ResourcePath ? QueryOption
 * For more details refer:
 * http://www.odata.org/developers/protocols/uri-conventions#UriComponents
 */
class UriProcessor
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
     * Holds reference to request expander.
     *
     * @var RequestExpander
     */
    private $expander;

    /**
     * Constructs a new instance of UriProcessor.
     *
     * @param IService $service Reference to the data service instance
     */
    private function __construct(IService $service)
    {
        $this->service = $service;
        $this->providers = $service->getProvidersWrapper();
    }

    /**
     * Process the resource path and query options of client's request uri.
     *
     * @param IService $service Reference to the data service instance
     *
     * @throws ODataException
     *
     * @return URIProcessor
     */
    public static function process(IService $service)
    {
        $absoluteRequestUri = $service->getHost()->getAbsoluteRequestUri();
        $absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri();

        if (!$absoluteServiceUri->isBaseOf($absoluteRequestUri)) {
            throw ODataException::createInternalServerError(
                Messages::uriProcessorRequestUriDoesNotHaveTheRightBaseUri(
                    $absoluteRequestUri->getUrlAsString(),
                    $absoluteServiceUri->getUrlAsString()
                )
            );
        }

        $uriProcessor = new self($service);
        //Parse the resource path part of the request Uri.
        $uriProcessor->request = ResourcePathProcessor::process($service);
        $uriProcessor->expander = new RequestExpander(
            $uriProcessor->getRequest(),
            $uriProcessor->getService(),
            $uriProcessor->getProviders()
        );

        $uriProcessor->getRequest()->setUriProcessor($uriProcessor);

        //Parse the query string options of the request Uri.
        QueryProcessor::process($uriProcessor->request, $service);

        return $uriProcessor;
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
     * Gets the request expander instance.
     *
     * @return RequestExpander
     */
    public function getExpander()
    {
        return $this->expander;
    }

    /**
     * Execute the client submitted request against the data source.
     */
    public function execute()
    {
        $service = $this->getService();
        $operationContext = !isset($service) ? null : $service->getOperationContext();
        if (!$operationContext) {
            $this->executeBase();

            return;
        }

        $requestMethod = $operationContext->incomingRequest()->getMethod();
        if ($requestMethod == HTTPRequestMethod::GET()) {
            $this->executeGet();
        } elseif ($requestMethod == HTTPRequestMethod::POST()) {
            $this->executePost();
        } elseif ($requestMethod == HTTPRequestMethod::PUT()) {
            $this->executePut();
        } elseif ($requestMethod == HTTPRequestMethod::DELETE()) {
            $this->executeDelete();
            //TODO: we probably need these verbs eventually.
        /*} elseif ($requestMethod == HTTPRequestMethod::PATCH()) {
            $this->executePatch();
        } elseif ($requestMethod == HTTPRequestMethod::MERGE()) {
            $this->executeMerge();*/
        } else {
            throw ODataException::createNotImplementedError(Messages::onlyReadSupport($requestMethod));
        }
    }

    /**
     * Execute the client submitted request against the data source (GET).
     */
    protected function executeGet()
    {
        return $this->executeBase();
    }

    /**
     * Execute the client submitted request against the data source (POST).
     */
    protected function executePost()
    {
        $segments = $this->getRequest()->getSegments();

        foreach ($segments as $segment) {
            $requestTargetKind = $segment->getTargetKind();
            if ($requestTargetKind == TargetKind::RESOURCE()) {
                $requestMethod = $this->getService()->getOperationContext()->incomingRequest()->getMethod();
                $resourceSet = $segment->getTargetResourceSetWrapper();
                $keyDescriptor = $segment->getKeyDescriptor();
                if (!$resourceSet) {
                    $url = $this->getService()->getHost()->getAbsoluteRequestUri()->getUrlAsString();
                    throw ODataException::createBadRequestError(
                        Messages::badRequestInvalidUriForThisVerb($url, $requestMethod)
                    );
                }
                $data = $this->getRequest()->getData();
                if (empty($data)) {
                    throw ODataException::createBadRequestError(Messages::noDataForThisVerb($requestMethod));
                }
                $queryResult = $this->getProviders()->createResourceforResourceSet($resourceSet, $keyDescriptor, $data);
                $segment->setResult($queryResult);
            }
        }
        //return $this->executeBase();
    }

    /**
     * Execute the client submitted request against the data source (PUT).
     */
    protected function executePut()
    {
        return $this->executeBase(function ($uriProcessor, $segment) {
            $requestMethod = $uriProcessor->getService()->getOperationContext()->incomingRequest()->getMethod();
            $resourceSet = $segment->getTargetResourceSetWrapper();
            $keyDescriptor = $segment->getKeyDescriptor();

            if (!$resourceSet || !$keyDescriptor) {
                $url = $uriProcessor->getService()->getHost()->getAbsoluteRequestUri()->getUrlAsString();
                throw ODataException::createBadRequestError(
                    Messages::badRequestInvalidUriForThisVerb($url, $requestMethod)
                );
            }

            $data = $uriProcessor->getRequest()->getData();
            if (!$data) {
                throw ODataException::createBadRequestError(Messages::noDataForThisVerb($requestMethod));
            }

            $queryResult = $uriProcessor->getProviders()->updateResource(
                $resourceSet,
                $segment->getResult(),
                $keyDescriptor,
                $data,
                false
            );
            $segment->setResult($queryResult);

            return $queryResult;
        });
    }

    /**
     * Execute the client submitted request against the data source (DELETE).
     */
    protected function executeDelete()
    {
        return $this->executeBase(function ($uriProcessor, $segment) {
            $requestMethod = $uriProcessor->getService()->getOperationContext()->incomingRequest()->getMethod();
            $resourceSet = $segment->getTargetResourceSetWrapper();
            $keyDescriptor = $segment->getKeyDescriptor();
            if (!$resourceSet || !$keyDescriptor) {
                $url = $uriProcessor->getService()->getHost()->getAbsoluteRequestUri()->getUrlAsString();
                throw ODataException::createBadRequestError(
                    Messages::badRequestInvalidUriForThisVerb($url, $requestMethod)
                );
            }

            return $uriProcessor->getProviders()->deleteResource($resourceSet, $segment->getResult());
        });
    }

    /**
     * Execute the client submitted request against the data source.
     *
     * @param callable $callback Function, what must be called
     */
    protected function executeBase($callback = null)
    {
        $segments = $this->getRequest()->getSegments();

        foreach ($segments as $segment) {
            $requestTargetKind = $segment->getTargetKind();

            if ($segment->getTargetSource() == TargetSource::ENTITY_SET) {
                $this->handleSegmentTargetsToResourceSet($segment);
            } elseif ($requestTargetKind == TargetKind::RESOURCE()) {
                if (is_null($segment->getPrevious()->getResult())) {
                    throw ODataException::createResourceNotFoundError(
                        $segment->getPrevious()->getIdentifier()
                    );
                }
                $this->handleSegmentTargetsToRelatedResource($segment);
            } elseif ($requestTargetKind == TargetKind::LINK()) {
                $segment->setResult($segment->getPrevious()->getResult());
            } elseif ($segment->getIdentifier() == ODataConstants::URI_COUNT_SEGMENT) {
                // we are done, $count will the last segment and
                // taken care by _applyQueryOptions method
                $segment->setResult($this->getRequest()->getCountValue());
                break;
            } else {
                if ($requestTargetKind == TargetKind::MEDIA_RESOURCE()) {
                    if (is_null($segment->getPrevious()->getResult())) {
                        throw ODataException::createResourceNotFoundError(
                            $segment->getPrevious()->getIdentifier()
                        );
                    }
                    // For MLE and Named Stream the result of last segment
                    // should be that of previous segment, this is required
                    // while retrieving content type or stream from IDSSP
                    $segment->setResult($segment->getPrevious()->getResult());
                    // we are done, as named stream property or $value on
                    // media resource will be the last segment
                    break;
                }

                $value = $segment->getPrevious()->getResult();
                while (!is_null($segment)) {
                    //TODO: what exactly is this doing here?  Once a null's found it seems everything will be null
                    if (!is_null($value)) {
                        $value = null;
                    } else {
                        // This is theoretically impossible to reach, but should that be changed, this will need to call
                        // ResourceType::getPropertyValue... somehow
                        try {
                            //see #88
                            $value = \POData\Common\ReflectionHandler::getProperty($value, $segment->getIdentifier());
                        } catch (\ReflectionException $reflectionException) {
                        }
                    }

                    $segment->setResult($value);
                    $segment = $segment->getNext();
                    if (!is_null($segment) && ODataConstants::URI_VALUE_SEGMENT == $segment->getIdentifier()) {
                        $segment->setResult($value);
                        $segment = $segment->getNext();
                    }
                }

                break;
            }

            if (is_null($segment->getNext())
                || ODataConstants::URI_COUNT_SEGMENT == $segment->getNext()->getIdentifier()
            ) {
                $this->applyQueryOptions($segment, $callback);
            }
        }

            // Apply $select and $expand options to result set, this function will be always applied
            // irrespective of return value of IDSQP2::canApplyQueryOptions which means library will
            // not delegate $expand/$select operation to IDSQP2 implementation
        $this->handleExpansion();
    }

    /**
     * Query for a resource set pointed by the given segment descriptor and update the descriptor with the result.
     *
     * @param SegmentDescriptor $segment Describes the resource set to query
     */
    private function handleSegmentTargetsToResourceSet(SegmentDescriptor $segment)
    {
        if ($segment->isSingleResult()) {
            $entityInstance = $this->getProviders()->getResourceFromResourceSet(
                $segment->getTargetResourceSetWrapper(),
                $segment->getKeyDescriptor()
            );

            $segment->setResult($entityInstance);
        } else {
            $skip = (null == $this->getRequest()) ? 0 : $this->getRequest()->getSkipCount();
            $skip = (null == $skip) ? 0 : $skip;
            $queryResult = $this->getProviders()->getResourceSet(
                $this->getRequest()->queryType,
                $segment->getTargetResourceSetWrapper(),
                $this->getRequest()->getFilterInfo(),
                $this->getRequest()->getInternalOrderByInfo(),
                $this->getRequest()->getTopCount(),
                $skip,
                null
            );
            $segment->setResult($queryResult);
        }
    }

    /**
     * Query for a related resource set or resource set reference pointed by the
     * given segment descriptor and update the descriptor with the result.
     *
     * @param SegmentDescriptor &$segment Describes the related resource
     *                                    to query
     */
    private function handleSegmentTargetsToRelatedResource(SegmentDescriptor $segment)
    {
        $projectedProperty = $segment->getProjectedProperty();
        $projectedPropertyKind = $projectedProperty->getKind();

        if ($projectedPropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE) {
            if ($segment->isSingleResult()) {
                $entityInstance = $this->getProviders()->getResourceFromRelatedResourceSet(
                    $segment->getPrevious()->getTargetResourceSetWrapper(),
                    $segment->getPrevious()->getResult(),
                    $segment->getTargetResourceSetWrapper(),
                    $projectedProperty,
                    $segment->getKeyDescriptor()
                );

                $segment->setResult($entityInstance);
            } else {
                $queryResult = $this->getProviders()->getRelatedResourceSet(
                    $this->getRequest()->queryType,
                    $segment->getPrevious()->getTargetResourceSetWrapper(),
                    $segment->getPrevious()->getResult(),
                    $segment->getTargetResourceSetWrapper(),
                    $segment->getProjectedProperty(),
                    $this->getRequest()->getFilterInfo(),
                    //TODO: why are these null?  see #98
                    null, // $orderby
                    null, // $top
                    null  // $skip
                );

                $segment->setResult($queryResult);
            }
        } elseif ($projectedPropertyKind == ResourcePropertyKind::RESOURCE_REFERENCE) {
            $entityInstance = $this->getProviders()->getRelatedResourceReference(
                $segment->getPrevious()->getTargetResourceSetWrapper(),
                $segment->getPrevious()->getResult(),
                $segment->getTargetResourceSetWrapper(),
                $segment->getProjectedProperty()
            );

            $segment->setResult($entityInstance);
        } else {
            //Unexpected state
        }
    }

    /**
     * Applies the query options to the resource(s) retrieved from the data source.
     *
     * @param SegmentDescriptor $segment  The descriptor which holds resource(s) on which query options to be applied
     * @param callable          $callback Function, what must be called
     */
    private function applyQueryOptions(SegmentDescriptor $segment, $callback = null)
    {
        // For non-GET methods
        if ($callback) {
            $callback($this, $segment);

            return;
        }

        //TODO: I'm not really happy with this..i think i'd rather keep the result the QueryResult
        //not even bother with the setCountValue stuff (shouldn't counts be on segments?)
        //and just work with the QueryResult in the object model serializer
        $result = $segment->getResult();

        if (!$result instanceof QueryResult) {
            //If the segment isn't a query result, then there's no paging or counting to be done
            return;
        }

        // Note $inlinecount=allpages means include the total count regardless of paging..so we set the counts first
        // regardless if POData does the paging or not.
        if ($this->getRequest()->queryType == QueryType::ENTITIES_WITH_COUNT()) {
            if ($this->getProviders()->handlesOrderedPaging()) {
                $this->getRequest()->setCountValue($result->count);
            } else {
                $this->getRequest()->setCountValue(count($result->results));
            }
        }

        //Have POData perform paging if necessary
        if (!$this->getProviders()->handlesOrderedPaging() && !empty($result->results)) {
            $result->results = $this->performPaging($result->results);
        }

        //a bit surprising, but $skip and $top affects $count so update it here, not above
        //IE  data.svc/Collection/$count?$top=10 returns 10 even if Collection has 11+ entries
        if ($this->getRequest()->queryType == QueryType::COUNT()) {
            if ($this->getProviders()->handlesOrderedPaging()) {
                $this->getRequest()->setCountValue($result->count);
            } else {
                $this->getRequest()->setCountValue(count($result->results));
            }
        }

        $segment->setResult($result->results);
    }

    /**
     * If the provider does not perform the paging (ordering, top, skip) then this method does it.
     *
     * @param array $result
     *
     * @return array
     */
    private function performPaging(array $result)
    {
        //Apply (implicit and explicit) $orderby option
        $internalOrderByInfo = $this->getRequest()->getInternalOrderByInfo();
        if (!is_null($internalOrderByInfo)) {
            $orderByFunction = $internalOrderByInfo->getSorterFunction();
            usort($result, $orderByFunction);
        }

        //Apply $skiptoken option
        $internalSkipTokenInfo = $this->getRequest()->getInternalSkipTokenInfo();
        if (!is_null($internalSkipTokenInfo)) {
            $matchingIndex = $internalSkipTokenInfo->getIndexOfFirstEntryInTheNextPage($result);
            $result = array_slice($result, $matchingIndex);
        }

        //Apply $top and $skip option
        if (!empty($result)) {
            $top = $this->getRequest()->getTopCount();
            $skip = $this->getRequest()->getSkipCount();
            if (is_null($skip)) {
                $skip = 0;
            }

            $result = array_slice($result, $skip, $top);
        }

        return $result;
    }

    /**
     * Perform expansion.
     */
    private function handleExpansion()
    {
        $this->getExpander()->handleExpansion();
    }
}
