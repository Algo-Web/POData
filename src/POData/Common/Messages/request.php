<?php

namespace POData\Common\Messages;

trait request
{
    /**
     * Format a message to show error when client requested version is
     * lower than the version required to intercept the response.
     *
     * @param string $requestedVersion The client requested version
     * @param string $requiredVersion  The minimum version required to
     *                                 intercept the response
     *
     * @return string The formatted message
     */
    public static function requestVersionTooLow($requestedVersion, $requiredVersion)
    {
        return "Request version '$requestedVersion' is not supported for the request payload. The only supported version is '$requiredVersion'.";
    }

    /**
     * Format a message to show error when version required to intercept
     * the response is greater than the configured maximum protocol version.
     *
     * @param string $requiredVersion   Required version
     * @param string $configuredVersion Configured version
     *
     * @return string The formatted message
     */
    public static function requestVersionIsBiggerThanProtocolVersion($requiredVersion, $configuredVersion)
    {
        return "The response requires that version $requiredVersion of the protocol be used, but the MaxProtocolVersion of the data service is set to $configuredVersion.";
    }

    /**
     * Format a message to show error when value of DataServiceVersion or
     * MaxDataServiceVersion is invalid.
     *
     * @param string $versionAsString String value of the version
     * @param string $headerName      Header name
     *
     * @return string The formatted message
     */
    public static function requestDescriptionInvalidVersionHeader($versionAsString, $headerName)
    {
        return "The header $headerName has malformed version value $versionAsString";
    }

    /**
     * Format a message to show error when value of DataServiceVersion or
     * MaxDataServiceVersion is invalid.
     *
     * @param string $requestHeaderName Name of the request header
     * @param string $requestedVersion  Requested version
     * @param string $availableVersions Available versions
     *
     * @return string The formatted message
     */
    public static function requestDescriptionUnSupportedVersion($requestHeaderName, $requestedVersion, $availableVersions)
    {
        return "The version value $requestedVersion in the header $requestHeaderName is not supported, available versions are $availableVersions";
    }
}
