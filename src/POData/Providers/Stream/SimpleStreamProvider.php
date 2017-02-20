<?php

namespace POData\Providers\Stream;

use POData\Providers\Metadata\ResourceStreamInfo;
use POData\OperationContext\IOperationContext;
use POData\Common\ODataException;

class SimpleStreamProvider implements IStreamProvider2
{
    public function getReadStream(
        $entity,
        $eTag,
        $checkETagForEquality,
        $operationContext
    ) {
         return null; // TODO: find default stream and return.
      }
    public function getStreamContentType($entity, $operationContext)
    {
        return 'application/octet-stream';
    }
     public function getStreamETag($entity, $operationContext)
    {
        return null; // TODO: find default stream and return.
    }

    public function getReadStreamUri($entity, $operationContext)
    {
        //let library creates default media url.
        return null;
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
        return null;
    }
}
