<?php

declare(strict_types=1);

namespace POData\Writers;

use Exception;
use POData\Common\HttpStatus;
use POData\Common\Messages;
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
     * @throws Exception
     */
    public static function write(
        IService $service,
        RequestDescription $request,
        $entityModel,
        $responseContentType
    ) {
        $targetKind   = $request->getTargetKind();
        $responseBody = null;

        if (TargetKind::METADATA() == $targetKind) {
            // /$metadata
            $responseBody = $service->getProvidersWrapper()->getMetadataXML();
        } elseif (TargetKind::SERVICE_DIRECTORY() == $targetKind) {
            $writer = $service->getODataWriterRegistry()->getWriter(
                $request->getResponseVersion(),
                $responseContentType
            );
            if (null === $writer) {
                throw new Exception(Messages::noWriterToHandleRequest());
            }
            $responseBody = $writer->writeServiceDocument($service->getProvidersWrapper())->getOutput();
        } elseif (TargetKind::PRIMITIVE_VALUE() == $targetKind
            && $responseContentType != MimeTypes::MIME_APPLICATION_OCTETSTREAM) {
            //This second part is to exclude binary properties
            // /Customer('ALFKI')/CompanyName/$value
            // /Customers/$count
            $responseBody = mb_convert_encoding($request->getTargetResult(), 'UTF-8');
        } elseif (MimeTypes::MIME_APPLICATION_OCTETSTREAM == $responseContentType
            || TargetKind::MEDIA_RESOURCE() == $targetKind
        ) {
            // Binary property or media resource
            if (TargetKind::MEDIA_RESOURCE() == $request->getTargetKind()) {
                $result     = $request->getTargetResult();
                $streamInfo = $request->getResourceStreamInfo();
                $provider   = $service->getStreamProviderWrapper();
                $eTag       = $provider->getStreamETag($result, $streamInfo);
                $service->getHost()->setResponseETag($eTag);
                $responseBody = $provider->getReadStream($result, $streamInfo);
            } else {
                $responseBody = $request->getTargetResult();
            }

            if (null === $responseContentType) {
                $responseContentType = MimeTypes::MIME_APPLICATION_OCTETSTREAM;
            }
        } else {
            $responsePieces      = explode(';', $responseContentType);
            $responsePieces      = array_filter($responsePieces, function ($line) {
                return false === stripos($line, 'charset');
            });
            $responseContentType = implode(';', $responsePieces);

            $writer = $service->getODataWriterRegistry()->getWriter(
                $request->getResponseVersion(),
                $responseContentType
            );
            if (null === $writer) {
                throw new Exception(Messages::noWriterToHandleRequest());
            }
            $segments = $request->getSegments();
            $numSeg   = count($segments);
            if (1 < $numSeg && '$links' == $segments[$numSeg - 2]->getIdentifier()) {
                if (null !== $entityModel) {
                    throw new Exception(Messages::modelPayloadOnLinkModification());
                }
            } else {
                assert(null !== $entityModel, 'EntityModel must not be null when not manipulating links');
            }

            $responseBody = $writer->write($entityModel)->getOutput();
        }
        /** @var array|null $headers */
        $headers = $service->getHost()->getResponseHeaders();
        /** @var int $rawCode */
        $rawCode = (null !== $headers) && isset($headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE]) ?
            $headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE] : HttpStatus::CODE_OK;
        $service->getHost()->setResponseStatusCode($rawCode);
        $service->getHost()->setResponseContentType($responseContentType);
        // Hack: this needs to be sorted out in the future as we hookup other versions.
        $service->getHost()->setResponseVersion('3.0;');
        $service->getHost()->setResponseCacheControl(ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE);
        $service->getHost()->getOperationContext()->outgoingResponse()->setStream($responseBody);
    }
}
