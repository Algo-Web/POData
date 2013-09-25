<?php

use POData\Common\Messages;
use POData\Common\HttpStatus;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\OperationContext\ServiceHost;
use POData\Common\ServiceConfig;
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
     * Reference to the instance of underlying ServiceHost
     * 
     * @var ServiceHost
     */
    private $_host;
  
    /**
     * Array holds details of current requested service.
     * 
     * @var ServiceConfig
     */
    private $_serviceInfo;


    function __construct()
    {        
        try {
            $this->_host = new ServiceHost();
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
            $this->_host->setAbsoluteServiceUri(
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
     * @return ServiceHost
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Gets the service name from the request uri.
     * 
     * @return string
     */
    private function _getServiceNameFromRequestUri()
    {
        $url = $this->_host->getAbsoluteRequestUri();
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
        $service = null;
        include_once $this->_serviceInfo['SERVICE_PATH'];
        try {
            $reflectionClass = new \ReflectionClass($this->_serviceInfo['SERVICE_CLASS']);
            $service = $reflectionClass->newInstance();
        } catch(\Exception $exception) {
            $this->_handleException(
                $exception->getMessage(), HttpStatus::CODE_INTERNAL_SERVER_ERROR
            );
        }

        $this->_writeResponse(
            $service->getHost()->getOperationContext()->outgoingResponse()
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