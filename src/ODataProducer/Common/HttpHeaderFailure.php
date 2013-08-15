<?php

namespace ODataProducer\Common;

/**
 * Class HttpHeaderFailure
 * @package ODataProducer\Common
 */
class HttpHeaderFailure extends \Exception
{
    private $_statusCode;
    
    /**
     * Creates new instance of HttpHeaderFailure
     * 
     * @param String $message    Error message
     * @param Int    $statusCode Http status code
     * @param Int    $errorCode  Http error code
     * 
     * @return void
     */
    public function  __construct($message, $statusCode, $errorCode = null) 
    {
        $this->_statusCode = $statusCode;
        parent::__construct($message, $errorCode);
    }
    
    /**
     * Get the status code
     * 
     * @return Int
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }
}