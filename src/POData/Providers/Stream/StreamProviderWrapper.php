<?php

namespace POData\Providers\Stream;

use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Version;
use POData\IService;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceStreamInfo;

/**
 * Class StreamProviderWrapper Wrapper over IDSSP and IDSSP2 implementations.
 */
class StreamProviderWrapper
{
    /**
     * Holds reference to the data service instance.
     *
     * @var IService
     */
    private $_service;

    /**
     * Holds reference to the implementation of IStreamProvider2.
     *
     *
     * @var IStreamProvider2
     */
    private $_streamProvider;

    /**
     * Used to check whether interface implementation modified response content type header or not.
     *
     *
     * @var string|null
     */
    private $_responseContentType;

    /**
     * Used to check whether interface implementation modified ETag header or not.
     *
     * @var string|null
     */
    private $_responseETag;

    /**
     * To set reference to the data service instance.
     *
     * @param IService $service The data service instance
     */
    public function setService(IService $service)
    {
        $this->_service = $service;
    }

    /**
     * To get stream associated with the given media resource.
     *
     * @param object             $entity             The media resource
     * @param ResourceStreamInfo $resourceStreamInfo This will be null if media
     *                                               resource is MLE, if media
     *                                               resource is named
     *                                               stream then will be the
     *                                               ResourceStreamInfo instance
     *                                               holding the details of
     *                                               named stream
     *
     * @return string|null
     */
    public function getReadStream($entity, ResourceStreamInfo $resourceStreamInfo = null)
    {
        $requestETag = null;
        $checkETagForEquality = null;
        $this->_getETagFromHeaders($requestETag, $checkETagForEquality);
        $stream = null;
        try {
            $this->_saveContentTypeAndETag();
            $opContext = $this->_service->getOperationContext();
            if (is_null($resourceStreamInfo)) {
                $this->_loadAndValidateStreamProvider();
                $stream = $this->_streamProvider->getReadStream2(
                    $entity,
                    null,
                    $requestETag,
                    $checkETagForEquality,
                    $opContext
                );
            } else {
                $this->_loadAndValidateStreamProvider2();
                $stream = $this->_streamProvider->getReadStream2(
                    $entity,
                    $resourceStreamInfo,
                    $requestETag,
                    $checkETagForEquality,
                    $opContext
                );
            }

            $this->_verifyContentTypeOrETagModified('IDSSP::getReadStream');
        } catch (ODataException $ex) {
            //Check for status code 304 (stream Not Modified)
            if (304 == $ex->getStatusCode()) {
                $eTag = $this->getStreamETag($entity, $resourceStreamInfo);
                if (!is_null($eTag)) {
                    $this->_service->getHost()->setResponseETag($eTag);
                }
            }
            throw $ex;
        }

        if (null == $stream) {
            if (null == $resourceStreamInfo) {
                // For default streams, we always expect getReadStream()
                // to return a non-null stream.  If we reach here, blow up.
                throw new InvalidOperationException(
                    Messages::streamProviderWrapperInvalidStreamFromGetReadStream()
                );
            } else {
                // For named streams, getReadStream() can return null to indicate
                // that the stream has not been created.
                // 204 == no content
                $this->_service->getHost()->setResponseStatusCode(204);
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
     *                                               type is to be obtained
     * @param ResourceStreamInfo $resourceStreamInfo This will be null if
     *                                               media resource is MLE,
     *                                               if media resource is named
     *                                               stream then will be the
     *                                               ResourceStreamInfo instance
     *                                               holding the details of
     *                                               named stream
     *
     * @return string|null
     */
    public function getStreamContentType($entity, ResourceStreamInfo $resourceStreamInfo = null)
    {
        $this->_saveContentTypeAndETag();
        $opContext = $this->_service->getOperationContext();
        if (is_null($resourceStreamInfo)) {
            $this->_loadAndValidateStreamProvider();
            $contentType = $this->_streamProvider->getStreamContentType2($entity, null, $opContext);
            if (is_null($contentType)) {
                throw new InvalidOperationException(
                    Messages::streamProviderWrapperGetStreamContentTypeReturnsEmptyOrNull()
                );
            }
        } else {
            $this->_loadAndValidateStreamProvider2();
            assert($this->_streamProvider instanceof IStreamProvider2);
            $contentType = $this->_streamProvider->getStreamContentType2($entity, $resourceStreamInfo, $opContext);
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
     *                                               the etag is to be obtained
     * @param ResourceStreamInfo $resourceStreamInfo This will be null if
     *                                               media resource is MLE,
     *                                               if media resource is named
     *                                               stream then will be the
     *                                               ResourceStreamInfo
     *                                               instance holding the
     *                                               details of named stream
     *
     * @throws InvalidOperationException
     *
     * @return string Etag
     */
    public function getStreamETag($entity, ResourceStreamInfo $resourceStreamInfo = null)
    {
        $this->_saveContentTypeAndETag();
        $opContext = $this->_service->getOperationContext();
        if (is_null($resourceStreamInfo)) {
            $this->_loadAndValidateStreamProvider();
            $eTag = $this->_streamProvider->getStreamETag2($entity, null, $opContext);
        } else {
            $this->_loadAndValidateStreamProvider2();
            assert($this->_streamProvider instanceof IStreamProvider2);
            $eTag = $this->_streamProvider->getStreamETag2($entity, $resourceStreamInfo, $opContext);
        }

        $this->_verifyContentTypeOrETagModified('IDSSP::getStreamETag');
        if (!self::isETagValueValid($eTag, true)) {
            throw new InvalidOperationException(
                Messages::streamProviderWrapperGetStreamETagReturnedInvalidETagFormat()
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
     *                                               be obtained
     * @param ResourceStreamInfo $resourceStreamInfo This will be null
     *                                               if media resource
     *                                               is MLE, if media
     *                                               resource is named
     *                                               stream then will be
     *                                               the ResourceStreamInfo
     *                                               instance holding the
     *                                               details of named stream
     * @param string             $mediaLinkEntryUri  MLE uri
     *
     * @throws InvalidOperationException
     *
     * @return string
     */
    public function getReadStreamUri(
        $entity,
        ResourceStreamInfo $resourceStreamInfo = null,
        $mediaLinkEntryUri
    ) {
        $this->_saveContentTypeAndETag();
        $opContext = $this->_service->getOperationContext();
        if (is_null($resourceStreamInfo)) {
            $this->_loadAndValidateStreamProvider();
            $readStreamUri = $this->_streamProvider->getReadStreamUri2($entity, null, $opContext);
        } else {
            $this->_loadAndValidateStreamProvider2();
            assert($this->_streamProvider instanceof IStreamProvider2);
            $readStreamUri = $this->_streamProvider->getReadStreamUri2($entity, $resourceStreamInfo, $opContext);
        }

        $this->_verifyContentTypeOrETagModified('IDSSP::getReadStreamUri');
        if (!is_null($readStreamUri)) {
            try {
                new \POData\Common\Url($readStreamUri);
            } catch (\POData\Common\UrlFormatException $ex) {
                throw new InvalidOperationException(
                    Messages::streamProviderWrapperGetReadStreamUriMustReturnAbsoluteUriOrNull()
                );
            }
        } else {
            if (is_null($resourceStreamInfo)) {
                // For MLEs the content src attribute is
                //required so we cannot return null.
                $readStreamUri = $this->getDefaultStreamEditMediaUri($mediaLinkEntryUri, null);
            }
        }

        // Note if readStreamUri is null, the self link for the
        // named stream will be omitted.
        return $readStreamUri;
    }

    /**
     * Checks the given value is a valid eTag.
     *
     * @param string $etag            eTag to validate
     * @param bool   $allowStrongEtag True if strong eTag is allowed
     *                                False otherwise
     *
     * @return bool
     */
    public static function isETagValueValid($etag, $allowStrongEtag)
    {
        if (is_null($etag) || '*' === $etag) {
            return true;
        }

        // HTTP RFC 2616, section 3.11:
        //   entity-tag = [ weak ] opaque-tag
        //   weak       = "W/"
        //   opaque-tag = quoted-string
        $etagValueStartIndex = 1;
        $eTagLength = strlen($etag);
        $isLastCharDubQuotes = ('"' == $etag[$eTagLength - 1]);

        if (0 === strpos($etag, 'W/"') && $isLastCharDubQuotes) {
            $etagValueStartIndex = 3;
        } elseif (!$allowStrongEtag || '"' != $etag[0] || !$isLastCharDubQuotes) {
            return false;
        }

        for ($i = $etagValueStartIndex; $i < $eTagLength - 1; ++$i) {
            // Format of etag looks something like: W/"etag property values"
            // or "strong etag value" according to HTTP RFC 2616, if someone
            // wants to specify more than 1 etag value, then need to specify
            // something like this: W/"etag values", W/"etag values", ...
            // To make sure only one etag is specified, we need to ensure
            // that if " is part of the key value, it needs to be escaped.
            if ('"' == $etag[$i]) {
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
     *                                     this parameter will hold NULL
     * @param mixed &$checkETagForEquality On return, this parameter will hold
     *                                     true if IfMatch is present, false if
     *                                     IfNoneMatch header is present, null
     *                                     otherwise
     */
    private function _getETagFromHeaders(&$eTag, &$checkETagForEquality)
    {
        $dataServiceHost = $this->_service->getHost();
        //TODO Do check for mutual exclusion of RequestIfMatch and
        //RequestIfNoneMatch in ServiceHost
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
     * Validates that an implementation of IStreamProvider exists and
     * load it.
     *
     *
     * @throws ODataException
     */
    private function _loadAndValidateStreamProvider()
    {
        if (is_null($this->_streamProvider)) {
            $this->_loadStreamProvider();
            if (is_null($this->_streamProvider)) {
                throw ODataException::createInternalServerError(
                    Messages::streamProviderWrapperMustImplementIStreamProviderToSupportStreaming()
                );
            }
        }
    }

    /**
     * Validates that an implementation of IStreamProvider2 exists and
     * load it.
     *
     *
     * @throws ODataException
     */
    private function _loadAndValidateStreamProvider2()
    {
        $maxServiceVersion = $this->_service->getConfiguration()->getMaxDataServiceVersion();
        if ($maxServiceVersion->compare(new Version(3, 0)) < 0) {
            throw ODataException::createInternalServerError(
                Messages::streamProviderWrapperMaxProtocolVersionMustBeV3OrAboveToSupportNamedStreams()
            );
        }

        if (is_null($this->_streamProvider)) {
            $this->_loadStreamProvider();
            if (is_null($this->_streamProvider)) {
                throw ODataException::createInternalServerError(
                    Messages::streamProviderWrapperMustImplementStreamProvider2ToSupportNamedStreams()
                );
            } elseif (!$this->_streamProvider instanceof IStreamProvider2) {
                throw ODataException::createInternalServerError(
                    Messages::streamProviderWrapperInvalidStream2Instance()
                );
            }
        }
    }

    /**
     * Ask data service to load stream provider instance.
     *
     *
     * @throws ODataException
     */
    private function _loadStreamProvider()
    {
        if (is_null($this->_streamProvider)) {
            $this->_streamProvider = $this->_service->getStreamProviderX();
            if (!is_null($this->_streamProvider) && (!$this->_streamProvider instanceof IStreamProvider2)) {
                throw ODataException::createInternalServerError(
                    Messages::streamProviderWrapperInvalidStream2Instance()
                );
            }
        }
    }

    /**
     * Construct the default edit media uri from the given media link entry uri.
     *
     * @param string                  $mediaLinkEntryUri  Uri to the media link entry
     * @param ResourceStreamInfo|null $resourceStreamInfo Stream info instance, if its
     *                                                    null default stream is assumed
     *
     * @return string Uri to the media resource
     */
    public function getDefaultStreamEditMediaUri($mediaLinkEntryUri, ResourceStreamInfo $resourceStreamInfo = null)
    {
        $base = rtrim($mediaLinkEntryUri, '/') . '/';
        $end = (null == $resourceStreamInfo) ? ODataConstants::URI_VALUE_SEGMENT
            : ltrim($resourceStreamInfo->getName(), '/');

        return $base . $end;
    }

    /**
     * Save value of content type and etag headers before invoking implementor
     * methods.
     */
    private function _saveContentTypeAndETag()
    {
        $this->_responseContentType = $this->_service->getHost()->getResponseContentType();
        $this->_responseETag = $this->_service->getHost()->getResponseETag();
    }

    /**
     * Check whether implementor modified content type or etag header
     * if so throw InvalidOperationException.
     *
     * @param string $methodName NAme of the method
     *
     * @throws InvalidOperationException
     */
    private function _verifyContentTypeOrETagModified($methodName)
    {
        if ($this->_responseContentType !== $this->_service->getHost()->getResponseContentType()
            || $this->_responseETag !== $this->_service->getHost()->getResponseETag()
        ) {
            throw new InvalidOperationException(
                Messages::streamProviderWrapperMustNotSetContentTypeAndEtag($methodName)
            );
        }
    }
}
