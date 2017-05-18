<?php

use POData\Configuration\EntitySetRights;
use POData\Configuration\ProtocolVersion;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\Writers\Metadata\MetadataWriter;
use UnitTests\POData\Facets\NorthWind2\NorthWindMetadata;
use UnitTests\POData\TestCase;

class MetadataWriterMockeryTest extends TestCase
{
    /** @var IQueryProvider */
    protected $mockQueryProvider;

    public function testWriteMetadataInAbsenceOfSpecificVersionRequest()
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

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>
<Edmx', $metadata);
        $versionString = 'DataServiceVersion="3.0"';
        $hasVersionString = false != strpos($metadata, $versionString);
        $this->assertTrue($hasVersionString);

        $customerResourceSet = $providersWrapper->resolveResourceSet('Customers');
        $this->assertEquals($customerResourceSet->getName(), 'Customers');
        $this->assertEquals($customerResourceSet->getResourceType()->getName(), 'Customer');

        $customerEntityType = $providersWrapper->resolveResourceType('Customer');
        $this->assertEquals($customerEntityType->getResourceTypeKind(), ResourceTypeKind::ENTITY);
    }

    protected function setUp()
    {
        $this->mockQueryProvider = \Mockery::mock('POData\Providers\Query\IQueryProvider');
    }
}
