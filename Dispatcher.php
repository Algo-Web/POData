<?php

use POData\Common\Messages;
use POData\Common\HttpStatus;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\OperationContext\DataServiceHost;
use POData\Common\ServiceConfig;
use POData\OperationContext\Web\WebOperationContext;
use POData\OperationContext\Web\IncomingRequest;
use POData\OperationContext\Web\OutgoingResponse;
use POData\HttpOutput;


/**
 * Class Dispatcher
 *
 * Class which responsible to delegate the request processing to handleRequest and
 * also  responsible to send the response to the client
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
        if (array_key_exists('POData\IDataService', $interfaces)) {
            $dataService->setHost($this->_dataServiceHost);
            if (array_key_exists('POData\IRequestHandler', $interfaces)) {
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