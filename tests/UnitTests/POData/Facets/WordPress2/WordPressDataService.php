<?php

namespace UnitTests\POData\Facets\WordPress2;


use POData\Configuration\EntitySetRights;
use POData\IService;
use POData\IRequestHandler;
use POData\Configuration\ServiceProtocolVersion;
use POData\Configuration\IServiceConfiguration;

use POData\BaseService;
use POData\OperationContext\ServiceHost;
use POData\Common\ODataException;
use POData\Common\ODataConstants;
use POData\Common\Messages;
use POData\UriProcessor\UriProcessor;
use Symfony\Component\Config\Definition\Exception\Exception;

class WordPressDataService extends BaseService
{
    private $_wordPressMetadata = null;
    private $_wordPressQueryProvider = null;
    private $_wordPressExpressionProvider = null;


    /**
     * This method is called only once to initialize service-wide policies
     * 
     * @param IServiceConfiguration $config Data service configuration object
     * 
     * @return void
     */
    public function initializeService(IServiceConfiguration $config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(ServiceProtocolVersion::V3);
    }


	/**
	 * @return \POData\Providers\Metadata\IMetadataProvider
	 */
	public function getMetadataProvider()
	{
		if (is_null($this->_wordPressMetadata)) {
			$this->_wordPressMetadata = WordPressMetadata::create();
			// $this->_wordPressMetadata->mappedDetails = CreateWordPressMetadata::mappingInitialize();
		}
		return $this->_wordPressMetadata;
	}

	/**
	 * @return \POData\Providers\Query\IQueryProvider
	 */
	public function getQueryProvider()
	{
		if (is_null($this->_wordPressQueryProvider)) {
			$this->_wordPressQueryProvider = new WordPressQueryProvider();
		}
		return $this->_wordPressQueryProvider;
	}

	/**
	 * @return \POData\Providers\Stream\IStreamProvider
	 */
	public function getStreamProviderX()
	{
		throw new Exception("not implemented");
	}



    
    // For testing we overridden the BaseService::handleRequest method, one thing is the
    // private member variable BaseService::_dataServiceHost is not accessible in this class,
    // so we are using getHost() below.
    public function handleRequest()
    {
    	try {
    		$this->createProviders();
    		$this->getHost()->validateQueryParameters();
    		$requestMethod = $this->getOperationContext()->incomingRequest()->getMethod();
    		if ($requestMethod !== ODataConstants::HTTP_METHOD_GET) {
    			ODataException::createNotImplementedError(Messages::onlyReadSupport($requestMethod));
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