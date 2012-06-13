<?php
/** 
 * Exception class for OData
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
namespace ODataProducer\Common;
/**
 * Class for OData Exception
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ODataException extends \Exception
{
    /**
     * The error code
     * 
     * @var int
     */
    private $_errorCode;

    /**
     * The HTTP status code
     * 
     * @var int
     */
    private $_statusCode;
   
    /**
     * Create new instance of ODataException
     * 
     * @param string $message    The error message
     * @param string $statusCode The status code
     * @param string $errorCode  The error code
     * 
     * @return nothing
     */
    public function __construct($message, $statusCode, $errorCode= null)
    {
        $this->_errorCode = $errorCode;
        $this->_statusCode = $statusCode;        
        parent::__construct($message, $errorCode);
    }

    /**
     * Get the status code
     * 
     * @return int
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    /**
     * Creates and throws an instance of ODataException 
     * representing HTTP bad request error
     * 
     * @param string $message The error message
     * 
     * @throws ODataException
     * @return nothing
     */
    public static function createBadRequestError($message)
    {
        throw new ODataException($message, 400);
    } 

    /**
     * Creates and throws an instance of ODataException 
     * representing syntax error in the query
     * 
     * @param string $message The error message
     * 
     * @throws ODataException
     * @return nothing
     */    
    public static function createSyntaxError($message)
    {
        self::createBadRequestError($message);
    }

    /**
     * Creates and throws an instance of ODataException when a 
     * resource represented by a segment in the url is not found
     * 
     * @param String $segment The segment in the url for which corrosponding
     * resource not present in the data source
     *  
     * @throws ODataException
     * @return nothing
     */
    public static function createResourceNotFoundError($segment)
    {
        throw new ODataException(Messages::uriProcessorResourceNotFound($segment), 404);
    }

    /**
     * Creates and throws an instance of ODataException when a 
     * resouce not found in the data source
     * 
     * @param string $message The error message
     * 
     * @throws ODataException
     * @return nothing
     */
    public static function resourceNotFoundError($message)
    {
        throw new ODataException($message, 404);
    }

    /**
     * Creates and throws an instance of ODataException when some
     * internal error happens in the library
     * 
     * @param string $message The detailed internal error message
     * 
     * @throws ODataException
     * @return nothing
     */
    public static function createInternalServerError($message)
    {
        throw new ODataException($message, 500);
    }

    /**
     * Creates and throws an instance of ODataException when requestor tries to
     * access a resource which is forbidden
     * 
     * @throws ODataException
     * @return nothing
     */
    public static function createForbiddenError()
    {
        throw new ODataException(Messages::uriProcessorForbidden(), 403);
    }

    /**
     * Creates a new exception to indicate Precondition error.
     * 
     * @param string $message Error message for this exception
     * 
     * @throws ODataException
     * @return nothing
     */
    public static function createPreConditionFailedError($message)
    {
        throw new ODataException($message, 412);
    }

    /**
     * Creates a new exception when requestor ask for a service facility
     * which is not implemented by this library.
     * 
     * @param string $message Error message for this exception
     * 
     * @throws ODataException
     * @return nothing
     */
    public static function createNotImplementedError($message)
    {
        throw new ODataException($message, 501);
    }

    /**
     * Creates and throws an instance of ODataException when requestor to
     * set value which is not allowed
     * 
     * @param string $message Error message for this exception
     * 
     * @throws ODataException
     * @return nothing
     */
    public static function notAcceptableError($message)
    {
        throw new ODataException($message, 406);
    }    
    
}
?>