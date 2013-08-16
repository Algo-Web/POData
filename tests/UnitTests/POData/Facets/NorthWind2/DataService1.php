<?php

namespace UnitTests\POData\Facets\NorthWind2;


/**
 * A wrapper class over DataService class, this class will be used for
 * testing the DataService and UriProcessor classes.
 * Why this class: 
 *  The DataService::handleRequest method will be serailizing the result
 *  or exception, so testing is difficult.
 *  Instead we will use DataService2::handleRequest as this function 
 *  works same as DataService::handleRequest expect it throws exception 
 *  in case of error and return instance of UriProcessor if paring is 
 *  successful.
 */

use POData\DataService;
use POData\Common\ODataException;
use POData\UriProcessor\UriProcessor;

abstract class DataService1 extends DataService
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