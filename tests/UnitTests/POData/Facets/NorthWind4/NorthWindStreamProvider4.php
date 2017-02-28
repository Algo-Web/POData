<?php

namespace UnitTests\POData\Facets\NorthWind4;

use POData\Common\ODataException;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\Web\WebOperationContext;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Stream\IStreamProvider2;

class NorthWindStreamProvider4 implements IStreamProvider2
{
    // NOTE: update this path as per your configuration
    const IMAGE_PATH_ROOT = 'D:\\Projects\\ODataPHPProducer\\services\\NorthWind\\images\\';

    //Begin IStreamProvider methods implementation

    /**
     * Method invoked by the data services framework to retrieve the default
     * stream associated with the entity instance specified by the entity parameter.
     *
     * @param object              $entity               The stream returned should be
     *                                                  the default stream associated
     *                                                  with this entity instance
     * @param string              $eTag                 The etag value sent by the
     *                                                  client (as the value of an
     *                                                  If[-None-]Match header)
     *                                                  as part of the HTTP request,
     *                                                  This parameter will be
     *                                                  null if no If[-None-]Match
     *                                                  header was present
     * @param bool                $checkETagForEquality True if an value of the etag
     *                                                  parameter was sent
     *                                                  to the server as the value
     *                                                  of an If-Match HTTP
     *                                                  request header,
     *                                                  False if an value of the etag
     *                                                  parameter was sent to the
     *                                                  server as the the value
     *                                                  of an If-None-Match HTTP
     *                                                  request header null if
     *                                                  the HTTP request for the
     *                                                  stream was not a
     *                                                  conditional request
     * @param WebOperationContext $operationContext     A reference to the context
     *                                                  for the current operation
     *
     * @throws ODataException if a valid stream cannot be returned.
     *                        Null should never be returned from this method
     *
     * @return mixed A valid  default stream which is associated with the entity,
     *               Null should never be returned from this method
     */
    public function getReadStream(
        $entity,
        $eTag,
        $checkETagForEquality,
        $operationContext
    ) {
        /*
        if (!is_null($checkETagForEquality)) {
            throw new ODataException(
                'This service does not support the ETag header for a media resource',
                400
            );
        }**/
        // NOTE: In this impementation we are not checking the eTag equality
        // We will return the stream irrespective of the whether the eTag match of not

        if (!($entity instanceof Employee)) {
            throw new ODataException(
                'Internal Server Error.',
                500
            );
        }

        $filePath = self::IMAGE_PATH_ROOT
            .'Employee_'.$entity->EmployeeID
            .'.jpg';
        if (file_exists($filePath)) {
            $handle = fopen($filePath, 'r');
            $stream = fread($handle, filesize($filePath));
            fclose($handle);

            return $stream;
        } else {
            throw new ODataException(
                'The image file could not be found',
                500
            );
        }
    }

    /**
     * Method invoked by the data services framework to obtain the
     * IANA content type (aka media type) of the stream associated
     * with the specified entity.  This metadata is needed when
     * constructing the payload for the Media Link Entry associated
     * with the stream (aka Media Resource) or setting the Content-Type
     * HTTP response header.
     *
     * @param object              $entity           The entity instance associated
     *                                              with the stream for which
     *                                              the content type is to
     *                                              be obtained
     * @param WebOperationContext $operationContext A reference to the context
     *                                              for the current operation
     *
     * @throws ODataException if a valid stream content type
     *                        associated with the entity specified could not be returned
     *
     * @return string Valid Content-Type string for the stream
     *                associated with the entity
     */
    public function getStreamContentType($entity, $operationContext)
    {
        if (!($entity instanceof Employee)) {
            throw new ODataException(
                'Internal Server Error.',
                500
            );
        }

        return 'image/jpeg';
    }

    /**
     * Method invoked by the data services framework to obtain the ETag
     * of the stream associated with the entity specified.
     * This metadata is needed when constructing the
     * payload for the Media Link Entry associated with the stream
     * (aka Media Resource) as well as to be used as the
     * value of the ETag HTTP response header.
     *
     * @param object              $entity           The entity instance
     *                                              associated with the
     *                                              stream for which an
     *                                              etag is to be obtained
     * @param WebOperationContext $operationContext A reference to the context
     *                                              for the current
     *                                              operation
     *
     * @return string ETag of the stream associated with the entity specified
     */
    public function getStreamETag($entity, $operationContext)
    {
        if (!($entity instanceof Employee)) {
            throw new ODataException(
                'Internal Server Error.',
                500
            );
        }

        $lastModifiedTime = null;
        $filePath = self::IMAGE_PATH_ROOT.'Employee_'.$entity->EmployeeID.'.jpg';
        if (file_exists($filePath)) {
            $lastModifiedTime = date('"m-d-Y H:i:s"', filemtime($filePath));
        } else {
            // The no stream associated the the requested enttiy, so no eTag
            return;
        }

        return $lastModifiedTime;
    }

    /**
     * This method is invoked by the data services framework
     * to obtain the URI clients should
     * use when making retrieve (ie. GET) requests to the stream(ie. Media Resource).
     * This metadata is needed when constructing the payload for the Media Link Entry
     * associated with the stream (aka Media Resource).
     *
     * @param object              $entity           The entity instance
     *                                              associated with the
     *                                              stream for which a read
     *                                              stream URI is to
     *                                              be obtained
     * @param WebOperationContext $operationContext A reference to the
     *                                              context for the current
     *                                              operation
     *
     * @return string The URI clients should use when making retrieve
     *                (ie. GET) requests to the stream(ie. Media Resource)
     */
    public function getReadStreamUri($entity, $operationContext)
    {
        //let library creates default media url.
    }

    //End IStreamProvider methods implementation

    //Begin IStreamProvider2 methods implementation

    /**
     * This method is invoked by the data services framework to retrieve the named stream
     * associated with the entity instance specified by the entity parameter.
     *
     * @param object              $entity               The stream returned should be the default
     *                                                  stream associated with this entity instance
     * @param ResourceStreamInfo  $resourceStreamInfo   The ResourceStreamInfo instance that describes
     *                                                  the named stream
     * @param string              $eTag                 The etag value sent by the client (as the
     *                                                  value of an If[-None-]Match header) as part
     *                                                  of the HTTP request, This parameter will be
     *                                                  null if no If[-None-]Match header was present
     * @param bool                $checkETagForEquality True if an value of the etag parameter was sent
     *                                                  to the server as the value of an If-Match HTTP
     *                                                  request header, False if an value of the etag
     *                                                  parameter was sent to the server as the the value
     *                                                  of an If-None-Match HTTP request header null if
     *                                                  the HTTP request for the stream was not a
     *                                                  conditional request
     * @param WebOperationContext $operationContext     A reference to the context for the current operation
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
        ResourceStreamInfo $resourceStreamInfo,
        $eTag,
        $checkETagForEquality,
        IOperationContext $operationContext
    ) {
        /*
        if (!is_null($checkETagForEquality)) {
            throw new ODataException(
                'This service does not support the ETag header for a media resource',
                400
            );
        }
        **/

        if (!($entity instanceof Employee)) {
            throw new ODataException('Internal Server Error.', 500);
        }

        $filePath = self::IMAGE_PATH_ROOT.'Employee_'
            .$entity->EmployeeID
            .'_'
            .$resourceStreamInfo->getName()
            .'.png';
        if (file_exists($filePath)) {
            $handle = fopen($filePath, 'r');
            $stream = fread($handle, filesize($filePath));
            fclose($handle);

            return $stream;
        } else {
            throw new ODataException('The image file could not be found', 500);
        }
    }

    /**
     * This method is invoked by the data services framework to obtain the IANA content type
     * (aka media type) of the named stream associated with the specified entity.  This
     * metadata is needed when constructing the payload for the entity associated with the
     * named stream or setting the Content-Type HTTP response header.
     *
     * @param object              $entity             The entity instance associated with the
     *                                                stream for which the content type is to
     *                                                be obtained
     * @param ResourceStreamInfo  $resourceStreamInfo The ResourceStreamInfo instance that describes
     *                                                the named stream
     * @param WebOperationContext $operationContext   A reference to the context for the current
     *                                                operation
     *
     * @return string Valid Content-Type string for the named stream associated with the entity
     */
    public function getStreamContentType2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo,
        IOperationContext $operationContext
    ) {
        if (!($entity instanceof Employee)) {
            throw new ODataException('Internal Server Error.', 500);
        }

        return 'image/png';
    }

    /**
     * This method is invoked by the data services framework to obtain the ETag of the
     * name stream associated with the entity specified. This metadata is needed when
     * constructing the payload for the entity associated with the named stream as well as
     * to be used as the value of the ETag HTTP response header.
     *
     * @param object              $entity             The entity instance associated with the
     *                                                stream for which an etag is to be obtained
     * @param ResourceStreamInfo  $resourceStreamInfo The ResourceStreamInfo instance that describes
     *                                                the named stream
     * @param WebOperationContext $operationContext   A reference to the context for the current
     *                                                operation
     *
     * @return string ETag of the named stream associated with the entity specified
     */
    public function getStreamETag2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo,
        IOperationContext $operationContext
    ) {
    }

    /**
     * This method is invoked by the data services framework to obtain the URI clients should
     * use when making retrieve (ie. GET) requests to the named stream.
     * This metadata is needed when constructing the payload for the entity associated with
     * the named stream.
     *
     * @param object              $entity             The entity instance associated with the
     *                                                stream for which a read stream URI is to
     *                                                be obtained
     * @param ResourceStreamInfo  $resourceStreamInfo The ResourceStreamInfo instance that describes
     *                                                the named stream
     * @param WebOperationContext $operationContext   A reference to the context for the current
     *                                                operation
     *
     * @return string The URI clients should use when making retrieve (ie. GET) requests to
     *                the stream(ie. Media Resource)
     */
    public function getReadStreamUri2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo,
        IOperationContext $operationContext
    ) {
    }

    //End IStreamProvider2 methods implementation
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
    ) {
        // TODO: Implement getDefaultStreamEditMediaUri() method.
    }
}
