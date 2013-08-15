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
use ODataProducer\OperationContext\DataServiceHost;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
use ODataProducer\UriProcessor\UriProcessor;

require_once 'NorthWindMetadata4.php';
require_once 'NorthWindQueryProvider4.php';
require_once 'NorthWindStreamProvider4.php';

class NorthWindDataService4 extends DataService implements IServiceProvider
{
    private $_northWindMetadata = null;
    private $_northWindQueryProvider = null;
    private $_dataServiceHost;

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
     * @see library/ODataProducer/ODataProducer.IServiceProvider::getService()
     * @return object
     */
    public function getService($serviceType)
    {
        if ($serviceType === 'IDataServiceMetadataProvider') {
            if (is_null($this->_northWindMetadata)) {
                $this->_northWindMetadata = CreateNorthWindMetadata4::create();
            }
            
            return $this->_northWindMetadata;
        } else if ($serviceType === 'IDataServiceQueryProvider2') {
            if (is_null($this->_northWindQueryProvider)) {
                $this->_northWindQueryProvider = new NorthWindQueryProvider4();
            }
            return $this->_northWindQueryProvider;
        } else if ($serviceType === 'IDataServiceStreamProvider') {
            return new NorthWindStreamProvider4();
        }
        return null;
    }
    
    // For testing we overridden the DataService::handleRequest method, one thing is the 
    // private memeber variable DataService::_dataServiceHost is not accessible in this class,
    // so we are using getHost() below.
    public function handleRequest()
    {
    	try {
    		$this->createProviders();
    		$this->getHost()->validateQueryParameters();
    		$requestMethod = $this->getOperationContext()->incomingRequest()->getMethod();
    		if ($requestMethod !== ODataConstants::HTTP_METHOD_GET) {
    			ODataException::createNotImplementedError(Messages::dataServiceOnlyReadSupport($requestMethod));
    		}
    	} catch (\Exception $exception) {
            throw $exception;
    	}
    
    	$uriProcessor = null;
    	try {
    		$uriProcessor = UriProcessor::process($this);
            return $uriProcessor;
    	} catch (\Exception $exception) {
            throw $exception;
    	}
    }
}
?>