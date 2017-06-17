<?php

namespace UnitTests\POData\Facets;

/*
 * Note: This is a dummy class for making the testing of
 * BaseService and UriProcessor.
 */

use Illuminate\Http\Request;
use POData\Common\ODataConstants;
use POData\HttpProcessUtility;
use POData\OperationContext\ServiceHost;

class ServiceHostTestFake extends ServiceHost
{
    private $hostInfo;

    public function __construct(array $hostInfo)
    {
        $this->hostInfo = $hostInfo;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL] = $this->hostInfo['AbsoluteRequestUri']->getScheme();
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = $this->hostInfo['AbsoluteRequestUri']->getHost().':'.$this->hostInfo['AbsoluteRequestUri']->getPort();
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = $this->hostInfo['AbsoluteRequestUri']->getPath();

        if (array_key_exists('DataServiceVersion', $this->hostInfo)) {
            $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_DATA_SERVICE_VERSION)]
                = $this->hostInfo['DataServiceVersion']->toString();
        }

        if (array_key_exists('MaxDataServiceVersion', $this->hostInfo)) {
            $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_MAX_DATA_SERVICE_VERSION)]
                = $this->hostInfo['MaxDataServiceVersion']->toString();
        }

        if (array_key_exists('RequestIfMatch', $this->hostInfo)) {
            $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_IF_MATCH)]
                = $this->hostInfo['RequestIfMatch'];
        }

        if (array_key_exists('RequestIfNoneMatch', $this->hostInfo)) {
            $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_IF_NONE)]
                = $this->hostInfo['RequestIfNoneMatch'];
        }

        if (array_key_exists('QueryString', $this->hostInfo)) {
            $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = $this->hostInfo['QueryString'];
        }
        //print_r($_SERVER);
        parse_str($hostInfo['QueryString'], $_GET);
        parse_str($hostInfo['QueryString'], $_REQUEST);
        parent::__construct(null, new Request($_GET, $_REQUEST, [], [], $_FILES, $_SERVER, null));

        if (array_key_exists('AbsoluteServiceUri', $this->hostInfo)) {
            $this->setServiceUri($this->hostInfo['AbsoluteServiceUri']->getUrlAsString());
        }
    }
}
