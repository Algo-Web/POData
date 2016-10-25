<?php

namespace POData\OperationContext;

use POData\OperationContext\Web\OutgoingResponse;

/**
 * Interface OperationContext
 * The contract used to get all the HTTP request details for an incoming request.
 */
interface IOperationContext
{
    /**
     * Gets the Web request context for the request being sent.
     *
     * @return OutgoingResponse reference of OutgoingResponse object
     */
    public function outgoingResponse();

    /**
     * Gets the Web request context for the request being received.
     *
     * @return IHTTPRequest reference of IncomingRequest object
     */
    public function incomingRequest();
}
