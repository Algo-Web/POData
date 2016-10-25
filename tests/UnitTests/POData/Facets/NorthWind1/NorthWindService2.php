<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\Configuration\EntitySetRights;
use POData\Configuration\ProtocolVersion;
use POData\Configuration\ServiceConfiguration;
use Symfony\Component\Config\Definition\Exception\Exception;

use UnitTests\POData\Facets\BaseServiceTestWrapper;

class NorthWindService2 extends BaseServiceTestWrapper
{
    private $_northWindMetadata = null;

    /**
     * This method is called only once to initialize service-wide policies.
     *
     * @param ServiceConfiguration $config
     */
    public function initialize(ServiceConfiguration $config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
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
     * @return \POData\Providers\Query\IQueryProvider
     */
    public function getQueryProvider()
    {
        return new NorthWindQueryProvider();
    }

    public function getStreamProviderX()
    {
        throw new Exception('not implemented');
    }
}
