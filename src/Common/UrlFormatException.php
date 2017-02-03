<?php

namespace POData\Common;

/**
 * Class UrlFormatException.
 */
class UrlFormatException extends \Exception
{
    /**
     * Construct a new instance of UrlFormatException.
     *
     * @param string $message The error message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
