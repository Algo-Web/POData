<?php

namespace POData\UriProcessor;

use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\ObjectModel\ModelDeserialiser;
use POData\ObjectModel\ODataEntry;
use POData\OperationContext\HTTPRequestMethod;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\Interfaces\IUriProcessor;
use POData\UriProcessor\QueryProcessor\QueryProcessor;
use POData\UriProcessor\ResourcePathProcessor\ResourcePathProcessor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;

/**
 * Class UriProcessorNew.
 *
 * A type to process client's requested URI
 * The syntax of request URI is:
 *  Scheme Host Port ServiceRoot ResourcePath ? QueryOption
 * For more details refer:
 * http://www.odata.org/developers/protocols/uri-conventions#UriComponents
 */
class UriProcessorNew implements IUriProcessor
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

    private $cereal;

    /**
     * Constructs a new instance of UriProcessor.
     *
     * @param IService $service Reference to the data service instance
     */
    private function __construct(IService $service)
    {
        $this->service = $service;
        $this->providers = $service->getProvidersWrapper();
        $this->request = ResourcePathProcessor::process($service);
        $this->expander = new RequestExpander(
            $this->getRequest(),
            $this->getService(),
            $this->getProviders()
        );
        $this->getRequest()->setUriProcessor($this);
        $this->cereal = new ModelDeserialiser();
    }

    /**
     * Process the resource path and query options of client's request uri.
     *
     * @param IService $service Reference to the data service instance
     *
     * @throws ODataException
     *
     * @return IUriProcessor
     */
    public static function process(IService $service)
    {
        $absRequestUri = $service->getHost()->getAbsoluteRequestUri();
        $absServiceUri = $service->getHost()->getAbsoluteServiceUri();

        if (!$absServiceUri->isBaseOf($absRequestUri)) {
            throw ODataException::createInternalServerError(
                Messages::uriProcessorRequestUriDoesNotHaveTheRightBaseUri(
                    $absRequestUri->getUrlAsString(),
                    $absServiceUri->getUrlAsString()
                )
            );
        }

        $processor = new self($service);

        //Parse the query string options of the request Uri.
        QueryProcessor::process($processor->request, $service);

        return $processor;
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
        assert($service instanceof IService, '!($service instanceof IService)');
        $context = $service->getOperationContext();
        $method = $context->incomingRequest()->getMethod();

        switch ($method) {
            case HTTPRequestMethod::GET():
                $this->executeGet();
                break;
            case HTTPRequestMethod::DELETE():
                $this->executeGet();
                $this->executeDelete();
                break;
            case HTTPRequestMethod::PUT():
                $this->executeGet();
                $this->executePut();
                break;
            case HTTPRequestMethod::POST():
                $this->executePost();
                break;
            default:
                throw ODataException::createNotImplementedError(Messages::onlyReadSupport($method));
        }

        // Apply $select and $expand options to result set, this function will be always applied
        // irrespective of return value of IDSQP2::canApplyQueryOptions which means library will
        // not delegate $expand/$select operation to IDSQP2 implementation
        $this->getExpander()->handleExpansion();
    }

    /**
     * Execute the client submitted request against the data source (GET).
     */
    protected function executeGet()
    {
        $segments = $this->getRequest()->getSegments();

        foreach ($segments as $segment) {
            $requestTargetKind = $segment->getTargetKind();

            switch ($requestTargetKind) {
                case TargetKind::SINGLETON():
                    $this->executeGetSingleton($segment);
                    break;
                case TargetKind::RESOURCE():
                    $this->executeGetResource($segment);
                    break;
                case TargetKind::MEDIA_RESOURCE():
                    $this->checkResourceExistsByIdentifier($segment);
                    $segment->setResult($segment->getPrevious()->getResult());
                    // a media resource means we're done - bail out of segment processing
                    break 2;
                case TargetKind::LINK():
                    $this->executeGetLink($segment);
                    break;
                case TargetKind::PRIMITIVE_VALUE():
                    $previous = $segment->getPrevious();
                    if (null !== $previous && TargetKind::RESOURCE() == $previous->getTargetKind()) {
                        $result = $previous->getResult();
                        if ($result instanceof QueryResult) {
                            $raw = null !== $result->count ? $result->count : count($result->results);
                            $segment->setResult($raw);
                        }
                    }
                    break;
                case TargetKind::PRIMITIVE():
                case TargetKind::COMPLEX_OBJECT():
                case TargetKind::BAG():
                    break;
                default:
                    assert(false, 'Not implemented yet');
            }

            if (null === $segment->getNext()
                || ODataConstants::URI_COUNT_SEGMENT == $segment->getNext()->getIdentifier()
            ) {
                $this->applyQueryOptions($segment);
            }
        }
    }

    /**
     * Execute the client submitted request against the data source (DELETE).
     */
    protected function executeDelete()
    {
        $segment = $this->getFinalEffectiveSegment();
        $requestMethod = $this->getService()->getOperationContext()->incomingRequest()->getMethod();
        $resourceSet = $segment->getTargetResourceSetWrapper();
        $keyDescriptor = $segment->getKeyDescriptor();

        $this->checkUriValidForSuppliedVerb($resourceSet, $keyDescriptor, $requestMethod);
        assert($resourceSet instanceof ResourceSet);
        $this->getProviders()->deleteResource($resourceSet, $segment->getResult());
    }

    /**
     * Execute the client submitted request against the data source (PUT).
     */
    protected function executePut()
    {
        $segment = $this->getFinalEffectiveSegment();
        $requestMethod = $this->getService()->getOperationContext()->incomingRequest()->getMethod();
        $resourceSet = null !== $segment ? $segment->getTargetResourceSetWrapper() : null;
        $keyDescriptor = null !== $segment ? $segment->getKeyDescriptor() : null;

        $this->checkUriValidForSuppliedVerb($resourceSet, $keyDescriptor, $requestMethod);
        assert($resourceSet instanceof ResourceSet);
        assert($keyDescriptor instanceof KeyDescriptor);

        $data = $this->getRequest()->getData();
        if ($data instanceof ODataEntry) {
            $data = $this->cereal->bulkDeserialise($resourceSet->getResourceType(), $data);
        }
        if (!$data) {
            throw ODataException::createBadRequestError(Messages::noDataForThisVerb($requestMethod));
        }

        $queryResult = $this->getProviders()->updateResource(
            $resourceSet,
            $segment->getResult(),
            $keyDescriptor,
            $data,
            false
        );
        $segment->setResult($queryResult);
    }

    /**
     * Execute the client submitted request against the data source (POST).
     */
    protected function executePost()
    {
        $segments = $this->getRequest()->getSegments();
        $requestMethod = $this->getService()->getOperationContext()->incomingRequest()->getMethod();

        foreach ($segments as $segment) {
            $requestTargetKind = $segment->getTargetKind();
            if ($requestTargetKind == TargetKind::RESOURCE()) {
                $resourceSet = $segment->getTargetResourceSetWrapper();
                if (!$resourceSet) {
                    $url = $this->getService()->getHost()->getAbsoluteRequestUri()->getUrlAsString();
                    $msg = Messages::badRequestInvalidUriForThisVerb($url, $requestMethod);
                    throw ODataException::createBadRequestError($msg);
                }

                $keyDescriptor = $segment->getKeyDescriptor();

                $data = $this->getRequest()->getData();
                if ($data instanceof ODataEntry) {
                    $data = $this->cereal->bulkDeserialise($resourceSet->getResourceType(), $data);
                }

                if (empty($data)) {
                    throw ODataException::createBadRequestError(Messages::noDataForThisVerb($requestMethod));
                }
                $queryResult = $this->getProviders()->createResourceforResourceSet($resourceSet, $keyDescriptor, $data);
                $segment->setResult($queryResult);
            }
        }
    }

    /**
     * @return null|SegmentDescriptor
     */
    protected function getFinalEffectiveSegment()
    {
        $segment = $this->getRequest()->getLastSegment();
        // if last segment is $count, back up one
        if (null !== $segment && ODataConstants::URI_COUNT_SEGMENT == $segment->getIdentifier()) {
            $segment = $segment->getPrevious();
            return $segment;
        }
        return $segment;
    }

    /**
     * @param $resourceSet
     * @param $keyDescriptor
     * @param $requestMethod
     * @throws ODataException
     */
    protected function checkUriValidForSuppliedVerb($resourceSet, $keyDescriptor, $requestMethod)
    {
        if (!$resourceSet || !$keyDescriptor) {
            $url = $this->getService()->getHost()->getAbsoluteRequestUri()->getUrlAsString();
            throw ODataException::createBadRequestError(
                Messages::badRequestInvalidUriForThisVerb($url, $requestMethod)
            );
        }
    }

    /**
     * @param $segment
     */
    private function executeGetSingleton($segment)
    {
        $segmentId = $segment->getIdentifier();
        $singleton = $this->getService()->getProvidersWrapper()->resolveSingleton($segmentId);
        $segment->setResult($singleton->get());
    }

    /**
     * @param $segment
     */
    private function executeGetResource($segment)
    {
        $isRelated = $segment->getTargetSource() != TargetSource::ENTITY_SET;
        if (!$isRelated) {
            $queryResult = $this->executeGetResourceDirect($segment);
        } else {
            $queryResult = $this->executeGetResourceRelated($segment);
        }
        $segment->setResult($queryResult);
    }

    /**
     * @param $segment
     */
    private function executeGetLink($segment)
    {
        $previous = $segment->getPrevious();
        assert(isset($previous));
        $segment->setResult($previous->getResult());
    }

    /**
     * @param $segment
     * @return null|object|QueryResult
     */
    private function executeGetResourceDirect($segment)
    {
        if ($segment->isSingleResult()) {
            $queryResult = $this->getProviders()->getResourceFromResourceSet(
                $segment->getTargetResourceSetWrapper(),
                $segment->getKeyDescriptor()
            );
        } else {
            $skip = $this->getRequest()->getSkipCount();
            $skip = (null === $skip) ? 0 : $skip;
            $skipToken = $this->getRequest()->getInternalSkipTokenInfo();
            $skipToken = (null != $skipToken) ? $skipToken->getSkipTokenInfo() : null;
            $queryResult = $this->getProviders()->getResourceSet(
                $this->getRequest()->queryType,
                $segment->getTargetResourceSetWrapper(),
                $this->getRequest()->getFilterInfo(),
                $this->getRequest()->getInternalOrderByInfo(),
                $this->getRequest()->getTopCount(),
                $skip,
                $skipToken
            );
        }
        return $queryResult;
    }

    /**
     * @param $segment
     * @return null|object|QueryResult
     */
    private function executeGetResourceRelated($segment)
    {
        $projectedProperty = $segment->getProjectedProperty();
        $projectedPropertyKind = null !== $projectedProperty ? $projectedProperty->getKind() : 0;
        $queryResult = null;
        switch ($projectedPropertyKind) {
            case ResourcePropertyKind::RESOURCE_REFERENCE:
                $queryResult = $this->getProviders()->getRelatedResourceReference(
                    $segment->getPrevious()->getTargetResourceSetWrapper(),
                    $segment->getPrevious()->getResult(),
                    $segment->getTargetResourceSetWrapper(),
                    $projectedProperty
                );
                break;
            case ResourcePropertyKind::RESOURCESET_REFERENCE:
                if ($segment->isSingleResult()) {
                    $queryResult = $this->getProviders()->getResourceFromRelatedResourceSet(
                        $segment->getPrevious()->getTargetResourceSetWrapper(),
                        $segment->getPrevious()->getResult(),
                        $segment->getTargetResourceSetWrapper(),
                        $projectedProperty,
                        $segment->getKeyDescriptor()
                    );
                } else {
                    $skipToken = $this->getRequest()->getInternalSkipTokenInfo();
                    $skipToken = (null !== $skipToken) ? $skipToken->getSkipTokenInfo() : null;
                    $queryResult = $this->getProviders()->getRelatedResourceSet(
                        $this->getRequest()->queryType,
                        $segment->getPrevious()->getTargetResourceSetWrapper(),
                        $segment->getPrevious()->getResult(),
                        $segment->getTargetResourceSetWrapper(),
                        $projectedProperty,
                        $this->getRequest()->getFilterInfo(),
                        null, // $orderby
                        null, // $top
                        null, // $skip
                        $skipToken
                    );
                }
                break;
            default:
                $this->checkResourceExistsByIdentifier($segment);
                assert(false, 'Invalid property kind type for resource retrieval');
        }
        return $queryResult;
    }

    /**
     * @param $segment
     * @throws ODataException
     */
    private function checkResourceExistsByIdentifier($segment)
    {
        if (null === $segment->getPrevious()->getResult()) {
            throw ODataException::createResourceNotFoundError(
                $segment->getPrevious()->getIdentifier()
            );
        }
    }

    /**
     * Applies the query options to the resource(s) retrieved from the data source.
     *
     * @param SegmentDescriptor $segment The descriptor which holds resource(s) on which query options to be applied
     */
    private function applyQueryOptions(SegmentDescriptor $segment)
    {
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
        $segment->setResult($result);
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
        if (null !== $internalOrderByInfo) {
            $orderByFunction = $internalOrderByInfo->getSorterFunction();
            usort($result, $orderByFunction);
        }
        //Apply $skiptoken option
        $internalSkipTokenInfo = $this->getRequest()->getInternalSkipTokenInfo();
        if (null !== $internalSkipTokenInfo) {
            $matchingIndex = $internalSkipTokenInfo->getIndexOfFirstEntryInTheNextPage($result);
            $result = array_slice($result, $matchingIndex);
        }
        //Apply $top and $skip option
        if (!empty($result)) {
            $top = $this->getRequest()->getTopCount();
            $skip = $this->getRequest()->getSkipCount();
            if (null === $skip) {
                $skip = 0;
            }
            $result = array_slice($result, $skip, $top);
        }
        return $result;
    }
}
