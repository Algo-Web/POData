<?php

declare(strict_types=1);

namespace POData\UriProcessor;

use Exception;
use POData\Common\HttpStatus;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\NotImplementedException;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\UrlFormatException;
use POData\IService;
use POData\ObjectModel\CynicDeserialiser;
use POData\ObjectModel\ModelDeserialiser;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataURL;
use POData\OperationContext\HTTPRequestMethod;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\Interfaces\IUriProcessor;
use POData\UriProcessor\QueryProcessor\QueryProcessor;
use POData\UriProcessor\ResourcePathProcessor\ResourcePathProcessor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentParser;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use ReflectionException;

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

    /**
     * @var ModelDeserialiser
     */
    private $cereal;

    /**
     * @var CynicDeserialiser
     */
    private $cynicDeserialiser;

    /**
     * Constructs a new instance of UriProcessor.
     *
     * @param  IService                  $service Reference to the data service instance
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws UrlFormatException
     * @throws ReflectionException
     */
    private function __construct(IService $service)
    {
        $this->service   = $service;
        $this->providers = $service->getProvidersWrapper();
        $this->request   = ResourcePathProcessor::process($service);
        $this->expander  = new RequestExpander(
            $this->getRequest(),
            $this->getService(),
            $this->getProviders()
        );
        $this->getRequest()->setUriProcessor($this);
        $this->cereal            = new ModelDeserialiser();
        $this->cynicDeserialiser = new CynicDeserialiser(
            $service->getMetadataProvider(),
            $service->getProvidersWrapper()
        );
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
     * Gets the data service instance.
     *
     * @return IService
     */
    public function getService()
    {
        return $this->service;
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
     * Process the resource path and query options of client's request uri.
     *
     * @param IService $service Reference to the data service instance
     *
     * @throws ODataException
     * @throws NotImplementedException
     * @throws ReflectionException
     * @throws UrlFormatException
     * @throws InvalidOperationException
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
     * Execute the client submitted request against the data source.
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws UrlFormatException
     * @throws ReflectionException
     */
    public function execute()
    {
        $service = $this->getService();
        if (!$service instanceof IService) {
            throw new InvalidOperationException('!($service instanceof IService)');
        }
        $context = $service->getOperationContext();
        $method  = $context->incomingRequest()->getMethod();

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
     * @throws ODataException
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    protected function executeGet()
    {
        $segments  = $this->getRequest()->getSegments();
        $root      = $this->getRequest()->getRootProjectionNode();
        $eagerLoad = (null === $root) ? [] : $root->getEagerLoadList();

        foreach ($segments as $segment) {
            $requestTargetKind = $segment->getTargetKind();

            switch ($requestTargetKind) {
                case TargetKind::SINGLETON():
                    $this->executeGetSingleton($segment);
                    break;
                case TargetKind::RESOURCE():
                    if (TargetSource::ENTITY_SET() == $segment->getTargetSource()) {
                        $this->handleSegmentTargetsToResourceSet($segment, $eagerLoad);
                    } else {
                        $this->executeGetResource($segment, $eagerLoad);
                    }
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
                    throw new InvalidOperationException('Not implemented yet');
            }

            if (null === $segment->getNext()
                || ODataConstants::URI_COUNT_SEGMENT == $segment->getNext()->getIdentifier()
            ) {
                $this->applyQueryOptions($segment);
            }
        }
    }

    /**
     * @param SegmentDescriptor $segment
     */
    private function executeGetSingleton(SegmentDescriptor $segment)
    {
        $segmentId = $segment->getIdentifier();
        $singleton = $this->getService()->getProvidersWrapper()->resolveSingleton($segmentId);
        $segment->setResult($singleton->get());
    }

    /**
     * Query for a resource set pointed by the given segment descriptor and update the descriptor with the result.
     *
     * @param  SegmentDescriptor         $segment   Describes the resource set to query
     * @param  array|null                $eagerLoad
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws ReflectionException
     */
    private function handleSegmentTargetsToResourceSet(SegmentDescriptor $segment, $eagerLoad)
    {
        if ($segment->isSingleResult()) {
            $entityInstance = $this->getProviders()->getResourceFromResourceSet(
                $segment->getTargetResourceSetWrapper(),
                $segment->getKeyDescriptor()
            );

            $segment->setResult($entityInstance);
        } else {
            $eagerLoad   = (null !== $eagerLoad) ? $eagerLoad : [];
            $skip        = (null == $this->getRequest()) ? 0 : $this->getRequest()->getSkipCount();
            $skip        = (null === $skip) ? 0 : $skip;
            $skipToken   = $this->getRequest()->getInternalSkipTokenInfo();
            $skipToken   = (null != $skipToken) ? $skipToken->getSkipTokenInfo() : null;
            $queryResult = $this->getProviders()->getResourceSet(
                $this->getRequest()->queryType,
                $segment->getTargetResourceSetWrapper(),
                $this->getRequest()->getFilterInfo(),
                $this->getRequest()->getInternalOrderByInfo(),
                $this->getRequest()->getTopCount(),
                $skip,
                $skipToken,
                $eagerLoad
            );
            $segment->setResult($queryResult);
        }
    }

    /**
     * @param  SegmentDescriptor         $segment
     * @param  array                     $eagerList
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws ReflectionException
     */
    private function executeGetResource(SegmentDescriptor $segment, array $eagerList = [])
    {
        foreach ($eagerList as $eager) {
            $nonEmpty = is_string($eager) && 0 < strlen($eager);
            if (!$nonEmpty) {
                throw new InvalidOperationException('Eager-load list elements must be non-empty strings');
            }
        }
        $isRelated = TargetSource::ENTITY_SET() == $segment->getTargetSource();
        if ($isRelated) {
            $queryResult = $this->executeGetResourceDirect($segment, $eagerList);
        } else {
            $queryResult = $this->executeGetResourceRelated($segment, $eagerList);
        }
        $segment->setResult($queryResult);
    }

    /**
     * @param  SegmentDescriptor         $segment
     * @param  array                     $eagerList
     * @throws ODataException
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return null|object|QueryResult
     */
    private function executeGetResourceDirect(SegmentDescriptor $segment, array $eagerList)
    {
        if ($segment->isSingleResult()) {
            $queryResult = $this->getProviders()->getResourceFromResourceSet(
                $segment->getTargetResourceSetWrapper(),
                $segment->getKeyDescriptor(),
                $eagerList
            );
        } else {
            $skip        = $this->getRequest()->getSkipCount();
            $skip        = (null === $skip) ? 0 : $skip;
            $skipToken   = $this->getRequest()->getInternalSkipTokenInfo();
            $skipToken   = (null != $skipToken) ? $skipToken->getSkipTokenInfo() : null;
            $queryResult = $this->getProviders()->getResourceSet(
                $this->getRequest()->queryType,
                $segment->getTargetResourceSetWrapper(),
                $this->getRequest()->getFilterInfo(),
                $this->getRequest()->getInternalOrderByInfo(),
                $this->getRequest()->getTopCount(),
                $skip,
                $skipToken,
                $eagerList
            );
        }
        return $queryResult;
    }

    /**
     * @param SegmentDescriptor $segment
     * @param $eagerList
     * @throws ODataException
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return null|object|QueryResult
     */
    private function executeGetResourceRelated(SegmentDescriptor $segment, $eagerList)
    {
        $projectedProperty     = $segment->getProjectedProperty();
        $projectedPropertyKind = null !== $projectedProperty ? $projectedProperty->getKind() :
            new ResourcePropertyKind(0);
        $queryResult = null;
        switch ($projectedPropertyKind) {
            case ResourcePropertyKind::RESOURCE_REFERENCE():
                $queryResult = $this->getProviders()->getRelatedResourceReference(
                    $segment->getPrevious()->getTargetResourceSetWrapper(),
                    $segment->getPrevious()->getResult(),
                    $segment->getTargetResourceSetWrapper(),
                    $projectedProperty
                );
                break;
            case ResourcePropertyKind::RESOURCESET_REFERENCE():
                if ($segment->isSingleResult()) {
                    $queryResult = $this->getProviders()->getResourceFromRelatedResourceSet(
                        $segment->getPrevious()->getTargetResourceSetWrapper(),
                        $segment->getPrevious()->getResult(),
                        $segment->getTargetResourceSetWrapper(),
                        $projectedProperty,
                        $segment->getKeyDescriptor()
                    );
                } else {
                    $skipToken   = $this->getRequest()->getInternalSkipTokenInfo();
                    $skipToken   = (null !== $skipToken) ? $skipToken->getSkipTokenInfo() : null;
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
                throw new InvalidOperationException('Invalid property kind type for resource retrieval');
        }
        return $queryResult;
    }

    /**
     * @param  SegmentDescriptor $segment
     * @throws ODataException
     */
    private function checkResourceExistsByIdentifier(SegmentDescriptor $segment)
    {
        if (null === $segment->getPrevious()->getResult()) {
            throw ODataException::createResourceNotFoundError(
                $segment->getPrevious()->getIdentifier()
            );
        }
    }

    /**
     * @param SegmentDescriptor $segment
     */
    private function executeGetLink(SegmentDescriptor $segment)
    {
        $previous = $segment->getPrevious();
        assert(isset($previous));
        $segment->setResult($previous->getResult());
    }

    /**
     * Applies the query options to the resource(s) retrieved from the data source.
     *
     * @param  SegmentDescriptor $segment The descriptor which holds resource(s) on which query options to be applied
     * @throws ODataException
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
     * @throws ODataException
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
            $result        = array_slice($result, $matchingIndex);
        }
        //Apply $top and $skip option
        if (!empty($result)) {
            $top  = $this->getRequest()->getTopCount();
            $skip = $this->getRequest()->getSkipCount();
            if (null === $skip) {
                $skip = 0;
            }
            $result = array_slice($result, $skip, $top);
        }
        return $result;
    }

    /**
     * Execute the client submitted request against the data source (DELETE).
     * @throws ODataException
     * @throws UrlFormatException
     */
    protected function executeDelete()
    {
        $segment       = $this->getFinalEffectiveSegment();
        $requestMethod = $this->getService()->getOperationContext()->incomingRequest()->getMethod();
        $resourceSet   = $segment->getTargetResourceSetWrapper();
        $keyDescriptor = $segment->getKeyDescriptor();

        $this->checkUriValidForSuppliedVerb($resourceSet, $keyDescriptor, $requestMethod);
        assert($resourceSet instanceof ResourceSet);
        $this->getProviders()->deleteResource($resourceSet, $segment->getResult());
        $this->getService()->getHost()->setResponseStatusCode(HttpStatus::CODE_NOCONTENT);
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
     * @throws UrlFormatException
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
     * Execute the client submitted request against the data source (PUT).
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws ReflectionException
     * @throws Exception
     */
    protected function executePut()
    {
        $segment       = $this->getFinalEffectiveSegment();
        $requestMethod = $this->getService()->getOperationContext()->incomingRequest()->getMethod();
        $resourceSet   = null !== $segment ? $segment->getTargetResourceSetWrapper() : null;
        $keyDescriptor = null !== $segment ? $segment->getKeyDescriptor() : null;

        $this->checkUriValidForSuppliedVerb($resourceSet, $keyDescriptor, $requestMethod);
        assert($resourceSet instanceof ResourceSet);
        assert($keyDescriptor instanceof KeyDescriptor);

        $payload = $this->getRequest()->getData();
        if (!$payload instanceof ODataEntry) {
            throw new InvalidOperationException(get_class($payload));
        }
        if (empty($payload->id)) {
            throw new InvalidOperationException('Payload ID must not be empty for PUT request');
        }
        $data = $this->getModelDeserialiser()->bulkDeserialise($resourceSet->getResourceType(), $payload);

        if (empty($data)) {
            throw ODataException::createBadRequestError(Messages::noDataForThisVerb($requestMethod));
        }

        $queryResult = $this->getCynicDeserialiser()->processPayload($payload);
        $segment->setResult($queryResult);
    }

    /**
     * @return ModelDeserialiser
     */
    public function getModelDeserialiser()
    {
        return $this->cereal;
    }

    /**
     * @return CynicDeserialiser
     */
    public function getCynicDeserialiser()
    {
        return $this->cynicDeserialiser;
    }

    /**
     * Execute the client submitted request against the data source (POST).
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws UrlFormatException
     * @throws ReflectionException
     * @throws Exception
     */
    protected function executePost()
    {
        $segments      = $this->getRequest()->getSegments();
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

                $payload = $this->getRequest()->getData();
                if ($payload instanceof ODataURL) {
                    $this->executeGet();
                    $masterModel        = $this->getRequest()->getSegments()[0]->getResult();
                    $masterResourceSet  = $this->getRequest()->getSegments()[0]->getTargetResourceSetWrapper();
                    $masterNavProperty  = $this->getRequest()->getLastSegment()->getIdentifier();
                    $slaveModelUri      = new Url($payload->getUrl());
                    $host               = $this->getService()->getHost();
                    $absoluteServiceUri = $host->getAbsoluteServiceUri();
                    $requestUriSegments = array_slice(
                        $slaveModelUri->getSegments(),
                        $absoluteServiceUri->getSegmentCount()
                    );
                    $newSegments = SegmentParser::parseRequestUriSegments(
                        $requestUriSegments,
                        $this->getService()->getProvidersWrapper(),
                        true
                    );
                    $this->executeGetResource($newSegments[0]);
                    $slaveModel       = $newSegments[0]->getResult();
                    $slaveResourceSet = $newSegments[0]->getTargetResourceSetWrapper();
                    $linkAdded        = $this->getProviders()
                        ->hookSingleModel(
                            $masterResourceSet,
                            $masterModel,
                            $slaveResourceSet,
                            $slaveModel,
                            $masterNavProperty
                        );
                    if ($linkAdded) {
                        $this->getService()->getHost()->setResponseStatusCode(HttpStatus::CODE_NOCONTENT);
                    } else {
                        throw ODataException::createInternalServerError('AdapterIndicatedLinkNotAttached');
                    }
                    foreach ($segments as $innerSegment) {
                        $innerSegment->setResult(null);
                    }
                    return;
                }
                if (!$payload instanceof ODataEntry) {
                    throw new InvalidOperationException(get_class($payload));
                }
                if (!empty($payload->id)) {
                    throw new InvalidOperationException('Payload ID must be empty for POST request');
                }

                $data = $this->getModelDeserialiser()->bulkDeserialise($resourceSet->getResourceType(), $payload);

                if (empty($data)) {
                    throw ODataException::createBadRequestError(Messages::noDataForThisVerb($requestMethod));
                }
                $this->getService()->getHost()->setResponseStatusCode(HttpStatus::CODE_CREATED);
                $queryResult = $this->getCynicDeserialiser()->processPayload($payload);
                $keyID       = $payload->id;
                if (!$keyID instanceof KeyDescriptor) {
                    throw new InvalidOperationException(get_class($keyID));
                }

                $locationUrl        = $keyID->generateRelativeUri($resourceSet->getResourceSet());
                $absoluteServiceUri = $this->getService()->getHost()->getAbsoluteServiceUri()->getUrlAsString();
                $location           = rtrim($absoluteServiceUri, '/') . '/' . $locationUrl;
                $this->getService()->getHost()->setResponseLocation($location);
                $segment->setResult($queryResult);
            }
        }
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
}
