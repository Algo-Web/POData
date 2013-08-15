<?php


require_once dirname(__FILE__).'/library/ODataProducer/Common/ClassAutoLoader.php';
require_once 'Dispatcher.php';

use ODataProducer\Common\ClassAutoLoader;
ClassAutoLoader::register();

/**
 * Initial entry point for all the request to the library.
 *
 */
$dispatcher = new Dispatcher();
$dispatcher->dispatch();