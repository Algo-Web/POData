<?php

namespace POData;

use POData\Configuration\IServiceConfiguration;
use POData\Configuration\ServiceConfiguration;
use POData\Configuration\EntitySetRights;
use POData\ObjectModel\IObjectSerialiser;
use POData\ObjectModel\ObjectModelSerializer;
use POData\Providers\Stream\IStreamProvider;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Metadata\SimpleMetadataProvider;

/**
 * DataService that implements IServiceProvider.
 **/
class SimpleDataService extends BaseService implements IService
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

    public function __construct($db, SimpleMetadataProvider $metaProvider, IObjectSerialiser $serialiser = null)
    {
        $this->metaProvider = $metaProvider;
        if ($db instanceof IQueryProvider) {
            $this->queryProvider = $db;
        } elseif (!empty($db->queryProviderClassName)) {
            $queryProviderClassName = $db->queryProviderClassName;
            $this->queryProvider = new $queryProviderClassName($db);
        } else {
            $this->queryProvider = new QueryProvider($db);
        }
        parent::__construct($serialiser);
    }

    public function initialize(ServiceConfiguration $config)
    {
        $config->setEntitySetPageSize('*', $this->maxPageSize);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
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
    public function initializeService(IServiceConfiguration $config)
    {
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
    }
}
