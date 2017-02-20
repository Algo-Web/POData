<?php

namespace POData\Providers\Stream;

use POData\OperationContext\IOperationContext;
use POData\Providers\Metadata\ResourceStreamInfo;

class SimpleStreamProvider implements IStreamProvider2
{
    public function getReadStream(
        $entity,
        $eTag,
        $checkETagForEquality,
        IOperationContext $operationContext
    ) {
        // TODO: find default stream and return.
    }

    public function getStreamContentType($entity, IOperationContext $operationContext)
    {
        return 'application/octet-stream';
    }

    public function getStreamETag($entity, IOperationContext $operationContext)
    {
        // TODO: find default stream and return.
    }

    public function getReadStreamUri($entity, IOperationContext $operationContext)
    {
        //let library creates default media url.
    }

    public function getReadStream2($entity, ResourceStreamInfo $resourceStreamInfo, $eTag, $checkETagForEquality, IOperationContext $operationContext)
    {
        $name = $resourceStreamInfo->getName();

        return $entity->$name;
    }

    public function getStreamContentType2($entity, ResourceStreamInfo $resourceStreamInfo, IOperationContext $operationContext)
    {
        return 'application/octet-stream';
    }

    public function getStreamETag2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo,
        IOperationContext $operationContext
    ) {
        $name = $resourceStreamInfo->getName();

        return sha1($entity->$name);
    }

    public function getReadStreamUri2(
        $entity,
        ResourceStreamInfo $resourceStreamInfo,
        IOperationContext $operationContext
    ) {
        //let library creates default media url.
    }
}
