<?php
/** 
 * The base DataService (DataService.php) should implement this interface so
 * that the Dispatcher can invoke handleRequest method upon receiving any
 * Request to a data service.
 * 

 */
namespace ODataProducer;
/**
 * Interface for Request handling
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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