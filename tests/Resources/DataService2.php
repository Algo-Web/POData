<?php
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
require_once 'ODataProducer\Common\ClassAutoLoader.php';
ODataProducer\Common\ClassAutoLoader::register();
use ODataProducer\DataService;
use ODataProducer\Common\ODataException;
use ODataProducer\UriProcessor\UriProcessor;
abstract class DataService2 extends DataService
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
?>