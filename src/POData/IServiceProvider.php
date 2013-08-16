<?php

namespace POData;

/**
 * Class IServiceProvider
 * @package POData
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