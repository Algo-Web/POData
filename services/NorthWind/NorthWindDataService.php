<?php

use POData\Configuration\EntitySetRights;
require_once 'POData\IBaseService.php';
require_once 'POData\IRequestHandler.php';
require_once 'POData\DataService.php';
require_once 'POData\IServiceProvider.php';
use POData\Configuration\ProtocolVersion;
use POData\Configuration\ServiceConfiguration;
use POData\BaseService;
require_once 'NorthWindMetadata.php';
require_once 'NorthWindQueryProvider.php';
require_once 'NorthWindStreamProvider.php';


class NorthWindDataService extends BaseService
{
    private $_northWindMetadata = null;
    private $_northWindQueryProvider = null;
    
    /**
     * This method is called only once to initialize service-wide policies
     * 
     * @param ServiceConfiguration $config Data service configuration object
     * 
     * @return void
     */
    public function initialize(ServiceConfiguration $config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(ProtocolVersion::V3());
    }

    /**
     * Get the service like IMetadataProvider, IDataServiceQueryProvider,
     * IStreamProvider
     * 
     * @param String $serviceType Type of service IMetadataProvider,
     *                            IDataServiceQueryProvider,
     *                            IQueryProvider,
     *                            IStreamProvider
     * 
     * @see library/POData.IServiceProvider::getService()
     * @return object
     */
    public function getService($serviceType)
    {
        if ($serviceType === 'IMetadataProvider') {
            if (is_null($this->_northWindMetadata)) {
                $this->_northWindMetadata = CreateNorthWindMetadata::create();
            }
            
            return $this->_northWindMetadata;
        } else if ($serviceType === 'IQueryProvider') {
            if (is_null($this->_northWindQueryProvider)) {
                $this->_northWindQueryProvider = new NorthWindQueryProvider();
            }
            return $this->_northWindQueryProvider;
        } else if ($serviceType === 'IStreamProvider') {
            return new NorthWindStreamProvider();
        }
        return null;
    }
}