<?php
/**
 * Class represents HTTP methods,headers and stream associated with a HTTP request
 * Note: This class will not throw any error
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
use ODataProducer\Common\ODataException;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Url;
/**
 * Class represents HTTP methods, headers and stream associated with a HTTP request
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_OperationContext_Web
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class IncomingRequest
{
    /**
     * The request headers
     * 
     * @var array
     */
    private $_headers;
    
    /**
     * The incoming url in raw format
     * 
     * @var string
     */
    private $_rawUrl  = null;
    
    /**
     * The incoming url as instance of Url
     * 
     * @var string
     */
    private $_url;
    
    /**
     * The request method (GET, POST, PUT, DELETE or MERGE)
     * 
     * @var string HttpVerb
     */
    private $_method;

    /**
     * The query options as key value.
     * 
     * @var array(string, string);
     */
    private $_queryOptions;

    /**
     * A collection that represents mapping between query 
     * option and its count.
     * 
     * @var array(string, int)
     */
    private $_queryOptionsCount;

    /**
     * Initialize a new instance of IncomingWebRequestContext
     */
    public function __construct()
    {
        $this->_method = $_SERVER['REQUEST_METHOD'];
        $this->_queryOptions = null;
        $this->_queryOptionsCount = null;
        $this->_headers = null;
        $this->getHeaders();
    }

    /**
     * Get the request headers
     * By-default we will get the following headers:
     * HTTP_HOST, HTTP_USER_AGENT, HTTP_ACCEPT, HTTP_ACCEPT_LANGUAGE,
     * HTTP_ACCEPT_ENCODING, HTTP_ACCEPT_CHARSET, HTTP_KEEP_ALIVE, HTTP_CONNECTION,
     * HTTP_CACHE_CONTROL, HTTP_USER_AGENT, HTTP_IF_MATCH, HTTP_IF_NONE_MATCH,
     * HTTP_IF_MODIFIED, HTTP_IF_MATCH, HTTP_IF_NONE_MATCH, HTTP_IF_UNMODIFIED_SINCE
     * REQUEST_URI,REQUEST_METHOD,REQUEST_TIME, SERVER_NAME
     * SERVER_PORT, SERVER_PORT_SECURE, SERVER_PROTOCOL, SERVER_SOFTWARE
     * CONTENT_TYPE, CONTENT_LENGTH
     * We may get user defined customized headers also like
     * HTTP_DATASERVICEVERSION, HTTP_MAXDATASERVICEVERSION
     * 
     * @return array<string, string>
     */
    public function &getHeaders()
    {
        if (is_null($this->_headers)) {
            $this->_headers = array();
            if (array_key_exists('QUERY_STRING', $_SERVER)) {
                $this->_headers[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING] 
                    = rawurldecode(utf8_decode(trim($_SERVER['QUERY_STRING'])));
            } else {
                $this->_headers[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING] = "";
            }   
                /**
                $hdr = null;
                foreach ( $_SERVER as $key => $value) {
                   $hdr .=  $key . '=' . $value . "\r\n";
                }
                $handle = fopen("d:\dump\log.txt", "w+");
                fwrite ($handle , $hdr);
                fclose($handle);
                exit;
                **/

            foreach ($_SERVER as $key => $value) {
                if ((strpos($key, 'HTTP_') === 0) 
                    || (strpos($key, 'REQUEST_') === 0)
                    || (strpos($key, 'SERVER_') === 0) 
                    || (strpos($key, 'CONTENT_') === 0)
                ) {
                    $trimmedValue = trim($value);
                    $this->_headers[$key] = isset($trimmedValue) ? $trimmedValue : null;
                }
            }

            if (!array_key_exists(ODataConstants::ODATASERVICEVERSION, $this->_headers)) {
                $this->_headers[ODataConstants::ODATASERVICEVERSION] = null;
            }

            if (!array_key_exists(ODataConstants::ODATAMAXSERVICEVERSION, $this->_headers)) {
                $this->_headers[ODataConstants::ODATAMAXSERVICEVERSION] = null;
            }
        }

        return $this->_headers;
    }

    /**
     * get the raw incoming url
     * 
     * @return string RequestURI called by User with the value of QueryString
     */  
    public function getRawUrl()
    {
        if (is_null($this->_rawUrl)) {
            if (!preg_match('/^HTTTPS/', $_SERVER[ODataConstants::HTTPREQUEST_HEADER_PROTOCOL])) {
                $this->_rawUrl = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTP;
            } else {
                $this->_rawUrl = ODataConstants::HTTPREQUEST_HEADER_PROTOCOL_HTTPS;
            }

            $this->_rawUrl .= "://".$_SERVER[ODataConstants::HTTPREQUEST_HEADER_HOST];
            $this->_rawUrl .= utf8_decode(urldecode($_SERVER[ODataConstants::HTTPREQUEST_HEADER_URI]));
        }

        return $this->_rawUrl;
    }

    /**
     * get the specific request headers
     * 
     * @param string $key The header name
     * 
     * @return string/NULL value of the header, NULL if header is absent.
     */
    public function getRequestHeader($key)
    {
        $trimmedKey = trim($key);
        if (array_key_exists($trimmedKey, $this->_headers)) {
            return $this->_headers[$trimmedKey];
        }

        return null;
    }

    /**
     * Get the QUERY_STRING
     * Note: This method will return empty string if no query string present.
     * 
     * @return string $_header[HttpRequestHeaderQueryString]
     */
    public function getQueryString()
    {
        return utf8_decode(
            urldecode(
                $this->getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING)
            )
        );
    }
    
    /**
     * Split the QueryString and assigns them as array element in KEY=VALUE
     * 
     * @return array(string/NULL, string)
     */
    public function &getQueryParameters()
    {
        if (is_null($this->_queryOptions)) {
            $queryString = $this->getQueryString();
            $this->_queryOptions = array();
            $i = 0;
            foreach (explode('&', $queryString) as $queryOptionAsString) {
                $queryOptionAsString = trim($queryOptionAsString);
                if (!empty($queryOptionAsString)) {    
                    $result = explode('=', $queryOptionAsString, 2);
                    $isNamedOptions = count($result) == 2;
                    if ($isNamedOptions) {
                        $this->_queryOptions[$i] 
                            = array ($result[0] => trim($result[1]));
                    } else {
                        $this->_queryOptions[$i] 
                            = array(null => trim($result[0]));
                    }
                    $i++;
                }
            }
        }

        return $this->_queryOptions;
    }

    /**
     * Gets an array that provides count of each query options. 
     * 
     * @return array(string, int)
     */
    public function &getQueryParametersCount()
    {
        if (is_null($this->_queryOptionsCount)) {
            $this->getQueryParameters();
            $this->_queryOptionsCount = array();
            foreach ($this->_queryOptions as $queryOption) {
                foreach ($queryOption as $key => $value) {
                    if (array_key_exists($key, $this->_queryOptionsCount)) {
                        $this->_queryOptionsCount[$key] += 1;
                    } else {
                        $this->_queryOptionsCount[$key] = 1;
                    }
                }
            }
        }

        return $this->_queryOptionsCount;
    }
    
    
    /**
     * Get the HTTP method
     * Value will be set from the value of the HTTP method of the 
     * incoming Web request.
     * 
     * @return string $_header[HttpRequestHeaderMethod] 
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Get the UserAgent header
     * 
     * @return string $_header[HttpRequestHeaderUserAgent]
     */
    public function getUserAgent()
    {
        return getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_USER_AGENT);
    }
    
    /**
     * Get the If-Modified-Since header
     *  
     * @return string $_header[HttpRequestHeaderIfModified]
     */
    public function getIfModifiedSince()
    {
        return getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_IFMODIFIED);
    }
    
    /**
     * Get the If-Unmatched-Since header
     * 
     * @return string $_header[HttpRequestHeaderIfUnmodified] 
     */
    public function getIfUnmodifiedSince()
    {
        return getRequestHeader(ODataConstants::HTTPREQUEST_HEADER_IFUNMODIFIED);
    }

    /**
     * To change the request accept type header in the request.
     * Note: This method will be used only when client specified $format
     * query option.
     * 
     * @param string $mime The mime value.
     * 
     * @return void
     */
    public function setRequestAccept($mime)
    {
        $this->_headers[ODataConstants::HTTPREQUEST_HEADER_ACCEPT] = $mime;
    }
}