<?php
/**
 * Note: This is a dummy class for making the testing of 
 * DataService and UriProcessor.
 */
require_once 'ODataProducer\Common\ClassAutoLoader.php';
ODataProducer\Common\ClassAutoLoader::register();
use ODataProducer\OperationContext\DataServiceHost;
class DataServiceHost2 extends DataServiceHost
{
    private $_hostInfo;
    
    public function __construct($hostInfo) 
    {
        $this->_hostInfo = $hostInfo;
        parent::__construct();
    }

    public function getAbsoluteRequestUri()
    {        
        return $this->_hostInfo['AbsoluteRequestUri'];
    }

    public function getAbsoluteServiceUri()
    {
        return $this->_hostInfo['AbsoluteServiceUri'];
    }

    public function getRequestVersion()
    {
        return $this->_hostInfo['DataServiceVersion'];
    }

    public function getRequestMaxVersion()
    {
        return $this->_hostInfo['MaxDataServiceVersion'];
    }

    public function getRequestIfMatch()
    {
        return $this->_hostInfo['RequestIfMatch'];
    }

    public function getRequestIfNoneMatch()
    {
        return $this->_hostInfo['RequestIfNoneMatch'];
    }

    public function getRequestAcceptHeader()
    {
    	return $this->_hostInfo['RequestAccept'];
    }

    public function getWebOperationContext()
    {
    	//TODO
		return null;    	
    }
    
    public function &getResponseContentType()
    {
    	return null;
    }

    public function &getResponseETag()
    {
    	return null;
    }

    public function setResponseStatusCode($statusCode)
    {
        //TODO
    }
    
    public function setResponseContentType($contentType)
    {
        //TODO
    }

    public function setResponseETag($eTag)
    {
    	//TODO
    }

    public function setResponseDataServiceVersion($version)
    {
        //TODO
    }

    public function validateQueryParameters($queryString = null)
    {
        parent::validateQueryParameters($this->_hostInfo['QueryString']);
    }
}
?>