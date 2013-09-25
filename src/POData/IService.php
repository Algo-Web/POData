<?php

namespace POData;

use POData\OperationContext\ServiceHost;
use POData\Providers\MetadataQueryProviderWrapper;
use POData\Configuration\IServiceConfiguration;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\OperationContext\IServiceHost;
use POData\OperationContext\IOperationContext;
use POData\Writers\ServiceDocumentWriterFactory;
use POData\Writers\ODataWriterFactory;

/**
 * Class IService
 *
 * The base BaseService (BaseService.php) should implement this interface
 * to make sure access to all providers and Operation context are available.
 *
 * @package POData
 */
interface IService
{
    /**
     * This method is called only once to initialize service-wide policies.
     * 
     * @param IServiceConfiguration $config data service configuration
     * 
     * @return void
     */
    public function initializeService(IServiceConfiguration $config);

    /**
     * Gets reference to the configuration class to access the
     * configuration set by the developer.
     * 
     * @return IServiceConfiguration
     */
    public function getServiceConfiguration();

    /**
     * Gets reference to wrapper class instance over IDSQP and IDSMP 
     * implementation
     * 
     * @return MetadataQueryProviderWrapper
     */
    public function getMetadataQueryProviderWrapper();

    /**
     * Gets reference to wrapper class instance over IDSSP implementation.
     * 
     * @return StreamProviderWrapper
     */
    public function getStreamProviderWrapper();

    /**
     * To set reference to the ServiceHost instance created by the dispathcer.
     *
     * @param ServiceHost $serviceHost data service host
     * 
     * @return void
     */
    public function setHost(ServiceHost $serviceHost);

    /**
     * Hold reference to the ServiceHost instance created by dispatcher,
     * using this library can access headers and body of Http Request 
     * dispatcher received and the Http Response Dispatcher is going to send.
     * 
     * @return IServiceHost
     */
    public function getHost();
    
    /**
     * To get reference to operation context where we have direct access to
     * headers and body of Http Request we have received and the Http Response
     * We are going to send.
     * 
     * @return IOperationContext
     */
    public function getOperationContext();

	/**
	 * Returns the ServiceDocumentWriterFactory to use when writing the response to a service document request
	 * @return ServiceDocumentWriterFactory
	 */
	public function getServiceDocumentWriterFactory();


	/**
	 * Returns the ODataWriterFactory to use when writing the response to a service document request
	 * @return ODataWriterFactory
	 */
	public function getODataWriterFactory();
}