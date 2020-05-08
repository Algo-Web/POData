<?php

declare(strict_types=1);

namespace POData\OperationContext\Web;

use POData\Common\ODataConstants;
use POData\HttpProcessUtility;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;

/**
 * Class IncomingRequest
 * Class represents HTTP methods,headers and stream associated with a HTTP request
 * Note: This class will not throw any error.
 */
class IncomingRequest implements IHTTPRequest
{
    /**
     * The request headers.
     *
     * @var array
     */
    private $headers = [];

    /**
     * The incoming url in raw format.
     *
     * @var string|null
     */
    private $rawUrl = null;

    /**
     * The request method (GET, POST, PUT, DELETE or MERGE).
     *
     * @var HTTPRequestMethod HttpVerb
     */
    private $method;

    /**
     * The query options as key value.
     *
     * @var array(string, string);
     */
    private $queryOptions = [];

    /**
     * A collection that represents mapping between query option and its count.
     *
     * @var array(string, int)
     */
    private $queryOptionsCount = [];

    /**
     * The raw query string.
     *
     * @var string|null;
     */
    private $queryString = null;

    /**
     * The raw input.
     *
     * @var string|null;
     */
    private $rawInput = null;

    /**
     * Initialize a new instance of IHTTPRequest.
     *
     * @param HttpRequestMethod|null $method
     * @param array                  $queryOptions
     * @param array                  $queryOptionsCount
     * @param array                  $headers
     * @param string|null            $queryString
     * @param string|null            $rawInput
     * @param string|null            $rawUrl
     */
    public function __construct(
        HTTPRequestMethod $method = null,
        array $queryOptions = [],
        array $queryOptionsCount = [],
        array $headers = [],
        string $queryString = null,
        string $rawInput = null,
        string $rawUrl = null
    ) {
        $this->method            = $method ?? new HTTPRequestMethod($_SERVER['REQUEST_METHOD']);
        $this->queryOptions      = $queryOptions;
        $this->queryOptionsCount = $queryOptionsCount;
        $this->headers           = $headers;
        $this->queryString       = $queryString;
        $this->rawInput          = $rawInput;
        $this->rawUrl            = $rawUrl;
    }

    /**
     * get the raw incoming url.
     *
     * @return string RequestURI called by User with the value of QueryString
     */
    public function getRawUrl(): string
    {
        if (null === $this->rawUrl) {
            $rawProtocol = $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] ?? '';
            if (false === stripos($rawProtocol, 'HTTPS')) {
                $this->rawUrl = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
            } else {
                $this->rawUrl = ODataConstants::HTTPREQUEST_PROTOCOL_HTTPS;
            }

            $rawHost = $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] ?? '';
            $rawUri  = $_SERVER[ODataConstants::HTTPREQUEST_URI] ?? '';
            $this->rawUrl .= '://' . $rawHost;
            $this->rawUrl .= utf8_decode(urldecode($rawUri));
        }

        return $this->rawUrl;
    }

    /**
     * get the specific request headers.
     *
     * @param string $key The header name
     *
     * @return string|null value of the header, NULL if header is absent
     */
    public function getRequestHeader(string $key): ?string
    {
        if (0 == count($this->headers)) {
            $this->getHeaders();
        }
        //PHP normalizes header keys
        $trimmedKey = HttpProcessUtility::headerToServerKey(trim($key));

        if (array_key_exists($trimmedKey, $this->headers)) {
            return $this->headers[$trimmedKey];
        }
        return null;
    }

    /**
     * Get the request headers
     * By-default we will get the following headers:
     * HTTP_HOST
     * HTTP_USER_AGENT
     * HTTP_ACCEPT
     * HTTP_ACCEPT_LANGUAGE
     * HTTP_ACCEPT_ENCODING
     * HTTP_ACCEPT_CHARSET
     * HTTP_KEEP_ALIVE
     * HTTP_CONNECTION,
     * HTTP_CACHE_CONTROL
     * HTTP_USER_AGENT
     * HTTP_IF_MATCH
     * HTTP_IF_NONE_MATCH
     * HTTP_IF_MODIFIED
     * HTTP_IF_MATCH
     * HTTP_IF_NONE_MATCH
     * HTTP_IF_UNMODIFIED_SINCE
     * //TODO: these aren't really headers...
     * REQUEST_URI
     * REQUEST_METHOD
     * REQUEST_TIME
     * SERVER_NAME
     * SERVER_PORT
     * SERVER_PORT_SECURE
     * SERVER_PROTOCOL
     * SERVER_SOFTWARE
     * CONTENT_TYPE
     * CONTENT_LENGTH
     * We may get user defined customized headers also like
     * HTTP_DATASERVICEVERSION, HTTP_MAXDATASERVICEVERSION.
     *
     * @return string[]
     */
    private function getHeaders(): array
    {
        if (0 == count($this->headers)) {
            $this->headers = [];

            foreach ($_SERVER as $key => $value) {
                if ((0 === strpos($key, 'HTTP_'))
                    || (0 === strpos($key, 'REQUEST_'))
                    || (0 === strpos($key, 'SERVER_'))
                    || (0 === strpos($key, 'CONTENT_'))
                ) {
                    $trimmedValue        = trim(strval($value));
                    $this->headers[$key] = isset($trimmedValue) ? $trimmedValue : null;
                }
            }
        }

        return $this->headers;
    }

    /**
     * Split the QueryString and assigns them as array element in KEY=VALUE.
     *
     * @return string[]|array[]
     */
    public function getQueryParameters(): array
    {
        if (0 == count($this->queryOptions)) {
            $queryString        = $this->getQueryString();
            $this->queryOptions = [];

            foreach (explode('&', $queryString) as $queryOptionAsString) {
                $queryOptionAsString = trim($queryOptionAsString);
                if (!empty($queryOptionAsString)) {
                    $result         = explode('=', $queryOptionAsString, 2);
                    $isNamedOptions = 2 == count($result);
                    $rawUrl         = rawurldecode($result[0]);
                    if ($isNamedOptions) {
                        $this->queryOptions[] = [$rawUrl => trim(rawurldecode($result[1]))];
                    } else {
                        $this->queryOptions[] = [null => trim($rawUrl)];
                    }
                }
            }
        }

        return $this->queryOptions;
    }

    /**
     * Get the QUERY_STRING
     * Note: This method will return empty string if no query string present.
     *
     * @return string $_header[HttpRequestHeaderQueryString]
     */
    private function getQueryString(): string
    {
        if (null === $this->queryString) {
            if (array_key_exists(ODataConstants::HTTPREQUEST_QUERY_STRING, $_SERVER)) {
                $rawString         = $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] ?? '';
                $this->queryString = utf8_decode(trim($rawString));
            } else {
                $this->queryString = '';
            }
        }
        return $this->queryString;
    }

    /**
     * Get the HTTP method
     * Value will be set from the value of the HTTP method of the
     * incoming Web request.
     *
     * @return HTTPRequestMethod $_header[HttpRequestHeaderMethod]
     */
    public function getMethod(): HTTPRequestMethod
    {
        return $this->method;
    }

    /**
     * @return string|null
     */
    public function getAllInput(): ?string
    {
        if (null === $this->rawInput) {
            $this->rawInput = file_get_contents('php://input');
        }
        return $this->rawInput;
    }

    /**
     * @param string $contentID
     */
    public function applyContentID(string $contentID,string $contentIdValue): void
    {
        $this->rawInput    = str_replace('$' . $contentID, $contentIdValue, $this->rawInput);
        $this->rawUrl = str_replace('$' . $contentID, $contentIdValue, $this->rawUrl);
    }
}
