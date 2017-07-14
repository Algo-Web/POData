<?php

namespace POData\OperationContext\Web;

use POData\Common\ODataConstants;

/**
 * Class OutgoingResponse represents HTTP methods,headers and stream associated with a HTTP response.
 */
class OutgoingResponse
{
    /**
     * Gets the headers from the outgoing Web response.
     *
     * @var []
     */
    private $_headers;

    /**
     * The stream associated with the outgoing response.
     *
     * @var string
     */
    private $_stream;

    /**
     * Gets and sets the DataServiceVersion of the outgoing Web response
     * This is used by the server to generate the response and should not be greater
     * than the httpRequest(MaxDataServiceVersion).
     *
     * @var string
     */
    private $_dataServiceVersion;

    /**
     * Initialize a new instance of OutgoingWebResponseContext.
     */
    public function __construct()
    {
        $this->_headers = [];
        $this->_initializeResponseHeaders();
    }

    /**
     * Sets the initial value of the default response headers.
     */
    private function _initializeResponseHeaders()
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE] = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTLENGTH] = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_ETAG] = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL] = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_LASTMODIFIED] = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_LOCATION] = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS] = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE] = null;
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_DESC] = null;
        $this->_dataServiceVersion = null;
    }

    /**
     * Get the response headers
     * By-default we will get the following headers:
     * HttpResponseHeaderStrContentType, HttpResponseHeaderStrContentLength,
     * HttpResponseHeaderStrETag, HttpResponseHeaderStrCacheControl,
     * HttpResponseHeaderStrLastModified, HttpResponseHeaderStrLocation,
     * HttpResponseHeaderStrStatus, HttpResponseHeaderStrStatusCode,
     * HttpResponseHeaderStrStatusDesc.
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
     * Gets the ContentType header of the response.
     *
     * @return string _headers[HttpResponseHeaderStrContentType]
     */
    public function getContentType()
    {
        return $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE];
    }

    /**
     * Set the ContentType header for the response.
     *
     * @param string $value The content type value
     */
    public function setContentType($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE] = $value;
    }

    /**
     * Set the ContentLength header for the response.
     *
     * @param string $value The content length header
     */
    public function setContentLength($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTLENGTH] = $value;
    }

    /**
     * Set the Cache-Control header for the response.
     *
     * @param string $value the cache-contro; value
     */
    public function setCacheControl($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL] = $value;
    }

    /**
     * Gets the value of the ETag header of the response.
     *
     * @return string reference of _headers[HttpResponseHeaderStrETag]
     */
    public function getETag()
    {
        return $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_ETAG];
    }

    /**
     * Sets the value of the ETag header for the response.
     *
     * @param string $value the etag value
     */
    public function setETag($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_ETAG] = $value;
    }

    /**
     * Sets the value of the Last-Modified header for the response.
     *
     * @param string $value The last-modified value
     */
    public function setLastModified($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_LASTMODIFIED] = $value;
    }

    /**
     * Sets the value of the Location header for the response.
     *
     * @param string $value The value of location
     */
    public function setLocation($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_LOCATION] = $value;
    }

    /**
     * Sets the value of the Status header for the response
     * Format StatusCode [StatusDescription]?
     *
     * @param string $value The value of status header
     */
    public function setStatusCode($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS] = $value;
    }

    /**
     * Sets the value of the StatusDescription header for the response.
     *
     * @param string $value The value of status description
     */
    public function setStatusDescription($value)
    {
        $this->_headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_DESC] = $value;
    }

    /**
     * Gets the stream to be send a response.
     *
     * @return string
     */
    public function &getStream()
    {
        return $this->_stream;
    }

    /**
     * Sets the value stream to be send a response.
     *
     * @param string &$value The value of stream
     */
    public function setStream(&$value)
    {
        $this->_stream = $value;
    }

    /**
     * Sets the value of the dataServiceVersion header on the response.
     *
     * @param string $value The value of data service version header
     */
    public function setServiceVersion($value)
    {
        $this->_headers[ODataConstants::ODATAVERSIONHEADER] = $value;
    }

    /**
     * Add a response header.
     *
     * @param string $name  The header name
     * @param string $value The header value
     */
    public function addHeader($name, $value)
    {
        $this->_headers[$name] = $value;
    }
}
