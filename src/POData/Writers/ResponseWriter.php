<?php

namespace POData\Writers;

use POData\Common\HttpStatus;
use POData\Common\ODataConstants;
use POData\ResponseFormat;
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
     * @param IService        $service
     * @param RequestDescription $request Request description object
     * @param mixed $entityModel OData model instance
     * @param String             $responseContentType Content type of the response
     * @param ResponseFormat             $responseFormat      Output format
     * 
     * @return void
     */
    public static function write(
	    IService $service,
        RequestDescription $request,
        $entityModel,
        $responseContentType, 
        ResponseFormat $responseFormat
    ) {
        $responseBody = null;
        $dataServiceVersion = $request->getResponseVersion();

        if ($responseFormat == ResponseFormat::METADATA_DOCUMENT()) {
            // /$metadata
            $writer = new MetadataWriter($service->getProvidersWrapper());
            $responseBody = $writer->writeMetadata();            
            $dataServiceVersion = $writer->getDataServiceVersion();
        } else if ($responseFormat == ResponseFormat::TEXT()) {
            // /Customer('ALFKI')/CompanyName/$value
            // /Customers/$count
            $responseBody = utf8_encode($request->getTargetResult());
        } else if ($responseFormat == ResponseFormat::BINARY()) {
            // Binary property or media resource
            if ($request->getTargetKind() == TargetKind::MEDIA_RESOURCE) {
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
                $responseContentType = ODataConstants::MIME_APPLICATION_OCTETSTREAM;
            }
        } else {
            $writer = $service->getODataWriterFactory()->getWriter($service, $request, $responseFormat);

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