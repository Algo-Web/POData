<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\Configuration\EntitySetRights;
use POData\IService;
use POData\IRequestHandler;

use POData\Configuration\ServiceProtocolVersion;
use POData\Configuration\IServiceConfiguration;
use POData\BaseService;

use UnitTests\POData\Facets\NorthWind1\NorthWindQueryProvider;
use UnitTests\POData\Facets\BaseServiceTestWrapper;

class NorthWindServiceV1 extends BaseServiceTestWrapper
{
    private $_northWindMetadata = null;
    
    /**
     * This method is called only once to initialize service-wide policies
     * 
     * @param IServiceConfiguration $config
     */
    public function initialize(IServiceConfiguration $config)
    {
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        //we are using V1 protocol, but still we set page size because with
        //a top value which is less than pagesize we can use V1 protocol 
        //even though paging is enabled.
        $config->setEntitySetPageSize('*', 5);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(ServiceProtocolVersion::V1);
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

	public function getStreamProviderX(){
		throw new Exception("not implemented");
	}
}