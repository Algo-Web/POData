<?php

namespace POData\Common\Messages;

trait eTag
{
    /**
     * Message to show error when data service found eTag
     * header for non-existing resource.
     *
     * @return string The message
     */
    public static function eTagNotAllowedForNonExistingResource()
    {
        return 'The resource targeted by the request does not exists, eTag header is not allowed for non-existing resource.';
    }

    /**
     * Message to show error when request contains eTag headers
     * but targeted resource type does not have eTag properties defined.
     *
     * @return string The message
     */
    public static function noETagPropertiesForType()
    {
        return 'If-Match or If-None-Match headers cannot be specified if the target type does not have etag properties defined.';
    }

    /**
     * Message to show error when data service found the request eTag
     * does not match with entry eTag.
     *
     * @return string The message
     */
    public static function eTagValueDoesNotMatch()
    {
        return 'The etag value in the request header does not match with the current etag value of the object.';
    }

    /**
     * Format a message to show error when request eTag header has been
     * specified but eTag is not allowed for the targeted resource.
     *
     * @param string $uri Url
     *
     * @return string The formatted message
     */
    public static function eTagCannotBeSpecified($uri)
    {
        return "If-Match or If-None-Match HTTP headers cannot be specified since the URI '$uri' refers to a collection of resources or has a \$count or \$link segment or has a \$expand as one of the query parameters.";
    }
}
