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
use ODataProducer\Common\ErrorHandler;
require_once 'ODataProducer\Common\ClassAutoLoader.php';
ODataProducer\Common\ClassAutoLoader::register();
use ODataProducer\DataService;
use ODataProducer\Common\ODataException;
use ODataProducer\UriProcessor\UriProcessor;
class DataService2 extends DataService
{
  public function handleRequest()
  {
      try {
          $this->createProviders();   
          $this->_dataServiceHost->validateQueryParameters();
      } catch (\Exception $exception) {
          ErrorHandler::handleException($exception, $this);
          //TODO we are done call HTTPOUTPUT and remove exit
          exit;
      }
      

      $ObjectModelInstance = null;
      try {
          $uriProcessor = null;
          $uriProcessor = UriProcessor::process($this);          
          $this->serializeResult($uriProcessor->getRequestDescription(), $uriProcessor);
      } catch (\Exception $exception) {
          ErrorHandler::handleException($exception, $this);
          //TODO we are done call HTTPOUTPUT and remove exit
          exit;
      }
  }
}
?>