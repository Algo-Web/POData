<?php
use ODataProducer\Configuration\EntitySetRights;
require_once 'ODataProducer\IDataService.php';
require_once 'ODataProducer\IRequestHandler.php';
require_once 'ODataProducer\DataService.php';
require_once 'ODataProducer\IServiceProvider.php';
use ODataProducer\Configuration\DataServiceProtocolVersion;
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\IServiceProvider;
use ODataProducer\DataService;
require_once 'NorthWindMetadata.php';
require_once 'DataService2.php';
require_once 'NorthWindQueryProvider.php';
class NorthWindDataServiceV1 extends DataService2 implements IServiceProvider
{
    private $_northWindMetadata = null;
    
    /**
     * This method is called only once to initialize service-wide policies
     * 
     * @param DataServiceConfiguration $config
     */
    public function initializeService(DataServiceConfiguration &$config)
    {
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        //we are using V1 protocol, but still we set page size because with
        //a top value which is less than pagesize we can use V1 protocol 
        //even though paging is enabled.
        $config->setEntitySetPageSize('*', 5);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(DataServiceProtocolVersion::V1);
    }

    /**
     * 
     * @see library/ODataProducer/ODataProducer.IServiceProvider::getService()
     * 
     * @return object
     */
    public function getService($serviceType)
    {
        if ($serviceType === 'IDataServiceMetadataProvider') {
            if (is_null($this->_northWindMetadata)) {
                $this->_northWindMetadata = CreateNorthWindMetadata3::Create();
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
?>