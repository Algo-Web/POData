<?php
require_once 'ODataProducer\Providers\Stream\IDataServiceStreamProvider2.php';
require_once 'ODataProducer\Common\ODataException.php';
require_once 'NorthWindMetadata.php';
use ODataProducer\Providers\Metadata\ResourceStreamInfo;
use ODataProducer\Providers\Stream\IDataServiceStreamProvider2;
use ODataProducer\Common\ODataException;

class NorthWindStreamProvider implements IDataServiceStreamProvider2
{
    //Begin IDataServiceStreamProvider methods implementation
     
    public function getReadStream($entity, $eTag, $checkETagForEquality, /*TODO WebOperationContext*/$operationContext)
    {
        if (!is_null($checkETagForEquality)) {
            throw new ODataException('This service does not support the ETag header for a media resource', 400);
        }

        if (!($entity instanceof Employee)) {
            throw new ODataException('Internal Server Error.', 500);
        }

        //echo getcwd(); exit;
        $filePath = 'D:\Projects\ODataPHPProducer Yash\Tests\Resources\NorthWind3\images\Employee_' . $entity->EmployeeID . '.jpg';
        if (file_exists($filePath)) {
            $handle = fopen($filePath, 'r');
            $stream = fread($handle, filesize($filePath));
            fclose($handle);
            return $stream;
        } else {
            throw new ODataException('The image file could not be found', 500);
        }
    }

    public function getStreamContentType($entity, /*TODO WebOperationContext*/$operationContext)
    {
        if (!($entity instanceof Employee)) {
            throw new ODataException('Internal Server Error.', 500);
        }

        return 'image/jpeg';
    }

    public function getStreamETag($entity, /*TODO WebOperationContext*/$operationContext)
    {
        //Here the code should check the file's (stream) last update time as etag
        return '"2/6/2011 11:08:32 PM"';        
    }

    public function getReadStreamUri($entity, /*TODO WebOperationContext*/ $operationContext)
    {
        //let library creates default media url.
        return null;
    }

    //End IDataServiceStreamProvider methods implementation

    //Begin IDataServiceStreamProvider2 methods implementation
        
    public function getReadStream2($entity, ResourceStreamInfo $resourceStreamInfo, $eTag, $checkETagForEquality, /*TODO WebOperationContext*/$operationContext)
    {
        if (!is_null($checkETagForEquality)) {
            throw new ODataException('This service does not support the ETag header for a media resource', 400);
        }

        if (!($entity instanceof Employee)) {
            throw new ODataException('Internal Server Error.', 500);
        }

        //echo getcwd(); exit;
        $filePath = 'D:\Projects\ODataPHPProducer Yash\Tests\Resources\NorthWind3\images\Employee_' . $entity->EmployeeID . '_' . $resourceStreamInfo->getName() . '.png';
        if (file_exists($filePath)) {
            $handle = fopen($filePath, 'r');
            $stream = fread($handle, filesize($filePath));
            fclose($handle);
            return $stream;
        } else {
            throw new ODataException('The image file could not be found', 500);
        }
    }

    public function getStreamContentType2($entity, ResourceStreamInfo $resourceStreamInfo, /*TODO WebOperationContext*/$operationContext)
    {
        if (!($entity instanceof Employee)) {
            throw new ODataException('Internal Server Error.', 500);
        }

        return 'image/png';
    }

    public function getStreamETag2($entity, ResourceStreamInfo $resourceStreamInfo, /*TODO WebOperationContext*/$operationContext)
    {
        return null;
    }

    public function getReadStreamUri2($entity, ResourceStreamInfo $resourceStreamInfo, /*TODO WebOperationContext*/ $operationContext)
    {
        return null;
    }
    
    //End IDataServiceStreamProvider2 methods implementation    
}
?>