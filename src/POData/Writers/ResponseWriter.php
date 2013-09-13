<?php

namespace POData\Writers;

use POData\Common\HttpStatus;
use POData\Common\ODataConstants;
use POData\Common\Version;
use POData\ResponseFormat;
use POData\IService;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use POData\Writers\Metadata\MetadataWriter;
use POData\Writers\Common\ODataWriter;


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
     * @param Object             &$odataModelInstance OData model instance
     * @param String             $responseContentType Content type of the response
     * @param String             $responseFormat      Output format
     * 
     * @return void
     */
    public static function write(
	    IService $service,
        RequestDescription $requestDescription,
        &$odataModelInstance, 
        $responseContentType, 
        $responseFormat
    ) {
        $responseBody = null;
        $dataServiceVersion = $requestDescription->getResponseDataServiceVersion();
        if ($responseFormat == ResponseFormat::METADATA_DOCUMENT) {
            // /$metadata
            $writer = new MetadataWriter($service->getMetadataQueryProviderWrapper());
            $responseBody = $writer->writeMetadata();            
            $dataServiceVersion = $writer->getDataServiceVersion();
        } else if ($responseFormat == ResponseFormat::TEXT) {
            // /Customer('ALFKI')/CompanyName/$value
            // /Customers/$count
            $responseBody = utf8_encode($requestDescription->getTargetResult());
        } else if ($responseFormat == ResponseFormat::BINARY) {
            // Binary property or media resource
            $targetKind = $requestDescription->getTargetKind();
            if ($targetKind == RequestTargetKind::MEDIA_RESOURCE) {
                $eTag = $service->getStreamProvider()->getStreamETag(
                    $requestDescription->getTargetResult(),  
                    $requestDescription->getResourceStreamInfo()
                );
                $service->getHost()->setResponseETag($eTag);
                $responseBody = $service->getStreamProvider()->getReadStream(
                    $requestDescription->getTargetResult(), 
                    $requestDescription->getResourceStreamInfo()
                );
            } else {
                $responseBody = $requestDescription->getTargetResult(); 
            }

            if (is_null($responseContentType)) {
                $responseContentType = ODataConstants::MIME_APPLICATION_OCTETSTREAM;
            }
            
        } else {
            $writer = null;
            $absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
            if ($responseFormat == ResponseFormat::ATOM 
                || $responseFormat == ResponseFormat::PLAIN_XML
            ) {
                if (is_null($odataModelInstance)) {
                    $writer = new \POData\Writers\ServiceDocument\Atom\ServiceDocumentWriter(
                        $service->getMetadataQueryProviderWrapper(),
                        $absoluteServiceUri
                    );
                } else {
                    $isPostV1 = ($requestDescription->getResponseDataServiceVersion()->compare(new Version(1, 0)) == 1);
                    $writer = new ODataWriter(
                        $absoluteServiceUri, 
                        $isPostV1, 
                        'atom'
                    );
                }
            } else if ($responseFormat == ResponseFormat::JSON) {
                if (is_null($odataModelInstance)) {
                    $writer = new \POData\Writers\ServiceDocument\Json\ServiceDocumentWriter(
                        $service->getMetadataQueryProviderWrapper(),
                        $absoluteServiceUri
                    );
                } else {
                    $isPostV1 = ($requestDescription->getResponseDataServiceVersion()->compare(new Version(1, 0)) == 1);
                    $writer = new ODataWriter(
                        $absoluteServiceUri, 
                        $isPostV1, 
                        'json'
                    );
                }
            }           
            
            $responseBody = $writer->writeRequest($odataModelInstance);
        }

        $service->getHost()->setResponseStatusCode(HttpStatus::CODE_OK);
        $service->getHost()->setResponseContentType($responseContentType);
        $service->getHost()->setResponseVersion(
            $dataServiceVersion->toString() .';'
        );
        $service->getHost()->setResponseCacheControl(ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE);
        $service->getHost()->getWebOperationContext()->outgoingResponse()->setStream($responseBody);
    }    
}