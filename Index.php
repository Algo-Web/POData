<?php


require_once __DIR__  .'vendor/autoload.php';
require_once 'Dispatcher.php';

/**
 * Initial entry point for all the request to the library.
 *
 */
$dispatcher = new Dispatcher();
$dispatcher->dispatch();