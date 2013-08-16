<?php
use POData\Common\Url;
use POData\OperationContext\DataServiceHost;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\MetadataQueryProviderWrapper;
use POData\Configuration\DataServiceConfiguration;
use POData\Configuration\EntitySetRights;
use POData\Configuration\DataServiceProtocolVersion;
use POData\Providers\Metadata\IDataServiceMetadataProvider;
use POData\Common\ODataException;
use POData\Writers\Metadata\MetadataWriter;
use POData\Common\Version;


use UnitTests\POData\Facets\NorthWind2\NorthWindMetadata;


class TestMetadataWriter extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }
    
    public function testWriteMetadata()
    {
		$northWindMetadata = NorthWindMetadata::Create();
        $configuration = new DataServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule("*", EntitySetRights::ALL);
        $configuration->setMaxDataServiceVersion(DataServiceProtocolVersion::V3);

        $metaQueryProverWrapper = new MetadataQueryProviderWrapper(
            $northWindMetadata, //IDataServiceMetadataProvider implementation 
            null, //This should not be used for meta data writing
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