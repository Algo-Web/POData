<?php
/** 
 * Exception class for invalid operations
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
 * Exception class for invalid operations
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Common
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class InvalidOperationException extends \Exception
{
    /**
     * Creates new instance of InvalidOperationException
     * 
     * @param string $message The error message 
     */
    public function __construct($message) 
    {
        parent::__construct($message);
    }
}
?>