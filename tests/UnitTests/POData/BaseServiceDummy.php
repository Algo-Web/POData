<?php

namespace UnitTests\POData;

use POData\BaseService;
use POData\Configuration\IServiceConfiguration;
use POData\ObjectModel\IObjectSerialiser;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Stream\StreamProviderWrapper;
use UnitTests\POData\Facets\BaseServiceTestWrapper;

class BaseServiceDummy extends BaseServiceTestWrapper
{
    /**
     * @var IMetadataProvider
     */
    protected $metaProvider;

    /**
     * @var IQueryProvider
     */
    protected $queryProvider;

    public $maxPageSize = 200;

    public function __construct(
        IQueryProvider $db = null,
        ServiceHost $host = null,
        IObjectSerialiser $serialiser = null,
        StreamProviderWrapper $provider = null,
        IMetadataProvider $metaProvider = null,
        IServiceConfiguration $config = null
    ) {
        $this->metaProvider = $metaProvider;
        $this->queryProvider = $db;
        $provider->setService($this);
        $this->streamProvider = $provider;
        $this->setHost($host);
        $this->config = $config;
        parent::__construct($serialiser);
    }

    /**
     * @return IQueryProvider
     */
    public function getQueryProvider()
    {
        return $this->queryProvider;
    }

    /**
     * @return IMetadataProvider
     */
    public function getMetadataProvider()
    {
        return $this->metaProvider;
    }

    /**
     * @return \POData\Providers\Stream\IStreamProvider
     */
    public function getStreamProviderX()
    {
        // TODO: Implement getStreamProviderX() method.
    }

    /**
     * This method is called only once to initialize service-wide policies.
     *
     * @param IServiceConfiguration $config data service configuration
     */
    public function initialize(IServiceConfiguration $config)
    {
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
    }

    public function handleRequest()
    {
        parent::handleRequest();
        $outbound = $this->getHost()->getOperationContext()->outgoingResponse();
        return $outbound->getStream();
    }

    public function compareETag(
        &$entryObject,
        ResourceType &$resourceType,
        &$needToSerializeResponse
    ) {
        return parent::compareETag($entryObject, $resourceType, $needToSerializeResponse);
    }

    public function getETagForEntry(&$entryObject, ResourceType &$resourceType)
    {
        return parent::getETagForEntry($entryObject, $resourceType);
    }
}