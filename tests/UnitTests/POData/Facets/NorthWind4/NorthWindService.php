<?php

namespace UnitTests\POData\Facets\NorthWind4;

use POData\BaseService;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\IServiceConfiguration;
use POData\Configuration\ProtocolVersion;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\ServiceHost;
use POData\UriProcessor\UriProcessor;

class NorthWindService extends BaseService
{
    private $_northWindMetadata = null;
    private $_northWindQueryProvider = null;
    //private $_serviceHost;

    public function __construct(ServiceHost $serviceHost)
    {
        $this->setHost($serviceHost);
        parent::__construct(null);
    }

    /**
     * This method is called only once to initialize service-wide policies.
     *
     * @param IServiceConfiguration $config Data service configuration object
     */
    public function initialize(IServiceConfiguration $config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(ProtocolVersion::V3());
    }

    /**
     * @return \POData\Providers\Metadata\IMetadataProvider
     */
    public function getMetadataProvider()
    {
        return NorthWindMetadata::Create();
    }

    /**
     * @return \POData\Providers\Query\IQueryProvider
     */
    public function getQueryProvider()
    {
        if (is_null($this->_northWindQueryProvider)) {
            $this->_northWindQueryProvider = new NorthWindQueryProvider4();
        }

        return $this->_northWindQueryProvider;
    }

    /**
     * @return \POData\Providers\Stream\IStreamProvider
     */
    public function getStreamProviderX()
    {
        return new NorthWindStreamProvider4();
    }

    // For testing we overridden the BaseService::handleRequest method, one thing is the
    // private member variable BaseService::_dataServiceHost is not accessible in this class,
    // so we are using getHost() below.
    public function handleRequest()
    {
        $request = $this->getOperationContext()->incomingRequest();
        $this->createProviders();
        $this->getHost()->validateQueryParameters();
        $requestMethod = $request->getMethod();
        if ($requestMethod != HTTPRequestMethod::GET()) {
            throw ODataException::createNotImplementedError(Messages::onlyReadSupport($requestMethod));
        }

        $result = UriProcessor::process($this);
        $this->objectSerialiser->setRequest($result->getRequest());

        return $result;
    }
}
