<?php

namespace POData\Common;

/**
 * Class HttpHeaderFailure.
 */
class HttpHeaderFailure extends \Exception
{
    private $statusCode;

    /**
     * Creates new instance of HttpHeaderFailure.
     *
     * @param string $message    Error message
     * @param int    $statusCode Http status code
     * @param int    $errorCode  Http error code
     */
    public function __construct($message, $statusCode, $errorCode = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $errorCode);
    }

    /**
     * Get the status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
