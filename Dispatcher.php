<?php
/**
 * Defines the dispatcher class
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
use ODataProducer\Common\Messages;
use ODataProducer\Common\HttpStatus;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\ODataException;
use ODataProducer\OperationContext\DataServiceHost;
use ODataProducer\Common\ServiceConfig;
use ODataProducer\OperationContext\Web\WebOperationContext;
use ODataProducer\OperationContext\Web\IncomingRequest;
use ODataProducer\OperationContext\Web\OutgoingResponse;
use ODataProducer\HttpOutput;
/**
 * Class which responsible to delegate the request processing to handleRequest and 
 * also  responsible to send the response to the client
 * 
 * @category  ODataProducer
 * @package   ODataProducer
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class Dispatcher
{
    /**
     * Reference to the instance of underlying DataServiceHost
     * 
     * @var DataServiceHost
     */
    private $_dataServiceHost;
  
    /**
     * Array holds details of current requested service.
     * 
     * @var ServiceConfig
     */
    private $_serviceInfo;

    /**
     * This function is initializes the properties of Dispatcher class.
     * 
     * @return Object of WebOperationContext class
     */
    function __construct()
    {        
        try {
            $this->_dataServiceHost = new DataServiceHost();
        } catch(\Exception $exception) {
            self::_handleException(
                $exception->getMessage(), 
                $exception->getStatusCode()
            );
        }
        
        try {
            ServiceConfig::validateAndGetsServiceInfo(
                $this->_getServiceNameFromRequestUri(), 
                $this->_serviceInfo
            );
            $this->_dataServiceHost->setAbsoluteServiceUri(
                $this->_serviceInfo['SERVICE_BASEURL']
            );
        } catch(ODataException $exception) {
            self::_handleException(
                $exception->getMessage(), 
                $exception->getStatusCode()
            );
        }   
    }

    /**
     * Gets reference to the data service host.
     * 
     * @return DataServiceHost
     */
    public function getHost()
    {
        return $this->_dataServiceHost;
    }

    /**
     * Gets the service name from the request uri.
     * 
     * @return string
     */
    private function _getServiceNameFromRequestUri()
    {
        $url = $this->_dataServiceHost->getAbsoluteRequestUri();
        $segments = $url->getSegments();
        for ($i = count($segments) - 1; $i >= 0; $i--) {
            if (stripos($segments[$i], '.svc') !== false) {
                return $segments[$i];
            }
        }

        return null;
    }
    
    /**
     * This function perform the following steps:
     * 1) Resolve and validate the service
     * 2) Creates the instance of requested service
     * 3) Calls the handleRequest() to process the request
     * 4) Calls the handleresponse() to sendback the o/p to the client
     *
     * @return void
     */
    public function dispatch()
    {
        $dataService = null;
        include_once $this->_serviceInfo['SERVICE_PATH'];
        try {
            $reflectionClass = new \ReflectionClass($this->_serviceInfo['SERVICE_CLASS']);
            $dataService = $reflectionClass->newInstance();
        } catch(\Exception $exception) {
            $this->_handleException(
                $exception->getMessage(), HttpStatus::CODE_INTERNAL_SERVER_ERROR
            );
        }

        $interfaces = class_implements($dataService);
        if (array_key_exists('ODataProducer\IDataService', $interfaces)) {
            $dataService->setHost($this->_dataServiceHost);
            if (array_key_exists('ODataProducer\IRequestHandler', $interfaces)) {
                // DataService::handleRequest will never throw an error
                // All exception that can occur while parsing the request and
                // serializing the result will be handled by 
                // DataService::handleRequest
                $dataService->handleRequest();
            } else {
                $this->_handleException(
                    Messages::dispatcherServiceClassShouldImplementIRequestHandler(), 
                    HttpStatus::CODE_INTERNAL_SERVER_ERROR
                );
            }
        } else {
            $this->_handleException(
                Messages::dispatcherServiceClassShouldImplementIDataService(), 
                HttpStatus::CODE_INTERNAL_SERVER_ERROR
            );
        }

        $this->_writeResponse(
            $dataService->getHost()->getWebOperationContext()->outgoingResponse()
        );
    }

    /**
     * Handles exception occured in dispatcher.
     * 
     * @param string $message    The error message.
     * @param string $statusCode The status code.
     * 
     * @return void
     */
    private static function _handleException($message, $statusCode)
    {
        header(
            ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE . 
            ':' . 
            ODataConstants::MIME_TEXTPLAIN
        );
        $statusDescription = HttpStatus::getStatusDescription($statusCode);
        if (!is_null($statusDescription)) {
            $statusDescription = ' ' . $statusDescription;
        }

        header(
            ODataConstants::HTTPRESPONSE_HEADER_STATUS . 
            ':' . 
            $statusCode . $statusDescription
        );
        echo $message;
        exit;
    }

    /**
     * Write the response (header and response body).
     * 
     * @param OutgoingResponse &$outGoingResponse Headers and streams to output.
     * 
     * @return void
     */
    private function _writeResponse(OutgoingResponse &$outGoingResponse)
    {
        foreach ($outGoingResponse->getHeaders() as $headerName => $headerValue) {
            if (!is_null($headerValue)) {
                header($headerName . ':' . $headerValue);
            }
        }

        echo $outGoingResponse->getStream();
    }
}
?>