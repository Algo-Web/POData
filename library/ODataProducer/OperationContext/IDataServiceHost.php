<?php
/**
 * DataServiceHost class implements this interface and it uses WebOperationContext
 * and expose the same details using this interface methods.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_OperationContext
 * @author    Anu T Chandy <odataphpproducer@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\OperationContext;
/**
 * interface for DataServiceHost
 * 
 * @category  ODataProducer
 * @package   ODataProducer_OperationContext
 * @author    Anu T Chandy <odataphpproducer@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
interface IDataServiceHost
{
    /**
     * Gets reference to the operation context.
     * 
     * @return WebOperationContext
     */
    public function &getWebOperationContext();

    /**
      * Gets the absolute request Uri 
      * 
      * @return Url
      */
    public function getAbsoluteRequestUri();

    /**
     * Gets the absolute request Uri as string
     * Note: This will not contain query string
     * 
     * @return string
     */
    public function getAbsoluteRequestUriAsString();

    /**
     * Gets the absolute Uri to the service.
     * Note: This will be the value taken from configuration file.
     * 
     * @return Url
     */
    public function getAbsoluteServiceUri();

    /**
     * Gets the absolute service Uri as string
     * Note: This will be the value taken from configuration file.
     * 
     * @return string
     */
    public function getAbsoluteServiceUriAsString();
       
    /**
     * Get comma separated list of client-supported MIME Accept types
     * 
     * @return string
     */
    public function getRequestAccept();
        
    /**
     * Get the character set encoding that the client requested
     * 
     * @return string
     */
    public function getRequestAcceptCharSet();
        
    /**
     * Gets the MIME type of the  request stream
     * 
     * @return string
     */
    public function getRequestContentType();

    /**
     * To change the request accept type header in the request.
     * Note: This method will be used when client specified $format
     * query option.
     * 
     * @param string $mimeType MIME to set.
     * 
     * @return void
     */
    public function setRequestAccept($mimeType);

    /**
     * Gets the MIME type of the  request length
     * 
     * @return string
     */
    public function getRequestContentLength();
    
    /**
     * Gets the HTTP verb used by the client
     * 
     * @return string
     */
    public function getRequestHttpMethod();
        
    /**
     * Get the value of If-Match header of the request
     * 
     * @return string 
     */
    public function getRequestIfMatch();
        
    /**
     * Gets the value of If-None-Match header of the request
     * 
     * @return string
     */
    public function getRequestIfNoneMatch();      
      
    /**
     * Gets the value of MaxDataServiceVersion header of the request
     * 
     * @return  string
     */
    public function getRequestMaxVersion();     

    /**
     * Gets the value for the DataServiceVersion header of the request.
     * 
     * @return string
     */
    public function getRequestVersion();

    /**
     * Gets the value for the specified item in the request query string
     * 
     * @param string $item The query item to get the value of.
     * 
     * @return string The value for the specified item in the request query 
     *                string null if $item not found
     */
    public function getQueryStringItem($item);

    /**
     * Get the request headers
     * 
     * @return array<headername, headerValue>
     */
    public function &getRequestHeaders();

    /**
     * Set the Cache-Control header on the response
     * 
     * @param string $value The cache-control value.
     * 
     * @return void
     */
    public function setResponseCacheControl($value);

    /**
     * Gets the HTTP MIME type of the output stream
     * 
     * @return string
     */
    public function getResponseContentType();

    /**
     * Sets the HTTP MIME type of the output stream
     * 
     * @param string $value The HTTP MIME type
     * 
     * @return void
     */
    public function setResponseContentType($value);

    /**
     * Sets the content length of the output stream
     * 
     * @param string $value The content length
     * 
     * @return void
     */
    public function setResponseContentLength($value);

    /**
     * Gets the value of the ETag header on the response
     * 
     * @return string
     */
    public function getResponseETag();

    /**
     * Sets the value of the ETag header on the response
     * 
     * @param string $value The ETag value
     * 
     * @return void
     */
    public function setResponseETag($value);

    /**
     * Sets the value Location header on the response
     * 
     * @param string $value The location.
     * 
     * @return void
     */
    public function setResponseLocation($value);

    /**
     * Sets the value status code header on the response
     * 
     * @param string $value The status code
     * 
     * @return void
     */
    public function setResponseStatusCode($value);

    /**
     * Sets the value stream to be send a response
     * 
     * @param string &$value The stream
     * 
     * @return void
     */
    public function setResponseStream(&$value);

    /**
     * Sets the DataServiceVersion response header
     * 
     * @param string $value The version
     * 
     * @return void
     */
    public function setResponseVersion($value);

    /**
     * Add a header to response header collection
     * 
     * @param string $headerName  The name of the header
     * @param string $headerValue The value of the header
     * 
     * @return void
    */
    public function addResponseHeader($headerName, $headerValue);  

    /**
     * Get the response headers
     * 
     * @return array<headername, headerValue>
     */
    public function &getResponseHeaders();

    /**
    * Add a header to validate the DataServiceVersion and 
    * MaxDataServiceVersion of the request
    * 
    * @return void
    * 
    * @throws ODataException
    */
    public function validateQueryParameters();
}