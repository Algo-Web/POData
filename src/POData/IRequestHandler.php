<?php

namespace POData;

/**
 * Class IRequestHandler.
 *
 * The base BaseService (BaseService.php) should implement this interface so
 * that the Dispatcher can invoke handleRequest method upon receiving any
 * Request to a data service.
 */
interface IRequestHandler
{
    /**
     * Handler method invoked by Dispatcher. Dispatcher call this method when
     * it receives a Request to the service represented by the class which
     * implements this function.
     */
    public function handleRequest();
}
