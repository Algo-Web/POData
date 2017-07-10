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
use POData\UriProcessor\Interfaces\IUriProcessor;
use POData\UriProcessor\QueryProcessor\QueryProcessor;
use POData\UriProcessor\ResourcePathProcessor\ResourcePathProcessor;
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
                    $segmentId = $segment->getIdentifier();
                    $singleton = $this->getService()->getProvidersWrapper()->resolveSingleton($segmentId);
                    $segment->setResult($singleton->get());
                    break;
                case TargetKind::RESOURCE():
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
                    $segment->setResult($queryResult);
                    break;
                case TargetKind::MEDIA_RESOURCE():
                    $segment->setResult($segment->getPrevious()->getResult());
                    // a media resource means we're done - bail out of segment processing
                    break 2;
                case TargetKind::PRIMITIVE():
                    break;
                case TargetKind::PRIMITIVE_VALUE():
                    break;
                case TargetKind::COMPLEX_OBJECT():
                    break;
                case TargetKind::BAG():
                    break;
                case TargetKind::LINK():
                    $previous = $segment->getPrevious();
                    assert(isset($previous));
                    $segment->setResult($previous->getResult());
                    break;
                default:
                    assert(false, "Not implemented yet");
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
        if (!$resourceSet || !$keyDescriptor) {
            $url = $this->getService()->getHost()->getAbsoluteRequestUri()->getUrlAsString();
            throw ODataException::createBadRequestError(
                Messages::badRequestInvalidUriForThisVerb($url, $requestMethod)
            );
        }
        $this->getProviders()->deleteResource($resourceSet, $segment->getResult());
    }

    /**
     * @return null|SegmentDescriptor
     */
    protected function getFinalEffectiveSegment()
    {
        $segment = $this->getRequest()->getLastSegment();
        // if last segment is $count, back up one
        if (ODataConstants::URI_COUNT_SEGMENT == $segment->getIdentifier()) {
            $segment = $segment->getPrevious();
            return $segment;
        }
        return $segment;
    }
}
