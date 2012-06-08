<?php
/** 
 * The base DataService (DataService.php) should implement this interface so
 * that the Dispatcher can invoke handleRequest method upon receiving any
 * Request to a data service.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer;
/**
 * Interface for Request handling
 * 
 * @category  ODataProducer
 * @package   ODataProducer
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
interface IRequestHandler
{
    /**
     * Handler method invoked by Dispatcher. Dispatcher call this method when
     * it receives a Request to the service represented by the class which 
     * implements this function.
     * 
     * @return void
     */
    public function handleRequest();
}
?>