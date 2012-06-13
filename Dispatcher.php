<?php
/**
 * Defines the dispatcher class
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
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
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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