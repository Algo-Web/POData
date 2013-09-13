<?php
use POData\Common\Url;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\MetadataQueryProviderWrapper;
use POData\Configuration\ServiceConfiguration;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ServiceProtocolVersion;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Common\ODataException;
use POData\Writers\Metadata\MetadataWriter;
use POData\Common\Version;


use UnitTests\POData\Facets\NorthWind2\NorthWindMetadata;
use POData\Providers\Query\IQueryProvider;

class TestMetadataWriter extends PHPUnit_Framework_TestCase
{
	/** @var  IQueryProvider */
	protected $mockQueryProvider;

    protected function setUp()
    {
	    $this->mockQueryProvider = \Phockito::mock('POData\Providers\Query\IQueryProvider');
    }
    
    public function testWriteMetadata()
    {
		$northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule("*", EntitySetRights::ALL);
        $configuration->setMaxDataServiceVersion(ServiceProtocolVersion::V3);

        $metaQueryProverWrapper = new MetadataQueryProviderWrapper(
            $northWindMetadata, //IMetadataProvider implementation
	        $this->mockQueryProvider, //This should not be used for meta data writing
            $configuration, //Service configuration
            false
        );
        $metadataWriter = new MetadataWriter($metaQueryProverWrapper);
        $metadata = $metadataWriter->writeMetadata();

        $this->assertNotNull($metadata);
        $this->assertEquals($metaQueryProverWrapper->getContainerName(), 'NorthWindEntities');
        $this->assertEquals($metaQueryProverWrapper->getContainerNamespace(), 'NorthWind');
        
        $this->assertStringStartsWith('<edmx:Edmx Version="1.0"',$metadata);
        
        $customerResourceSet = $metaQueryProverWrapper->resolveResourceSet('Customers');
        $this->assertEquals($customerResourceSet->getName(), 'Customers');
        $this->assertEquals($customerResourceSet->getResourceType()->getName(), 'Customer');
        
        $customerEntityType = $metaQueryProverWrapper->resolveResourceType('Customer');
        $this->assertEquals($customerEntityType->getResourceTypeKind(), ResourceTypeKind::ENTITY);
    }
}