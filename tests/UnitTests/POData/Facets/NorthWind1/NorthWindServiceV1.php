<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\Configuration\EntitySetRights;
use POData\Configuration\ProtocolVersion;
use POData\Configuration\ServiceConfiguration;
use UnitTests\POData\Facets\BaseServiceTestWrapper;

class NorthWindServiceV1 extends BaseServiceTestWrapper
{
    private $_northWindMetadata = null;

    /**
     * This method is called only once to initialize service-wide policies.
     *
     * @param ServiceConfiguration $config
     */
    public function initialize(ServiceConfiguration $config)
    {
        $this->objectSerialiser = new ObjectModelSerializer($this, null);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        //we are using V1 protocol, but still we set page size because with
        //a top value which is less than pagesize we can use V1 protocol
        //even though paging is enabled.
        $config->setEntitySetPageSize('*', 5);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(ProtocolVersion::V1());
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
