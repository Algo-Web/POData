<?php
/**
 * DataServiceHost class implements IDataServiceHost interface
 * It uses WebOperationContext to get/set all context related 
 * headers/stream info It also validates the each header value 
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_OperationContext
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
 namespace ODataProducer\OperationContext;
use ODataProducer\Common\Messages;
use ODataProducer\Common\HttpStatus;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Url;
use ODataProducer\Common\UrlFormatException;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\InvalidOperationException;
use ODataProducer\OperationContext\IDataServiceHost;
use ODataProducer\OperationContext\Web\WebOperationContext;
use ODataProducer\OperatingContext\Web\IncomingRequest;
use ODataProducer\OperatingContext\Web\OutgoingResponse;
/**
 * This class exposes the context related details using methods declared 
 * in the IDataServiceHost interface.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_OperationContext
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
Class DataServiceHost implements IDataServiceHost
{
    /**
     * Holds reference to the underlying operation context.
     * 
     * @var WebOperationContext
     */
    private $_operationContext;

    /**
     * The absolute request Uri as Url instance.
     * Note: This will not contain query string
     * 
     * @var Url
     */
    private $_absoluteRequestUri;

    /**
     * The absolute request Uri as string
     * Note: This will not contain query string
     * 
     * @var string
     */
    private $_absoluteRequestUriAsString = null;

    /**
     * The absolute service uri as Url instance.
     * Note: This value will be taken from configuration file
     * 
     * @var Url
     */
    private $_absoluteServiceUri;

    /**
     * The absolute service uri string.
     * Note: This value will be taken from configuration file
     * 
     * @var string
     */
    private $_absoluteServiceUriAsString = null;

    /**
     * array of query-string parameters
     * 
     * @var array(string, string)
     */
    private $_queryOptions;

    /**
     * Gets reference to the operation context.
     * 
     * @return WebOperationContext
     */
    public function &getWebOperationContext()
    {
        return $this->_operationContext;
    }

    /**
      * Creates new instance of DataServiceHost.
      */
    public function __construct()
    {
        // WebOperationContext::current will not throw any error
        $this->_operationContext = WebOperationContext::current();
        // getAbsoluteRequestUri can throw UrlFormatException 
        // let Dispatcher handle it
        $this->_absoluteRequestUri = $this->getAbsoluteRequestUri();
        $this->_absoluteServiceUri = null;
    }

    /**
      * Gets the absolute request Uri as Url instance
      * Note: This method will be called first time from constructor.
      * 
      * @throws Exception if AbsoluteRequestUri is not a valid URI 
      * 
      * @return Url
      */
    public function getAbsoluteRequestUri()
    {
        if (is_null($this->_absoluteRequestUri)) {
            $this->_absoluteRequestUriAsString 
                = $this->_operationContext->incomingRequest()->getRawUrl();
            // Validate the uri first
            try {
                $this->_absoluteRequestUri 
                    = new Url($this->_absoluteRequestUriAsString);
                unset($this->_absoluteRequestUri);
            } catch (UrlFormatException $exception) {
                ODataException::createBadRequestError($exception->getMessage());
            }

            $queryStartIndex = strpos($this->_absoluteRequestUriAsString, '?');
            if ($queryStartIndex !== false) {
                $this->_absoluteRequestUriAsString 
                    = substr(
                        $this->_absoluteRequestUriAsString,
                        0,
                        $queryStartIndex
                    );
            }

            // We need the absolute uri only not associated components 
            // (query, fragments etc..)
            $this->_absoluteRequestUri 
                = new Url($this->_absoluteRequestUriAsString);
            $this->_absoluteRequestUriAsString 
                = rtrim($this->_absoluteRequestUriAsString, '/');
        }

        return $this->_absoluteRequestUri;
    }

    /**
     * Gets the absolute request Uri as string
     * Note: This will not contain query string
     * 
     * @return string
     */
    public function getAbsoluteRequestUriAsString()
    {
        return $this->_absoluteRequestUriAsString;
    }


    /**
     * Sets the absolute service url from configuration file.
     * Note: This is an one time called internal method invoked 
     * from Dispathcer.
     *   
     * @param string $serviceUri The service url.
     * 
     * @return void
     * 
     * @throws ODataException If the base uri in the configuration is malformed.
     */
    public function setAbsoluteServiceUri($serviceUri)
    {
        if (is_null($this->_absoluteServiceUri)) {
            $isAbsoluteServiceUri = (strpos($serviceUri, 'http://') === 0)
                || (strpos($serviceUri, 'https://') === 0);
            try {
                $this->_absoluteServiceUri = new Url(
                    $serviceUri, 
                    $isAbsoluteServiceUri
                );
            } catch (UrlFormatException $exception) {
                ODataException::createInternalServerError(
                    Messages::dataServiceHostMalFormedBaseUriInConfig()
                );
            }

            $segments = $this->_absoluteServiceUri->getSegments();
            $lastSegment = $segments[count($segments) - 1];
            $endsWithSvc 
                = (substr_compare($lastSegment, '.svc', -strlen('.svc'), strlen('.svc')) === 0);
            if (!$endsWithSvc 
                || !is_null($this->_absoluteServiceUri->getQuery()) 
                || !is_null($this->_absoluteServiceUri->getFragment())
            ) {
                ODataException::createInternalServerError(
                    Messages::dataServiceHostMalFormedBaseUriInConfig(true)
                );
            }

            if (!$isAbsoluteServiceUri) {
                $requestUriSegments = $this->_absoluteRequestUri->getSegments();
                $i = count($requestUriSegments) - 1;
                // Find index of segment in the request uri that end with .svc
                // There will be always a .svc segment in the request uri otherwise
                // uri redirection will not happen.
                for (; $i >=0; $i--) {
                    $endsWithSvc = (substr_compare($requestUriSegments[$i], '.svc', -strlen('.svc'), strlen('.svc')) === 0);
                    if ($endsWithSvc) {
                        break;
                    }
                }
                
                $j = count($segments) - 1;
                $k = $i;
                if ($j > $i) {
                    ODataException::createBadRequestError(
                        Messages::dataServiceHostRequestUriIsNotBasedOnRelativeUriInConfig(
                            $this->_absoluteRequestUriAsString,
                            $serviceUri
                        )
                    );
                }

                while ($j >= 0 && ($requestUriSegments[$i] === $segments[$j])) {
                    $i--; $j--;
                }

                if ($j != -1) {
                    ODataException::createBadRequestError(
                        Messages::dataServiceHostRequestUriIsNotBasedOnRelativeUriInConfig(
                            $this->_absoluteRequestUriAsString,
                            $serviceUri
                        )
                    );
                }

                $serviceUri = $this->_absoluteRequestUri->getScheme() 
                    . '://' 
                    . $this->_absoluteRequestUri->getHost()
                    . ':' 
                    . $this->_absoluteRequestUri->getPort();

                for ($l = 0; $l <= $k; $l++) {
                    $serviceUri .= '/' . $requestUriSegments[$l];
                }
                
                $this->_absoluteServiceUri = new Url(
                    $serviceUri
                );
            }

            $this->_absoluteServiceUriAsString = $serviceUri;
        }
    }


    /**
     * Gets the absolute Uri to the service as Url instance.
     * Note: This will be the value taken from configuration file.
     * 
     * @return Url
     */
    public function getAbsoluteServiceUri()
    {
        return $this->_absoluteServiceUri;
    }

    /**
     * Gets the absolute Uri to the service as string
     * Note: This will be the value taken from configuration file.
     * 
     * @return string
     */
    public function getAbsoluteServiceUriAsString()
    {
        return $this->_absoluteServiceUriAsString;
    }


    /**
     * This method verfiy the client provided url query parameters and check whether
     * any of the odata query option specified more than once or check any of the 
     * non-odata query parameter start will $ symbol or check any of the odata query 
     * option specified with out value. If any of the above check fails throws 
     * ODataException, else set _queryOptions memeber variable
     * 
     * @return void
     * 
     * @throws ODataException
     */
    public function validateQueryParameters()
    {
        $queryOptions 
            = &$this->_operationContext->incomingRequest()->getQueryParameters();
        reset($queryOptions);
        // Check whether user specified $format query option
        while ($queryOption = current($queryOptions)) {
            $optionName =  key($queryOption);
            $optionValue = current($queryOption);
            if (!empty($optionName) 
                && $optionName === ODataConstants::HTTPQUERY_STRING_FORMAT
            ) {
                //$optionValue is the format
                if (!is_null($optionValue)) {
                    if ($optionValue === ODataConstants::FORMAT_ATOM) {
                        $this->setRequestAccept(
                            ODataConstants::MIME_APPLICATION_ATOM . ';q=1.0'
                        );
                    } else if ($optionValue === ODataConstants::FORMAT_JSON) {
                        $this->setRequestAccept(
                            ODataConstants::MIME_APPLICATION_JSON . ';q=1.0'
                        );
                    } else {
                        // Invalid format value, this error should not be 
                        // serialized in atom or json format since we don't 
                        // know which format client can understand, so error
                        // will be in plain text.
                        header(
                            ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE . 
                            ':' . 
                            ODataConstants::MIME_TEXTPLAIN
                        );

                        header(
                            ODataConstants::HTTPRESPONSE_HEADER_STATUS . 
                            ':' . HttpStatus::CODE_BAD_REQUEST . ' ' . 'Bad Request'
                        );

                        echo Messages::queryProcessorInvalidValueForFormat();
                        exit;
                    }
                }

                break;
            }

            next($queryOptions); 
        }

        reset($queryOptions);
        $namesFound = array();
        while ($queryOption = current($queryOptions)) {
            $optionName =  key($queryOption);
            $optionValue = current($queryOption);
            if (empty($optionName)) {
                if (!empty($optionValue)) {
                    if ($optionValue[0] == '$') {
                        if ($this->_isODataQueryOption($optionValue)) {
                            ODataException::createBadRequestError(
                                Messages::dataServiceHostODataQueryOptionFoundWithoutValue(
                                    $optionValue
                                )
                            );
                        } else {
                            ODataException::createBadRequestError(
                                Messages::dataServiceHostNonODataOptionBeginsWithSystemCharacter(
                                    $optionValue
                                )
                            );
                        }
                    }
                }
            } else {
                if ($optionName[0] == '$') {
                    if (!$this->_isODataQueryOption($optionName)) {
                        ODataException::createBadRequestError(
                            Messages::dataServiceHostNonODataOptionBeginsWithSystemCharacter(
                                $optionName
                            )
                        );
                    }

                    if (array_search($optionName, $namesFound) !== false) {
                        ODataException::createBadRequestError(
                            Messages::dataServiceHostODataQueryOptionCannotBeSpecifiedMoreThanOnce(
                                $optionName
                            )
                        );
                    }
                    
                    if (empty($optionValue)) {
                        ODataException::createBadRequestError(
                            Messages::dataServiceHostODataQueryOptionFoundWithoutValue(
                                $optionName
                            )
                        );
                    }

                    $namesFound[] = $optionName;
                }
            }
            
            next($queryOptions);
        }
        
        $this->_queryOptions = $queryOptions;
    }
    
    /**
     * Varifies the given url option is a valid odata query option.
     * 
     * @param string $optionName option to validate
     * 
     * @return boolean True if the given option is a valid odata option
     *                 False otherwise.
     */
    private function _isODataQueryOption($optionName)
    {
        return ($optionName === ODataConstants::HTTPQUERY_STRING_FILTER ||
                $optionName === ODataConstants::HTTPQUERY_STRING_EXPAND ||
                $optionName === ODataConstants::HTTPQUERY_STRING_INLINECOUNT ||
                $optionName === ODataConstants::HTTPQUERY_STRING_ORDERBY ||
                $optionName === ODataConstants::HTTPQUERY_STRING_SELECT ||
                $optionName === ODataConstants::HTTPQUERY_STRING_SKIP ||
                $optionName === ODataConstants::HTTPQUERY_STRING_SKIPTOKEN ||
                $optionName === ODataConstants::HTTPQUERY_STRING_TOP ||
                $optionName === ODataConstants::HTTPQUERY_STRING_FORMAT);
    }

    /**
     * Gets the value for the specified item in the request query string
     * Remark: This method assumes 'validateQueryParameters' has already been 
     * called.
     * 
     * @param string $item The query item to get the value of.
     * 
     * @return string/NULL The value for the specified item in the request 
     *                     query string NULL if the query option is absent.
     */
    public function getQueryStringItem($item)
    {
        foreach ($this->_queryOptions as $queryOption) {
            if (array_key_exists($item, $queryOption)) {
                return $queryOption[$item];
            }
        }

        return null;
    }

    /**
     * Gets the value for the DataServiceVersion header of the request.
     * 
     * @return string/NULL
     */
    public function getRequestVersion()
    {
        return $this->_operationContext
            ->incomingRequest()
            ->getRequestHeader(ODataConstants::ODATASERVICEVERSION);
    }

    /**
     * Gets the value of MaxDataServiceVersion header of the request
     * 
     * @return string/NULL
     */
    public function getRequestMaxVersion()
    {
        return $this->_operationContext
            ->incomingRequest()
            ->getRequestHeader(ODataConstants::ODATAMAXSERVICEVERSION);
    }     

    
    /**
     * Get comma separated list of client-supported MIME Accept types
     * 
     * @return string
     */
    public function getRequestAccept()
    {
        return $this->_operationContext
            ->incomingRequest()
            ->getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_ACCEPT);
    }

    /**
     * To change the request accept type header in the request.
     * Note: This method will be used when client specified $format
     * query option.
     * 
     * @param string $mimeType MIME to set.
     * 
     * @return void
     * 
     */
    public function setRequestAccept($mimeType)
    {
        $this->_operationContext
            ->incomingRequest()
            ->setRequestAccept($mimeType);
    }

    /**
     * Get the character set encoding that the client requested
     * 
     * @return string
     */
    public function getRequestAcceptCharSet()
    {
        return $this->_operationContext
            ->incomingRequest()
            ->getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_ACCEPT_CHARSET);
    }
        
    /**
     * Gets the MIME type of the  request stream
     * 
     * @return string
     */
    public function getRequestContentType()
    {
        return $this->_operationContext
            ->incomingRequest()
            ->getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_CONTENT_TYPE);
    }

    /**
     * Gets the ContentLength of the  request
     * 
     * @return string
     */
    public function getRequestContentLength()
    {
        return $this->_operationContext
            ->incomingRequest()
            ->getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_CONTENT_LENGTH);
    }
    
    /**
     * Gets the HTTP verb used by the client
     * 
     * @return  string
     */
    public function getRequestHttpMethod()
    {        
        return $this->_operationContext
            ->incomingRequest()
            ->getMethod();
    }
        
    /**
     * Get the value of If-Match header of the request
     * 
     * @return string 
     */
    public function getRequestIfMatch()
    {
        return $this->_operationContext
            ->incomingRequest()
            ->getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_IFMATCH);
    }
        
    /**
     * Gets the value of If-None-Match header of the request
     * 
     * @return string
     */
    public function getRequestIfNoneMatch()
    {
        return $this->_operationContext
            ->incomingRequest()
            ->getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_IFNONE);
    }      

    /**
     * Get the request headers
     * 
     * @return array<headername, headerValue>
     */
    public function &getRequestHeaders()
    {
        return $this->_operationContext->incomingRequest()->getHeaders();
    }

    /**
     * Set the Cache-Control header on the response
     * 
     * @param string $value The cache-control value.
     * 
     * @return void
     * 
     @ throws InvalidOperation
     */
    public function setResponseCacheControl($value)
    {
        $this->_operationContext->outgoingResponse()->setCacheControl($value);
    }      

    /**
     * Gets the HTTP MIME type of the output stream
     * 
     * @return string
     */
    public function getResponseContentType()
    {
        return $this->_operationContext
            ->outgoingResponse()
            ->getContentType();
    }
        
    /**
     * Sets the HTTP MIME type of the output stream
     * 
     * @param string $value The HTTP MIME type
     * 
     * @return void
     */
    public function setResponseContentType($value)
    {
        $this->_operationContext
            ->outgoingResponse()
            ->setContentType($value);
    }      
        
    /**
     * Sets the content length of the output stream
     * 
     * @param string $value The content length
     * 
     * @return void
     * 
     * @throw Exception if $value is not numeric throws notAcceptableError
     */
    public function setResponseContentLength($value)
    {
        if (preg_match('/[0-9]+/', $value)) {
            $this->_operationContext->outgoingResponse()->setContentLength($value);
        } else {
            \ODataProducer\Common\ODataException::notAcceptableError(
                "ContentLength:$value is invalid"
            );
        }
    }
    
    /**
     * Gets the value of the ETag header on the response
     * 
     * @return string/NULL
     */
    public function getResponseETag()
    {
        return $this->_operationContext->outgoingResponse()->getETag();
    }
        
    /**
     * Sets the value of the ETag header on the response
     * 
     * @param string $value The ETag value
     * 
     * @return void
     */
    public function setResponseETag($value)
    {
        $this->_operationContext->outgoingResponse()->setETag($value);
    }

    /**
     * Sets the value Location header on the response
     * 
     * @param string $value The location.
     * 
     * @return void
     */
    public function setResponseLocation($value)
    {
        $this->_operationContext->outgoingResponse()->setLocation($value);
    }

    /**
     * Sets the value status code header on the response
     * 
     * @param string $value The status code
     * 
     * @return void
     */
    public function setResponseStatusCode($value)
    {
        $floor = floor($value / 100);
        if ($floor >= 1 && $floor <= 5) {
            $statusDescription = HttpStatus::getStatusDescription($value);
            if (!is_null($statusDescription)) {
                $statusDescription = ' ' . $statusDescription;
            }

            $this->_operationContext
                ->outgoingResponse()->setStatusCode($value . $statusDescription);
        } else {
            ODataException::createInternalServerError(
                'Invalid Status Code' . $value
            );
        }
    }

    /**
     * Sets the value status description header on the response
     * 
     * @param string $value The status description
     * 
     * @return void
     */
    public function setResponseStatusDescription($value)
    {
        $this->_operationContext
            ->outgoingResponse()->setStatusDescription($value);
    }

    /**
     * Sets the value stream to be send a response
     * 
     * @param string &$value The stream
     * 
     * @return void
     */
    public function setResponseStream(&$value)
    {
        $this->_operationContext->outgoingResponse()->setStream($value);
    }

    /**
     * Sets the DataServiceVersion response header
     * 
     * @param string $value The version
     * 
     * @return void
     */
    public function setResponseVersion($value)
    {
        $this->_operationContext->outgoingResponse()->setServiceVersion($value);
    }

    /**
     * Get the response headers
     * 
     * @return array<headername, headerValue>
     */
    public function &getResponseHeaders()
    {
        return $this->_operationContext->outgoingResponse()->getHeaders();
    }

    /**
     * Add a header to response header collection
     * 
     * @param string $headerName  The name of the header
     * @param string $headerValue The value of the header
     * 
     * @return void
     */
    public function addResponseHeader($headerName, $headerValue)
    {
        $this->_operationContext
            ->outgoingResponse()->addHeader($headerName, $headerValue);
    }
}