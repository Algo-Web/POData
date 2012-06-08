<?php
/** 
 * Type to represent the exception thrown while processing request headers.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Common
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Common;
/** 
 * Http Header Failure class
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Common
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class HttpHeaderFailure extends \Exception
{
    private $_statusCode;
    
    /**
     * Creates new instance of HttpHeaderFailure
     * 
     * @param String $message    Error message
     * @param Int    $statusCode Http status code
     * @param Int    $errorCode  Http error code
     * 
     * @return nothing
     */
    public function  __construct($message, $statusCode, $errorCode = null) 
    {
        $this->_statusCode = $statusCode;
        parent::__construct($message, $errorCode);
    }
    
    /**
     * Get the status code
     * 
     * @return Int
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }
}
?>