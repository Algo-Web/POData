<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\Configuration\EntitySetRights;
use POData\IService;
use POData\IRequestHandler;
use POData\BaseService;
use POData\Configuration\ServiceProtocolVersion;
use POData\Configuration\IServiceConfiguration;
use Symfony\Component\Config\Definition\Exception\Exception;

use UnitTests\POData\Facets\NorthWind1\NorthWindQueryProvider;
use UnitTests\POData\Facets\NorthWind1\NorthWindExpressionProvider;
use UnitTests\POData\Facets\BaseServiceTestWrapper;

class NorthWindService2 extends BaseServiceTestWrapper
{
    private $_northWindMetadata = null;
    
    /**
     * This method is called only once to initialize service-wide policies
     * 
     * @param IServiceConfiguration $config
     */
    public function initialize(IServiceConfiguration $config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(ServiceProtocolVersion::V2);
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