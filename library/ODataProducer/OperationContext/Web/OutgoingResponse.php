<?php
/**
 * Helper class to represent and access the context of outgoingResponse
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_OperationContext_Web
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
namespace ODataProducer\OperationContext\Web;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\ODataException;
/**
 * Class represents HTTP methods,headers and stream associated with a HTTP response
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_OperationContext_Web
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class OutgoingResponse
{
    /**
     * Gets the headers from the outgoing Web response.
     * 
     * @var WebHeaderCollection
     */
    private $_headers;

    /**
     * The stream associated with the outgoing response
     * 
     * @var string
     */
    private $_stream;

    /**
     * Gets and sets the DataServiceVersion of the outgoing Web response 
     * This is used by the server to generate the response and should not be greater
     * than the httpRequest(MaxDataServiceVersion) 
     * 
     * @var string
     */
    private $_dataServiceVersion;
    
    
    /**
     * Initialize a new instance of OutgoingWebResponseContext
     * 
     * @param string $requestStream The response stream
     */
    public function __construct($requestStream = null)
    {
        $this->_stream = $requestStream;
        $this->_headers = array();
        $this->_initializeResponseHeaders();
    }

    /**
     * Sets the initial value of the default response headers
     * 
     * @return void
     */
    private function _initializeResponseHeaders()
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE]   = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTLENGTH] = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_ETAG]          = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL]  = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_LASTMODIFIED]  = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_LOCATION]      = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS]        = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE]   = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_DESC]   = null;
        $this->_dataServiceVersion = null;
    }
    
    /**
     * Get the response headers
     * By-default we will get the following headers:
     * HttpResponseHeaderStrContentType, HttpResponseHeaderStrContentLength, 
     * HttpResponseHeaderStrETag, HttpResponseHeaderStrCacheControl,
     * HttpResponseHeaderStrLastModified, HttpResponseHeaderStrLocation,
     * HttpResponseHeaderStrStatus, HttpResponseHeaderStrStatusCode, 
     * HttpResponseHeaderStrStatusDesc
     * 
     * It may contain service based customized headers also like dataServiceVersion
     * 
     * @return array<string, string>
     */
    public function &getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Gets the ContentType header of the response
     * 
     * @return string _headers[HttpResponseHeaderStrContentType]
     */
    public function getContentType()
    {
        return $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE];
    }
        
    /**
     * Set the ContentType header for the response
     * 
     * @param string $value The content type value.
     * 
     * @return void
     */
    public function setContentType($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE] 
            = $value;
    }      
         
    /**
     * Set the ContentLength header for the response
     * 
     * @param string $value The content length header.
     * 
     * @return void
     */
    public function setContentLength($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTLENGTH] 
            = $value;
    }      

    /**
     * Set the Cache-Control header for the response
     * 
     * @param string $value the cache-contro; value.
     * 
     * @return void
     */
    public function setCacheControl($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL] 
            = $value;
    }      


    /**
     * Gets the value of the ETag header of the response
     * 
     * @return reference of _headers[HttpResponseHeaderStrETag]
     */
    public function getETag()
    {
        return $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_ETAG];
    }
        
    /**
     * Sets the value of the ETag header for the response
     * 
     * @param string $value the etag value.
     * 
     * @return void
     */
    public function setETag($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_ETAG] 
            = $value;
    }
        
    /**
     * Sets the value of the Last-Modified header for the response
     * 
     * @param string $value The last-modified value.
     * 
     * @return void
     */
    public function setLastModified($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_LASTMODIFIED] 
            = $value;
    }

    /**
     * Sets the value of the Location header for the response
     * 
     * @param string $value The value of location.
     * 
     * @return void
     */
    public function setLocation($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_LOCATION] 
            = $value;
    }
        
    /**
     * Sets the value of the Status header for the response
     * Format StatusCode [StatusDescription]?
     * 
     * @param string $value The value of status header.
     * 
     * @return void
     */
    public function setStatusCode($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS] 
            = $value;
    }
        
    /**
     * Sets the value of the StatusDescription header for the response
     * 
     * @param string $value The value of status description.
     * 
     * @return void
     */
    public function setStatusDescription($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_DESC] 
            = $value;
    }
    
    /**
     * Gets the stream to be send a response
     * 
     * @return string
     */
    public function &getStream()
    {
        return $this->_stream;
    }
        
    /**
     * Sets the value stream to be send a response
     * 
     * @param string &$value The value of stream.
     * 
     * @return void
     */
    public function setStream(&$value)
    {
        $this->_stream = $value;
    }
        
    /**
     * Sets the value of the dataServiceVersion header on the response
     * 
     * @param string $value The value of data service version header.
     * 
     * @return void
     */
    public function setServiceVersion($value)
    {
        $this->_headers[ODataConstants::ODATAVERSIONHEADER] 
            = $value;
    }

    /**
     * Add a response header.
     *
     * @param string $name  The header name. 
     * @param string $value The header value.
     * 
     * @return void
     */
    public function addHeader($name, $value)
    {
        $this->_headers[$name] = $value;
    }
}
