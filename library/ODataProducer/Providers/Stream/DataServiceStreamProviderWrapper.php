<?php
/**
 * Wrapper over IDSSP and IDSSP2 implementations.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Stream
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
namespace ODataProducer\Providers\Stream;
use ODataProducer\DataService;
use ODataProducer\Providers\Metadata\ResourceStreamInfo;
use ODataProducer\OperationContext\DataServiceHost;
use ODataProducer\Common\Version;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
use ODataProducer\Common\InvalidOperationException;
/**
 * Wrapper over IDSSP and IDSSP2 implementations.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Stream
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class DataServiceStreamProviderWrapper
{
    /**
     * Holds reference to the data service instance.
     * 
     * @var DataService
     */
    private $_dataService;

    /**
     * Holds reference to the implementation of IDataServiceStreamProvider
     * or IDataServiceStreamProvider2.
     * 
     * @var IDataServiceStreamProvider/IDataServiceStreamProvider2
     */
    private $_streamProvider;

    /**
     * Used to check whether interface implementor modified repsonse content
     * type header or not.
     *
     * @var string/NULL
     */
    private $_responseContentType;

    /**
     * Used to check whether interface implementor modified etag header or not.
     *
     * @var string/NULL
     */
    private $_responseETag;

    /**
     * Constructs a new instance of DataServiceStreamProviderWrapper
     */
    public function __construct()
    {
    }

    /**
     * To set reference to the data service instance.
     * 
     * @param DataService &$dataService The data service instance.
     * 
     * @return void
     */
    public function setDataService(DataService &$dataService)
    {
        $this->_dataService = $dataService;
    }

    /**
     * To get stream associated with the given media resource.
     * 
     * @param object             $entity             The media resource.
     * @param ResourceStreamInfo $resourceStreamInfo This will be null if media
     *                                               resource is MLE, if media 
     *                                               resource is named
     *                                               stream then will be the 
     *                                               ResourceStreamInfo instance 
     *                                               holding the details of 
     *                                               named stream.
     * 
     * @return string/NULL
     */
    public function getReadStream($entity, $resourceStreamInfo)
    {
        $requestETag = null;
        $checkETagForEquality = null;
        $this->_getETagFromHeaders($requestETag, $checkETagForEquality);
        $stream = null;
        try {
            $this->_saveContentTypeAndETag();
            if (is_null($resourceStreamInfo)) {
                $this->_loadAndValidateStreamProvider();
                $stream = $this->_streamProvider->getReadStream(
                    $entity,
                    $requestETag,
                    $checkETagForEquality,
                    $this->_dataService->getOperationContext()
                );
            } else {
                $this->_loadAndValidateStreamProvider2();
                $stream = $this->_streamProvider->getReadStream2(
                    $entity,
                    $resourceStreamInfo,
                    $requestETag,
                    $checkETagForEquality,
                    $this->_dataService->getOperationContext()
                );
            }

            $this->_verifyContentTypeOrETagModified('IDSSP::getReadStream');
        } catch(ODataException $odataException) {
            //Check for status code 304 (stream Not Modified)
            if ($odataException->getStatusCode() == 304) {
                $eTag = $this->getStreamETag($entity, $resourceStreamInfo);
                if (!is_null($eTag)) {
                    $this->_dataService->getHost()->setResponseETag($eTag);
                }
            }
            throw $odataException;
        }

        if ($resourceStreamInfo == null) {
            // For default streams, we always expect getReadStream()
            // to return a non-null stream.
            if (is_null($stream)) {
                throw new InvalidOperationException(
                    Messages::dataServiceStreamProviderWrapperInvalidStreamFromGetReadStream()
                );
            }
        } else {
            // For named streams, getReadStream() can return null to indicate
            // that the stream has not been created.
            if (is_null($stream)) {
                // 204 == no content                
                $this->_dataService->getHost()->setResponseStatusCode(204);
            }
        }

        return $stream;
    }

    /**
     * Gets the IANA content type (aka media type) of the stream associated with 
     * the specified media resource.
     * 
     * @param object             $entity             The entity instance 
     *                                               (media resource) associated with
     *                                               the stream for which the content
     *                                               type is to be obtained.
     * @param ResourceStreamInfo $resourceStreamInfo This will be null if 
     *                                               media resource is MLE, 
     *                                               if media resource is named
     *                                               stream then will be the 
     *                                               ResourceStreamInfo instance 
     *                                               holding the details of 
     *                                               named stream.
     * 
     * @return string/NULL
     */
    public function getStreamContentType($entity, $resourceStreamInfo)
    {
        $contentType = null;
        $this->_saveContentTypeAndETag();
        if (is_null($resourceStreamInfo)) {
            $this->_loadAndValidateStreamProvider();
            $contentType = $this->_streamProvider->getStreamContentType(
                $entity,
                $this->_dataService->getOperationContext()
            );
            if (is_null($contentType)) {
                throw new InvalidOperationException(
                    Messages::dataServiceStreamProviderWrapperGetStreamContentTypeReturnsEmptyOrNull()
                );
            }
        } else {
            $this->_loadAndValidateStreamProvider2();
            $contentType = $this->_streamProvider->getStreamContentType2(
                $entity,
                $resourceStreamInfo,
                $this->_dataService->getOperationContext()
            );
        }

        $this->_verifyContentTypeOrETagModified('IDSSP::getStreamContentType');
        return $contentType;
    }

    /**
     * Get the ETag of the stream associated with the entity specified.
     * 
     * @param object             $entity             The entity instance 
     *                                               (media resource) associated
     *                                               with the stream for which 
     *                                               the etag is to be obtained.
     * @param ResourceStreamInfo $resourceStreamInfo This will be null if 
     *                                               media resource is MLE, 
     *                                               if media resource is named
     *                                               stream then will be the 
     *                                               ResourceStreamInfo
     *                                               instance holding the 
     *                                               details of named stream.
     * 
     * @throws InvalidOperationException
     * @return String Etag
     */
    public function getStreamETag($entity, $resourceStreamInfo)
    {
        $eTag = null;
        $this->_saveContentTypeAndETag();
        if (is_null($resourceStreamInfo)) {
            $this->_loadAndValidateStreamProvider();
            $eTag = $this->_streamProvider->getStreamETag(
                $entity,
                $this->_dataService->getOperationContext()
            );
        } else {
            $this->_loadAndValidateStreamProvider2();
            $eTag = $this->_streamProvider->getStreamETag2(
                $entity,
                $resourceStreamInfo,
                $this->_dataService->getOperationContext()
            );
        }

        $this->_verifyContentTypeOrETagModified('IDSSP::getStreamETag');
        if (!self::isETagValueValid($eTag, true)) {
            throw new InvalidOperationException(
                Messages::dataServiceStreamProviderWrapperGetStreamETagReturnedInvalidETagFormat()
            );
        }

        return $eTag;
    }

    /**
     * Gets the URI clients should use when making retrieve (ie. GET) requests 
     * to the stream.
     * 
     * @param object             $entity             The entity instance 
     *                                               associated with the
     *                                               stream for which a 
     *                                               read stream URI is to
     *                                               be obtained.
     * @param ResourceStreamInfo $resourceStreamInfo This will be null 
     *                                               if media resource
     *                                               is MLE, if media 
     *                                               resource is named
     *                                               stream then will be 
     *                                               the ResourceStreamInfo
     *                                               instance holding the 
     *                                               details of named stream.
     * @param string             $mediaLinkEntryUri  MLE uri.
     * 
     * @return string
     * 
     * @throws InvalidOperationException
     */
    public function getReadStreamUri($entity, $resourceStreamInfo, 
        $mediaLinkEntryUri
    ) {
        $readStreamUri = null;
        $this->_saveContentTypeAndETag();
        if (is_null($resourceStreamInfo)) {
            $this->_loadAndValidateStreamProvider();
            $readStreamUri = $this->_streamProvider->getReadStreamUri(
                $entity,
                $this->_dataService->getOperationContext()
            );
        } else {
            $this->_loadAndValidateStreamProvider2();
            $readStreamUri = $this->_streamProvider->getReadStreamUri2(
                $entity,
                $resourceStreamInfo,
                $this->_dataService->getOperationContext()
            );
        }

        $this->_verifyContentTypeOrETagModified('IDSSP::getReadStreamUri');
        if (!is_null($readStreamUri)) {
            try {
                new ODataProducer\Common\Url($readStreamUri);
            } catch (\ODataProducer\Common\UrlFormatException $ex) {
                throw new InvalidOperationException(
                    Messages::dataServiceStreamProviderWrapperGetReadStreamUriMustReturnAbsoluteUriOrNull()
                );
            }            
        } else {
            if (is_null($resourceStreamInfo)) {
                // For MLEs the content src attribute is 
                //required so we cannot return null.
                $readStreamUri
                    = $this->getDefaultStreamEditMediaUri(
                        $mediaLinkEntryUri,
                        null
                    );
            }
        }

        // Note if readStreamUri is null, the self link for the
        // named stream will be omitted.
        return $readStreamUri;
    }

    /**
     * Checks the given value is a valid eTag.
     * 
     * @param string  $etag            eTag to validate.
     * @param boolean $allowStrongEtag True if strong eTag is allowed 
     *                                 False otherwise.
     * 
     * @return boolean
     */
    public static function isETagValueValid($etag, $allowStrongEtag)
    {
        if (is_null($etag) || $etag === '*') {
            return true;
        }

        // HTTP RFC 2616, section 3.11:
        //   entity-tag = [ weak ] opaque-tag
        //   weak       = "W/"
        //   opaque-tag = quoted-string
        $etagValueStartIndex = 1;
        $eTagLength = strlen($etag);

        if (strpos($etag, "W/\"") === 0 && $etag[$eTagLength - 1] == '"') {
            $etagValueStartIndex = 3;
        } else if (!$allowStrongEtag || $etag[0] != '"' 
            || $etag[$eTagLength - 1] != '"'
        ) {
            return false;
        }

        for ($i = $etagValueStartIndex; $i < $eTagLength - 1; $i++) {
            // Format of etag looks something like: W/"etag property values" 
            // or "strong etag value" according to HTTP RFC 2616, if someone 
            // wants to specify more than 1 etag value, then need to specify 
            // something like this: W/"etag values", W/"etag values", ...
            // To make sure only one etag is specified, we need to ensure 
            // that if " is part of the key value, it needs to be escaped.
            if ($etag[$i] == '"') {
                return false;
            }
        }

        return true;
    }

    /**
     * Get ETag header value from request header.
     *      
     * @param mixed &$eTag                 On return, this parameter will hold
     *                                     value of IfMatch or IfNoneMatch
     *                                     header, if this header is absent then
     *                                     this parameter will hold NULL.
     * @param mixed &$checkETagForEquality On return, this parameter will hold
     *                                     true if IfMatch is present, false if
     *                                     IfNoneMatch header is present, null
     *                                     otherwise.
     * 
     * @return void
     */
    private function _getETagFromHeaders(&$eTag, &$checkETagForEquality)
    {
        $dataServiceHost = $this->_dataService->getHost();
        //TODO Do check for mutual exclusion of RequestIfMatch and
        //RequestIfNoneMatch in DataServiceHost
        $eTag = $dataServiceHost->getRequestIfMatch();
        if (!is_null($eTag)) {
            $checkETagForEquality = true;
            return;
        }

        $eTag = $dataServiceHost->getRequestIfNoneMatch();
        if (!is_null($eTag)) {
            $checkETagForEquality = false;
            return;
        }

        $checkETagForEquality = null;
    }

    /**
     * Validates that an implementation of IDataServiceStreamProvider exists and
     * load it.
     * 
     * @return void
     * 
     * @throws ODataException
     */
    private function _loadAndValidateStreamProvider()
    {
        if (is_null($this->_streamProvider)) {
            $this->_loadStreamProvider();
            if (is_null($this->_streamProvider)) {
                ODataException::createInternalServerError(
                    Messages::dataServiceStreamProviderWrapperMustImplementIDataServiceStreamProviderToSupportStreaming()
                );
            }
        }
    }

    /**
     * Validates that an implementation of IDataServiceStreamProvider2 exists and
     * load it.
     * 
     * @return void
     * 
     * @throws ODataException
     */
    private function _loadAndValidateStreamProvider2()
    {
        $maxServiceVersion = $this->_dataService
            ->getServiceConfiguration()
            ->getMaxDataServiceVersionObject();
        if ($maxServiceVersion->compare(new Version(3, 0)) < 0) {
            ODataException::createInternalServerError(
                Messages::dataServiceStreamProviderWrapperMaxProtocolVersionMustBeV3OrAboveToSupportNamedStreams()
            );
        }

        if (is_null($this->_streamProvider)) {
            $this->_loadStreamProvider();
            if (is_null($this->_streamProvider)) {
                ODataException::createInternalServerError(
                    Messages::dataServiceStreamProviderWrapperMustImplementDataServiceStreamProvider2ToSupportNamedStreams()
                );
            } else if (array_search('ODataProducer\Providers\Stream\IDataServiceStreamProvider2', class_implements($this->_streamProvider)) === false) {
                ODataException::createInternalServerError(
                    Messages::dataServiceStreamProviderWrapperInvalidStream2Instance()
                );
            }
        }
    }

    /**
     * Ask data service to load stream provider instance.
     * 
     * @return void
     * 
     * @throws ODataException
     */
    private function _loadStreamProvider()
    {
        if (is_null($this->_streamProvider)) {
            $maxServiceVersion = $this->_dataService
                ->getServiceConfiguration()
                ->getMaxDataServiceVersionObject();
            if ($maxServiceVersion->compare(new Version(3, 0)) >= 0) {
                $this->_streamProvider 
                    = $this->_dataService->getService('IDataServiceStreamProvider2');
                if (!is_null($this->_streamProvider) && (!is_object($this->_streamProvider) || array_search('ODataProducer\Providers\Stream\IDataServiceStreamProvider2', class_implements($this->_streamProvider)) === false)) {
                    ODataException::createInternalServerError(
                        Messages::dataServiceStreamProviderWrapperInvalidStream2Instance()
                    ); 
                }
            }

            if (is_null($this->_streamProvider)) {
                $this->_streamProvider 
                    = $this->_dataService->getService('IDataServiceStreamProvider');
                if (!is_null($this->_streamProvider) && (!is_object($this->_streamProvider) || array_search('ODataProducer\Providers\Stream\IDataServiceStreamProvider', class_implements($this->_streamProvider)) === false)) {
                    ODataException::createInternalServerError(
                        Messages::dataServiceStreamProviderWrapperInvalidStreamInstance()
                    );
                }
            }
        }
    }

    /**
     * Construct the default edit media uri from the given media link entry uri.
     * 
     * @param string             $mediaLinkEntryUri  Uri to the media link entry.
     * @param ResourceStremaInfo $resourceStreamInfo Stream info instance, if its 
     *                                               null default stream is assumed.
     * 
     * @return string Uri to the media resource.
     */
    public function getDefaultStreamEditMediaUri($mediaLinkEntryUri, $resourceStreamInfo)
    {
        if (is_null($resourceStreamInfo)) {
            return rtrim($mediaLinkEntryUri, '/') . '/' . ODataConstants::URI_VALUE_SEGMENT;
        } else {
            return rtrim($mediaLinkEntryUri, '/') . '/' . ltrim($resourceStreamInfo->getName(), '/');
        }
    }

    /**
     * Save value of content type and etag headers before invoking implementor
     * methods.
     * 
     * @return void
     */
    private function _saveContentTypeAndETag()
    {
        $this->_responseContentType
            = $this->_dataService->getHost()->getResponseContentType();
        $this->_responseETag
            = $this->_dataService->getHost()->getResponseETag();
    }

    /**
     * Check whether implementor modified content type or etag header
     * if so throw InvalidOperationException.
     * 
     * @param string $methodName NAme of the method
     * 
     * @return void
     * 
     * @throws InvalidOperationException
     */
    private function _verifyContentTypeOrETagModified($methodName)
    {
        if ($this->_responseContentType !== $this->_dataService->getHost()->getResponseContentType()
            || $this->_responseETag !== $this->_dataService->getHost()->getResponseETag()
        ) {
            throw new InvalidOperationException(
                Messages::dataServiceStreamProviderWrapperMustNotSetContentTypeAndEtag($methodName)
            );
        }
    }
}
?>
