<?php

namespace POData\Common;

/**
 * Class ODataException.
 */
class ODataException extends \Exception
{
    /**
     * The error code.
     *
     * @var int
     */
    private $_errorCode;

    /**
     * The HTTP status code.
     *
     * @var int
     */
    private $_statusCode;

    /**
     * Create new instance of ODataException.
     *
     * @param string $message    The error message
     * @param string $statusCode The status code
     * @param string $errorCode  The error code
     *
     * @return ODataException
     */
    public function __construct($message, $statusCode, $errorCode = null)
    {
        $this->_errorCode = $errorCode;
        $this->_statusCode = $statusCode;
        parent::__construct($message, $errorCode);
    }

    /**
     * Get the status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    /**
     * Creates an instance of ODataException
     * representing HTTP bad request error.
     *
     * @param string $message The error message
     *
     * @return ODataException
     */
    public static function createBadRequestError($message)
    {
        return new self($message, 400);
    }

    /**
     * Creates an instance of ODataException
     * representing syntax error in the query.
     *
     * @param string $message The error message
     *
     * @return ODataException
     */
    public static function createSyntaxError($message)
    {
        return self::createBadRequestError($message);
    }

    /**
     * Creates an instance of ODataException when a
     * resource represented by a segment in the url is not found.
     *
     * @param string $segment The segment in the url for which corresponding
     *                        resource not present in the data source
     *
     * @return ODataException
     */
    public static function createResourceNotFoundError($segment)
    {
        return new self(Messages::uriProcessorResourceNotFound($segment), 404);
    }

    /**
     * Creates an instance of ODataException when a
     * resouce not found in the data source.
     *
     * @param string $message The error message
     *
     * @return ODataException
     */
    public static function resourceNotFoundError($message)
    {
        return new self($message, 404);
    }

    /**
     * Creates an instance of ODataException when some
     * internal error happens in the library.
     *
     * @param string $message The detailed internal error message
     *
     * @return ODataException
     */
    public static function createInternalServerError($message)
    {
        return new self($message, 500);
    }

    /**
     * Creates an instance of ODataException when requestor tries to
     * access a resource which is forbidden.
     *
     * @return ODataException
     */
    public static function createForbiddenError()
    {
        return new self(Messages::uriProcessorForbidden(), 403);
    }

    /**
     * Creates a new exception to indicate Precondition error.
     *
     * @param string $message Error message for this exception
     *
     * @return ODataException
     */
    public static function createPreConditionFailedError($message)
    {
        return new self($message, 412);
    }

    /**
     * Creates a new exception when requestor ask for a service facility
     * which is not implemented by this library.
     *
     * @param string $message Error message for this exception
     *
     * @return ODataException
     */
    public static function createNotImplementedError($message)
    {
        return new self($message, 501);
    }

    /**
     * Creates an instance of ODataException when requestor to
     * set value which is not allowed.
     *
     * @param string $message Error message for this exception
     *
     * @return ODataException
     */
    public static function notAcceptableError($message)
    {
        return new self($message, 406);
    }
}
