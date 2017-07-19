<?php

namespace POData\Writers;

use POData\Common\HttpStatus;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\IService;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;

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
     *
     * @throws \Exception
     */
    public static function write(
        IService $service,
        RequestDescription $request,
        $entityModel,
        $responseContentType
    ) {
        $targetKind = $request->getTargetKind();

        if (TargetKind::METADATA() == $targetKind) {
            // /$metadata
            $responseBody = $service->getProvidersWrapper()->getMetadataXML();
        } elseif (TargetKind::SERVICE_DIRECTORY() == $targetKind) {
            $writer = $service->getODataWriterRegistry()->getWriter(
                $request->getResponseVersion(),
                $responseContentType
            );
            if (null === $writer) {
                throw new \Exception('No writer can handle the request.');
            }
            $responseBody = $writer->writeServiceDocument($service->getProvidersWrapper())->getOutput();
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

            if (null === $responseContentType) {
                $responseContentType = MimeTypes::MIME_APPLICATION_OCTETSTREAM;
            }
        } else {
            $responsePieces = explode(';', $responseContentType);
            $responseContentType = $responsePieces[0];

            $writer = $service->getODataWriterRegistry()->getWriter(
                $request->getResponseVersion(),
                $responseContentType
            );
            //TODO: move to Messages
            if (null === $writer) {
                throw new \Exception('No writer can handle the request.');
            }
            assert(null !== $entityModel);

            $responseBody = $writer->write($entityModel)->getOutput();
        }
        $service->getHost()->setResponseStatusCode(HttpStatus::CODE_OK);
        $service->getHost()->setResponseContentType($responseContentType);
        // Hack: this needs to be sorted out in the future as we hookup other versions.
        $service->getHost()->setResponseVersion('3.0;');
        $service->getHost()->setResponseCacheControl(ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE);
        $service->getHost()->getOperationContext()->outgoingResponse()->setStream($responseBody);
    }
}
