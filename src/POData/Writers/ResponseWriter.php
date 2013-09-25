<?php

namespace POData\Writers;

use POData\Common\HttpStatus;
use POData\Common\ODataConstants;
use POData\ResponseFormat;
use POData\IService;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
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
     * @param IService        $service
     * @param RequestDescription $requestDescription Request description object
     * @param mixed $entityModel OData model instance
     * @param String             $responseContentType Content type of the response
     * @param ResponseFormat             $responseFormat      Output format
     * 
     * @return void
     */
    public static function write(
	    IService $service,
        RequestDescription $requestDescription,
        $entityModel,
        $responseContentType, 
        ResponseFormat $responseFormat
    ) {
        $responseBody = null;
        $dataServiceVersion = $requestDescription->getResponseDataServiceVersion();

        if ($responseFormat == ResponseFormat::METADATA_DOCUMENT()) {
            // /$metadata
            $writer = new MetadataWriter($service->getMetadataQueryProviderWrapper());
            $responseBody = $writer->writeMetadata();            
            $dataServiceVersion = $writer->getDataServiceVersion();
        } else if ($responseFormat == ResponseFormat::TEXT()) {
            // /Customer('ALFKI')/CompanyName/$value
            // /Customers/$count
            $responseBody = utf8_encode($requestDescription->getTargetResult());
        } else if ($responseFormat == ResponseFormat::BINARY()) {
            // Binary property or media resource
            if ($requestDescription->getTargetKind() == RequestTargetKind::MEDIA_RESOURCE) {
	            $result = $requestDescription->getTargetResult();
	            $streamInfo =  $requestDescription->getResourceStreamInfo();
	            $provider = $service->getStreamProviderWrapper();
                $eTag = $provider->getStreamETag( $result, $streamInfo );
                $service->getHost()->setResponseETag($eTag);
                $responseBody = $provider->getReadStream( $result, $streamInfo );
            } else {
                $responseBody = $requestDescription->getTargetResult(); 
            }

            if (is_null($responseContentType)) {
                $responseContentType = ODataConstants::MIME_APPLICATION_OCTETSTREAM;
            }
            
        } else if (is_null($entityModel)) {  //TODO: this seems like a weird way to know that the request is for a service document..i'd think we know this some other way
			$writer = $service->getServiceDocumentWriterFactory()->getWriter($service, $responseFormat);
	        $responseBody = $writer->getOutput();
        }
	    else {
            $writer = $service->getODataWriterFactory()->getWriter($service, $requestDescription, $responseFormat);
		    $writer->write($entityModel);
            $responseBody = $writer->getOutput();
        }

        $service->getHost()->setResponseStatusCode(HttpStatus::CODE_OK);
        $service->getHost()->setResponseContentType($responseContentType);
        $service->getHost()->setResponseVersion(
            $dataServiceVersion->toString() .';'
        );
        $service->getHost()->setResponseCacheControl(ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE);
        $service->getHost()->getOperationContext()->outgoingResponse()->setStream($responseBody);
    }    
}