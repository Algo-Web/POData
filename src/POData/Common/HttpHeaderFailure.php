<?php

declare(strict_types=1);

namespace POData\Common;

use Exception;

/**
 * Class HttpHeaderFailure.
 */
class HttpHeaderFailure extends Exception
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
        if (null !== $errorCode) {
            parent::__construct($message, $errorCode);
        }
        parent::__construct($message, $statusCode);
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
