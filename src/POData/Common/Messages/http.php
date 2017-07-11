<?php

namespace POData\Common\Messages;

use POData\OperationContext\HTTPRequestMethod;

trait http
{
    /**
     * Message to show error when baseUrl given in service.config.xml is invalid.
     *
     * @param bool $notEndWithSvcOrHasQuery Base url end with svc or not
     *
     * @return string The message
     */
    public static function hostMalFormedBaseUriInConfig($notEndWithSvcOrHasQuery = false)
    {
        if ($notEndWithSvcOrHasQuery) {
            return 'Malformed base service uri in the configuration file (should end with .svc, there should not be query or fragment in the base service uri)';
        }

        return 'Malformed base service uri in the configuration file';
    }

    /**
     * Format a message to show error when request uri is not
     * based on configured relative uri.
     *
     * @param string $requestUri  The request uri
     * @param string $relativeUri The relative uri in service.config.xml
     *
     * @return string The formatted message
     */
    public static function hostRequestUriIsNotBasedOnRelativeUriInConfig($requestUri, $relativeUri)
    {
        return 'The request uri ' . $requestUri . ' is not valid as it is not based on the configured relative uri ' . $relativeUri;
    }

    /**
     * Message to show error when data service found a request method other than GET.
     *
     * @param HTTPRequestMethod $method Request method
     *
     * @return string The formatted message
     */
    public static function onlyReadSupport(HTTPRequestMethod $method)
    {
        // TODO: Update to reflect expanded library capabilities?
        return "This release of library supports only GET (read) request, received a request with method $method";
    }

    /**
     * Format a message to show error when the uri for verb is wrong.
     *
     * @param string $uri  Url pointing to resource
     * @param string $verb GET/POST/PUT/DELETE/PATCH/MERGE
     *
     * @return string The formatted message
     */
    public static function badRequestInvalidUriForThisVerb($uri, $verb)
    {
        return "The URI '$uri' is not valid for $verb method.";
    }

    /**
     * Format a message to show error when data for non-GET requests is empty.
     *
     * @param string $verb GET/POST/PUT/DELETE/PATCH/MERGE
     *
     * @return string The formatted message
     */
    public static function noDataForThisVerb($verb)
    {
        return "Method $verb expecting some data, but received empty data.";
    }

    /**
     * Format a message to show error when the uri that look like pointing to
     * MLE but actaully it is not.
     *
     * @param string $uri Url pointing to MLE
     *
     * @return string The formatted message
     */
    public static function badRequestInvalidUriForMediaResource($uri)
    {
        return "The URI '$uri' is not valid. The segment before '\$value' must be a Media Link Entry or a primitive property.";
    }

    /**
     * Format a message to show error when library found non-odata
     * query option begins with $ character.
     *
     * @param string $optionName Name of the query option
     *
     * @return string The formatted message
     */
    public static function hostNonODataOptionBeginsWithSystemCharacter($optionName)
    {
        return "The query parameter '$optionName' begins with a system-reserved '$' character but is not recognized.";
    }

    /**
     * Format a message to show error when library found
     * a query option without value.
     *
     * @param string $optionName Name of the query option
     *
     * @return string The formatted message
     */
    public static function hostODataQueryOptionFoundWithoutValue($optionName)
    {
        return "Query parameter '$optionName' is specified, but it should be specified with value.";
    }

    /**
     * Format a message to show error when library found
     * a query option specified multiple times.
     *
     * @param string $optionName Name of the query option
     *
     * @return string The formatted message
     */
    public static function hostODataQueryOptionCannotBeSpecifiedMoreThanOnce($optionName)
    {
        return "Query parameter '$optionName' is specified, but it should be specified exactly once.";
    }

    /**
     * Message to show error when data service found presence of both
     * If-Match and if-None-Match headers.
     *
     * @return string The message
     */
    public static function bothIfMatchAndIfNoneMatchHeaderSpecified()
    {
        return 'Both If-Match and If-None-Match HTTP headers cannot be specified at the same time. Please specify either one of the headers or none of them.';
    }
}
