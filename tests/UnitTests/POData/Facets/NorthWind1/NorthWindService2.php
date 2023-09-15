<?php

declare(strict_types=1);

namespace UnitTests\POData\Facets\NorthWind1;

use POData\Common\NotImplementedException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\IServiceConfiguration;
use POData\Configuration\ProtocolVersion;
use POData\OperationContext\ServiceHost;
use POData\Providers\Query\IReadQueryProvider;
use UnitTests\POData\Facets\BaseServiceTestWrapper;

class NorthWindService2 extends BaseServiceTestWrapper
{
    private $_northWindMetadata = null;

    public function __construct(ServiceHost $serviceHost)
    {
        $this->setHost($serviceHost);
        parent::__construct(null);
    }

    /**
     * This method is called only once to initialize service-wide policies.
     *
     * @param IServiceConfiguration $config
     */
    public function initialize(IServiceConfiguration $config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL());
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(ProtocolVersion::V2());
    }

    /**
     * @return \POData\Providers\Metadata\IMetadataProvider
     */
    public function getMetadataProvider()
    {
        return NorthWindMetadata::Create();
    }

    /**
     * @return \POData\Providers\Query\IReadQueryProvider
     */
    public function getReadQueryProvider(): ?IReadQueryProvider
    {
        return new NorthWindQueryProvider();
    }

    public function getStreamProviderX()
    {
        throw new NotImplementedException('not implemented');
    }
}
