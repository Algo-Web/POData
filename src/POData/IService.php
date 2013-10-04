<?php

namespace POData;

use POData\OperationContext\ServiceHost;
use POData\Providers\ProvidersWrapper;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\OperationContext\IOperationContext;
use POData\Writers\ServiceDocumentWriterFactory;
use POData\Writers\ODataWriterFactory;


interface IService
{
    /**
     * This method is called only once to initialize service-wide policies.
     * 
     * @param ServiceConfiguration $config data service configuration
     * 
     */
    public function initialize(ServiceConfiguration $config);

    /**
     * Gets reference to the configuration class to access the
     * configuration set by the developer.
     * 
     * @return ServiceConfiguration
     */
    public function getConfiguration();

    /**
     * Gets reference to wrapper class instance over IDSQP and IDSMP 
     * implementation
     * 
     * @return ProvidersWrapper
     */
    public function getProvidersWrapper();

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
     * @return ServiceHost
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
	 * Returns the ODataWriterFactory to use when writing the response to a service document request
	 * @return ODataWriterFactory
	 */
	public function getODataWriterFactory();
}