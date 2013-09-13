<?php

namespace UnitTests\POData\Facets;

/**
 * A wrapper class over BaseService class, this class will be used for
 * testing the BaseService and UriProcessor classes.
 * Why this class: 
 *  The BaseService::handleRequest method will be serailizing the result
 *  or exception, so testing is difficult.
 *  Instead we will use BaseServiceTestWrapper::handleRequest as this function
 *  works same as BaseService::handleRequest expect it throws exception
 *  in case of error and return instance of UriProcessor if paring is 
 *  successful.
 */

use POData\BaseService;
use POData\Common\ODataException;
use POData\UriProcessor\UriProcessor;

abstract class BaseServiceTestWrapper extends BaseService
{
  public function handleRequest()
  {
      $this->createProviders();      
      try {
          $this->getHost()->validateQueryParameters();          
      } catch (ODataException $odataException) {
          throw $odataException; 
      }

      $uriProcessor = null;
      try {
          $uriProcessor = UriProcessor::process($this);
      } catch (ODataException $odataException) {
          throw $odataException;
      }
      
      return $uriProcessor;
  }
}