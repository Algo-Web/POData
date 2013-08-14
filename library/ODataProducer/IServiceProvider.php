<?php
/** 
 * Defines a mechanism for retrieving a service object; that is, an 
 * object that provides custom support to other objects.
 * 

 */
namespace ODataProducer;
/**
 * Interface for Service provider
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
interface IServiceProvider
{
    /**
     * Get service object
     * 
     * @param String $serviceType type of service
     * 
     * @return void
     */
    public function getService($serviceType);
}
?>