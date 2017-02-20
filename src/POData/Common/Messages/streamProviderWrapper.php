<?php

namespace POData\Common\Messages;

trait streamProviderWrapper
{
    /**
     * Message to show error when IStreamProvider.GetStreamETag returns invalid etag value.
     *
     * @return string The message
     */
    public static function streamProviderWrapperGetStreamETagReturnedInvalidETagFormat()
    {
        return 'The method \'IStreamProvider.GetStreamETag\' returned an entity tag with invalid format.';
    }

    /**
     * Message to show error when IStreamProvider.GetStreamContentType returns null or empty string.
     *
     * @return string The message
     */
    public static function streamProviderWrapperGetStreamContentTypeReturnsEmptyOrNull()
    {
        return 'The method \'IStreamProvider.GetStreamContentType\' must not return a null or empty string.';
    }

    /**
     * Message to show error when IStreamProvider.GetReadStream non stream.
     *
     * @return string The message
     */
    public static function streamProviderWrapperInvalidStreamFromGetReadStream()
    {
        return 'IStreamProvider.GetReadStream() must return a valid readable stream.';
    }

    /**
     * Message to show error when IStreamProvider.GetReadStreamUri returns relative uri.
     *
     * @return string The message
     */
    public static function streamProviderWrapperGetReadStreamUriMustReturnAbsoluteUriOrNull()
    {
        return 'The method IStreamProvider.GetReadStreamUri must return an absolute Uri or null.';
    }

    /**
     * Message to show error when data service does not implement IDSSP or IDSSP2 interfaces.
     *
     * @return string The message
     */
    public static function streamProviderWrapperMustImplementIStreamProviderToSupportStreaming()
    {
        return 'To support streaming, the data service must implement IService::getStreamProviderX() to return an implementation of IStreamProvider or IStreamProvider2';
    }

    /**
     * Message to show error when try to configure data service version as 2 for which named stream is defined.
     *
     * @return string The message
     */
    public static function streamProviderWrapperMaxProtocolVersionMustBeV3OrAboveToSupportNamedStreams()
    {
        return 'To support named streams, the MaxProtocolVersion of the data service must be set to ProtocolVersion.V3 or above.';
    }

    /**
     * Message to show error when data service does not provide implementation of IDDSP2 for which named stream is defined.
     *
     * @return string The message
     */
    public static function streamProviderWrapperMustImplementStreamProvider2ToSupportNamedStreams()
    {
        return 'To support named streams, the data service must implement IServiceProvider.GetService() to return an implementation of IStreamProvider2 or the data source must implement IStreamProvider2.';
    }

    /**
     * Message to show error when IDSSP/IDSSP2 implementation methods try to set etag or content type.
     *
     * @param string $methodName Method name
     *
     * @return string The formatted message
     */
    public static function streamProviderWrapperMustNotSetContentTypeAndEtag($methodName)
    {
        return "The method $methodName must not set the HTTP response headers 'Content-Type' and 'ETag'";
    }

    /**
     * Message to show error when IServiceProvider.GetService implementation returns invaild object when request for
     * IStreamProvider implementation.
     *
     * @return string The message
     */
    public static function streamProviderWrapperInvalidStreamInstance()
    {
        return 'return \'IServiceProvider.GetService\' for IStreamProvider returns invalid object.';
    }

    /**
     * Message to show error when IServiceProvider.GetService implementation returns invaild object when request for
     * IStreamProvider2 implementation.
     *
     * @return string The message
     */
    public static function streamProviderWrapperInvalidStream2Instance()
    {
        return 'return \'IServiceProvider.GetService\' for IStreamProvider2 returns invalid object.';
    }
}
