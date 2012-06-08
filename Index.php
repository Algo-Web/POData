<?php
/**
 * Initial entry point for all the request to the library.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
require_once dirname(__FILE__).'/library/ODataProducer/Common/ClassAutoLoader.php';
require_once 'Dispatcher.php';
use ODataProducer\Common\ClassAutoLoader;
ClassAutoLoader::register();
/**
 * Initial entry point for all the request to the library.
 * 
 * @category  ODataProducer
 * @package   ODataProducer
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
$dispatcher = new Dispatcher();
$dispatcher->dispatch();
?>