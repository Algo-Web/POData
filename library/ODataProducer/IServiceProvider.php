<?php
/** 
 * Defines a mechanism for retrieving a service object; that is, an 
 * object that provides custom support to other objects.
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
 * Interface for Service provider
 * 
 * @category  ODataProducer
 * @package   ODataProducer
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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