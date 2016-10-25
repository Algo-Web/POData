<?php

namespace UnitTests\POData\Facets;

/*
 * A wrapper class over BaseService class, this class will be used for
 * testing the BaseService and UriProcessor classes.
 * Why this class:
 *  The BaseService::handleRequest method will be serializing the result
 *  or exception, so testing is difficult.
 *  Instead we will use BaseServiceTestWrapper::handleRequest as this function
 *  works same as BaseService::handleRequest expect it throws exception
 *  in case of error and return instance of UriProcessor if paring is
 *  successful.
 */

use POData\BaseService;
use POData\UriProcessor\UriProcessor;

abstract class BaseServiceTestWrapper extends BaseService
{
    public function handleRequest()
    {
        $this->createProviders();
        $this->getHost()->validateQueryParameters();

        return UriProcessor::process($this);
    }
}
