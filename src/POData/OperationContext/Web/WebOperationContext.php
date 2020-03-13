<?php

declare(strict_types=1);

namespace POData\OperationContext\Web;

use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;

/**
 * Class WebOperationContext
 * Class which is used to get all the HTTP header detail for a IncomingRequest
 * and we can set the header also before sending the OutgoingResponse.
 *
 * Provide access to the current HTTP context over WebOperationContext::Current()
 * method.  This is a singleton class. Class represents the HTTP methods,headers
 * and stream associated with a HTTP request and HTTP response
 */
class WebOperationContext implements IOperationContext
{
    /**
     * Object of IncomingRequest which is needed to get all the HTTP headers info.
     *
     * @var IncomingRequest
     */
    private $incomingRequest;

    /**
     * Object of OutgoingResponse which is needed to get all the HTTP headers info.
     *
     * @var OutgoingResponse
     */
    private $outgoingResponse;

    /**
     * Initializes a new instance of the WebOperationContext class.
     * This function will perform the following tasks:
     *  (1) Retrieve the current HTTP method,headers and stream.
     *  (2) Populate $_incomingRequest using these.
     */
    public function __construct()
    {
        $this->incomingRequest  = new IncomingRequest();
        $this->outgoingResponse = new OutgoingResponse();
    }

    /**
     * Gets the Web request context for the request being sent.
     *
     * @return OutgoingResponse
     */
    public function outgoingResponse()
    {
        return $this->outgoingResponse;
    }

    /**
     * Gets the Web request context for the request being received.
     *
     * @return IHTTPRequest
     */
    public function incomingRequest()
    {
        return $this->incomingRequest;
    }
}
