<?php

namespace POData\Writers;

use POData\Common\HttpStatus;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\IService;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\Writers\Metadata\MetadataWriter;

/**
 * Class ResponseWriter.
 */
class ResponseWriter
{
    /**
     * Write in specific format.
     *
     * @param IService           $service
     * @param RequestDescription $request             the OData request
     * @param mixed              $entityModel         OData model instance
     * @param string             $responseContentType Content type of the response
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

        if (TargetKind::METADATA() == $targetKind) {
            // /$metadata
            $responseBody = $service->getProvidersWrapper()->GetMetadataXML();
        } elseif (TargetKind::PRIMITIVE_VALUE() == $targetKind
                  && $responseContentType != MimeTypes::MIME_APPLICATION_OCTETSTREAM) {
            //This second part is to exclude binary properties
            // /Customer('ALFKI')/CompanyName/$value
            // /Customers/$count
            $responseBody = utf8_encode($request->getTargetResult());
        } elseif (MimeTypes::MIME_APPLICATION_OCTETSTREAM == $responseContentType
                  || TargetKind::MEDIA_RESOURCE() == $targetKind
        ) {
            // Binary property or media resource
            if (TargetKind::MEDIA_RESOURCE() == $request->getTargetKind()) {
                $result = $request->getTargetResult();
                $streamInfo = $request->getResourceStreamInfo();
                $provider = $service->getStreamProviderWrapper();
                $eTag = $provider->getStreamETag($result, $streamInfo);
                $service->getHost()->setResponseETag($eTag);
                $responseBody = $provider->getReadStream($result, $streamInfo);
            } else {
                $responseBody = $request->getTargetResult();
            }

            if (is_null($responseContentType)) {
                $responseContentType = MimeTypes::MIME_APPLICATION_OCTETSTREAM;
            }
        } else {
            $responsePieces = explode(';', $responseContentType);
            $responseContentType = $responsePieces[0];

            $writer = $service->getODataWriterRegistry()->getWriter(
                $request->getResponseVersion(),
                $responseContentType
            );
            //TODO: move ot Messages
            if (is_null($writer)) {
                throw new \Exception('No writer can handle the request.');
            }

            //TODO: this seems like a weird way to know that the request is for a service document..
            //i'd think we know this some other way
            if (is_null($entityModel)) {
                $responseBody = $writer->writeServiceDocument($service->getProvidersWrapper())->getOutput();
            } else {
                $responseBody = $writer->write($entityModel)->getOutput();
            }
        }

        $service->getHost()->setResponseStatusCode(HttpStatus::CODE_OK);
        $service->getHost()->setResponseContentType($responseContentType);
        $service->getHost()->setResponseVersion($dataServiceVersion->toString() . ';');
        $service->getHost()->setResponseCacheControl(ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE);
        $service->getHost()->getOperationContext()->outgoingResponse()->setStream($responseBody);
    }
}
