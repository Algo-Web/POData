<?php

namespace UnitTests\POData\Facets\NorthWind1;


use POData\Configuration\EntitySetRights;
use POData\IDataService;
use POData\IRequestHandler;
use POData\Configuration\DataServiceProtocolVersion;
use POData\Configuration\DataServiceConfiguration;
use POData\IServiceProvider;
use POData\DataService;



class NorthWindDataServiceV3 extends DataService2 implements IServiceProvider
{
    private $_northWindMetadata = null;
    
    /**
     * This method is called only once to initialize service-wide policies
     * 
     * @param DataServiceConfiguration $config
     */
    public function initializeService(DataServiceConfiguration &$config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $config->setAcceptCountRequests(true);
        //Disable projection request for testing purpose
        $config->setAcceptProjectionRequests(false);
        $config->setMaxDataServiceVersion(DataServiceProtocolVersion::V3);
    }

    /**
     * 
     * @see library/POData.IServiceProvider::getService()
     * 
     * @return object
     */
    public function getService($serviceType)
    {
        if ($serviceType === 'IDataServiceMetadataProvider') {
            if (is_null($this->_northWindMetadata)) {
                $this->_northWindMetadata = NorthWindMetadata::Create();
            }

            return $this->_northWindMetadata;
        } else if ($serviceType === 'IDataServiceQueryProvider') {
            return new NorthWindQueryProvider2();
        } else if ($serviceType === 'IDataServiceStreamProvider') {
            return null;
        }

        return null;
    }    
}