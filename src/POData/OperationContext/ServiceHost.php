<?php

declare(strict_types=1);

namespace POData\OperationContext;

use Illuminate\Http\Request;
use POData\Common\HttpStatus;
use POData\Common\Messages;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\UrlFormatException;
use POData\Common\Version;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext;

/**
 * Class ServiceHost.
 *
 * It uses an IOperationContext implementation to get/set all context related
 * headers/stream info It also validates the each header value
 */
class ServiceHost
{
    /**
     * Holds reference to the underlying operation context.
     *
     * @var IOperationContext
     */
    private $operationContext;

    /**
     * The absolute request Uri as Url instance.
     * Note: This will not contain query string.
     *
     * @var Url
     */
    private $absoluteRequestUri;

    /**
     * The absolute request Uri as string
     * Note: This will not contain query string.
     *
     * @var string
     */
    private $absoluteRequestUriAsString = null;

    /**
     * The absolute service uri as Url instance.
     * Note: This value will be taken from configuration file.
     *
     * @var Url
     */
    private $absoluteServiceUri;

    /**
     * The absolute service uri string.
     * Note: This value will be taken from configuration file.
     *
     * @var string
     */
    private $absoluteServiceUriAsString = null;

    /**
     * array of query-string parameters.
     *
     * @var array<string|array>
     */
    private $queryOptions;

    /**
     * Gets reference to the operation context.
     *
     * @return IOperationContext
     */
    public function getOperationContext()
    {
        return $this->operationContext;
    }

    /**
     * @param IOperationContext|null $context the OperationContext implementation to use.
     *                                        If null the IlluminateOperationContext will be used.  Default null.
     *
     * Currently we are forcing the input request to be of type
     * \Illuminate\Http\Request but in the future we could make this more flexible if needed
     * @param Request $incomingRequest
     *
     * @throws ODataException
     * @throws UrlFormatException
     */
    public function __construct(IOperationContext $context = null, Request $incomingRequest)
    {
        if (null === $context) {
            $this->operationContext = new IlluminateOperationContext($incomingRequest);
        } else {
            $this->operationContext = $context;
        }

        // getAbsoluteRequestUri can throw UrlFormatException
        // let Dispatcher handle it
        $this->absoluteRequestUri = $this->getAbsoluteRequestUri();
        $this->absoluteServiceUri = null;

        //Dev Note: Andrew Clinton 5/19/16
        //absoluteServiceUri is never being set from what I can tell
        //so for now we'll set it as such
        $this->setServiceUri($this->getServiceUri());
    }

    /**
     * Gets the absolute request Uri as Url instance
     * Note: This method will be called first time from constructor.
     *
     * @throws ODataException     if AbsoluteRequestUri is not a valid URI
     * @throws UrlFormatException
     *
     * @return Url
     */
    public function getAbsoluteRequestUri(): Url
    {
        if (null === $this->absoluteRequestUri) {
            $this->absoluteRequestUriAsString = $this->getOperationContext()->incomingRequest()->getRawUrl();
            // Validate the uri first
            try {
                new Url($this->absoluteRequestUriAsString);
            } catch (UrlFormatException $exception) {
                throw ODataException::createBadRequestError($exception->getMessage());
            }

            $queryStartIndex = strpos($this->absoluteRequestUriAsString, '?');
            if ($queryStartIndex !== false) {
                $this->absoluteRequestUriAsString = substr(
                    $this->absoluteRequestUriAsString,
                    0,
                    $queryStartIndex
                );
            }

            // We need the absolute uri only not associated components
            // (query, fragments etc..)
            $this->absoluteRequestUri         = new Url($this->absoluteRequestUriAsString);
            $this->absoluteRequestUriAsString = rtrim($this->absoluteRequestUriAsString, '/');
        }

        return $this->absoluteRequestUri;
    }

    /**
     * Gets the absolute request Uri as string
     * Note: This will not contain query string.
     *
     * @return string
     */
    public function getAbsoluteRequestUriAsString(): string
    {
        return $this->absoluteRequestUriAsString;
    }

    /**
     * Sets the service url from which the OData URL is parsed.
     *
     * @param string $serviceUri The service url, absolute or relative
     *
     * @throws ODataException     If the base uri in the configuration is malformed
     * @throws UrlFormatException
     */
    public function setServiceUri($serviceUri): void
    {
        $builtServiceUri = null;
        if (null === $this->absoluteServiceUri) {
            $isAbsoluteServiceUri = (0 === strpos($serviceUri, 'http://')) || (0 === strpos($serviceUri, 'https://'));
            try {
                $this->absoluteServiceUri = new Url($serviceUri, $isAbsoluteServiceUri);
            } catch (UrlFormatException $exception) {
                throw ODataException::createInternalServerError(Messages::hostMalFormedBaseUriInConfig(false));
            }

            $segments    = $this->absoluteServiceUri->getSegments();
            $lastSegment = $segments[count($segments) - 1];
            $sLen        = strlen('.svc');
            $endsWithSvc = (0 === substr_compare($lastSegment, '.svc', -$sLen, $sLen));
            if (!$endsWithSvc
                || null !== $this->absoluteServiceUri->getQuery()
                || null !== $this->absoluteServiceUri->getFragment()
            ) {
                throw ODataException::createInternalServerError(Messages::hostMalFormedBaseUriInConfig(true));
            }

            if (!$isAbsoluteServiceUri) {
                $requestUriSegments = $this->getAbsoluteRequestUri()->getSegments();
                $requestUriScheme   = $this->getAbsoluteRequestUri()->getScheme();
                $requestUriPort     = $this->getAbsoluteRequestUri()->getPort();
                $i                  = count($requestUriSegments) - 1;
                // Find index of segment in the request uri that end with .svc
                // There will be always a .svc segment in the request uri otherwise
                // uri redirection will not happen.
                for (; $i >= 0; --$i) {
                    $endsWithSvc = (0 === substr_compare($requestUriSegments[$i], '.svc', -$sLen, $sLen));
                    if ($endsWithSvc) {
                        break;
                    }
                }

                $j = count($segments) - 1;
                $k = $i;
                if ($j > $i) {
                    throw ODataException::createBadRequestError(
                        Messages::hostRequestUriIsNotBasedOnRelativeUriInConfig(
                            $this->absoluteRequestUriAsString,
                            $serviceUri
                        )
                    );
                }

                while (0 <= $j && ($requestUriSegments[$i] === $segments[$j])) {
                    --$i;
                    --$j;
                }

                if (-1 != $j) {
                    throw ODataException::createBadRequestError(
                        Messages::hostRequestUriIsNotBasedOnRelativeUriInConfig(
                            $this->absoluteRequestUriAsString,
                            $serviceUri
                        )
                    );
                }

                $builtServiceUri = $requestUriScheme . '://' . $this->getAbsoluteRequestUri()->getHost();

                if (($requestUriScheme == 'http' && $requestUriPort != '80') ||
                    ($requestUriScheme == 'https' && $requestUriPort != '443')
                ) {
                    $builtServiceUri .= ':' . $requestUriPort;
                }

                for ($l = 0; $l <= $k; ++$l) {
                    $builtServiceUri .= '/' . $requestUriSegments[$l];
                }

                $this->absoluteServiceUri = new Url($builtServiceUri);
            }

            $this->absoluteServiceUriAsString = $isAbsoluteServiceUri ? $serviceUri : $builtServiceUri;
        }
    }

    /**
     * Gets the absolute Uri to the service as Url instance.
     * Note: This will be the value taken from configuration file.
     *
     * @return Url
     */
    public function getAbsoluteServiceUri(): Url
    {
        return $this->absoluteServiceUri;
    }

    /**
     * Gets the absolute Uri to the service as string
     * Note: This will be the value taken from configuration file.
     *
     * @return string
     */
    public function getAbsoluteServiceUriAsString(): string
    {
        return $this->absoluteServiceUriAsString;
    }

    /**
     * This method verifies the client provided url query parameters.
     *
     * A query parameter is valid if and only if all the following conditions hold:
     * 1. It does not duplicate another parameter
     * 2. It has a supplied value.
     * 3. If a non-OData query parameter, its name does not start with $.
     * A valid parameter is then stored in _queryOptions, while an invalid parameter
     * trips an ODataException
     *
     * @throws ODataException
     */
    public function validateQueryParameters(): void
    {
        $queryOptions = $this->getOperationContext()->incomingRequest()->getQueryParameters();

        reset($queryOptions);
        $namesFound = [];
        while ($queryOption = current($queryOptions)) {
            $optionName  = key($queryOption);
            $optionValue = current($queryOption);
            if (!is_string($optionValue)) {
                $optionName  = array_keys($optionValue)[0];
                $optionValue = $optionValue[$optionName];
            }
            if (empty($optionName)) {
                if (!empty($optionValue)) {
                    if ('$' == $optionValue[0]) {
                        if ($this->isODataQueryOption($optionValue)) {
                            throw ODataException::createBadRequestError(
                                Messages::hostODataQueryOptionFoundWithoutValue($optionValue)
                            );
                        } else {
                            throw ODataException::createBadRequestError(
                                Messages::hostNonODataOptionBeginsWithSystemCharacter($optionValue)
                            );
                        }
                    }
                }
            } else {
                if ('$' == $optionName[0]) {
                    if (!$this->isODataQueryOption($optionName)) {
                        throw ODataException::createBadRequestError(
                            Messages::hostNonODataOptionBeginsWithSystemCharacter($optionName)
                        );
                    }

                    if (false !== array_search($optionName, $namesFound)) {
                        throw ODataException::createBadRequestError(
                            Messages::hostODataQueryOptionCannotBeSpecifiedMoreThanOnce($optionName)
                        );
                    }

                    if (empty($optionValue) && '0' !== $optionValue) {
                        throw ODataException::createBadRequestError(
                            Messages::hostODataQueryOptionFoundWithoutValue($optionName)
                        );
                    }

                    $namesFound[] = $optionName;
                }
            }

            next($queryOptions);
        }

        $this->queryOptions = $queryOptions;
    }

    /**
     * Dev Note: Andrew Clinton
     * 5/19/16.
     *
     * Currently it doesn't seem that the service URI is ever being built
     * so I am doing that here.
     *
     * return string
     */
    private function getServiceUri(): string
    {
        if (($pos = strpos($this->absoluteRequestUriAsString, '.svc')) !== false) {
            $serviceUri = substr($this->absoluteRequestUriAsString, 0, $pos + strlen('.svc'));

            return $serviceUri;
        }

        return $this->absoluteRequestUriAsString;
    }

    /**
     * Verifies the given url option is a valid odata query option.
     *
     * @param string $optionName option to validate
     *
     * @return bool True if the given option is a valid odata option False otherwise
     */
    private function isODataQueryOption($optionName): bool
    {
        return $optionName === ODataConstants::HTTPQUERY_STRING_FILTER ||
               $optionName === ODataConstants::HTTPQUERY_STRING_EXPAND ||
               $optionName === ODataConstants::HTTPQUERY_STRING_INLINECOUNT ||
               $optionName === ODataConstants::HTTPQUERY_STRING_ORDERBY ||
               $optionName === ODataConstants::HTTPQUERY_STRING_SELECT ||
               $optionName === ODataConstants::HTTPQUERY_STRING_SKIP ||
               $optionName === ODataConstants::HTTPQUERY_STRING_SKIPTOKEN ||
               $optionName === ODataConstants::HTTPQUERY_STRING_TOP ||
               $optionName === ODataConstants::HTTPQUERY_STRING_FORMAT;
    }

    /**
     * Gets the value for the specified item in the request query string
     * Remark: This method assumes 'validateQueryParameters' has already been
     * called.
     *
     * @param string $item The query item to get the value of
     *
     * @return string|null The value for the specified item in the request
     *                     query string NULL if the query option is absent
     */
    public function getQueryStringItem(string $item): ?string
    {
        foreach ($this->queryOptions as $queryOption) {
            if (array_key_exists($item, $queryOption)) {
                return $queryOption[$item];
            }
        }
        return null;
    }

    /**
     * Gets the value for the DataServiceVersion header of the request.
     *
     * @return string|null
     */
    public function getRequestVersion(): ?string
    {
        $headerType = ODataConstants::HTTPREQUEST_HEADER_DATA_SERVICE_VERSION;
        return $this->getRequestHeader($headerType);
    }

    /**
     * Gets the value of MaxDataServiceVersion header of the request.
     *
     * @return string|null
     */
    public function getRequestMaxVersion(): ?string
    {
        $headerType = ODataConstants::HTTPREQUEST_HEADER_MAX_DATA_SERVICE_VERSION;
        return $this->getRequestHeader($headerType);
    }

    /**
     * Get comma separated list of client-supported MIME Accept types.
     *
     * @return string|null
     */
    public function getRequestAccept(): ?string
    {
        $headerType = ODataConstants::HTTPREQUEST_HEADER_ACCEPT;
        return $this->getRequestHeader($headerType);
    }

    /**
     * Get the character set encoding that the client requested.
     *
     * @return string|null
     */
    public function getRequestAcceptCharSet(): ?string
    {
        $headerType = ODataConstants::HTTPREQUEST_HEADER_ACCEPT_CHARSET;
        return $this->getRequestHeader($headerType);
    }

    /**
     * Get the value of If-Match header of the request.
     *
     * @return string|null
     */
    public function getRequestIfMatch(): ?string
    {
        $headerType = ODataConstants::HTTPREQUEST_HEADER_IF_MATCH;
        return $this->getRequestHeader($headerType);
    }

    /**
     * Gets the value of If-None-Match header of the request.
     *
     * @return string|null
     */
    public function getRequestIfNoneMatch(): ?string
    {
        $headerType = ODataConstants::HTTPREQUEST_HEADER_IF_NONE;
        return $this->getRequestHeader($headerType);
    }

    /**
     * Gets the value of Content-Type header of the request.
     *
     * @return string|null
     */
    public function getRequestContentType(): ?string
    {
        $headerType = ODataConstants::HTTP_CONTENTTYPE;
        return $this->getRequestHeader($headerType);
    }

    /**
     * Set the Cache-Control header on the response.
     *
     * @param string $value The cache-control value
     */
    public function setResponseCacheControl($value): void
    {
        $this->getOperationContext()->outgoingResponse()->setCacheControl($value);
    }

    /**
     * Gets the HTTP MIME type of the output stream.
     *
     * @return string
     */
    public function getResponseContentType(): string
    {
        return $this->getOperationContext()->outgoingResponse()->getContentType();
    }

    /**
     * Sets the HTTP MIME type of the output stream.
     *
     * @param  string $value The HTTP MIME type
     * @return void
     */
    public function setResponseContentType($value): void
    {
        $this->getOperationContext()->outgoingResponse()->setContentType($value);
    }

    /**
     * Sets the content length of the output stream.
     *
     * @param string $value The content length
     *
     * @throws ODataException
     * @return void
     */
    public function setResponseContentLength($value): void
    {
        if (preg_match('/[0-9]+/', $value)) {
            $this->getOperationContext()->outgoingResponse()->setContentLength($value);
        } else {
            throw ODataException::notAcceptableError(
                'ContentLength: ' . $value . ' is invalid'
            );
        }
    }

    /**
     * Gets the value of the ETag header on the response.
     *
     * @return string|null
     */
    public function getResponseETag(): ?string
    {
        return $this->getOperationContext()->outgoingResponse()->getETag();
    }

    /**
     * Sets the value of the ETag header on the response.
     *
     * @param string $value The ETag value
     */
    public function setResponseETag(string $value): void
    {
        $this->getOperationContext()->outgoingResponse()->setETag($value);
    }

    /**
     * Sets the value Location header on the response.
     *
     * @param string $value The location
     */
    public function setResponseLocation(string $value): void
    {
        $this->getOperationContext()->outgoingResponse()->setLocation($value);
    }

    /**
     * Sets the value status code header on the response.
     *
     * @param int $value The status code
     *
     * @throws ODataException
     */
    public function setResponseStatusCode(int $value): void
    {
        $floor = floor($value/100);
        if ($floor >= 1 && $floor <= 5) {
            $statusDescription = HttpStatus::getStatusDescription($value);
            if (null !== $statusDescription) {
                $statusDescription = ' ' . $statusDescription;
            }

            $this->getOperationContext()->outgoingResponse()->setStatusCode($value . $statusDescription);
        } else {
            $msg = 'Invalid status code: ' . $value;
            throw ODataException::createInternalServerError($msg);
        }
    }

    /**
     * Sets the value status description header on the response.
     *
     * @param string $value The status description
     */
    public function setResponseStatusDescription(string $value): void
    {
        $this->getOperationContext()->outgoingResponse()->setStatusDescription($value);
    }

    /**
     * Sets the value stream to be send a response.
     *
     * @param string &$value The stream
     */
    public function setResponseStream(string &$value): void
    {
        $this->getOperationContext()->outgoingResponse()->setStream($value);
    }

    /**
     * Sets the DataServiceVersion response header.
     *
     * @param string $value The version
     */
    public function setResponseVersion(string $value): void
    {
        $this->getOperationContext()->outgoingResponse()->setServiceVersion($value);
    }

    /**
     * Get the response headers.
     *
     * @return array<string,string>
     */
    public function &getResponseHeaders(): array
    {
        return $this->getOperationContext()->outgoingResponse()->getHeaders();
    }

    /**
     * Add a header to response header collection.
     *
     * @param string $headerName  The name of the header
     * @param string $headerValue The value of the header
     */
    public function addResponseHeader(string $headerName, string $headerValue): void
    {
        $this->getOperationContext()->outgoingResponse()->addHeader($headerName, $headerValue);
    }

    /**
     * Translates the short $format forms into the full mime type forms.
     *
     * @param Version $responseVersion the version scheme to interpret the short form with
     * @param string  $format          the short $format form
     *
     * @return string the full mime type corresponding to the short format form for the given version
     */
    public static function translateFormatToMime(Version $responseVersion, string $format): string
    {
        //TODO: should the version switches be off of the requestVersion, not the response version? see #91

        switch ($format) {
            case ODataConstants::FORMAT_XML:
                $format = MimeTypes::MIME_APPLICATION_XML;
                break;

            case ODataConstants::FORMAT_ATOM:
                $format = MimeTypes::MIME_APPLICATION_ATOM;
                break;

            case ODataConstants::FORMAT_VERBOSE_JSON:
                if ($responseVersion == Version::v3()) {
                    //only translatable in 3.0 systems
                    $format = MimeTypes::MIME_APPLICATION_JSON_VERBOSE;
                }
                break;

            case ODataConstants::FORMAT_JSON:
                if ($responseVersion == Version::v3()) {
                    $format = MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META;
                } else {
                    $format = MimeTypes::MIME_APPLICATION_JSON;
                }
                break;
        }

        return $format . ';q=1.0';
    }

    /**
     * @param string $headerType
     * @return null|string
     */
    private function getRequestHeader(string $headerType): ?string
    {
        $result = $this->getOperationContext()->incomingRequest()->getRequestHeader($headerType);
        assert(null === $result || is_string($result));
        return $result;
    }
}
