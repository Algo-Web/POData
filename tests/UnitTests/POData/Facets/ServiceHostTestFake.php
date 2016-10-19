<?php

namespace UnitTests\POData\Facets;

/**
 * Note: This is a dummy class for making the testing of 
 * BaseService and UriProcessor.
 */

use Illuminate\Http\Request;
use POData\Common\ODataConstants;
use POData\OperationContext\ServiceHost;
use POData\HttpProcessUtility;

class ServiceHostTestFake extends ServiceHost
{
    private $_hostInfo;
    
    public function __construct(array $hostInfo)
    {
        $this->_hostInfo = $hostInfo;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = $this->_hostInfo['AbsoluteRequestUri']->getScheme();
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = $this->_hostInfo['AbsoluteRequestUri']->getHost() . ':' .  $this->_hostInfo['AbsoluteRequestUri']->getPort();
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = $this->_hostInfo['AbsoluteRequestUri']->getPath();

        if (array_key_exists('DataServiceVersion', $this->_hostInfo)) {
            $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_DATA_SERVICE_VERSION)] = $this->_hostInfo['DataServiceVersion']->toString();
        }

        if (array_key_exists('MaxDataServiceVersion', $this->_hostInfo)) {
            $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_MAX_DATA_SERVICE_VERSION)] = $this->_hostInfo['MaxDataServiceVersion']->toString();
        }

        if (array_key_exists('RequestIfMatch', $this->_hostInfo)) {
            $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_IF_MATCH)] = $this->_hostInfo['RequestIfMatch'];
        }

        if (array_key_exists('RequestIfNoneMatch', $this->_hostInfo)) {
            $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_IF_NONE)]= $this->_hostInfo['RequestIfNoneMatch'];
            
        }

        if (array_key_exists('QueryString', $this->_hostInfo)) {
            $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = $this->_hostInfo['QueryString'];
        }
        //print_r($_SERVER); 
        parse_str($hostInfo['QueryString'], $_GET);
        parse_str($hostInfo['QueryString'], $_REQUEST);
        parent::__construct(null, new Request($_GET, $_REQUEST, array(), array(), $_FILES, $_SERVER, null));

        if (array_key_exists('AbsoluteServiceUri', $this->_hostInfo)) {
            $this->setServiceUri($this->_hostInfo['AbsoluteServiceUri']->getUrlAsString());

        }
    }
}