<?php

declare(strict_types=1);

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
     * @var array
     */
    private $headers = [];

    /**
     * The stream associated with the outgoing response.
     *
     * @var string
     */
    private $stream;

    /**
     * Gets and sets the DataServiceVersion of the outgoing Web response
     * This is used by the server to generate the response and should not be greater
     * than the httpRequest(MaxDataServiceVersion).
     *
     * @var string
     */
    private $dataServiceVersion;

    /**
     * Initialize a new instance of OutgoingWebResponseContext.
     */
    public function __construct()
    {
        $this->headers = [];
        $this->initializeResponseHeaders();
    }

    /**
     * Sets the initial value of the default response headers.
     */
    private function initializeResponseHeaders()
    {
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE]   = null;
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTLENGTH] = null;
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_ETAG]          = null;
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL]  = null;
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_LASTMODIFIED]  = null;
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_LOCATION]      = null;
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS]        = null;
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE]   = null;
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_DESC]   = null;
        $this->dataServiceVersion                                         = null;
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
     * @return array<string,string>
     */
    public function &getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Gets the ContentType header of the response.
     *
     * @return string _headers[HttpResponseHeaderStrContentType]
     */
    public function getContentType(): string
    {
        $key = ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE;
        return $this->headers[$key] ?? '';
    }

    /**
     * Set the ContentType header for the response.
     *
     * @param string $value The content type value
     */
    public function setContentType(string $value): void
    {
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE] = $value . ';charset=UTF-8';
    }

    /**
     * Set the ContentLength header for the response.
     *
     * @param string $value The content length header
     */
    public function setContentLength(string $value): void
    {
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_CONTENTLENGTH] = $value;
    }

    /**
     * Set the Cache-Control header for the response.
     *
     * @param string $value the cache-control value
     */
    public function setCacheControl(string $value): void
    {
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL] = $value;
    }

    /**
     * Gets the value of the ETag header of the response.
     *
     * @return string reference of headers[HttpResponseHeaderStrETag]
     */
    public function getETag(): string
    {
        return $this->headers[ODataConstants::HTTPRESPONSE_HEADER_ETAG];
    }

    /**
     * Sets the value of the ETag header for the response.
     *
     * @param string $value the etag value
     */
    public function setETag(string $value): void
    {
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_ETAG] = $value;
    }

    /**
     * Sets the value of the Last-Modified header for the response.
     *
     * @param string $value The last-modified value
     */
    public function setLastModified(string $value): void
    {
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_LASTMODIFIED] = $value;
    }

    /**
     * Sets the value of the Location header for the response.
     *
     * @param string $value The value of location
     */
    public function setLocation(string $value): void
    {
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_LOCATION] = $value;
    }

    /**
     * Sets the value of the Status header for the response
     * Format StatusCode [StatusDescription]?
     *
     * @param string $value The value of status header
     */
    public function setStatusCode(string $value): void
    {
        $rawCode = substr($value, 0, 3);
        assert(is_numeric($rawCode), 'Raw HTTP status code is not numeric - is ' . $rawCode);
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS]      = $value;
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_CODE] = intval($rawCode);
    }

    /**
     * Sets the value of the StatusDescription header for the response.
     *
     * @param string $value The value of status description
     */
    public function setStatusDescription(string $value): void
    {
        $this->headers[ODataConstants::HTTPRESPONSE_HEADER_STATUS_DESC] = $value;
    }

    /**
     * Gets the stream to be send a response.
     *
     * @return string
     */
    public function &getStream(): string
    {
        return $this->stream;
    }

    /**
     * Sets the value stream to be send a response.
     *
     * @param string &$value The value of stream
     */
    public function setStream(string &$value): void
    {
        $this->stream = $value;
    }

    /**
     * Sets the value of the dataServiceVersion header on the response.
     *
     * @param string $value The value of data service version header
     */
    public function setServiceVersion(string $value): void
    {
        $this->headers[ODataConstants::ODATAVERSIONHEADER] = $value;
    }

    /**
     * Add a response header.
     *
     * @param string $name  The header name
     * @param string $value The header value
     */
    public function addHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }
}
