<?php
/** 
 * Common class used by the library to handle exception at any point.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
namespace ODataProducer\Common;
use ODataProducer\Writers\Json\JsonODataWriter;

use ODataProducer\Writers\Atom\AtomODataWriter;
use ODataProducer\DataService;
use ODataProducer\HttpProcessUtility;
/** 
 * Exception handler class
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ErrorHandler
{
    /**
     * Common function to handle exceptions in the data service.
     * 
     * @param Exception   $exception    exception occured
     * @param DataService &$dataService dataservice
     * 
     * @return nothing
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
?>