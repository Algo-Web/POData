<?php

namespace POData\Providers\Stream;

use POData\Common\ODataException;
use POData\OperationContext\IOperationContext;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;

/**
 * Class IStreamProvider2.
 *
 * The IStreamProvider2 interface defines the contract between the
 * data services framework server component and a data source's and a data
 * source's named stream implementation (ie. a stream provider).
 */
interface IStreamProvider2
{
    /**
     * This method is invoked by the data services framework to retrieve the named stream
     * associated with the entity instance specified by the entity parameter.
     *
     * Notes to interface implementers:
     *  Concurrency check:
     *    An implementer of this method MUST perform concurrency checks as needed in their
     *    implementation of this method.  If an If-Match or If-None-Match request header was
     *    included in the request, then the $etag parameter will be non null, which indicates
     *    this method MUST perform the appropriate concurrency check.  If the concurrency check
     *    passes, this method should return the requested stream.  If the concurrency checks fails,
     *    the method should throw  an ODataException with the appropriate HTTP response code
     *    as defined in HTTP RFC 2616 section 14.24 and section 14.26.
     *     a. If the etag was sent as the value of an If-Match request header, the value of the
     *        $checkETagForEquality header will be set to true.
     *     b. If the etag was sent as the value of an If-None-Match request header, the value of
     *        the $checkETagForEquality header will be set to false.
     * Using request headers in request:
     *   The $operationContext argument is passed as it is likely that an implementer of this
     *   interface method will need information from the HTTP request headers in order to construct
     *   a stream.  Likely header values required are:
     *    a. 'Accept'
     *    b. 'Accept-Charset'
     *    c. 'Accept-Encoding'
     * Setting response headers:
     *   An implementer of this method MUST NOT set the following HTTP response headers on the
     *   $operationContext parameter as they are set by the data service runtime:
     *    a. Content-Type
     *    b. ETag
     *  An implementer of this method may set HTTP response headers (other than those forbidden above)
     *  on the $operationContext parameter.
     *  An implementer of this method should only set the properties on the $operationContext parameter
     *  which it requires to be set for a successful response.  Altering other properties on the
     *  $operationContext parameter may corrupt the response from the data service.
     *
     * If an error occurs while reading the stream, then the data services framework will generate an
     * in-stream error which is sent back to the client.  See the error contract specification for a
     * description of the format of in-stream errors
     *
     *  If the stream returned from this method contains 0 byte, this method should set the response
     *  status code on the $operationContext.
     *
     * @param object             $entity               The stream returned should be the default
     *                                                 stream associated with this entity instance
     * @param ResourceStreamInfo $resourceStreamInfo   The ResourceStreamInfo instance that describes
     *                                                 the named stream
     * @param string             $eTag                 The etag value sent by the client (as the
     *                                                 value of an If[-None-]Match header) as part
     *                                                 of the HTTP request, This parameter will be
     *                                                 null if no If[-None-]Match header was present
     * @param bool               $checkETagForEquality True if an value of the etag parameter was sent
     *                                                 to the server as the value of an If-Match HTTP
     *                                                 request header, False if an value of the etag
     *                                                 parameter was sent to the server as the the value
     *                                                 of an If-None-Match HTTP request header null if
     *                                                 the HTTP request for the stream was not a
     *                                                 conditional request
     * @param IOperationContext  $operationContext     A reference to the context for the current operation
     *
     * @throws ODataException if a valid stream or null cannot be returned for the given arguments
     *
     * @return mixed A valid stream the data service use to query/read a named stream which is
     *               associated with the $entity. Null may be returned from this method if the requested named
     *               stream has not been created since the creation of $entity. The data service will respond
     *               with 204 if this method returns null
     */
    public function getReadStream2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo = null,
        $eTag,
        $checkETagForEquality,
        IOperationContext $operationContext
    );

    /**
     * This method is invoked by the data services framework to obtain the IANA content type
     * (aka media type) of the named stream associated with the specified entity.  This
     * metadata is needed when constructing the payload for the entity associated with the
     * named stream or setting the Content-Type HTTP response header.
     *
     * The string should be returned in a format which is directly usable as the value
     * of an HTTP Content-Type response header. For example, if the stream represented
     * a PNG image the return value would be "image/png"
     *
     * If the requested named stream has not yet been uploaded, this method must return null or
     * empty string. Otherwise this method MUST always return a valid content type string
     * for the requested named stream.
     *
     * Altering properties on the $operationContext parameter may corrupt the response
     * from the data service.
     *
     * @param object             $entity             The entity instance associated with the
     *                                               stream for which the content type is to
     *                                               be obtained
     * @param ResourceStreamInfo $resourceStreamInfo The ResourceStreamInfo instance that describes
     *                                               the named stream
     * @param IOperationContext  $operationContext   A reference to the context for the current
     *                                               operation
     *
     * @return string Valid Content-Type string for the named stream associated with the entity
     */
    public function getStreamContentType2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo = null,
        IOperationContext $operationContext
    );

    /**
     * This method is invoked by the data services framework to obtain the ETag of the
     * name stream associated with the entity specified. This metadata is needed when
     * constructing the payload for the entity associated with the named stream as well as
     * to be used as the value of the ETag HTTP response header.
     *
     * This method enables a named stream to have an ETag which is different from that
     * of its associated entity. The returned string MUST be formatted such that it is
     * directly usable as the value of an HTTP ETag response header. If null is returned
     * the data service framework will assume that no ETag is associated with the stream.
     *
     * NOTE: Altering properties on the $operationContext parameter may corrupt the response
     * from the data service.
     *
     * @param object             $entity             The entity instance associated with the
     *                                               stream for which an etag is to be obtained
     * @param ResourceStreamInfo $resourceStreamInfo The ResourceStreamInfo instance that describes
     *                                               the named stream
     * @param IOperationContext  $operationContext   A reference to the context for the current
     *                                               operation
     *
     * @return string ETag of the named stream associated with the entity specified
     */
    public function getStreamETag2($entity,
        ResourceStreamInfo $resourceStreamInfo = null,
        IOperationContext $operationContext
    );

    /**
     * This method is invoked by the data services framework to obtain the URI clients should
     * use when making retrieve (ie. GET) requests to the named stream.
     * This metadata is needed when constructing the payload for the entity associated with
     * the named stream.
     *
     * This method was added such that an entity representation could state that a named stream
     * is to be edited using one URI and read using another.
     * This is supported such that a data service could leverage a Content Distribution Network
     * for its stream content.
     *
     * The URI returned maps to the value of of the self link for the named media resource.
     * If the JSON format is used (as noted in section 3.2.3) this URI represents the value
     * of the src_media name/value pair.
     *
     * The returned URI MUST be an absolute URI and represents the location where a
     * consumer (reader) of the stream should send requests to in order to obtain the contents
     * of the stream.
     *
     * If URI returned is null, then the data service runtime omit the self link for the
     * named media resource.
     *
     * @param object             $entity             The entity instance associated with the
     *                                               stream for which a read stream URI is to
     *                                               be obtained
     * @param ResourceStreamInfo $resourceStreamInfo The ResourceStreamInfo instance that describes
     *                                               the named stream
     * @param IOperationContext  $operationContext   A reference to the context for the current
     *                                               operation
     *
     * @return string The URI clients should use when making retrieve (ie. GET) requests to
     *                the stream(ie. Media Resource)
     */
    public function getReadStreamUri2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo = null,
        IOperationContext $operationContext
    );

    /**
     * @param $entity
     * @param ResourceType $resourceType
     * @param ResourceStreamInfo|null $resourceStreamInfo
     * @param IOperationContext $operationContext
     * @param null $relativeUri
     * @return mixed
     */
    public function getDefaultStreamEditMediaUri(
        $entity,
        ResourceType $resourceType,
        ResourceStreamInfo $resourceStreamInfo = null,
        IOperationContext $operationContext,
        $relativeUri = null
    );
}
