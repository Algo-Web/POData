<?php
/** 
 * Response writer either in atom or json.
 *
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers
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
namespace ODataProducer\Writers;
use ODataProducer\Common\HttpStatus;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Version;
use ODataProducer\ResponseFormat;
use ODataProducer\DataService;
use ODataProducer\UriProcessor\RequestDescription;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use ODataProducer\Writers\Metadata\MetadataWriter;
use ODataProducer\Writers\Common\ODataWriter;
/** 
 * Response writer class
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ResponseWriter
{
    /**
     * Write in specific format 
     * 
     * @param DataService        &$dataService        Dataservice
     * @param RequestDescription &$requestDescription Request description object
     * @param Object             &$odataModelInstance OData model instance
     * @param String             $responseContentType Content type of the response
     * @param String             $responseFormat      Output format
     * 
     * @return nothing
     */
    public static function write(DataService &$dataService, 
        RequestDescription &$requestDescription, 
        &$odataModelInstance, 
        $responseContentType, 
        $responseFormat
    ) {
        $responseBody = null;
        $dataServiceVersion = $requestDescription->getResponseDataServiceVersion();
        if ($responseFormat == ResponseFormat::METADATA_DOCUMENT) {
            // /$metadata
            $writer = new MetadataWriter($dataService->getMetadataQueryProviderWrapper());
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
                $eTag = $dataService->getStreamProvider()->getStreamETag(
                    $requestDescription->getTargetResult(),  
                    $requestDescription->getResourceStreamInfo()
                );
                $dataService->getHost()->setResponseETag($eTag);
                $responseBody = $dataService->getStreamProvider()->getReadStream(
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
            $absoluteServiceUri = $dataService->getHost()->getAbsoluteServiceUri()->getUrlAsString();
            if ($responseFormat == ResponseFormat::ATOM 
                || $responseFormat == ResponseFormat::PLAIN_XML
            ) {
                if (is_null($odataModelInstance)) {
                    $writer = new \ODataProducer\Writers\ServiceDocument\Atom\ServiceDocumentWriter(
                        $dataService->getMetadataQueryProviderWrapper(), 
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
                    $writer = new \ODataProducer\Writers\ServiceDocument\Json\ServiceDocumentWriter(
                        $dataService->getMetadataQueryProviderWrapper(), 
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

        $dataService->getHost()->setResponseStatusCode(HttpStatus::CODE_OK);
        $dataService->getHost()->setResponseContentType($responseContentType);
        $dataService->getHost()->setResponseVersion(
            $dataServiceVersion->toString() .';'
        );
        $dataService->getHost()->setResponseCacheControl(ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE);
        $dataService->getHost()->getWebOperationContext()->outgoingResponse()->setStream($responseBody);
    }    
}
?>