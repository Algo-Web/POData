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
class NorthWindDataService2 extends DataService2 implements IServiceProvider
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
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(DataServiceProtocolVersion::V2);
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

    /**
     * This method will be called to verify that DSExpressionProvider is 
     * implemented by the end-developer or not
     * 
     * @return object
     */
    public function &getExpressionProvider()
    {
    	return null;
    }
}
?>