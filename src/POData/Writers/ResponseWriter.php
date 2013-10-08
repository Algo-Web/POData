<?php

namespace POData\Writers;

use POData\Common\HttpStatus;
use POData\Common\ODataConstants;
use POData\Common\MimeTypes;
use POData\IService;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\Writers\Metadata\MetadataWriter;




/**
 * Class ResponseWriter
 * @package POData\Writers
 */
class ResponseWriter
{
    /**
     * Write in specific format 
     * 
     * @param IService $service
     * @param RequestDescription $request the OData request
     * @param mixed $entityModel OData model instance
     * @param String $responseContentType Content type of the response
     * 
     */
    public static function write(
	    IService $service,
        RequestDescription $request,
        $entityModel,
        $responseContentType
    ) {
        $responseBody = null;
        $dataServiceVersion = $request->getResponseVersion();
	    $targetKind = $request->getTargetKind();

        if ($targetKind == TargetKind::METADATA()) {
            // /$metadata
            $writer = new MetadataWriter($service->getProvidersWrapper());
            $responseBody = $writer->writeMetadata();            
            $dataServiceVersion = $writer->getDataServiceVersion();
        } else if ($targetKind == TargetKind::PRIMITIVE_VALUE() && $responseContentType != MimeTypes::MIME_APPLICATION_OCTETSTREAM) {
	        //This second part is to exclude binary properties
            // /Customer('ALFKI')/CompanyName/$value
            // /Customers/$count
            $responseBody = utf8_encode($request->getTargetResult());
        } else if ($responseContentType == MimeTypes::MIME_APPLICATION_OCTETSTREAM || $targetKind == TargetKind::MEDIA_RESOURCE()) {
            // Binary property or media resource
            if ($request->getTargetKind() == TargetKind::MEDIA_RESOURCE()) {
	            $result = $request->getTargetResult();
	            $streamInfo =  $request->getResourceStreamInfo();
	            $provider = $service->getStreamProviderWrapper();
                $eTag = $provider->getStreamETag( $result, $streamInfo );
                $service->getHost()->setResponseETag($eTag);
                $responseBody = $provider->getReadStream( $result, $streamInfo );
            } else {
                $responseBody = $request->getTargetResult();
            }

            if (is_null($responseContentType)) {
                $responseContentType = MimeTypes::MIME_APPLICATION_OCTETSTREAM;
            }
        } else {
            $writer = $service->getODataWriterFactory()->getWriter($service, $request, $responseContentType);

            if (is_null($entityModel)) {  //TODO: this seems like a weird way to know that the request is for a service document..i'd think we know this some other way
                $responseBody = $writer->writeServiceDocument($service->getProvidersWrapper())->getOutput();
            }
            else {
                $responseBody = $writer->write($entityModel)->getOutput();
            }
        }

        $service->getHost()->setResponseStatusCode(HttpStatus::CODE_OK);
        $service->getHost()->setResponseContentType($responseContentType);
        $service->getHost()->setResponseVersion($dataServiceVersion->toString() .';');
        $service->getHost()->setResponseCacheControl(ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE);
        $service->getHost()->getOperationContext()->outgoingResponse()->setStream($responseBody);
    }    
}