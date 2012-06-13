<?php
/** 
 * The base class for all DataService specific classes. This class implements 
 * the following interfaces:
 *  (1) IRequestHandler
 *      Implementing this interface requires defining the function 
 *      'handleRequest' that will be invoked by dispatcher
 *  (2) IDataService
 *      Force DataService class to implement functions for custom 
 *      data service providers  
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS 
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN 
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\ObjectModel\ODataPropertyContent;
use ODataProducer\Common\ErrorHandler;
use ODataProducer\Common\Messages;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\NotImplementedException;
use ODataProducer\Common\InvalidOperationException;
use ODataProducer\Common\HttpStatus;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Providers\Stream\DataServiceStreamProviderWrapper;
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\UriProcessor\UriProcessor;
use ODataProducer\UriProcessor\RequestDescription;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use ODataProducer\OperationContext\DataServiceHost;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\Type\Binary;
use ODataProducer\ObjectModel\ObjectModelSerializer;
use ODataProducer\Writers\ResponseWriter;
/**
 * The DataService base class.
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
abstract class DataService implements IRequestHandler, IDataService
{
    /** 
     * To hold reference to DataServiceProviderWrapper which is a wrapper
     * over IDataServiceQueryProvider and IDataServiceMetadataProvider
     * Implementation.
     * 
     * @var MetadataQueryProviderWrapper
     */
    private $_metadataQueryProviderWrapper;

    /**
     * To hold reference to DataServiceStreamProviderWrapper, which is a 
     * wrapper over IDataServiceServiceProvider implementation
     * 
     * @var DataServiceStreamProviderWrapper
     */
    private $_dataServiceStreamProvider;

    /**
     * Hold reference to the DataServiceHost instance created by dispatcher,
     * using this library can access headers and body of Http Request 
     * dispatcher received and the Http Response Dispatcher is going to send.
     * 
     * @var DataServiceHost
     */
    private $_dataServiceHost;

    /**
     * To hold reference to DataServiceOperationContext, using this we can 
     * access headers and body of Http Request dispatcher received and the 
     * Http Response Dispatcher is going to send.
     * 
     * @var DataServiceOperationContext
     */
    private $_dataServiceOperationContext;

    /**
     * To hold reference to DataServiceConfiguration instance where the 
     * service specific rules (page limit, resource set access rights 
     * etc...) are defined.
     * 
     * @var DataServiceConfiguration
     */
    private $_dataServiceConfiguration;

    /**
     * Gets reference to DataServiceConfiguration instance so that 
     * service specific rules defined by the developer can be 
     * accessed.
     * 
     * @return DataServiceConfiguration
     */
    public function getServiceConfiguration()
    {
        return $this->_dataServiceConfiguration;
    }

    /**
     * To get the reference to DataServiceProviderWrapper, a wrapper
     * over developer's IDataServiceQueryProvider and 
     * IDataServiceMetadataProvider implementation.
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
     * @return DataServiceStreamProviderWrapper
     */
    public function getStreamProviderWrapper()
    {
        return $this->_dataServiceStreamProvider;
    }

    /**
     * Get reference to the data service host instance.
     * 
     * @return DataServiceHost
     */
    public function getHost()
    {
        return $this->_dataServiceHost;
    }

    /**
     * Sets the data service host instance.
     * 
     * @param DataServiceHost $dataServiceHost The data service host instance.
     * 
     * @return void
     */
    public function setHost(DataServiceHost $dataServiceHost)
    {
        $this->_dataServiceHost = $dataServiceHost;
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
        return $this->_dataServiceHost->getWebOperationContext();
    }

    /**
     * Get reference to the wrapper over IDataServiceStreamProvider or 
     * IDataServiceStreamProvider2 implementations.
     * 
     * @return DataServiceStreamProviderWrapper
     */
    public function getStreamProvider()
    {
        if (is_null($this->_dataServiceStreamProvider)) {
            $this->_dataServiceStreamProvider = new DataServiceStreamProviderWrapper();
            $this->_dataServiceStreamProvider->setDataService($this);
        }

        return $this->_dataServiceStreamProvider;
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
     *     IDataServiceMetaDataProvider and IDataServiceQueryProvider. 
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
     *      (c). Parse the request uri and using, IDataServiceQueryProvider 
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
            $this->_dataServiceHost->validateQueryParameters();
            $requestMethod = $this->getOperationContext()->incomingRequest()->getMethod();
            if ($requestMethod !== ODataConstants::HTTP_METHOD_GET) {
                ODataException::createNotImplementedError(Messages::dataServiceOnlyReadSupport($requestMethod));   
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
     * This method will query and validates for IDataServiceMetadataProvider and 
     * IDataServiceQueryProvider implementations, invokes 
     * DataService::InitializeService to initialize service specific policies.
     * 
     * @return void
     * 
     * @throws ODataException
     */
    protected function createProviders()
    { 
        if (array_search('ODataProducer\IServiceProvider', class_implements($this)) === false) {
            ODataException::createInternalServerError(
                Messages::dataServiceNotImplementsIServiceProvider()
            );
        }

        $metadataProvider = $this->getService('IDataServiceMetadataProvider');
        if (is_null($metadataProvider)) {
            ODataException::createInternalServerError(
                Messages::dataServiceMetadataQueryProviderNull()
            );
        }
    
        if (!is_object($metadataProvider) 
            || array_search('ODataProducer\Providers\Metadata\IDataServiceMetadataProvider', class_implements($metadataProvider)) === false
        ) {
            ODataException::createInternalServerError(
                Messages::dataServiceInvalidMetadataInstance()
            );
        }

        $expectingQP2 = false;
        $queryProvider = $this->getService('IDataServiceQueryProvider');
        if (is_null($queryProvider)) {
          $expectingQP2 = true;
          $queryProvider = $this->getService('IDataServiceQueryProvider2'); 
        }

        if (is_null($queryProvider)) {
            ODataException::createInternalServerError(
                Messages::dataServiceMetadataQueryProviderNull()
            );
        }

        if (!is_object($queryProvider)) {
          ODataException::createInternalServerError(
              Messages::dataServiceInvalidQueryInstance()
          );
        }

        if ($expectingQP2) {
            if (array_search('ODataProducer\Providers\Query\IDataServiceQueryProvider2', class_implements($queryProvider)) === false) {
                ODataException::createInternalServerError(
                    Messages::dataServiceInvalidQueryInstance()
                );
            }
        } else {
          if (array_search('ODataProducer\Providers\Query\IDataServiceQueryProvider', class_implements($queryProvider)) === false) {
            ODataException::createInternalServerError(
                Messages::dataServiceInvalidQueryInstance()
            );
          }
        }

        $this->_dataServiceConfiguration = new DataServiceConfiguration($metadataProvider);
        $this->_metadataQueryProviderWrapper = new MetadataQueryProviderWrapper(
            $metadataProvider, 
            $queryProvider, 
            $this->_dataServiceConfiguration,
            $expectingQP2
        );

        
        $this->initializeService($this->_dataServiceConfiguration);
    }

    /**
     * Serialize the requested resource.
     * 
     * @param RequestDescription &$requestDescription The description of the request 
     *                                                submitted by the client.
     * @param UriProcessor       &$uriProcessor       Reference to the uri processor.
     * 
     * @return void
     */
    protected function serializeResult(RequestDescription &$requestDescription, 
        UriProcessor &$uriProcessor
    ) {
        $isETagHeaderAllowed = $requestDescription->isETagHeaderAllowed();
        if ($this->_dataServiceConfiguration->getValidateETagHeader() && !$isETagHeaderAllowed) {
            if (!is_null($this->_dataServiceHost->getRequestIfMatch()) 
                ||!is_null($this->_dataServiceHost->getRequestIfNoneMatch())
            ) {
                ODataException::createBadRequestError(
                    Messages::dataServiceETagCannotBeSpecified(
                        $this->getHost()->getAbsoluteRequestUri()->getUrlAsString()
                    )
                );
            }
        }

        $responseContentType = null;
        $responseFormat
            = self::getResponseFormat(
                $requestDescription, 
                $uriProcessor, $this, $responseContentType
            );
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
            $objectModelSerializer 
                = new ObjectModelSerializer($this, $requestDescription);
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
                    $odataModelInstance 
                        = $objectModelSerializer->writeUrlElements($entryObjects);
                    self::assert(
                        $odataModelInstance instanceof \ODataProducer\ObjectModel\ODataURLCollection, 
                        '$odataModelInstance instanceof ODataURLCollection'
                    );
                } else {
                    $odataModelInstance = $objectModelSerializer->writeTopLevelElements($entryObjects);
                    self::assert(
                        $odataModelInstance instanceof \ODataProducer\ObjectModel\ODataFeed, 
                        '$odataModelInstance instanceof ODataFeed'
                    );
                }
            } else {
                // Code path for entry, complex, bag, resource reference link, 
                // primitive type or primitive value
                $result = $requestDescription->getTargetResult();
                $requestTargetKind = $requestDescription->getTargetKind();
                if ($requestDescription->isLinkUri()) {
                    // In the query 'Orders(1245)/$links/Customer', the targetted 
                    // Customer might be null
                    if (is_null($result)) {
                        ODataException::createResourceNotFoundError(
                            $requestDescription->getIdentifier()
                        );
                    }

                    $odataModelInstance 
                        = $objectModelSerializer->writeUrlElement($result);
                } else if ($requestTargetKind == RequestTargetKind::RESOURCE) {
                    if (!is_null($this->_dataServiceHost->getRequestIfMatch()) 
                        && !is_null($this->_dataServiceHost->getRequestIfNoneMatch())
                    ) {
                        ODataException::createBadRequestError(
                            Messages::dataServiceBothIfMatchAndIfNoneMatchHeaderSpecified()
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
                            $this->_dataServiceHost->setResponseStatusCode(
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
                        $this->_dataServiceHost
                            ->setResponseStatusCode(HttpStatus::CODE_NOT_MODIFIED);
                        $hasResponseBody = false;
                    }

                    // if resource has eTagProperty then eTag header needs to written
                    if (!is_null($eTag)) {
                        $this->_dataServiceHost->setResponseETag($eTag);
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
            if ($responseFormat != ResponseFormat::BINARY) {
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
     * @param RequestDescription &$requestDescription  The request submitted by 
     *                                                 client and it's execution 
     *                                                 result.
     * @param UriProcessor       &$uriProcessor        The reference to the 
     *                                                 UriProcessor.
     * @param DataService        &$dataService         Reference to the data 
     *                                                 service instance
     * @param string             &$responseContentType On Return, this will hold
     * the response content-type, a null value means the requested resource
     * is named stream and IDSSP2::getStreamContentType returned null.
     * 
     * @return ResponseFormat The format in which response needs to be serialized.
     * 
     * @throws ODataException, HttpHeaderFailure
     */
    public static function getResponseFormat(RequestDescription &$requestDescription, 
        UriProcessor &$uriProcessor, DataService &$dataService, 
        &$responseContentType
    ) {
        // The Accept request-header field specifies media types which are 
        // acceptable for the response
        $requestAcceptText = $dataService->getHost()->getRequestAccept();
        $responseFormat = ResponseFormat::UNSUPPORTED;
        $requestTargetKind = $requestDescription->getTargetKind();

        if ($requestDescription->isLinkUri()) {
            $requestTargetKind = RequestTargetKind::LINK;
        }

        if ($requestTargetKind == RequestTargetKind::METADATA) {
            $responseContentType = HttpProcessUtility::selectMimeType(
                $requestAcceptText, 
                array(ODataConstants::MIME_APPLICATION_XML)
            );
            if (!is_null($responseContentType)) {
                $responseFormat = ResponseFormat::METADATA_DOCUMENT;
            }
        } else if ($requestTargetKind == RequestTargetKind::SERVICE_DIRECTORY) {
            $responseContentType = HttpProcessUtility::selectMimeType(
                $requestAcceptText, 
                array(
                    ODataConstants::MIME_APPLICATION_XML,
                    ODataConstants::MIME_APPLICATION_ATOMSERVICE, 
                    ODataConstants::MIME_APPLICATION_JSON
                )
            );
            if (!is_null($responseContentType)) {
                $responseFormat = self::_getContentFormat($responseContentType);
            }
        } else if ($requestTargetKind == RequestTargetKind::PRIMITIVE_VALUE) {
            $supportedResponseMimeTypes = array(ODataConstants::MIME_TEXTPLAIN);
            $responseFormat = ResponseFormat::TEXT;
            if ($requestDescription->getIdentifier() != '$count') {
                $projectedProperty = $requestDescription->getProjectedProperty();
                self::assert(
                    !is_null($projectedProperty), 
                    '!is_null($projectedProperty)'
                );
                $type = $projectedProperty->getInstanceType();
                self::assert(
                    !is_null($type) && array_search(
                        'ODataProducer\Providers\Metadata\Type\IType', 
                        class_implements($type)
                    ) !== false, 
                    '!is_null($type) && array_search(\'ODataProducer\Providers\Metadata\Type\IType\', class_implements($type)) !== false'
                );
                if ($type instanceof Binary) {
                    $supportedResponseMimeTypes 
                        = array(ODataConstants::MIME_APPLICATION_OCTETSTREAM);
                    $responseFormat = ResponseFormat::BINARY;
                }
            }

            $responseContentType = HttpProcessUtility::selectMimeType(
                $requestAcceptText, 
                $supportedResponseMimeTypes
            );
            if (is_null($responseContentType)) {
                $responseFormat = ResponseFormat::UNSUPPORTED;
            }
        } else if ($requestTargetKind == RequestTargetKind::PRIMITIVE
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
            if (!is_null($responseContentType)) {
                $responseFormat = self::_getContentFormat($responseContentType);
            }
        } else if ($requestTargetKind == RequestTargetKind::RESOURCE) {
            $responseContentType = HttpProcessUtility::selectMimeType(
                $requestAcceptText, 
                array(
                    ODataConstants::MIME_APPLICATION_ATOM, 
                    ODataConstants::MIME_APPLICATION_JSON
                  )
            );
            if (!is_null($responseContentType)) {
                $responseFormat = self::_getContentFormat($responseContentType);
            }
        } else if ($requestTargetKind == RequestTargetKind::MEDIA_RESOURCE) {
            $responseFormat = ResponseFormat::BINARY;
            if ($requestDescription->isNamedStream() 
                || $requestDescription->getTargetResourceType()->isMediaLinkEntry()
            ) {
                $streamInfo = $requestDescription->getResourceStreamInfo();
                //Execute the query as we need media resource instance for 
                //further processing
                $uriProcessor->execute();
                $requestDescription->setExecuted();
                // DSSW::getStreamContentType can throw error in 2 cases
                // 1. If the required stream implementation not found
                // 2. If IDSSP::getStreamContentType returns NULL for MLE 
                $contentType 
                    = $dataService->getStreamProvider()
                        ->getStreamContentType(
                            $requestDescription->getTargetResult(), 
                            $streamInfo
                        );
                if (!is_null($contentType)) {
                    $responseContentType = HttpProcessUtility::selectMimeType(
                        $requestAcceptText, 
                        array($contentType)
                    );

                    if (is_null($responseContentType)) {
                        $responseFormat = ResponseFormat::UNSUPPORTED;
                    }
                } else {
                    // For NamedStream StreamWrapper::getStreamContentType 
                    // can return NULL if the requested named stream has not 
                    // yet been uploaded. But for an MLE if 
                    // IDSSP::getStreamContentType
                    // returns NULL then StreamWrapper will throw error
                    $responseContentType = null;
                }
            } else {
                ODataException::createBadRequestError(
                    Messages::badRequestInvalidUriForMediaResource(
                        $dataService->getHost()->getAbsoluteRequestUri()->getUrlAsString()
                    )
                );
            }
        }

        if ($responseFormat == ResponseFormat::UNSUPPORTED) {
            throw new ODataException(
                Messages::dataServiceExceptionUnsupportedMediaType(), 
                415
            );
        }

        return $responseFormat;
    }

    /**
     * Get the content format corresponding to the given mime type.
     *
     * @param string $mime mime type for the request.
     * 
     * @return ResponseFormat Response format mapping to the given mime type.
     */
    private static function _getContentFormat($mime)
    {
        if (strcasecmp($mime, ODataConstants::MIME_APPLICATION_JSON) === 0) {
            return ResponseFormat::JSON;
        } else if (strcasecmp($mime, ODataConstants::MIME_APPLICATION_ATOM) === 0) {
            return ResponseFormat::ATOM;
        } else {
            $flag 
                = strcasecmp($mime, ODataConstants::MIME_APPLICATION_XML) === 0 ||
                    strcasecmp($mime, ODataConstants::MIME_APPLICATION_ATOMSERVICE) === 0 ||
                    strcasecmp($mime, ODataConstants::MIME_TEXTXML) === 0;
            self::assert(
                $flag, 
                'expecting application/xml, application/atomsvc+xml or plain/xml, got ' . $mime
            );
            return ResponseFormat::PLAIN_XML;
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
     * @return string/NULL The ETag for the entry object if it has eTag properties 
     *                     NULL otherwise.
     */
    protected function compareETag(&$entryObject, ResourceType &$resourceType, 
        &$needToSerializeResponse
    ) {      
        $needToSerializeResponse = true;
        $eTag = null;
        $ifMatch = $this->_dataServiceHost->getRequestIfMatch();
        $ifNoneMatch = $this->_dataServiceHost->getRequestIfNoneMatch();
        if (is_null($entryObject)) {
            if (!is_null($ifMatch)) {
                ODataException::createPreConditionFailedError(
                    Messages::dataServiceETagNotAllowedForNonExistingResource()
                ); 
            }

            return null;
        }
     
        if ($this->_dataServiceConfiguration->getValidateETagHeader() && !$resourceType->hasETagProperties()) {
            if (!is_null($ifMatch) || !is_null($ifNoneMatch)) {
                // No eTag properties but request has eTag headers, bad request
                ODataException::createBadRequestError(
                    Messages::dataServiceNoETagPropertiesForType()
                );
            }

            // We need write the response but no eTag header 
            return null;
        }

        if (!$this->_dataServiceConfiguration->getValidateETagHeader()) {
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
                        Messages::dataServiceETagValueDoesNotMatch()
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
     * @return string/NULL ETag value for the given resource (with values encoded 
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
                && array_search('ODataProducer\Providers\Metadata\Type\IType', class_implements($type)) !== false,
                '!is_null($type) 
                && array_search(\'ODataProducer\Providers\Metadata\Type\IType\', class_implements($type)) !== false'
            );
      
            $value = null; 
            try {
                $reflectionProperty 
                    = new \ReflectionProperty(
                        $entryObject, 
                        $eTagProperty->getName()
                    );
                $value = $reflectionProperty->getValue($entryObject);
            } catch (\ReflectionException $reflectionException) {
                throw ODataException::createInternalServerError(
                    Messages::dataServiceFailedToAccessProperty(
                        $eTagProperty->getName(), 
                        $resourceType->getName()
                    )
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
     * @return RequestDescription/null Instance of RequestDescription with 
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
?>