<?php

namespace UnitTests\POData\Facets\WordPress2;


use ODataProducer\Configuration\EntitySetRights;
use ODataProducer\IDataService;
use ODataProducer\IRequestHandler;
use ODataProducer\Configuration\DataServiceProtocolVersion;
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\IServiceProvider;
use ODataProducer\DataService;
use ODataProducer\OperationContext\DataServiceHost;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
use ODataProducer\UriProcessor\UriProcessor;



class WordPressDataService extends DataService implements IServiceProvider
{
    private $_wordPressMetadata = null;
    private $_wordPressQueryProvider = null;
    private $_wordPressExpressionProvider = null;
    
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
     *                            IDataServiceStreamProvider
     * 
     * @see library/ODataProducer/ODataProducer.IServiceProvider::getService()
     * @return object
     */
    public function getService($serviceType)
    {
    	if(($serviceType === 'IDataServiceMetadataProvider') || 
    		($serviceType === 'IDataServiceQueryProvider2') ||
    		($serviceType === 'IDataServiceStreamProvider')) {
    		if (is_null($this->_wordPressExpressionProvider)) {
				$this->_wordPressExpressionProvider = new WordPressDSExpressionProvider();    			
    		}    	
    	}
        if ($serviceType === 'IDataServiceMetadataProvider') {
            if (is_null($this->_wordPressMetadata)) {
                $this->_wordPressMetadata = WordPressMetadata::create();
                // $this->_wordPressMetadata->mappedDetails = CreateWordPressMetadata::mappingInitialize();
            }
            return $this->_wordPressMetadata;
        } else if ($serviceType === 'IDataServiceQueryProvider2') {
            if (is_null($this->_wordPressQueryProvider)) {
                $this->_wordPressQueryProvider = new WordPressQueryProvider();
            }
            return $this->_wordPressQueryProvider;
        } else if ($serviceType === 'IDataServiceStreamProvider') {
            return new WordPressStreamProvider();
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