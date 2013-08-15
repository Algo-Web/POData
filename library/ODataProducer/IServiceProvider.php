<?php

namespace ODataProducer;

/**
 * Class IServiceProvider
 * @package ODataProducer
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