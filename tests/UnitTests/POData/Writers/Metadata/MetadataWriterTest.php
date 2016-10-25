<?php

use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\Configuration\ServiceConfiguration;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ProtocolVersion;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Writers\Metadata\MetadataWriter;
use POData\Common\Version;

use Phockito\Phockito;

use UnitTests\POData\Facets\NorthWind2\NorthWindMetadata;
use POData\Providers\Query\IQueryProvider;

class MetadataWriterTest extends PHPUnit_Framework_TestCase
{
    /** @var IQueryProvider */
    protected $mockQueryProvider;

    protected function setUp()
    {
	    $this->mockQueryProvider = Phockito::mock('POData\Providers\Query\IQueryProvider');
    }

    public function testWriteMetadata()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $configuration->setMaxDataServiceVersion(ProtocolVersion::V3());

        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider, //This should not be used for meta data writing
            $configuration, //Service configuration
            false
        );
        $metadataWriter = new MetadataWriter($providersWrapper);
        $metadata = $metadataWriter->writeMetadata();

        $this->assertNotNull($metadata);
        $this->assertEquals($providersWrapper->getContainerName(), 'NorthWindEntities');
        $this->assertEquals($providersWrapper->getContainerNamespace(), 'NorthWind');

        $this->assertStringStartsWith('<edmx:Edmx Version="1.0"', $metadata);

        $customerResourceSet = $providersWrapper->resolveResourceSet('Customers');
        $this->assertEquals($customerResourceSet->getName(), 'Customers');
        $this->assertEquals($customerResourceSet->getResourceType()->getName(), 'Customer');

        $customerEntityType = $providersWrapper->resolveResourceType('Customer');
        $this->assertEquals($customerEntityType->getResourceTypeKind(), ResourceTypeKind::ENTITY);
    }
}
