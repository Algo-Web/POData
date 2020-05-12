<?php

declare(strict_types=1);

namespace POData;

use Exception;
use POData\BatchProcessor\BatchProcessor;
use POData\Common\ErrorHandler;
use POData\Common\HttpStatus;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\ReflectionHandler;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\Configuration\ServiceConfiguration;
use POData\ObjectModel\IObjectSerialiser;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataURLCollection;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Stream\IStreamProvider2;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\Readers\Atom\AtomODataReader;
use POData\Readers\ODataReaderRegistry;
use POData\UriProcessor\Interfaces\IUriProcessor;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessorNew;
use POData\Writers\Atom\AtomODataWriter;
use POData\Writers\Json\JsonLightMetadataLevel;
use POData\Writers\Json\JsonLightODataWriter;
use POData\Writers\Json\JsonODataV1Writer;
use POData\Writers\Json\JsonODataV2Writer;
use POData\Writers\ODataWriterRegistry;
use POData\Writers\ResponseWriter;
use ReflectionException;

/**
 * Class BaseService.
 *
 * The base class for all BaseService specific classes. This class implements
 * the following interfaces:
 *  (1) IRequestHandler
 *      Implementing this interface requires defining the function
 *      'handleRequest' that will be invoked by dispatcher
 *  (2) IService
 *      Force BaseService class to implement functions for custom
 *      data service providers
 */
abstract class BaseService implements IRequestHandler, IService
{
    /**
     * The wrapper over IStreamProvider implementation.
     *
     * @var StreamProviderWrapper
     */
    protected $streamProvider;
    /**
     * To hold reference to ServiceConfiguration instance where the
     * service specific rules (page limit, resource set access rights
     * etc...) are defined.
     *
     * @var IServiceConfiguration
     */
    protected $config;
    /**
     * Hold reference to object serialiser - bit wot turns PHP objects
     * into message traffic on wire.
     *
     * @var IObjectSerialiser
     */
    protected $objectSerialiser;
    /** @var ODataWriterRegistry */
    protected $writerRegistry;
    /** @var ODataReaderRegistry */
    protected $readerRegistry;
    /**
     * The wrapper over IQueryProvider and IMetadataProvider implementations.
     *
     * @var ProvidersWrapper
     */
    private $providersWrapper;
    /**
     * Hold reference to the ServiceHost instance created by dispatcher,
     * using this library can access headers and body of Http Request
     * dispatcher received and the Http Response Dispatcher is going to send.
     *
     * @var ServiceHost
     */
    private $serviceHost;

    /**
     * BaseService constructor.
     * @param  IObjectSerialiser|null     $serialiser
     * @param  IMetadataProvider|null     $metaProvider
     * @param  IServiceConfiguration|null $config
     * @throws Exception
     */
    protected function __construct(
        IObjectSerialiser $serialiser = null,
        IMetadataProvider $metaProvider = null,
        IServiceConfiguration $config = null
    ) {
        if (null != $serialiser) {
            $serialiser->setService($this);
        } else {
            $serialiser = new ObjectModelSerializer($this, null);
        }
        $this->config           = $config ?? $this->initializeDefaultConfig(new ServiceConfiguration($metaProvider));
        $this->objectSerialiser = $serialiser;
    }

    //TODO: shouldn't we hide this from the interface..if we need it at all.

    protected function initializeDefaultConfig(IServiceConfiguration $config)
    {
        return $config;
    }

    /**
     * Sets the data service host instance.
     *
     * @param ServiceHost $serviceHost The data service host instance
     */
    public function setHost(ServiceHost $serviceHost): void
    {
        $this->serviceHost = $serviceHost;
    }

    /**
     * To get reference to operation context where we have direct access to
     * headers and body of Http Request, we have received and the Http Response
     * We are going to send.
     *
     * @return IOperationContext
     */
    public function getOperationContext(): IOperationContext
    {
        return $this->getHost()->getOperationContext();
    }

    /**
     * Get reference to the data service host instance.
     *
     * @return ServiceHost
     */
    public function getHost(): ServiceHost
    {
        assert(null != $this->serviceHost);

        return $this->serviceHost;
    }

    /**
     * Get reference to the wrapper over IStreamProvider or
     * IStreamProvider2 implementations.
     *
     * @return StreamProviderWrapper
     */
    public function getStreamProvider(): StreamProviderWrapper
    {
        if (null === $this->streamProvider) {
            $this->streamProvider = new StreamProviderWrapper();
            $this->streamProvider->setService($this);
        }

        return $this->streamProvider;
    }

    /**
     * Top-level handler invoked by Dispatcher against any request to this
     * service. This method will hand over request processing task to other
     * functions which process the request, set required headers and Response
     * stream (if any in Atom/Json format) in
     * WebOperationContext::Current()::OutgoingWebResponseContext.
     * Once this function returns, dispatcher uses global WebOperationContext
     * to write out the request response to client.
     * This function will perform the following operations:
     * (1) Check whether the top level service class implements
     *     IServiceProvider which means the service is a custom service, in
     *     this case make sure the top level service class implements
     *     IMetaDataProvider and IQueryProvider.
     *     These are the minimal interfaces that a custom service to be
     *     implemented in order to expose its data as OData. Save reference to
     *     These interface implementations.
     *     NOTE: Here we will ensure only providers for IDSQP and IDSMP. The
     *     IDSSP will be ensured only when there is an GET request on MLE/Named
     *     stream.
     *
     * (2). Invoke 'Initialize' method of top level service for
     *      collecting the configuration rules set by the developer for this
     *      service.
     *
     * (3). Invoke the Uri processor to process the request URI. The uri
     *      processor will do the following:
     *      (a). Validate the request uri syntax using OData uri rules
     *      (b). Validate the request using metadata of this service
     *      (c). Parse the request uri and using, IQueryProvider
     *           implementation, fetches the resources pointed by the uri
     *           if required
     *      (d). Build a RequestDescription which encapsulate everything
     *           related to request uri (e.g. type of resource, result
     *           etc...)
     * (3). Invoke handleRequest2 for further processing
     * @throws ODataException
     */
    public function handleRequest()
    {
        try {
            $this->createProviders();
            $this->getHost()->validateQueryParameters();
            $uriProcessor = UriProcessorNew::process($this);
            $request      = $uriProcessor->getRequest();
            if (TargetKind::BATCH() == $request->getTargetKind()) {
                //dd($request);
                $this->getProvidersWrapper()->startTransaction(true);
                try {
                    $this->handleBatchRequest($request);
                } catch (Exception $ex) {
                    $this->getProvidersWrapper()->rollBackTransaction();
                    throw $ex;
                }
                $this->getProvidersWrapper()->commitTransaction();
            } else {
                $this->serializeResult($request, $uriProcessor);
            }
        } catch (Exception $exception) {
            ErrorHandler::handleException($exception, $this);
            // Return to dispatcher for writing serialized exception
            return;
        }
    }

    /**
     * This method will query and validates for IMetadataProvider and IQueryProvider implementations, invokes
     * BaseService::Initialize to initialize service specific policies.
     *
     * @throws ODataException
     * @throws Exception
     */
    protected function createProviders()
    {
        $metadataProvider = $this->getMetadataProvider();
        if (null === $metadataProvider) {
            throw ODataException::createInternalServerError(Messages::providersWrapperNull());
        }

        if (!$metadataProvider instanceof IMetadataProvider) {
            throw ODataException::createInternalServerError(Messages::invalidMetadataInstance());
        }

        $queryProvider = $this->getQueryProvider();

        if (null === $queryProvider) {
            throw ODataException::createInternalServerError(Messages::providersWrapperNull());
        }

        $this->providersWrapper = new ProvidersWrapper(
            $metadataProvider,
            $queryProvider,
            $this->config
        );

        $this->initialize($this->config);

        //TODO: this seems like a bad spot to do this
        $this->writerRegistry = new ODataWriterRegistry();
        $this->readerRegistry = new ODataReaderRegistry();
        $this->registerWriters();
        $this->registerReaders();
    }

    /**
     * @return IMetadataProvider
     */
    abstract public function getMetadataProvider();

    /**
     * @return IQueryProvider|null
     */
    abstract public function getQueryProvider(): ?IQueryProvider;

    /**
     * @throws Exception
     */
    public function registerWriters()
    {
        $registry       = $this->getODataWriterRegistry();
        $serviceVersion = $this->getConfiguration()->getMaxDataServiceVersion();
        $serviceURI     = $this->getHost()->getAbsoluteServiceUri()->getUrlAsString();

        //We always register the v1 stuff
        $registry->register(
            new JsonODataV1Writer(
                $this->getConfiguration()->getLineEndings(),
                $this->getConfiguration()->getPrettyOutput()
            )
        );
        $registry->register(
            new AtomODataWriter(
                $this->getConfiguration()->getLineEndings(),
                $this->getConfiguration()->getPrettyOutput(),
                $serviceURI
            )
        );

        if (-1 < $serviceVersion->compare(Version::v2())) {
            $registry->register(
                new JsonODataV2Writer(
                    $this->getConfiguration()->getLineEndings(),
                    $this->getConfiguration()->getPrettyOutput()
                )
            );
        }

        if (-1 < $serviceVersion->compare(Version::v3())) {
            $registry->register(
                new JsonLightODataWriter(
                    $this->getConfiguration()->getLineEndings(),
                    $this->getConfiguration()->getPrettyOutput(),
                    JsonLightMetadataLevel::NONE(),
                    $serviceURI
                )
            );
            $registry->register(
                new JsonLightODataWriter(
                    $this->getConfiguration()->getLineEndings(),
                    $this->getConfiguration()->getPrettyOutput(),
                    JsonLightMetadataLevel::MINIMAL(),
                    $serviceURI
                )
            );
            $registry->register(
                new JsonLightODataWriter(
                    $this->getConfiguration()->getLineEndings(),
                    $this->getConfiguration()->getPrettyOutput(),
                    JsonLightMetadataLevel::FULL(),
                    $serviceURI
                )
            );
        }
    }

    /**
     * Returns the ODataWriterRegistry to use when writing the response to a service document or resource request.
     *
     * @return ODataWriterRegistry
     */
    public function getODataWriterRegistry(): ODataWriterRegistry
    {
        assert(null != $this->writerRegistry);

        return $this->writerRegistry;
    }

    /**
     * Gets reference to ServiceConfiguration instance so that
     * service specific rules defined by the developer can be
     * accessed.
     *
     * @return IServiceConfiguration
     */
    public function getConfiguration(): IServiceConfiguration
    {
        assert(null != $this->config);

        return $this->config;
    }

    public function registerReaders()
    {
        $registry = $this->getODataReaderRegistry();
        //We always register the v1 stuff
        $registry->register(new AtomODataReader());
    }

    /**
     * Returns the ODataReaderRegistry to use when writing the response to a service document or resource request.
     *
     * @return ODataReaderRegistry
     */
    public function getODataReaderRegistry(): ODataReaderRegistry
    {
        assert(null != $this->writerRegistry);

        return $this->readerRegistry;
    }

    /**
     * Get the wrapper over developer's IQueryProvider and IMetadataProvider implementation.
     *
     * @return ProvidersWrapper
     */
    public function getProvidersWrapper(): ProvidersWrapper
    {
        return $this->providersWrapper;
    }

    /**
     * @param $request
     * @throws ODataException
     */
    private function handleBatchRequest($request)
    {
        $cloneThis      = clone $this;
        $batchProcessor = new BatchProcessor($cloneThis, $request);
        $batchProcessor->handleBatch();
        $response = $batchProcessor->getResponse();
        $this->getHost()->setResponseStatusCode(HttpStatus::CODE_ACCEPTED);
        $this->getHost()->setResponseContentType('multipart/mixed; boundary=' . $batchProcessor->getBoundary());
        // Hack: this needs to be sorted out in the future as we hookup other versions.
        $this->getHost()->setResponseVersion('3.0;');
        $this->getHost()->setResponseCacheControl(ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE);
        $this->getHost()->getOperationContext()->outgoingResponse()->setStream($response);
    }

    //TODO: i don't want this to be public..but it's the only way to test it right now...

    /**
     * Serialize the requested resource.
     *
     * @param RequestDescription $request      The description of the request  submitted by the client
     * @param IUriProcessor      $uriProcessor Reference to the uri processor
     *
     * @throws Common\HttpHeaderFailure
     * @throws Common\UrlFormatException
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws ReflectionException
     * @throws Exception
     */
    protected function serializeResult(RequestDescription $request, IUriProcessor $uriProcessor)
    {

        if ($this->getConfiguration()->getValidateETagHeader() && !$request->isETagHeaderAllowed() &&
            (null !== $this->getHost()->getRequestIfMatch() || null !== $this->getHost()->getRequestIfNoneMatch())) {
            throw ODataException::createBadRequestError(
                Messages::eTagCannotBeSpecified($this->getHost()->getAbsoluteRequestUri()->getUrlAsString())
            );
        }

        $targetKindToSeralizeMethod = [
            TargetKind::COMPLEX_OBJECT()->getValue() => 'writeTopLevelComplexObject',
            TargetKind::BAG()->getValue()            => 'writeTopLevelBagObject',
            TargetKind::PRIMITIVE()->getValue()      => 'writeTopLevelPrimitive',
        ];
        $targetKindsRequiringProperties = [
            TargetKind::COMPLEX_OBJECT(),
            TargetKind::BAG()
        ];
        $justWrite = [
            TargetKind::COMPLEX_OBJECT(),
            TargetKind::BAG(),
            TargetKind::PRIMITIVE()
        ];


        $responseContentType = $this->getResponseContentType($request, $uriProcessor);

        if (null === $responseContentType && $request->getTargetKind() != TargetKind::MEDIA_RESOURCE()) {
            //the responseContentType can ONLY be null if it's a stream (media resource) and
            // that stream is storing null as the content type
            throw new ODataException(Messages::unsupportedMediaType(), 415);
        }

        $odataModelInstance = null;
        $hasResponseBody    = true;
        // Execution required at this point if request points to any resource other than

        // (1) media resource - For Media resource 'getResponseContentType' already performed execution as
        // it needs to know the mime type of the stream
        // (2) metadata - internal resource
        // (3) service directory - internal resource
        if ($request->needExecution()) {
            $method = $this->getHost()->getOperationContext()->incomingRequest()->getMethod();
            $uriProcessor->execute();
            if (HTTPRequestMethod::DELETE() == $method) {
                $this->getHost()->setResponseStatusCode(HttpStatus::CODE_NOCONTENT);
                return;
            }

            $objectModelSerializer = $this->getObjectSerialiser();
            $objectModelSerializer->setRequest($request);

            $targetResourceType = $request->getTargetResourceType();
            if (null === $targetResourceType) {
                throw new InvalidOperationException('Target resource type cannot be null');
            }

            $methodIsNotPost   = (HTTPRequestMethod::POST() != $method);
            $methodIsNotDelete = (HTTPRequestMethod::DELETE() != $method);
            if (!$request->isSingleResult() && $methodIsNotPost) {
                // Code path for collection (feed or links)
                $entryObjects = $request->getTargetResult();
                assert($entryObjects instanceof QueryResult, '!$entryObjects instanceof QueryResult');
                assert(is_array($entryObjects->results), '!is_array($entryObjects->results)');

                // If related resource set is empty for an entry then we should
                // not throw error instead response must be empty feed or empty links
                $method = $request->isLinkUri() ? 'writeUrlElements' : 'writeTopLevelElements';
                $odataModelInstance = $objectModelSerializer->{$method}($entryObjects);
            } else {
                // Code path for entity, complex, bag, resource reference link,
                // primitive type or primitive value
                $result = $request->getTargetResult();
                if (!$result instanceof QueryResult) {
                    $result          = new QueryResult();
                    $result->results = $request->getTargetResult();
                }
                $requestTargetKind = $request->getTargetKind();
                $requestProperty   = $request->getProjectedProperty();
                if ($request->isLinkUri() && $methodIsNotPost && $methodIsNotDelete) {
                    // In the query 'Orders(1245)/$links/Customer', the targeted
                    // Customer might be null
                    if (null === $result->results) {
                        throw ODataException::createResourceNotFoundError($request->getIdentifier());
                    }
                    $odataModelInstance = $objectModelSerializer->writeUrlElement($result);
                } elseif (TargetKind::RESOURCE() == $requestTargetKind
                    || TargetKind::SINGLETON() == $requestTargetKind) {
                    if (null !== $this->getHost()->getRequestIfMatch()
                        && null !== $this->getHost()->getRequestIfNoneMatch()
                    ) {
                        throw ODataException::createBadRequestError(
                            Messages::bothIfMatchAndIfNoneMatchHeaderSpecified()
                        );
                    }
                    // handle entry resource
                    $needToSerializeResponse = true;
                    $eTag                    = $this->compareETag(
                        $result,
                        $targetResourceType,
                        $needToSerializeResponse
                    );
                    if ($needToSerializeResponse && null !== $result && null !== $result->results) {
                        $odataModelInstance = $objectModelSerializer->writeTopLevelElement($result);
                    } else {
                        // In the query 'Orders(1245)/Customer', the targeted
                        // Customer might be null
                        // set status code to 204 => 'No Content'

                        // Resource is not modified so set status code
                        // to 304 => 'Not Modified'
                        $status = $needToSerializeResponse ? HttpStatus::CODE_NOCONTENT : HttpStatus::CODE_NOT_MODIFIED ;

                        $this->getHost()->setResponseStatusCode($status);
                        $hasResponseBody = false;
                    }

                    // if resource has eTagProperty then eTag header needs to written
                    if (null !== $eTag) {
                        $this->getHost()->setResponseETag($eTag);
                    }
                } elseif (in_array($requestTargetKind, $justWrite)) {
                    $needsRequestProperty = in_array($requestTargetKind, $targetKindsRequiringProperties);
                    if (in_array($requestTargetKind, $targetKindsRequiringProperties) && null === $requestProperty) {
                        throw new InvalidOperationException('Projected request property cannot be null');
                    }
                    $property = $needsRequestProperty ? $requestProperty->getName() : $requestProperty;
                    $odataModelInstance = $objectModelSerializer->{$targetKindToSeralizeMethod[$requestTargetKind->getValue()]}(
                        $result,
                        $property,
                        $targetResourceType
                    );
                } elseif (TargetKind::PRIMITIVE_VALUE() == $requestTargetKind) {
                    // Code path for primitive value (Since its primitive no need for
                    // object model serialization)
                    // Customers('ANU')/CompanyName/$value => string
                    // Employees(1)/Photo/$value => binary stream
                    // Customers/$count => string
                } else {
                    throw new InvalidOperationException('Unexpected resource target kind');
                }
            }
        }

        //Note: Response content type can be null for named stream
        if ($hasResponseBody && null !== $responseContentType &&
            TargetKind::MEDIA_RESOURCE() != $request->getTargetKind() &&
            MimeTypes::MIME_APPLICATION_OCTETSTREAM != $responseContentType)
        {
            //append charset for everything except:
            //stream resources as they have their own content type
            //binary properties (they content type will be App Octet for those...is this a good way?
            //we could also decide based upon the projected property

            $responseContentType .= ';charset=utf-8';
        }

        if ($hasResponseBody) {
            ResponseWriter::write($this, $request, $odataModelInstance, $responseContentType);
        }
    }

    /**
     * Gets the response format for the requested resource.
     *
     * @param RequestDescription $request      The request submitted by client and it's execution result
     * @param IUriProcessor      $uriProcessor The reference to the IUriProcessor
     *
     * @throws Common\HttpHeaderFailure
     * @throws InvalidOperationException
     * @throws ODataException            , HttpHeaderFailure
     * @throws ReflectionException
     * @throws Common\UrlFormatException
     * @return string|null               the response content-type, a null value means the requested resource
     *                                   is named stream and IDSSP2::getStreamContentType returned null
     */
    public function getResponseContentType(
        RequestDescription $request,
        IUriProcessor $uriProcessor
    ): ?string {
        $baseMimeTypes = [
            MimeTypes::MIME_APPLICATION_JSON,
            MimeTypes::MIME_APPLICATION_JSON_FULL_META,
            MimeTypes::MIME_APPLICATION_JSON_NO_META,
            MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META,
            MimeTypes::MIME_APPLICATION_JSON_VERBOSE,];

        // The Accept request-header field specifies media types which are acceptable for the response

        $host              = $this->getHost();
        $requestAcceptText = $host->getRequestAccept();
        $requestVersion    = $request->getResponseVersion();

        //if the $format header is present it overrides the accepts header
        $format = $host->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FORMAT);
        if (null !== $format) {
            //There's a strange edge case..if application/json is supplied and it's V3
            if (MimeTypes::MIME_APPLICATION_JSON == $format && Version::v3() == $requestVersion) {
                //then it's actual minimalmetadata
                //TODO: should this be done with the header text too?
                $format = MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META;
            }

            $requestAcceptText = ServiceHost::translateFormatToMime($requestVersion, $format);
        }

        //The response format can be dictated by the target resource kind. IE a $value will be different then expected
        //getTargetKind doesn't deal with link resources directly and this can change things
        $targetKind = $request->isLinkUri() ? TargetKind::LINK() : $request->getTargetKind();

        $availableMimeTypesByTarget = [
            TargetKind::METADATA()->getValue()          => [MimeTypes::MIME_APPLICATION_XML],
            TargetKind::SERVICE_DIRECTORY()->getValue() => array_merge([MimeTypes::MIME_APPLICATION_ATOMSERVICE], $baseMimeTypes),
            TargetKind::PRIMITIVE()->getValue()              => array_merge([MimeTypes::MIME_APPLICATION_XML, MimeTypes::MIME_TEXTXML,], $baseMimeTypes),
            TargetKind::COMPLEX_OBJECT()->getValue()              => array_merge([MimeTypes::MIME_APPLICATION_XML, MimeTypes::MIME_TEXTXML,], $baseMimeTypes),
            TargetKind::BAG()->getValue()              => array_merge([MimeTypes::MIME_APPLICATION_XML, MimeTypes::MIME_TEXTXML,], $baseMimeTypes),
            TargetKind::LINK()->getValue()              => array_merge([MimeTypes::MIME_APPLICATION_XML, MimeTypes::MIME_TEXTXML,], $baseMimeTypes),
            TargetKind::SINGLETON()->getValue()          => array_merge([MimeTypes::MIME_APPLICATION_ATOM], $baseMimeTypes),
            TargetKind::RESOURCE()->getValue()          => array_merge([MimeTypes::MIME_APPLICATION_ATOM], $baseMimeTypes),
        ];

        if($targetKind && array_key_exists($targetKind->getValue(),$availableMimeTypesByTarget)){
            return HttpProcessUtility::selectMimeType($requestAcceptText, $availableMimeTypesByTarget[$targetKind->getValue()]);

        }
        switch ($targetKind) {
            case TargetKind::PRIMITIVE_VALUE():
                $supportedResponseMimeTypes = [MimeTypes::MIME_TEXTPLAIN];

                if ('$count' != $request->getIdentifier()) {
                    $projectedProperty = $request->getProjectedProperty();
                    if (null === $projectedProperty) {
                        throw new InvalidOperationException('is_null($projectedProperty)');
                    }
                    $type = $projectedProperty->getInstanceType();
                    if (!$type instanceof IType) {
                        throw new InvalidOperationException('!$type instanceof IType');
                    }
                    if ($type instanceof Binary) {
                        $supportedResponseMimeTypes = [MimeTypes::MIME_APPLICATION_OCTETSTREAM];
                    }
                }

                return HttpProcessUtility::selectMimeType(
                    $requestAcceptText,
                    $supportedResponseMimeTypes
                );


            case TargetKind::MEDIA_RESOURCE():
                if (!$request->isNamedStream() && true !== $request->getTargetResourceType()->isMediaLinkEntry()) {
                    throw ODataException::createBadRequestError(
                        Messages::badRequestInvalidUriForMediaResource(
                            $host->getAbsoluteRequestUri()->getUrlAsString()
                        )
                    );
                }

                $uriProcessor->execute();
                $request->setExecuted();
                // DSSW::getStreamContentType can throw error in 2 cases
                // 1. If the required stream implementation not found
                // 2. If IDSSP::getStreamContentType returns NULL for MLE
                $responseContentType = $this->getStreamProviderWrapper()
                    ->getStreamContentType(
                        $request->getTargetResult(),
                        $request->getResourceStreamInfo()
                    );

                // Note StreamWrapper::getStreamContentType can return NULL if the requested named stream has not
                // yet been uploaded. But for an MLE if IDSSP::getStreamContentType returns NULL
                // then StreamWrapper will throw error
                if (null !== $responseContentType) {
                    $responseContentType = HttpProcessUtility::selectMimeType(
                        $requestAcceptText,
                        [$responseContentType]
                    );
                }

                return $responseContentType;
        }

        //If we got here, we just don't know what it is...
        throw new ODataException(Messages::unsupportedMediaType(), 415);
    }

    /**
     * Gets reference to wrapper class instance over IDSSP implementation.
     *
     * @return StreamProviderWrapper
     */
    public function getStreamProviderWrapper()
    {
        return $this->streamProvider;
    }

    /**
     * Get reference to object serialiser - bit wot turns PHP objects
     * into message traffic on wire.
     *
     * @return IObjectSerialiser
     */
    public function getObjectSerialiser(): IObjectSerialiser
    {
        assert(null != $this->objectSerialiser);

        return $this->objectSerialiser;
    }

    /**
     * For the given entry object compare its eTag (if it has eTag properties)
     * with current eTag request headers (if present).
     *
     * @param mixed        &$entryObject             entity resource for which etag
     *                                               needs to be checked
     * @param ResourceType &$resourceType            Resource type of the entry
     *                                               object
     * @param bool         &$needToSerializeResponse On return, this will contain
     *                                               True if response needs to be
     *                                               serialized, False otherwise
     *
     * @throws InvalidOperationException
     * @throws ReflectionException
     * @throws ODataException
     * @return string|null               The ETag for the entry object if it has eTag properties
     *                                   NULL otherwise
     */
    protected function compareETag(
        &$entryObject,
        ResourceType &$resourceType,
        &$needToSerializeResponse
    ): ?string {
        $needToSerializeResponse = true;
        $eTag                    = null;
        $ifMatch                 = $this->getHost()->getRequestIfMatch();
        $ifNoneMatch             = $this->getHost()->getRequestIfNoneMatch();
        if (null === $entryObject) {
            if (null !== $ifMatch) {
                throw ODataException::createPreConditionFailedError(
                    Messages::eTagNotAllowedForNonExistingResource()
                );
            }

            return null;
        }

        if ($this->getConfiguration()->getValidateETagHeader() && !$resourceType->hasETagProperties()) {
            if (null !== $ifMatch || null !== $ifNoneMatch) {
                // No eTag properties but request has eTag headers, bad request
                throw ODataException::createBadRequestError(
                    Messages::noETagPropertiesForType()
                );
            }

            // We need write the response but no eTag header
            return null;
        }

        if (!$this->getConfiguration()->getValidateETagHeader()) {
            // Configuration says do not validate ETag, so we will not write ETag header in the
            // response even though the requested resource support it
            return null;
        }

        if (null === $ifMatch && null === $ifNoneMatch) {
            // No request eTag header, we need to write the response
            // and eTag header
        } elseif (0 === strcmp(strval($ifMatch), '*')) {
            // If-Match:* => we need to write the response and eTag header
        } elseif (0 === strcmp(strval($ifNoneMatch), '*')) {
            // if-None-Match:* => Do not write the response (304 not modified),
            // but write eTag header
            $needToSerializeResponse = false;
        } else {
            $eTag = $this->getETagForEntry($entryObject, $resourceType);
            // Note: The following code for attaching the prefix W\"
            // and the suffix " can be done in getETagForEntry function
            // but that is causing an issue in Linux env where the
            // firefox browser is unable to parse the ETag in this case.
            // Need to follow up PHP core devs for this.
            $eTag = ODataConstants::HTTP_WEAK_ETAG_PREFIX . $eTag . '"';
            if (null !== $ifMatch) {
                if (0 != strcmp($eTag, $ifMatch)) {
                    // Requested If-Match value does not match with current
                    // eTag Value then pre-condition error
                    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
                    throw ODataException::createPreConditionFailedError(
                        Messages::eTagValueDoesNotMatch()
                    );
                }
            } elseif (0 === strcmp($eTag, $ifNoneMatch)) {
                //304 not modified, but in write eTag header
                $needToSerializeResponse = false;
            }
        }

        if (null === $eTag) {
            $eTag = $this->getETagForEntry($entryObject, $resourceType);
            // Note: The following code for attaching the prefix W\"
            // and the suffix " can be done in getETagForEntry function
            // but that is causing an issue in Linux env where the
            // firefox browser is unable to parse the ETag in this case.
            // Need to follow up PHP core devs for this.
            $eTag = ODataConstants::HTTP_WEAK_ETAG_PREFIX . $eTag . '"';
        }

        return $eTag;
    }

    /**
     * Returns the etag for the given resource.
     * Note: This function will not add W\" prefix and " suffix, that is caller's
     * responsibility.
     *
     * @param mixed        &$entryObject  Resource for which etag value needs to
     *                                    be returned
     * @param ResourceType &$resourceType Resource type of the $entryObject
     *
     * @throws InvalidOperationException
     * @throws ReflectionException
     * @throws ODataException
     * @return string|null               ETag value for the given resource (with values encoded
     *                                   for use in a URI) there are etag properties, NULL if
     *                                   there is no etag property
     */
    protected function getETagForEntry(&$entryObject, ResourceType &$resourceType): ?string
    {
        $eTag  = null;
        $comma = null;
        foreach ($resourceType->getETagProperties() as $eTagProperty) {
            $type = $eTagProperty->getInstanceType();
            if (!$type instanceof IType) {
                throw new InvalidOperationException('!$type instanceof IType');
            }

            $value    = null;
            $property = $eTagProperty->getName();
            try {
                //TODO #88...also this seems like dupe work
                $value = ReflectionHandler::getProperty($entryObject, $property);
            } catch (ReflectionException $reflectionException) {
                throw ODataException::createInternalServerError(
                    Messages::failedToAccessProperty($property, $resourceType->getName())
                );
            }

            $eTagBase = $eTag . $comma;
            $eTag     = $eTagBase . ((null == $value) ? 'null' : $type->convertToOData($value));

            $comma = ',';
        }

        if (null !== $eTag) {
            // If eTag is made up of datetime or string properties then the above
            // IType::convertToOData will perform utf8 and url encode. But we don't
            // want this for eTag value.
            $eTag = urldecode(utf8_decode($eTag));

            return rtrim($eTag, ',');
        }
        return null;
    }

    /**
     * @return IStreamProvider2
     */
    abstract public function getStreamProviderX();
}
