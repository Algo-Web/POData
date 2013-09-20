<?php

namespace POData;

use POData\Providers\Metadata\ResourceTypeKind;
use POData\ObjectModel\ODataPropertyContent;
use POData\Common\ErrorHandler;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Common\ODataConstants;
use POData\Common\NotImplementedException;
use POData\Common\InvalidOperationException;
use POData\Common\HttpStatus;
use POData\Providers\MetadataQueryProviderWrapper;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\Configuration\ServiceConfiguration;
use POData\UriProcessor\UriProcessor;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\ObjectModel\ObjectModelSerializer;
use POData\Writers\ResponseWriter;

use POData\Providers\Query\IQueryProvider;
use POData\Providers\Metadata\IMetadataProvider;
use POData\OperationContext\Web\WebOperationContext;
use POData\Writers\ServiceDocumentWriterFactory;
use POData\Writers\ODataWriterFactory;

/**
 * Class BaseService
 *
 * The base class for all BaseService specific classes. This class implements
 * the following interfaces:
 *  (1) IRequestHandler
 *      Implementing this interface requires defining the function
 *      'handleRequest' that will be invoked by dispatcher
 *  (2) IService
 *      Force BaseService class to implement functions for custom
 *      data service providers
 *
 * @package POData
 */
abstract class BaseService implements IRequestHandler, IService
{
    /** 
     * The wrapper over IQueryProvider and IMetadataProvider implementations.
     * 
     * @var MetadataQueryProviderWrapper
     */
    private $_metadataQueryProviderWrapper;

    /**
     * The wrapper over IStreamProvider implementation
     * 
     * @var StreamProviderWrapper
     */
    private $_streamProvider;

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
     * @var ServiceConfiguration
     */
    private $_serviceConfiguration;

    /**
     * Gets reference to ServiceConfiguration instance so that
     * service specific rules defined by the developer can be 
     * accessed.
     * 
     * @return ServiceConfiguration
     */
    public function getServiceConfiguration()
    {
        return $this->_serviceConfiguration;
    }


	//TODO: shouldn't we hide this from the interface..if we need it at all.
    /**
     * Get the wrapper over developer's IQueryProvider and IMetadataProvider implementation.
     * 
     * @return MetadataQueryProviderWrapper
     */
    public function getMetadataQueryProviderWrapper()
    {
          return $this->_metadataQueryProviderWrapper;
    }

    /**
     * Gets reference to wrapper class instance over IDSSP implementation
     * 
     * @return StreamProviderWrapper
     */
    public function getStreamProviderWrapper()
    {
        return $this->_streamProvider;
    }

    /**
     * Get reference to the data service host instance.
     * 
     * @return ServiceHost
     */
    public function getHost()
    {
        return $this->_serviceHost;
    }

    /**
     * Sets the data service host instance.
     * 
     * @param ServiceHost $serviceHost The data service host instance.
     * 
     * @return void
     */
    public function setHost(ServiceHost $serviceHost)
    {
        $this->_serviceHost = $serviceHost;
    }

    /**
     * To get reference to operation context where we have direct access to
     * headers and body of Http Request we have received and the Http Response
     * We are going to send.
     * 
     * @return WebOperationContext
     */
    public function getOperationContext()
    {
        return $this->_serviceHost->getWebOperationContext();
    }

    /**
     * Get reference to the wrapper over IStreamProvider or
     * IStreamProvider2 implementations.
     * 
     * @return StreamProviderWrapper
     */
    public function getStreamProvider()
    {
        if (is_null($this->_streamProvider)) {
            $this->_streamProvider = new StreamProviderWrapper();
            $this->_streamProvider->setService($this);
        }

        return $this->_streamProvider;
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
     * (2). Invoke 'InitializeService' method of top level service for 
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
     * 
     * @return void
     */
    public function handleRequest()
    {
        try {
            $this->createProviders();
            $this->_serviceHost->validateQueryParameters();
            $requestMethod = $this->getOperationContext()->incomingRequest()->getMethod();
            if ($requestMethod !== ODataConstants::HTTP_METHOD_GET) {
                ODataException::createNotImplementedError(Messages::onlyReadSupport($requestMethod));
            }          
        } catch (\Exception $exception) {
            ErrorHandler::handleException($exception, $this);
            // Return to dispatcher for writing serialized exception
            return;
        }

        $uriProcessor = null;
        try {
            $uriProcessor = UriProcessor::process($this);
            $requestDescription = $uriProcessor->getRequestDescription();
            $this->serializeResult($requestDescription, $uriProcessor);
        } catch (\Exception $exception) {
            ErrorHandler::handleException($exception, $this);
            // Return to dispatcher for writing serialized exception
            return;
        }

        // Return to dispatcher for writing result
    }

	/**
	 * @return IQueryProvider
	 */
	public abstract function getQueryProvider();

	/**
	 * @return IMetadataProvider
	 */
	public abstract function getMetadataProvider();

	/**
	 * @return \POData\Providers\Stream\IStreamProvider
	 */
	public abstract function getStreamProviderX();


	/**
	 * Returns the ServiceDocumentWriterFactory to use when writing the response to a service document request
	 * Implementations can override this to handle custom formats.
	 * @return ServiceDocumentWriterFactory
	 */
	public function getServiceDocumentWriterFactory(){
		return new ServiceDocumentWriterFactory();
	}

	/**
	 * Returns the ODataWriterFactory to use when writing the response to a service document request
	 * Implementations can override this to handle custom formats.
	 * @return ODataWriterFactory
	 */
	public function getODataWriterFactory(){
		return new ODataWriterFactory();
	}


    /**
     * This method will query and validates for IMetadataProvider and IQueryProvider implementations, invokes
     * BaseService::InitializeService to initialize service specific policies.
     * 
     * @return void
     * 
     * @throws ODataException
     */
    protected function createProviders()
    { 

        $metadataProvider = $this->getMetadataProvider();
        if (is_null($metadataProvider)) {
            ODataException::createInternalServerError(
                Messages::metadataQueryProviderNull()
            );
        }
    
        if (!is_object($metadataProvider) 
            || array_search('POData\Providers\Metadata\IMetadataProvider', class_implements($metadataProvider)) === false
        ) {
            ODataException::createInternalServerError(
                Messages::invalidMetadataInstance()
            );
        }


        $queryProvider = $this->getQueryProvider();


        if (is_null($queryProvider)) {
            ODataException::createInternalServerError(
                Messages::metadataQueryProviderNull()
            );
        }

        if (!is_object($queryProvider)) {
          ODataException::createInternalServerError(
              Messages::invalidQueryInstance()
          );
        }


        if (array_search('POData\Providers\Query\IQueryProvider', class_implements($queryProvider)) === false) {
            ODataException::createInternalServerError(
                Messages::invalidQueryInstance()
            );
        }


        $this->_serviceConfiguration = new ServiceConfiguration($metadataProvider);
        $this->_metadataQueryProviderWrapper = new MetadataQueryProviderWrapper(
            $metadataProvider, 
            $queryProvider, 
            $this->_serviceConfiguration

        );

        
        $this->initializeService($this->_serviceConfiguration);
    }

    /**
     * Serialize the requested resource.
     * 
     * @param RequestDescription &$requestDescription The description of the request  submitted by the client.
     * @param UriProcessor       $uriProcessor       Reference to the uri processor.
     * 
     * @return void
     */
    protected function serializeResult(RequestDescription &$requestDescription, UriProcessor $uriProcessor
    ) {
        $isETagHeaderAllowed = $requestDescription->isETagHeaderAllowed();
        if ($this->_serviceConfiguration->getValidateETagHeader() && !$isETagHeaderAllowed) {
            if (!is_null($this->_serviceHost->getRequestIfMatch())
                ||!is_null($this->_serviceHost->getRequestIfNoneMatch())
            ) {
                ODataException::createBadRequestError(
                    Messages::eTagCannotBeSpecified(
                        $this->getHost()->getAbsoluteRequestUri()->getUrlAsString()
                    )
                );
            }
        }

        $responseContentType = null;
        $responseFormat = self::getResponseFormat($requestDescription, $uriProcessor, $this, $responseContentType );

	    if (is_null($responseContentType)) {
		    //Note: when refactoring this, if it's targeting a media resource it may return null and be ok..not sure
		    //what situations this will arrise.
		    throw new ODataException( Messages::unsupportedMediaType(), 415 );
	    }

	    $odataModelInstance = null;
        $hasResponseBody = true;
        // Execution required at this point if request target to any resource 
        // other than
        // (1) media resource - For Media resource 'getResponseFormat' already 
        //     performed execution
        // (2) metadata - internal resource
        // (3) service directory - internal resource
        if ($requestDescription->needExecution()) {
            $uriProcessor->execute();
            $objectModelSerializer = new ObjectModelSerializer($this, $requestDescription);
            if (!$requestDescription->isSingleResult()) {
                // Code path for collection (feed or links)
                $entryObjects = $requestDescription->getTargetResult();
                self::assert(
                    !is_null($entryObjects) && is_array($entryObjects), 
                    '!is_null($entryObjects) && is_array($entryObjects)'
                );
                // If related resource set is empty for an entry then we should 
                // not throw error instead response must be empty feed or empty links
                if ($requestDescription->isLinkUri()) {
                    $odataModelInstance = $objectModelSerializer->writeUrlElements($entryObjects);
                    self::assert(
                        $odataModelInstance instanceof \POData\ObjectModel\ODataURLCollection, 
                        '$odataModelInstance instanceof ODataURLCollection'
                    );
                } else {
                    $odataModelInstance = $objectModelSerializer->writeTopLevelElements($entryObjects);
                    self::assert(
                        $odataModelInstance instanceof \POData\ObjectModel\ODataFeed, 
                        '$odataModelInstance instanceof ODataFeed'
                    );
                }
            } else {
                // Code path for entry, complex, bag, resource reference link, 
                // primitive type or primitive value
                $result = $requestDescription->getTargetResult();
                $requestTargetKind = $requestDescription->getTargetKind();
                if ($requestDescription->isLinkUri()) {
                    // In the query 'Orders(1245)/$links/Customer', the targeted
                    // Customer might be null
                    if (is_null($result)) {
                        ODataException::createResourceNotFoundError(
                            $requestDescription->getIdentifier()
                        );
                    }

                    $odataModelInstance = $objectModelSerializer->writeUrlElement($result);
                } else if ($requestTargetKind == RequestTargetKind::RESOURCE) {
                    if (!is_null($this->_serviceHost->getRequestIfMatch())
                        && !is_null($this->_serviceHost->getRequestIfNoneMatch())
                    ) {
                        ODataException::createBadRequestError(
                            Messages::bothIfMatchAndIfNoneMatchHeaderSpecified()
                        );
                    }
                    // handle entry resource
                    $needToSerializeResponse = true;
                    $targetResourceType = $requestDescription->getTargetResourceType();
                    $eTag = $this->compareETag(
                        $result, 
                        $targetResourceType, 
                        $needToSerializeResponse
                    );

                    if ($needToSerializeResponse) {
                        if (is_null($result)) {
                            // In the query 'Orders(1245)/Customer', the targetted 
                            // Customer might be null
                            // set status code to 204 => 'No Content'
                            $this->_serviceHost->setResponseStatusCode(
                                HttpStatus::CODE_NOCONTENT
                            );
                            $hasResponseBody = false;
                        } else {
                            $odataModelInstance 
                                = $objectModelSerializer->writeTopLevelElement($result);
                        }
                    } else {
                        // Resource is not modified so set status code 
                        // to 304 => 'Not Modified'
                        $this->_serviceHost
                            ->setResponseStatusCode(HttpStatus::CODE_NOT_MODIFIED);
                        $hasResponseBody = false;
                    }

                    // if resource has eTagProperty then eTag header needs to written
                    if (!is_null($eTag)) {
                        $this->_serviceHost->setResponseETag($eTag);
                    }
                } else if ($requestTargetKind == RequestTargetKind::COMPLEX_OBJECT) {
                    $odataModelInstance = new ODataPropertyContent();
                    $targetResourceTypeComplex = $requestDescription->getTargetResourceType();
                    $objectModelSerializer->writeTopLevelComplexObject(
                        $result, 
                        $requestDescription->getProjectedProperty()->getName(),
                        $targetResourceTypeComplex, 
                        $odataModelInstance
                    );
                } else if ($requestTargetKind == RequestTargetKind::BAG) {
                    $odataModelInstance = new ODataPropertyContent();
                    $targetResourceTypeBag = $requestDescription->getTargetResourceType();
                    $objectModelSerializer->writeTopLevelBagObject(
                        $result, 
                        $requestDescription->getProjectedProperty()->getName(),
                        $targetResourceTypeBag,
                        $odataModelInstance
                    );
                } else if ($requestTargetKind == RequestTargetKind::PRIMITIVE) {
                    $odataModelInstance = new ODataPropertyContent();
                    $projectedProperty = $requestDescription->getProjectedProperty();
                    $objectModelSerializer->writeTopLevelPrimitive(
                        $result, 
                        $projectedProperty,
                        $odataModelInstance
                    );
                } else if ($requestTargetKind == RequestTargetKind::PRIMITIVE_VALUE) {
                    // Code path for primitive value (Since its primitve no need for
                    // object model serialization) 
                    // Customers('ANU')/CompanyName/$value => string 
                    // Employees(1)/Photo/$value => binary stream
                    // Customers/$count => string
                } else {
                    self::assert(false, 'Unexpected resource target kind');
                }
            }
        }

        //Note: Response content type can be null for named stream
        if ($hasResponseBody && !is_null($responseContentType)) {
            if ($responseFormat != ResponseFormat::BINARY()) {
                $responseContentType .= ';charset=utf-8';
            }
        }

        if ($hasResponseBody) {
            ResponseWriter::write(
                $this, 
                $requestDescription, 
                $odataModelInstance, 
                $responseContentType, 
                $responseFormat
            );
        }
    }

    /**
     * Gets the response format for the requested resource.
     * 
     * @param RequestDescription $request  The request submitted by client and it's execution result.
     * @param UriProcessor       $uriProcessor        The reference to the UriProcessor.
     * @param IService        $service         Reference to the service implementation instance
     * @param string             &$responseContentType On Return, this will hold
     * the response content-type, a null value means the requested resource
     * is named stream and IDSSP2::getStreamContentType returned null.
     * 
     * @return ResponseFormat The format in which response needs to be serialized.
     * 
     * @throws ODataException, HttpHeaderFailure
     */
    public static function getResponseFormat(
	    RequestDescription $request,
        UriProcessor $uriProcessor,
        IService $service,
        &$responseContentType
    ) {

        // The Accept request-header field specifies media types which are acceptable for the response
	    // Note the $format QSP is parsed and shoved in the header collection so it overrides
	    // any normal headers
        $requestAcceptText = $service->getHost()->getRequestAccept();

	    //The response format can be dictated by the target resource kind. IE a $value will be different then expected
	    //getTargetKind doesn't deal with link resources directly and this can change things
	    $requestTargetKind = $request->isLinkUri() ? RequestTargetKind::LINK : $request->getTargetKind();


        if ($requestTargetKind == RequestTargetKind::METADATA) {
            $responseContentType = HttpProcessUtility::selectMimeType(
                $requestAcceptText,
                array(ODataConstants::MIME_APPLICATION_XML)
            );


            return ResponseFormat::METADATA_DOCUMENT();
        }


	    if ($requestTargetKind == RequestTargetKind::SERVICE_DIRECTORY) {
            $responseContentType = HttpProcessUtility::selectMimeType(
                $requestAcceptText, 
                array(
                    ODataConstants::MIME_APPLICATION_XML,
                    ODataConstants::MIME_APPLICATION_ATOMSERVICE, 
                    ODataConstants::MIME_APPLICATION_JSON
                )
            );

            return self::_getResponseFormatForMime($responseContentType);

        }


	    if ($requestTargetKind == RequestTargetKind::PRIMITIVE_VALUE) {
            $supportedResponseMimeTypes = array(ODataConstants::MIME_TEXTPLAIN);
            $responseFormat = ResponseFormat::TEXT();

            if ($request->getIdentifier() != '$count') {
                $projectedProperty = $request->getProjectedProperty();
                self::assert(
                    !is_null($projectedProperty), 
                    '!is_null($projectedProperty)'
                );
                $type = $projectedProperty->getInstanceType();
                self::assert(
                    !is_null($type) && array_search(
                        'POData\Providers\Metadata\Type\IType', 
                        class_implements($type)
                    ) !== false, 
                    '!is_null($type) && array_search(\'POData\Providers\Metadata\Type\IType\', class_implements($type)) !== false'
                );
                if ($type instanceof Binary) {
                    $supportedResponseMimeTypes 
                        = array(ODataConstants::MIME_APPLICATION_OCTETSTREAM);
                    $responseFormat = ResponseFormat::BINARY();
                }
            }

            $responseContentType = HttpProcessUtility::selectMimeType(
                $requestAcceptText, 
                $supportedResponseMimeTypes
            );

		    return $responseFormat;
	    }


	    if ($requestTargetKind == RequestTargetKind::PRIMITIVE
            || $requestTargetKind == RequestTargetKind::COMPLEX_OBJECT
            || $requestTargetKind == RequestTargetKind::BAG
            || $requestTargetKind == RequestTargetKind::LINK
        ) {
            $responseContentType = HttpProcessUtility::selectMimeType(
                $requestAcceptText, 
                array(
                    ODataConstants::MIME_APPLICATION_XML, 
                    ODataConstants::MIME_TEXTXML, 
                    ODataConstants::MIME_APPLICATION_JSON
                  )
            );

		    return self::_getResponseFormatForMime($responseContentType);
        }


	    if ($requestTargetKind == RequestTargetKind::RESOURCE) {
            $responseContentType = HttpProcessUtility::selectMimeType(
                $requestAcceptText, 
                array(
                    ODataConstants::MIME_APPLICATION_ATOM, 
                    ODataConstants::MIME_APPLICATION_JSON
                  )
            );

		    return self::_getResponseFormatForMime($responseContentType);
        }


	    if ($requestTargetKind == RequestTargetKind::MEDIA_RESOURCE) {

		    if (!$request->isNamedStream() && !$request->getTargetResourceType()->isMediaLinkEntry()){
			    ODataException::createBadRequestError(
				    Messages::badRequestInvalidUriForMediaResource(
					    $service->getHost()->getAbsoluteRequestUri()->getUrlAsString()
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
		    // yet been uploaded. But for an MLE if IDSSP::getStreamContentType returns NULL then StreamWrapper will throw error
		    if (!is_null($responseContentType)) {
                $responseContentType = HttpProcessUtility::selectMimeType(
                    $requestAcceptText,
                    array($responseContentType)
                );
            }

		    return ResponseFormat::BINARY();

        }

	    //If we got here, we just don't know what it is...
        throw new ODataException( Messages::unsupportedMediaType(), 415 );

    }

    /**
     * Get the format corresponding to the given mime type.
     *
     * @param string $mime mime type for the request.
     * 
     * @return ResponseFormat Response format mapping to the given mime type.
     */
    private static function _getResponseFormatForMime($mime)
    {
        if (strcasecmp($mime, ODataConstants::MIME_APPLICATION_JSON) === 0) {
            return ResponseFormat::JSON();
        } else if (strcasecmp($mime, ODataConstants::MIME_APPLICATION_ATOM) === 0) {
            return ResponseFormat::ATOM();
        } else {
            $flag 
                = strcasecmp($mime, ODataConstants::MIME_APPLICATION_XML) === 0 ||
                    strcasecmp($mime, ODataConstants::MIME_APPLICATION_ATOMSERVICE) === 0 ||
                    strcasecmp($mime, ODataConstants::MIME_TEXTXML) === 0;
            self::assert(
                $flag, 
                'expecting application/xml, application/atomsvc+xml or plain/xml, got ' . $mime
            );
            return ResponseFormat::PLAIN_XML();
        }
    }

    /**
     * For the given entry object compare it's eTag (if it has eTag properties)
     * with current eTag request headers (if it present).
     * 
     * @param mixed        &$entryObject             entity resource for which etag 
     *                                               needs to be checked.
     * @param ResourceType &$resourceType            Resource type of the entry 
     *                                               object.
     * @param boolean      &$needToSerializeResponse On return, this will contain 
     *                                               True if response needs to be
     *                                               serialized, False otherwise.
     *                                              
     * @return string|null The ETag for the entry object if it has eTag properties 
     *                     NULL otherwise.
     */
    protected function compareETag(&$entryObject, ResourceType &$resourceType, 
        &$needToSerializeResponse
    ) {      
        $needToSerializeResponse = true;
        $eTag = null;
        $ifMatch = $this->_serviceHost->getRequestIfMatch();
        $ifNoneMatch = $this->_serviceHost->getRequestIfNoneMatch();
        if (is_null($entryObject)) {
            if (!is_null($ifMatch)) {
                ODataException::createPreConditionFailedError(
                    Messages::eTagNotAllowedForNonExistingResource()
                ); 
            }

            return null;
        }
     
        if ($this->_serviceConfiguration->getValidateETagHeader() && !$resourceType->hasETagProperties()) {
            if (!is_null($ifMatch) || !is_null($ifNoneMatch)) {
                // No eTag properties but request has eTag headers, bad request
                ODataException::createBadRequestError(
                    Messages::noETagPropertiesForType()
                );
            }

            // We need write the response but no eTag header 
            return null;
        }

        if (!$this->_serviceConfiguration->getValidateETagHeader()) {
            // Configuration says do not validate ETag so we will not write ETag header in the 
            // response even though the requested resource support it
            return null;
        }

        if (is_null($ifMatch) && is_null($ifNoneMatch)) {
            // No request eTag header, we need to write the response 
            // and eTag header 
        } else if (strcmp($ifMatch, '*') == 0) {
            // If-Match:* => we need to write the response and eTag header 
        } else if (strcmp($ifNoneMatch, '*') == 0) {
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
                if (strcmp($eTag, $ifMatch) != 0) {
                    // Requested If-Match value does not match with current 
                    // eTag Value then pre-condition error
                    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
                    ODataException::createPreConditionFailedError(
                        Messages::eTagValueDoesNotMatch()
                    );
                }
            } else if (strcmp($eTag, $ifNoneMatch) == 0) {
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
     * repsonsability.
     *
     * @param mixed        &$entryObject  Resource for which etag value needs to 
     *                                    be returned
     * @param ResourceType &$resourceType Resource type of the $entryObject
     * 
     * @return string|null ETag value for the given resource (with values encoded 
     *                     for use in a URI) there are etag properties, NULL if 
     *                     there is no etag property.
     */
    protected function getETagForEntry(&$entryObject, ResourceType &$resourceType)
    {
        $eTag = null;
        $comma = null;
        foreach ($resourceType->getETagProperties() as $eTagProperty) {
            $type = $eTagProperty->getInstanceType();
            self::assert(
                !is_null($type) 
                && array_search('POData\Providers\Metadata\Type\IType', class_implements($type)) !== false,
                '!is_null($type) 
                && array_search(\'POData\Providers\Metadata\Type\IType\', class_implements($type)) !== false'
            );
      
            $value = null; 
            try {
                $reflectionProperty  = new \ReflectionProperty($entryObject, $eTagProperty->getName() );
                $value = $reflectionProperty->getValue($entryObject);
            } catch (\ReflectionException $reflectionException) {
                ODataException::createInternalServerError(
                    Messages::failedToAccessProperty($eTagProperty->getName(), $resourceType->getName() )
                );
            }

            if (is_null($value)) {
                $eTag = $eTag . $comma. 'null';
            } else {
                $eTag = $eTag . $comma . $type->convertToOData($value);
            }

            $comma = ',';
        }

        if (!is_null($eTag)) {
            // If eTag is made up of datetime or string properties then the above
            // IType::converToOData will perform utf8 and url encode. But we don't
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
     *     
     *     @return void
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
     *         result to be write back Null if no result to write.
     */
    protected function delegateRequestProcessing()
    {
    }

    /**
     * Serialize the result in the current request description using 
     * appropriate odata writer (AtomODataWriter/JSONODataWriter)
     * 
     * @return void
     * 
     */
    protected function serializeReultForResponseBody()
    {
    }

    /**
     * Handle POST request.
     * 
     * @return void
     * 
     * @throws NotImplementedException
     */
    protected function handlePOSTOperation()
    {
    }

    /**
     * Handle PUT/MERGE request.
     * 
     * @return void
     * 
     * @throws NotImplementedException
     */
    protected function handlePUTOperation()
    {
    }

    /**
     * Handle DELETE request.
     * 
     * @return void
     * 
     * @throws NotImplementedException
     */
    protected function handleDELETEOperation()
    {
    }

    /**
     * Assert that the given condition is true.
     * 
     * @param boolean $condition         The condtion to check.
     * @param string  $conditionAsString Message to show if assertion fails.
     * 
     * @return void
     * 
     * @throws InvalidOperationException
     */
    protected static function assert($condition, $conditionAsString)
    {
        if (!$condition) {
            throw new InvalidOperationException(
                "Unexpected state, expecting $conditionAsString"
            );
        }
    }
}