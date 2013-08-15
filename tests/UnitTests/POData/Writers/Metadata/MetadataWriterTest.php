<?php
use ODataProducer\Common\Url;
use ODataProducer\OperationContext\DataServiceHost;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\Configuration\EntitySetRights;
use ODataProducer\Configuration\DataServiceProtocolVersion;
use ODataProducer\Providers\Metadata\IDataServiceMetadataProvider;
use ODataProducer\Common\ODataException;
use ODataProducer\Writers\Metadata\MetadataWriter;
use ODataProducer\Common\Version;


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