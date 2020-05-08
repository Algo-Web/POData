<?php

declare(strict_types=1);

namespace POData\Providers\Stream;

use POData\OperationContext\IOperationContext;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;

/**
 * Class SimpleStreamProvider.
 * @package POData\Providers\Stream
 */
class SimpleStreamProvider implements IStreamProvider2
{
    /**
     * @param  object                  $entity
     * @param  string                  $eTag
     * @param  bool                    $checkETagForEquality
     * @param  IOperationContext       $operationContext
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @return string
     */
    public function getReadStream2(
        $entity,
        string $eTag,
        bool $checkETagForEquality,
        IOperationContext $operationContext,
        ResourceStreamInfo $resourceStreamInfo = null
    ) {
        if (null == $resourceStreamInfo) {
            return 'stream for ' . get_class($entity);
        }
        $name = $resourceStreamInfo->getName();
        return $entity->{$name};
    }

    /**
     * @param                          $entity
     * @param  ResourceType            $resourceType
     * @param  IOperationContext       $operationContext
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @param  string|null             $relativeUri
     * @return string
     */
    public function getDefaultStreamEditMediaUri(
        $entity,
        ResourceType $resourceType,
        IOperationContext $operationContext,
        ResourceStreamInfo $resourceStreamInfo = null,
        string $relativeUri = null
    ) {
        if (null == $resourceStreamInfo) {
            return $relativeUri . '/$value';
        }
        return $relativeUri . '/' . $resourceStreamInfo->getName();
    }

    /**
     * @param  object $entity
     * @param  IOperationContext $operationContext
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @return string
     */
    public function getStreamContentType2(
        $entity,
        IOperationContext $operationContext,
        ResourceStreamInfo $resourceStreamInfo = null
    ):string {
        if (null == $resourceStreamInfo) {
            return '*/*';
        }
        return 'application/octet-stream';
    }

    /**
     * @param  object $entity
     * @param  IOperationContext $operationContext
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @return string
     */
    public function getStreamETag2(
        $entity,
        IOperationContext $operationContext,
        ResourceStreamInfo $resourceStreamInfo = null
    ): string {
        if (null == $resourceStreamInfo) {
            return spl_object_hash($entity);
        }
        $name = $resourceStreamInfo->getName();
        $raw  = $entity->{$name} ?? '';

        return sha1($raw);
    }

    /**
     * @param  object                  $entity
     * @param  ResourceStreamInfo|null $resourceStreamInfo
     * @param  IOperationContext       $operationContext
     * @param  string|null             $relativeUri
     * @return string
     */
    public function getReadStreamUri2(
        $entity,
        IOperationContext $operationContext,
        ResourceStreamInfo $resourceStreamInfo = null,
        string $relativeUri = null
    ):string {
        if (null == $resourceStreamInfo) {
            return $relativeUri . '/$value';
        }
        return $relativeUri . '/' . $resourceStreamInfo->getName();
        //let library creates default media url.
    }
}
