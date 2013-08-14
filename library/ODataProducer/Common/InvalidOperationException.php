<?php

namespace ODataProducer\Common;

/**
 * Class InvalidOperationException
 * @package ODataProducer\Common
 */
class InvalidOperationException extends \Exception
{
    /**
     * Creates new instance of InvalidOperationException
     * 
     * @param string $message The error message 
     */
    public function __construct($message) 
    {
        parent::__construct($message);
    }
}