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

require_once 'ODataProducer\Common\ClassAutoLoader.php';
require_once (dirname(__FILE__) . "\.\..\..\Resources\NorthWind2\NorthWindMetadata2.php");
require_once (dirname(__FILE__) . "\.\..\..\Resources\NorthWind2\NorthWindQueryProvider.php");
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
ODataProducer\Common\ClassAutoLoader::register();

class TestMetadataWriter extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }
    
    public function testWriteMetadata()
    {
		$northWindMetadata = CreateNorthWindMetadata1::Create();
        $configuration = new DataServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule("*", EntitySetRights::ALL);
        $configuration->setMaxDataServiceVersion(DataServiceProtocolVersion::V3);
        $northWindQuery = new NorthWindQueryProvider1();
        $metaQueryProverWrapper = new MetadataQueryProviderWrapper(
            $northWindMetadata, //IDataServiceMetadataProvider implementation 
            $northWindQuery, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuuration
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
?>