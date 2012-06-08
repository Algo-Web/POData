<?php
/**
 * Provide access to the current HTTP context over WebOperationContext::Current()
 * method.  This is a singleton class. Class represents the HTTP methods,headers 
 * and stream associated with a HTTP request and HTTP response
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_OperationContext_Web
 * @author    Anu T Chandy <odataphpproducer@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\OperationContext\Web;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\ODataException;
use ODataProducer\OperationContext\Web\IncomingRequest;
use ODataProducer\OperationContext\Web\OutgoingResponse;
/**
 * Class which is used to get all the HTTP header detail for a IncomingRequest 
 * and we can set the header also before sending the OutgoingResponse
 * 
 * @category  ODataProducer
 * @package   ODataProducer_OperationContext_Web
 * @author    Anu T Chandy <odataphpproducer@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class WebOperationContext
{
    /**
     * Current context
     * 
     * @var WebOperationContext
     */
    private  static $_context = null;
    
    /**
     * Object of IncomingRequest which is needed to get all the HTTP headers info
     * 
     * @var IncomingWebRequestContext
     */
    private $_incomingRequest;
    
    /**
     * Object of OutgoingResponse which is needed to get all the HTTP headers info
     * 
     * @var OutgoingWebRequestContext
     */
    private $_outgoingResponse;
    
    /**
     * Method which is needed to make this class as singleton class 
     * It always provides the object which is already existed,if it is there 
     * or create a new object of WebOperationCVontext class if no instance was 
     * available of this class 
     * 
     * @return WebOperationContext Current web operation context
     */
    public static function current()
    {
        if (empty(self::$_context)) {
            self::$_context = new WebOperationContext();
        }

        return  self::$_context;
    }
    
    /**
     * The clone method is private, so it can't be call from outside of the class
     * 
     * @return void
     * 
     * $throws Exception if developer try to make a clone of WebOperationContext 
     *                   class.
     */ 
    public function __clone()
    {
        throw ODataException::notAcceptableError(
            "Cloning of WebOperationContext is not allowed!!!"
        );
    }
    
    /**
     * The constructor is protected, only through ‘Current’,method 
     * one can access the context.
     * Initializes a new instance of the WebOperationContext class. 
     * This function will perform the following tasks:
     *  (1) Retrieve the current HTTP method,headers and stream. 
     *  (2) Populate $_incomingRequest using these. 
     */
    private function __construct()
    {
        $this->_incomingRequest = new IncomingRequest();
        $this->_outgoingResponse = new OutgoingResponse();
    }
    
    /**
     * Gets the Web request context for the request being sent.
     * 
     * @return reference of OutgoingResponse object
     */
    public function &outgoingResponse()
    {
        return $this->_outgoingResponse;
    }
    
    /**
     * Gets the Web request context for the request being received.
     * 
     * @return reference of IncomingRequest object
     */
    public function &incomingRequest()
    {
        return $this->_incomingRequest;
    }

    /**
     * This is an internal method to reset the conotext.
     * Note: This is added for testing, end user is not
     * supposed to use this function.
     * 
     * @return void
     */
    public function resetWebContextInternal()
    {
        self::$_context = null;
    }
}
?>