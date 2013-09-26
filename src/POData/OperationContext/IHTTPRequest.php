<?php

namespace POData\OperationContext;

use POData\Common\ODataException;
use POData\Common\ODataConstants;
use POData\Common\Url;


/**
 * @package POData\OperationContext
 */
interface IHTTPRequest
{

    /**
     * get the raw incoming url
     * 
     * @return string RequestURI called by User with the value of QueryString
     */  
    public function getRawUrl();

    /**
     * get the specific request headers
     * 
     * @param string $key The header name
     * 
     * @return string|null value of the header, NULL if header is absent.
     */
    public function getRequestHeader($key);


    /**
     * Returns the Query String Parameters (QSPs) as an array of KEY-VALUE pairs.  If a QSP appears twice
     * it will have two entries in this array
     * 
     * @return array[]
     */
    public function getQueryParameters();


    
    /**
     * Get the HTTP method/verb of the HTTP Request
     *
     * @return HTTPRequestMethod
     */
    public function getMethod();


    /**
     * To change the request accept type header in the request.
     * Note: This method will be used only when client specified $format query option.
     * Any subsequent call to getRequestHeader("HTTP_ACCEPT") must return the value set with this call
     *
     * @param string $mime The mime value.
     * 
     * @return void
     */
    public function setRequestAccept($mime);
}