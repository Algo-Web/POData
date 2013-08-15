<?php

namespace ODataProducer\Common;


use ODataProducer\Writers\Json\JsonODataWriter;

use ODataProducer\Writers\Atom\AtomODataWriter;
use ODataProducer\DataService;
use ODataProducer\HttpProcessUtility;

/**
 * Class ErrorHandler
 * @package ODataProducer\Common
 */
class ErrorHandler
{
    /**
     * Common function to handle exceptions in the data service.
     * 
     * @param Exception   $exception    exception occured
     * @param DataService &$dataService dataservice
     * 
     * @return void
     */
    public static function handleException($exception, DataService &$dataService)
    {
        $acceptTypesText = $dataService->getHost()->getRequestAccept();
        $responseContentType = null;
        try {
            $responseContentType = HttpProcessUtility::selectMimeType(
                $acceptTypesText, 
                array (ODataConstants::MIME_APPLICATION_XML, 
                    ODataConstants::MIME_APPLICATION_JSON
                )
            );
        } catch (HttpHeaderFailure $exception) {
            $exception = new ODataException(
                $exception->getMessage(), 
                $exception->getStatusCode()
            );
        } catch (\Exception $exception) {
            // Never come here
        }

        if (is_null($responseContentType)) {
            $responseContentType = ODataConstants::MIME_APPLICATION_XML;
        }

        if (!($exception instanceof ODataException)) {
            $exception = new ODataException($exception->getMessage(), HttpStatus::CODE_INTERNAL_SERVER_ERROR);
        }

        $dataService->getHost()->setResponseVersion(ODataConstants::DATASERVICEVERSION_1_DOT_0 . ';');

        // At this point all kind of exceptions will be converted 
        //to 'ODataException' 
        if ($exception->getStatusCode() == HttpStatus::CODE_NOT_MODIFIED) {
            $dataService->getHost()->setResponseStatusCode(HttpStatus::CODE_NOT_MODIFIED);
        } else {
            $dataService->getHost()->setResponseStatusCode($exception->getStatusCode());
            $dataService->getHost()->setResponseContentType($responseContentType);
            $responseBody = null;
            if (strcasecmp($responseContentType, ODataConstants::MIME_APPLICATION_XML) == 0) {
                $responseBody = AtomODataWriter::serializeException($exception, true);
            } else {
                $responseBody = JsonODataWriter::serializeException($exception, true);
            }

            $dataService->getHost()->getWebOperationContext()->outgoingResponse()->setStream($responseBody);
        }
    }
}