<?php

namespace POData\Common\Messages;

trait httpProcessUtility
{
    /**
     * Message to show error when media type header found malformed.
     *
     * @return string The message
     */
    public static function httpProcessUtilityMediaTypeRequiresSemicolonBeforeParameter()
    {
        return "Media type requires a ';' character before a parameter definition.";
    }

    /**
     * Message to show error when media header value misses type segment.
     *
     * @return string The message
     */
    public static function httpProcessUtilityMediaTypeUnspecified()
    {
        return 'Media type is unspecified.';
    }

    /**
     * Message to show error when media header value misses slash after type.
     *
     * @return string The message
     */
    public static function httpProcessUtilityMediaTypeRequiresSlash()
    {
        return "Media type requires a '/' character.";
    }

    /**
     * Message to show error when media header value misses sub-type.
     *
     * @return string The message
     */
    public static function httpProcessUtilityMediaTypeRequiresSubType()
    {
        return 'Media type requires a subtype definition.';
    }

    /**
     * Message to show error when media type misses parameter value.
     *
     * @return string The message
     */
    public static function httpProcessUtilityMediaTypeMissingValue()
    {
        return 'Media type is missing a parameter value.';
    }

    /**
     * Format a message to show error when media type parameter value contain escape
     * character but the value is not quoted.
     *
     * @param string $parameterName Name of the parameter
     *
     * @return string The formatted message
     */
    public static function httpProcessUtilityEscapeCharWithoutQuotes($parameterName)
    {
        return "Value for MIME type parameter '$parameterName' is incorrect because it contained escape characters even though it was not quoted.";
    }

    /**
     * Format a message to show error when media type parameter value contain escape
     * character but the value at the end.
     *
     * @param string $parameterName Name of the parameter
     *
     * @return string The formatted message
     */
    public static function httpProcessUtilityEscapeCharAtEnd($parameterName)
    {
        return "Value for MIME type parameter '$parameterName' is incorrect because it terminated with escape character. Escape characters must always be followed by a character in a parameter value.";
    }

    /**
     * Format a message to show error when media parameter
     * value misses closing bracket.
     *
     * @param string $parameterName Name of the parameter
     *
     * @return string The formatted message
     */
    public static function httpProcessUtilityClosingQuoteNotFound($parameterName)
    {
        return "Value for MIME type parameter '$parameterName' is incorrect because the closing quote character could not be found while the parameter value started with a quote character.";
    }

    /**
     * Message to show error when the header found malformed.
     *
     * @return string The formatted message
     */
    public static function httpProcessUtilityMalformedHeaderValue()
    {
        return 'Malformed value in request header.';
    }
}
