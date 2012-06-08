<?php
/** 
 * Response writer either in atom or json.
 *
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
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
 * @category  ODataProducer
 * @package   ODataProducer_Writers
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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