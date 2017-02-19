<?php

namespace POData;

use POData\Common\MimeTypes;
use POData\Common\ReflectionHandler;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\ObjectModel\IObjectSerialiser;
use POData\OperationContext\HTTPRequestMethod;
use POData\Common\ErrorHandler;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Common\ODataConstants;
use POData\Common\NotImplementedException;
use POData\Common\InvalidOperationException;
use POData\Common\HttpStatus;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\Configuration\ServiceConfiguration;
use POData\UriProcessor\UriProcessor;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\ObjectModel\ObjectModelSerializer;
use POData\Writers\Atom\AtomODataWriter;
use POData\Writers\Json\JsonLightMetadataLevel;
use POData\Writers\Json\JsonLightODataWriter;
use POData\Writers\Json\JsonODataV1Writer;
use POData\Writers\Json\JsonODataV2Writer;
use POData\Writers\ResponseWriter;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Metadata\IMetadataProvider;
use POData\OperationContext\IOperationContext;
use POData\Writers\ODataWriterRegistry;

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
     * The wrapper over IQueryProvider and IMetadataProvider implementations.
     *
     * @var ProvidersWrapper
     */
    private $providersWrapper;

    /**
     * The wrapper over IStreamProvider implementation.
     *
     * @var StreamProviderWrapper
     */
    protected $streamProvider;

    /**
     * Hold reference to the ServiceHost instance created by dispatcher,
     * using this library can access headers and body of Http Request
     * dispatcher received and the Http Response Dispatcher is going to send.
     *
     * @var ServiceHost
     */
    private $_serviceHost;

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
     * into message traffic on wire
     *
     * @var IObjectSerialiser
     */
    protected $objectSerialiser;

    protected function __construct(IObjectSerialiser $serialiser = null)
    {
        $this->objectSerialiser = (null == $serialiser) ? new ObjectModelSerializer($this, null) : $serialiser;
    }

    /**
     * Gets reference to ServiceConfiguration instance so that
     * service specific rules defined by the developer can be
     * accessed.
     *
     * @return IServiceConfiguration
     */
    public function getConfiguration()
    {
        assert(null != $this->config);
        return $this->config;
    }

    //TODO: shouldn't we hide this from the interface..if we need it at all.
    /**
     * Get the wrapper over developer's IQueryProvider and IMetadataProvider implementation.
     *
     * @return ProvidersWrapper
     */
    public function getProvidersWrapper()
    {
        return $this->providersWrapper;
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
     * Get reference to the data service host instance.
     *
     * @return ServiceHost
     */
    public function getHost()
    {
        assert(null != $this->_serviceHost);
        return $this->_serviceHost;
    }

    /**
     * Sets the data service host instance.
     *
     * @param ServiceHost $serviceHost The data service host instance
     */
    public function setHost(ServiceHost $serviceHost)
    {
        $this->_serviceHost = $serviceHost;
    }

    /**
     * To get reference to operation context where we have direct access to
     * headers and body of Http Request, we have received and the Http Response
     * We are going to send.
     *
     * @return IOperationContext
     */
    public function getOperationContext()
    {
        return $this->getHost()->getOperationContext();
    }

    /**
     * Get reference to the wrapper over IStreamProvider or
     * IStreamProvider2 implementations.
     *
     * @return StreamProviderWrapper
     */
    public function getStreamProvider()
    {
        if (is_null($this->streamProvider)) {
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
     */
    public function handleRequest()
    {
        try {
            $this->createProviders();
            $this->getHost()->validateQueryParameters();
            //$requestMethod = $this->getOperationContext()->incomingRequest()->getMethod();
            //if ($requestMethod != HTTPRequestMethod::GET()) {
            // Now supporting GET and trying to support PUT
            //throw ODataException::createNotImplementedError(Messages::onlyReadSupport($requestMethod));
            //}

            $uriProcessor = UriProcessor::process($this);
            $request = $uriProcessor->getRequest();
            $this->serializeResult($request, $uriProcessor);
        } catch (\Exception $exception) {
            ErrorHandler::handleException($exception, $this);
            // Return to dispatcher for writing serialized exception
            return;
        }
    }

    /**
     * @return IQueryProvider
     */
    abstract public function getQueryProvider();

    /**
     * @return IMetadataProvider
     */
    abstract public function getMetadataProvider();

    /**
     * @return \POData\Providers\Stream\IStreamProvider
     */
    abstract public function getStreamProviderX();

    /** @var ODataWriterRegistry */
    private $writerRegistry;

    /**
     * Returns the ODataWriterRegistry to use when writing the response to a service document or resource request.
     *
     * @return ODataWriterRegistry
     */
    public function getODataWriterRegistry()
    {
        return $this->writerRegistry;
    }

    /**
     * This method will query and validates for IMetadataProvider and IQueryProvider implementations, invokes
     * BaseService::Initialize to initialize service specific policies.
     *
     * @throws ODataException
     */
    protected function createProviders()
    {
        $metadataProvider = $this->getMetadataProvider();
        if (is_null($metadataProvider)) {
            throw ODataException::createInternalServerError(Messages::providersWrapperNull());
        }

        if (!$metadataProvider instanceof IMetadataProvider) {
            throw ODataException::createInternalServerError(Messages::invalidMetadataInstance());
        }

        $queryProvider = $this->getQueryProvider();

        if (is_null($queryProvider)) {
            throw ODataException::createInternalServerError(Messages::providersWrapperNull());
        }

        if (!$queryProvider instanceof IQueryProvider) {
            throw ODataException::createInternalServerError(Messages::invalidQueryInstance());
        }

        $this->config = new ServiceConfiguration($metadataProvider);
        $this->providersWrapper = new ProvidersWrapper(
            $metadataProvider,
            $queryProvider,
            $this->config
        );

        $this->initialize($this->config);

        //TODO: this seems like a bad spot to do this
        $this->writerRegistry = new ODataWriterRegistry();
        $this->registerWriters();
    }

    //TODO: i don't want this to be public..but it's the only way to test it right now...
    public function registerWriters()
    {
        $registry = $this->getODataWriterRegistry();
        $serviceVersion = $this->getConfiguration()->getMaxDataServiceVersion();
        $serviceURI = $this->getHost()->getAbsoluteServiceUri()->getUrlAsString();

        //We always register the v1 stuff
        $registry->register(new JsonODataV1Writer());
        $registry->register(new AtomODataWriter($serviceURI));

        if (-1 < $serviceVersion->compare(Version::v2())) {
            $registry->register(new JsonODataV2Writer());
        }

        if (-1 < $serviceVersion->compare(Version::v3())) {
            $registry->register(new JsonLightODataWriter(JsonLightMetadataLevel::NONE(), $serviceURI));
            $registry->register(new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $serviceURI));
            $registry->register(new JsonLightODataWriter(JsonLightMetadataLevel::FULL(), $serviceURI));
        }
    }

    /**
     * Serialize the requested resource.
     *
     * @param RequestDescription $request      The description of the request  submitted by the client
     * @param UriProcessor       $uriProcessor Reference to the uri processor
     */
    protected function serializeResult(RequestDescription $request, UriProcessor $uriProcessor)
    {
        $isETagHeaderAllowed = $request->isETagHeaderAllowed();
        if ($this->getConfiguration()->getValidateETagHeader() && !$isETagHeaderAllowed) {
            if (!is_null($this->getHost()->getRequestIfMatch())
                || !is_null($this->getHost()->getRequestIfNoneMatch())
            ) {
                throw ODataException::createBadRequestError(
                    Messages::eTagCannotBeSpecified($this->getHost()->getAbsoluteRequestUri()->getUrlAsString())
                );
            }
        }

        $responseContentType = self::getResponseContentType($request, $uriProcessor, $this);

        if (is_null($responseContentType) && $request->getTargetKind() != TargetKind::MEDIA_RESOURCE()) {
            //the responseContentType can ONLY be null if it's a stream (media resource) and
            // that stream is storing null as the content type
            throw new ODataException(Messages::unsupportedMediaType(), 415);
        }

        $odataModelInstance = null;
        $hasResponseBody = true;
        // Execution required at this point if request points to any resource other than

        // (1) media resource - For Media resource 'getResponseContentType' already performed execution as
        // it needs to know the mime type of the stream
        // (2) metadata - internal resource
        // (3) service directory - internal resource
        if ($request->needExecution()) {
            $method = $this->getHost()->getOperationContext()->incomingRequest()->getMethod();
            $uriProcessor->execute();
            if (HTTPRequestMethod::DELETE() == $method) {
                $this->getHost()->setResponseStatusCode(HttpStatus::CODE_OK);
                return;
            }

            $this->objectSerialiser->setRequest($request);
            $objectModelSerializer = $this->objectSerialiser;
            $targetResourceType = $request->getTargetResourceType();
            assert(null != $targetResourceType, "Target resource type cannot be null");

            $method = (HTTPRequestMethod::POST() != $method);
            if (!$request->isSingleResult() && $method) {
                // Code path for collection (feed or links)
                $entryObjects = $request->getTargetResult();
                assert(is_array($entryObjects), '!is_array($entryObjects)');
                // If related resource set is empty for an entry then we should
                // not throw error instead response must be empty feed or empty links
                if ($request->isLinkUri()) {
                    $odataModelInstance = $objectModelSerializer->writeUrlElements($entryObjects);
                    assert(
                        $odataModelInstance instanceof \POData\ObjectModel\ODataURLCollection,
                        '!$odataModelInstance instanceof ODataURLCollection'
                    );
                } else {
                    $odataModelInstance = $objectModelSerializer->writeTopLevelElements($entryObjects);
                    assert(
                        $odataModelInstance instanceof \POData\ObjectModel\ODataFeed,
                        '!$odataModelInstance instanceof ODataFeed'
                    );
                }
            } else {
                // Code path for entry, complex, bag, resource reference link,
                // primitive type or primitive value
                $result = $request->getTargetResult();
                $requestTargetKind = $request->getTargetKind();
                if ($request->isLinkUri()) {
                    // In the query 'Orders(1245)/$links/Customer', the targeted
                    // Customer might be null
                    if (is_null($result)) {
                        throw ODataException::createResourceNotFoundError(
                            $request->getIdentifier()
                        );
                    }

                    $odataModelInstance = $objectModelSerializer->writeUrlElement($result);
                } elseif (TargetKind::RESOURCE() == $requestTargetKind) {
                    if (!is_null($this->getHost()->getRequestIfMatch())
                        && !is_null($this->getHost()->getRequestIfNoneMatch())
                    ) {
                        throw ODataException::createBadRequestError(
                            Messages::bothIfMatchAndIfNoneMatchHeaderSpecified()
                        );
                    }
                    // handle entry resource
                    $needToSerializeResponse = true;
                    $eTag = $this->compareETag(
                        $result,
                        $targetResourceType,
                        $needToSerializeResponse
                    );

                    if ($needToSerializeResponse) {
                        if (is_null($result)) {
                            // In the query 'Orders(1245)/Customer', the targeted
                            // Customer might be null
                            // set status code to 204 => 'No Content'
                            $this->getHost()->setResponseStatusCode(HttpStatus::CODE_NOCONTENT);
                            $hasResponseBody = false;
                        } else {
                            $odataModelInstance = $objectModelSerializer->writeTopLevelElement($result);
                        }
                    } else {
                        // Resource is not modified so set status code
                        // to 304 => 'Not Modified'
                        $this->getHost()->setResponseStatusCode(HttpStatus::CODE_NOT_MODIFIED);
                        $hasResponseBody = false;
                    }

                    // if resource has eTagProperty then eTag header needs to written
                    if (!is_null($eTag)) {
                        $this->getHost()->setResponseETag($eTag);
                    }
                } elseif (TargetKind::COMPLEX_OBJECT() == $requestTargetKind) {
                    $odataModelInstance = $objectModelSerializer->writeTopLevelComplexObject(
                        $result,
                        $request->getProjectedProperty()->getName(),
                        $targetResourceType
                    );
                } elseif (TargetKind::BAG() == $requestTargetKind) {
                    $odataModelInstance = $objectModelSerializer->writeTopLevelBagObject(
                        $result,
                        $request->getProjectedProperty()->getName(),
                        $targetResourceType,
                        $odataModelInstance
                    );
                } elseif (TargetKind::PRIMITIVE() == $requestTargetKind) {
                    $odataModelInstance = $objectModelSerializer->writeTopLevelPrimitive(
                        $result,
                        $request->getProjectedProperty(),
                        $odataModelInstance
                    );
                } elseif (TargetKind::PRIMITIVE_VALUE() == $requestTargetKind) {
                    // Code path for primitive value (Since its primitve no need for
                    // object model serialization)
                    // Customers('ANU')/CompanyName/$value => string
                    // Employees(1)/Photo/$value => binary stream
                    // Customers/$count => string
                } else {
                    assert(false, 'Unexpected resource target kind');
                }
            }
        }

        //Note: Response content type can be null for named stream
        if ($hasResponseBody && !is_null($responseContentType)) {
            if (TargetKind::MEDIA_RESOURCE() != $request->getTargetKind()
                && MimeTypes::MIME_APPLICATION_OCTETSTREAM != $responseContentType) {
                //append charset for everything except:
                //stream resources as they have their own content type
                //binary properties (they content type will be App Octet for those...is this a good way?
                //we could also decide based upon the projected property

                $responseContentType .= ';charset=utf-8';
            }
        }

        if ($hasResponseBody) {
            ResponseWriter::write(
                $this,
                $request,
                $odataModelInstance,
                $responseContentType
            );
        }
    }

    /**
     * Gets the response format for the requested resource.
     *
     * @param RequestDescription $request      The request submitted by client and it's execution result
     * @param UriProcessor       $uriProcessor The reference to the UriProcessor
     * @param IService           $service      Reference to the service implementation instance
     *
     * @return string the response content-type, a null value means the requested resource
     *                is named stream and IDSSP2::getStreamContentType returned null
     *
     * @throws ODataException, HttpHeaderFailure
     */
    public static function getResponseContentType(
        RequestDescription $request,
        UriProcessor $uriProcessor,
        IService $service
    ) {

        $baseMimeTypes = [
            MimeTypes::MIME_APPLICATION_JSON,
            MimeTypes::MIME_APPLICATION_JSON_FULL_META,
            MimeTypes::MIME_APPLICATION_JSON_NO_META,
            MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META,
            MimeTypes::MIME_APPLICATION_JSON_VERBOSE];


        // The Accept request-header field specifies media types which are acceptable for the response

        $host = $service->getHost();
        $requestAcceptText = $host->getRequestAccept();
        $requestVersion = $request->getResponseVersion();

        //if the $format header is present it overrides the accepts header
        $format = $host->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FORMAT);
        if (!is_null($format)) {
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

        switch ($targetKind) {
            case TargetKind::METADATA():
                return HttpProcessUtility::selectMimeType(
                    $requestAcceptText,
                    array(MimeTypes::MIME_APPLICATION_XML)
                );

            case TargetKind::SERVICE_DIRECTORY():
                return HttpProcessUtility::selectMimeType(
                    $requestAcceptText,
                    array_merge(
                        [MimeTypes::MIME_APPLICATION_ATOMSERVICE,
                        MimeTypes::MIME_APPLICATION_XML],
                        $baseMimeTypes
                    )
                );

            case TargetKind::PRIMITIVE_VALUE():
                $supportedResponseMimeTypes = array(MimeTypes::MIME_TEXTPLAIN);

                if ('$count' != $request->getIdentifier()) {
                    $projectedProperty = $request->getProjectedProperty();
                    assert(!is_null($projectedProperty), 'is_null($projectedProperty)');
                    $type = $projectedProperty->getInstanceType();
                    assert($type instanceof IType, '!$type instanceof IType');
                    if ($type instanceof Binary) {
                        $supportedResponseMimeTypes = array(MimeTypes::MIME_APPLICATION_OCTETSTREAM);
                    }
                }

                return HttpProcessUtility::selectMimeType(
                    $requestAcceptText,
                    $supportedResponseMimeTypes
                );

            case TargetKind::PRIMITIVE():
            case TargetKind::COMPLEX_OBJECT():
            case TargetKind::BAG():
            case TargetKind::LINK():
                return HttpProcessUtility::selectMimeType(
                    $requestAcceptText,
                    array_merge(
                        [MimeTypes::MIME_APPLICATION_XML,
                        MimeTypes::MIME_TEXTXML],
                        $baseMimeTypes
                    )
                );

            case TargetKind::RESOURCE():
                return HttpProcessUtility::selectMimeType(
                    $requestAcceptText,
                    array_merge(
                        [MimeTypes::MIME_APPLICATION_ATOM],
                        $baseMimeTypes
                    )
                );

            case TargetKind::MEDIA_RESOURCE():
                if (!$request->isNamedStream() && !$request->getTargetResourceType()->isMediaLinkEntry()) {
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
                $responseContentType = $service->getStreamProviderWrapper()
                    ->getStreamContentType(
                        $request->getTargetResult(),
                        $request->getResourceStreamInfo()
                    );

                // Note StreamWrapper::getStreamContentType can return NULL if the requested named stream has not
                // yet been uploaded. But for an MLE if IDSSP::getStreamContentType returns NULL
                // then StreamWrapper will throw error
                if (!is_null($responseContentType)) {
                    $responseContentType = HttpProcessUtility::selectMimeType(
                        $requestAcceptText,
                        array($responseContentType)
                    );
                }

                return $responseContentType;
        }

        //If we got here, we just don't know what it is...
        throw new ODataException(Messages::unsupportedMediaType(), 415);
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
     * @param bool         $needToSerializeResponse
     *
     * @return string|null The ETag for the entry object if it has eTag properties
     *                     NULL otherwise
     */
    protected function compareETag(
        &$entryObject,
        ResourceType & $resourceType,
        &$needToSerializeResponse
    ) {
        $needToSerializeResponse = true;
        $eTag = null;
        $ifMatch = $this->getHost()->getRequestIfMatch();
        $ifNoneMatch = $this->getHost()->getRequestIfNoneMatch();
        if (is_null($entryObject)) {
            if (!is_null($ifMatch)) {
                throw ODataException::createPreConditionFailedError(
                    Messages::eTagNotAllowedForNonExistingResource()
                );
            }

            return null;
        }

        if ($this->getConfiguration()->getValidateETagHeader() && !$resourceType->hasETagProperties()) {
            if (!is_null($ifMatch) || !is_null($ifNoneMatch)) {
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

        if (is_null($ifMatch) && is_null($ifNoneMatch)) {
            // No request eTag header, we need to write the response
            // and eTag header
        } elseif (0 === strcmp($ifMatch, '*')) {
            // If-Match:* => we need to write the response and eTag header
        } elseif (0 === strcmp($ifNoneMatch, '*')) {
            // if-None-Match:* => Do not write the response (304 not modified),
            // but write eTag header
            $needToSerializeResponse = false;
        } else {
            $eTag = $this->getETagForEntry($entryObject, $resourceType);
            // Note: The following code for attaching the prefix W\"
            // and the suffix " can be done in getETagForEntry function
            // but that is causing an issue in Linux env where the
            // firefix browser is unable to parse the ETag in this case.
            // Need to follow up PHP core devs for this.
            $eTag = ODataConstants::HTTP_WEAK_ETAG_PREFIX . $eTag . '"';
            if (!is_null($ifMatch)) {
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

        if (is_null($eTag)) {
            $eTag = $this->getETagForEntry($entryObject, $resourceType);
            // Note: The following code for attaching the prefix W\"
            // and the suffix " can be done in getETagForEntry function
            // but that is causing an issue in Linux env where the
            // firefix browser is unable to parse the ETag in this case.
            // Need to follow up PHP core devs for this.
            $eTag = ODataConstants::HTTP_WEAK_ETAG_PREFIX . $eTag . '"';
        }

        return $eTag;
    }

    /**
     * Returns the etag for the given resource.
     * Note: This function will not add W\" prefix and " suffix, its callers
     * repsonsibility.
     *
     * @param mixed        &$entryObject  Resource for which etag value needs to
     *                                    be returned
     * @param ResourceType &$resourceType Resource type of the $entryObject
     *
     * @return string|null ETag value for the given resource (with values encoded
     *                     for use in a URI) there are etag properties, NULL if
     *                     there is no etag property
     */
    protected function getETagForEntry(&$entryObject, ResourceType & $resourceType)
    {
        $eTag = null;
        $comma = null;
        foreach ($resourceType->getETagProperties() as $eTagProperty) {
            $type = $eTagProperty->getInstanceType();
            assert($type instanceof IType, '!$type instanceof IType');

            $value = null;
            $property = $eTagProperty->getName();
            try {
                //TODO #88...also this seems like dupe work
                $value = ReflectionHandler::getProperty($entryObject, $property);
            } catch (\ReflectionException $reflectionException) {
                throw ODataException::createInternalServerError(
                    Messages::failedToAccessProperty($property, $resourceType->getName())
                );
            }

            $eTagBase = $eTag . $comma;
            $eTag = $eTagBase . ((null == $value) ? 'null' : $type->convertToOData($value));

            $comma = ',';
        }

        if (!is_null($eTag)) {
            // If eTag is made up of datetime or string properties then the above
            // IType::convertToOData will perform utf8 and url encode. But we don't
            // want this for eTag value.
            $eTag = urldecode(utf8_decode($eTag));

            return rtrim($eTag, ',');
        }

        return null;
    }

    /**
     * This function will perform the following operations:
     * (1) Invoke delegateRequestProcessing method to process the request based
     *     on request method (GET, PUT/MERGE, POST, DELETE)
     * (3) If the result of processing of request needs to be serialized as HTTP
     *     response body (e.g. GET request result in single resource or resource
     *     collection, successful POST operation for an entity need inserted
     *     entity to be serialized back etc..), Serialize the result by using
     *     'serializeReultForResponseBody' method
     *     Set the serialized result to
     *     WebOperationContext::Current()::OutgoingWebResponseContext::Stream.
     */
    protected function handleRequest2()
    {
    }

    /**
     * This method will perform the following operations:
     * (1) If request method is GET, then result is already there in the
     *     RequestDescription so simply return the RequestDescription
     * (2). If request method is for CDU
     *      (Create/Delete/Update - POST/DELETE/PUT-MERGE) hand
     *      over the responsibility to respective handlers. The handler
     *      methods are:
     *      (a) handlePOSTOperation() => POST
     *      (b) handlePUTOperation() => PUT/MERGE
     *      (c) handleDELETEOperation() => DELETE
     * (3). Check whether its required to write any result to the response
     *      body
     *      (a). Request method is GET
     *      (b). Request is a POST for adding NEW Entry
     *      (c). Request is a POST for adding Media Resource Stream
     *      (d). Request is a POST for adding a link
     *      (e). Request is a DELETE for deleting entry or relationship
     *      (f). Request is a PUT/MERGE for updating an entry
     *      (g). Request is a PUT for updating a link
     *     In case a, b and c we need to write the result to response body,
     *     for d, e, f and g no body content.
     *
     * @return RequestDescription|null Instance of RequestDescription with
     *                                 result to be write back Null if no result to write
     */
    protected function delegateRequestProcessing()
    {
    }

    /**
     * Serialize the result in the current request description using
     * appropriate odata writer (AtomODataWriter/JSONODataWriter).
     */
    protected function serializeResultForResponseBody()
    {
    }

    /**
     * Handle POST request.
     *
     *
     * @throws NotImplementedException
     */
    protected function handlePOSTOperation()
    {
    }

    /**
     * Handle PUT/MERGE request.
     *
     *
     * @throws NotImplementedException
     */
    protected function handlePUTOperation()
    {
    }

    /**
     * Handle DELETE request.
     *
     *
     * @throws NotImplementedException
     */
    protected function handleDELETEOperation()
    {
    }
}
