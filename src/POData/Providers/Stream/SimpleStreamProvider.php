<?php

namespace POData\Providers\Stream;

use POData\OperationContext\IOperationContext;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;

/**
 * Class SimpleStreamProvider
 * @package POData\Providers\Stream
 */
class SimpleStreamProvider implements IStreamProvider2
{
    /**
     * @param  object                  $entity
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @param  string                  $eTag
     * @param  bool                    $checkETagForEquality
     * @param  IOperationContext       $operationContext
     * @return string
     */
    public function getReadStream2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo = null,
        $eTag,
        $checkETagForEquality,
        IOperationContext $operationContext
    ) {
        if (null == $resourceStreamInfo) {
            return 'stream for ' . get_class($entity);
        }
        $name = $resourceStreamInfo->getName();
        return $entity->$name;
    }

    /**
     * @param $entity
     * @param  ResourceType            $resourceType
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @param  IOperationContext       $operationContext
     * @param  null                    $relativeUri
     * @return string
     */
    public function getDefaultStreamEditMediaUri(
        $entity,
        ResourceType $resourceType,
        ResourceStreamInfo $resourceStreamInfo = null,
        IOperationContext $operationContext,
        $relativeUri = null
    ) {
        if (null == $resourceStreamInfo) {
            return $relativeUri . '/$value';
        }
        return $relativeUri . '/' . $resourceStreamInfo->getName();
    }

    /**
     * @param  object                  $entity
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @param  IOperationContext       $operationContext
     * @return string
     */
    public function getStreamContentType2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo = null,
        IOperationContext $operationContext
    ) {
        if (null == $resourceStreamInfo) {
            return '*/*';
        }
        return 'application/octet-stream';
    }

    /**
     * @param  object                  $entity
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @param  IOperationContext       $operationContext
     * @return string
     */
    public function getStreamETag2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo = null,
        IOperationContext $operationContext
    ) {
        if (null == $resourceStreamInfo) {
            return spl_object_hash($entity);
        }
        $name = $resourceStreamInfo->getName();

        return sha1($entity->$name);
    }

    /**
     * @param  object                  $entity
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @param  IOperationContext       $operationContext
     * @param  null                    $relativeUri
     * @return string
     */
    public function getReadStreamUri2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo = null,
        IOperationContext $operationContext,
        $relativeUri = null
    ) {
        if (null == $resourceStreamInfo) {
            return $relativeUri . '/$value';
        }
        return $relativeUri . '/' . $resourceStreamInfo->getName();
        //let library creates default media url.
    }
}
