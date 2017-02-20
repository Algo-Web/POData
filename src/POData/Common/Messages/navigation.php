<?php

namespace POData\Common\Messages;

trait navigation
{
    /**
     * The error message to show for invalid navigation resource type.
     *
     * @return string The message
     */
    public static function navigationInvalidResourceType()
    {
        return 'Only possible Navigation types are Complex and Entity.';
    }

    /**
     * Message to show error when there is a syntax error in the query.
     *
     * @return string The message
     */
    public static function syntaxError()
    {
        return 'Bad Request - Error in query syntax.';
    }

    /**
     * Format a message to show error when given url is malformed.
     *
     * @param string $url The malformed url
     *
     * @return string The formatted message
     */
    public static function urlMalformedUrl($url)
    {
        return "Bad Request - The url '$url' is malformed.";
    }
}
