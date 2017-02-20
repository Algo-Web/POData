<?php

namespace POData\Common\Messages;

trait uriProcessor
{
    /**
     * Format a message to show error when a resource not found.
     *
     * @param string $segment The segment follows primitive property segment
     *
     * @return string The formatted message
     */
    public static function uriProcessorResourceNotFound($segment)
    {
        return "Resource not found for the segment '$segment'.";
    }

    /**
     * The message to show error when trying to
     * access a resourceset which is forbidden.
     *
     * @return string The message
     */
    public static function uriProcessorForbidden()
    {
        return 'Forbidden.';
    }

    /**
     * Format a message to show error when the requested uri is not
     * based on the configured base service uri.
     *
     * @param string $requestUri The uri requested by the client
     * @param string $serviceUri The base service uri
     *
     * @return string The formatted message
     */
    public static function uriProcessorRequestUriDoesNotHaveTheRightBaseUri($requestUri, $serviceUri)
    {
        return "The URI '$requestUri' is not valid since it is not based on '$serviceUri'";
    }
}
