<?php

use POData\Configuration\EntitySetRights;
require_once 'POData\IDataService.php';
require_once 'POData\IRequestHandler.php';
require_once 'POData\DataService.php';
require_once 'POData\IServiceProvider.php';
use POData\Configuration\DataServiceProtocolVersion;
use POData\Configuration\DataServiceConfiguration;
use POData\IServiceProvider;
use POData\DataService;
require_once 'NorthWindMetadata.php';
require_once 'NorthWindQueryProvider.php';
require_once 'NorthWindStreamProvider.php';


class NorthWindDataService extends DataService implements IServiceProvider
{
    private $_northWindMetadata = null;
    private $_northWindQueryProvider = null;
    
    /**
     * This method is called only once to initialize service-wide policies
     * 
     * @param DataServiceConfiguration &$config Data service configuration object
     * 
     * @return void
     */
    public function initializeService(DataServiceConfiguration &$config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(DataServiceProtocolVersion::V3);
    }

    /**
     * Get the service like IDataServiceMetadataProvider, IDataServiceQueryProvider,
     * IDataServiceStreamProvider
     * 
     * @param String $serviceType Type of service IDataServiceMetadataProvider, 
     *                            IDataServiceQueryProvider,
     *                            IDataServiceQueryProvider2,
     *                            IDataServiceStreamProvider
     * 
     * @see library/POData.IServiceProvider::getService()
     * @return object
     */
    public function getService($serviceType)
    {
        if ($serviceType === 'IDataServiceMetadataProvider') {
            if (is_null($this->_northWindMetadata)) {
                $this->_northWindMetadata = CreateNorthWindMetadata::create();
            }
            
            return $this->_northWindMetadata;
        } else if ($serviceType === 'IDataServiceQueryProvider2') {
            if (is_null($this->_northWindQueryProvider)) {
                $this->_northWindQueryProvider = new NorthWindQueryProvider();
            }
            return $this->_northWindQueryProvider;
        } else if ($serviceType === 'IDataServiceStreamProvider') {
            return new NorthWindStreamProvider();
        }
        return null;
    }
}