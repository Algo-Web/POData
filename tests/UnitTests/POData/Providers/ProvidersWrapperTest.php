<?php

namespace UnitTests\POData\Providers\Metadata;

use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\Configuration\ServiceConfiguration;
use POData\Configuration\EntitySetRights;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Common\ODataException;
use POData\Common\Messages;
use POData\Providers\Metadata\Type\String;

use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use POData\Providers\Query\IQueryProvider;

use Phockito;
use UnitTests\POData\BaseUnitTestCase;

class ProvidersWrapperTest extends BaseUnitTestCase
{

	/** @var  IQueryProvider */
	protected $mockQueryProvider;


	/** @var  IMetadataProvider */
	protected $mockMetadataProvider;

	/**
	 * @var ServiceConfiguration
	 */
	protected $mockServiceConfig;

	/** @var  ResourceSet */
	protected $mockResourceSet;

	/** @var  ResourceSet */
	protected $mockResourceSet2;

	/** @var  ResourceType */
	protected $mockResourceType;


	/**
	 * @return ProvidersWrapper
	 */
	public function getMockedWrapper()
	{
		return new ProvidersWrapper(
			$this->mockMetadataProvider,
			$this->mockQueryProvider,
			$this->mockServiceConfig
		);
	}

    public function testGetContainerName()
    {

	    $fakeContainerName = "BigBadContainer";
	    Phockito::when($this->mockMetadataProvider->getContainerName())
		    ->return($fakeContainerName);

        $wrapper = $this->getMockedWrapper();

        $this->assertEquals($fakeContainerName, $wrapper->getContainerName());

    }

	public function testGetContainerNameThrowsWhenNull()
	{


		$wrapper = $this->getMockedWrapper();

		try{
			$wrapper->getContainerName();
			$this->fail("Expected exception not thrown");
		} catch(ODataException $ex) {
			$this->assertEquals(Messages::providersWrapperContainerNameMustNotBeNullOrEmpty(), $ex->getMessage());
			$this->assertEquals(500, $ex->getStatusCode());
		}

	}

	public function testGetContainerNameThrowsWhenEmpty()
	{

		Phockito::when($this->mockMetadataProvider->getContainerName())
			->return('');
		$wrapper = $this->getMockedWrapper();

		try{
			$wrapper->getContainerName();
			$this->fail("Expected exception not thrown");
		} catch(ODataException $ex) {
			$this->assertEquals(Messages::providersWrapperContainerNameMustNotBeNullOrEmpty(), $ex->getMessage());
			$this->assertEquals(500, $ex->getStatusCode());
		}

	}

	public function testGetContainerNamespace()
	{
		$fakeContainerNamespace = "BigBadNamespace";
		Phockito::when($this->mockMetadataProvider->getContainerNamespace())
			->return($fakeContainerNamespace);

		$wrapper = $this->getMockedWrapper();

		$this->assertEquals($fakeContainerNamespace, $wrapper->getContainerNamespace());

	}

	public function testGetContainerNamespaceThrowsWhenNull()
	{


		$wrapper = $this->getMockedWrapper();

		try{
			$wrapper->getContainerNamespace();
			$this->fail("Expected exception not thrown");
		} catch(ODataException $ex) {
			$this->assertEquals(Messages::providersWrapperContainerNamespaceMustNotBeNullOrEmpty(), $ex->getMessage());
			$this->assertEquals(500, $ex->getStatusCode());
		}

	}

	public function testGetContainerNamespaceThrowsWhenEmpty()
	{

		Phockito::when($this->mockMetadataProvider->getContainerNamespace())
			->return('');

		$wrapper = $this->getMockedWrapper();

		try{
			$wrapper->getContainerNamespace();
			$this->fail("Expected exception not thrown");
		} catch(ODataException $ex) {
			$this->assertEquals(Messages::providersWrapperContainerNamespaceMustNotBeNullOrEmpty(), $ex->getMessage());
			$this->assertEquals(500, $ex->getStatusCode());
		}

	}

	public function testResolveResourceSet()
	{
		$fakeSetName = 'SomeSet';

		Phockito::when($this->mockMetadataProvider->resolveResourceSet($fakeSetName))
			->return($this->mockResourceSet);


		Phockito::when($this->mockResourceSet->getResourceType())
			->return($this->mockResourceType);

		//Indicate the resource set is visible
		Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet))
			->return(EntitySetRights::READ_SINGLE);


		$wrapper = $this->getMockedWrapper();

		$actual = $wrapper->resolveResourceSet($fakeSetName);

		$this->assertEquals(new ResourceSetWrapper($this->mockResourceSet, $this->mockServiceConfig), $actual);

		//Verify it comes from cache
		$actual2 = $wrapper->resolveResourceSet($fakeSetName);
		$this->assertSame($actual, $actual2);

	}

	public function testResolveResourceSetNotVisible()
	{
		$fakeSetName = 'SomeSet';

		Phockito::when($this->mockMetadataProvider->resolveResourceSet($fakeSetName))
			->return($this->mockResourceSet);


		Phockito::when($this->mockResourceSet->getResourceType())
			->return($this->mockResourceType);

		//Indicate the resource set is NOT visible
		Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet))
			->return(EntitySetRights::NONE);

		Phockito::when($this->mockResourceSet->getName())
			->return($fakeSetName);

		$wrapper = $this->getMockedWrapper();

		$this->assertNull($wrapper->resolveResourceSet($fakeSetName));

		//verify it comes from cache
		$wrapper->resolveResourceSet($fakeSetName); //call it again

		//make sure the metadata provider was only called once
		Phockito::verify($this->mockMetadataProvider, 1)->resolveResourceSet($fakeSetName);

	}

	public function testResolveResourceSetNonExistent()
	{
		$fakeSetName = 'SomeSet';

		Phockito::when($this->mockMetadataProvider->resolveResourceSet($fakeSetName))
			->return(null);

		$wrapper = $this->getMockedWrapper();

		$this->assertNull($wrapper->resolveResourceSet($fakeSetName));

	}


    public function testResolveResourceType1()
    {

        $configuration = null;
        $metadataProvider = $this->_createMetadataAndConfiguration1($configuration);
        $providersWrapper = new ProvidersWrapper(
            $metadataProvider, //IMetadataProvider implementation
	        $this->mockQueryProvider,
	        $configuration, //Service configuration
            false
        );
        //Try to resolve non-existing type
        $type = $providersWrapper->resolveResourceType('Customer1');
        $this->assertNull($type);
        $customerEntityType = $providersWrapper->resolveResourceType('Customer');
        $this->assertNotNull($customerEntityType);
        $this->assertEquals($customerEntityType->getName(), 'Customer');
        $this->assertEquals($customerEntityType->getResourceTypeKind(), ResourceTypeKind::ENTITY);

        $addressCoomplexType = $providersWrapper->resolveResourceType('Address');
        $this->assertNotNull($addressCoomplexType);
        $this->assertEquals($addressCoomplexType->getName(), 'Address');
        $this->assertEquals($addressCoomplexType->getResourceTypeKind(), ResourceTypeKind::COMPLEX);

    }

    public function testGetTypes1()
    {

        $configuration = null;
        $metadataProvider = $this->_createMetadataAndConfiguration1($configuration);
        $providersWrapper = new ProvidersWrapper(
                                    $metadataProvider, //IMetadataProvider implementation
	        $this->mockQueryProvider,
	        $configuration, //Service configuration
                                    false
                                    );
        $resourceTypes = $providersWrapper->getTypes();
        $this->assertEquals(count($resourceTypes), 7);
        $orderEntityType = $providersWrapper->resolveResourceType('Order');
        $this->assertNotNull($orderEntityType);
        $this->assertEquals($orderEntityType->getName(), 'Order');

    }

    public function testGetDerivedTypes1()
    {

        $configuration = null;
        $metadataProvider = $this->_createMetadataAndConfiguration1($configuration);
        $providersWrapper = new ProvidersWrapper(
                                    $metadataProvider, //IMetadataProvider implementation
	        $this->mockQueryProvider,
	        $configuration, //Service configuration
                                    false
                                    );
        $customerEntityType = $providersWrapper->resolveResourceType('Customer');
        $this->assertNotNull($customerEntityType);
        $derivedTypes = $providersWrapper->getDerivedTypes($customerEntityType);
        $this->assertEquals(array(), $derivedTypes);

    }

    public function testHasDerivedTypes1()
    {

        $configuration = null;
        $metadataProvider = $this->_createMetadataAndConfiguration1($configuration);
        $providersWrapper = new ProvidersWrapper(
                                    $metadataProvider, //IMetadataProvider implementation
	        $this->mockQueryProvider,
	        $configuration, //Service configuration
                                    false
                                    );
        $orderEntityType = $providersWrapper->resolveResourceType('Order');
        $this->assertNotNull($orderEntityType);
        $hasDerivedTypes = $providersWrapper->hasDerivedTypes($orderEntityType);
        $this->assertFalse($hasDerivedTypes);

    }

    public function testGetResourceAssociationSet1()
    {

        //Get the association set where resource set in both ends are visible
        $configuration = null;
        $metadataProvider = $this->_createMetadataAndConfiguration1($configuration);
        $providersWrapper = new ProvidersWrapper(
                                    $metadataProvider, //IMetadataProvider implementation
	        $this->mockQueryProvider,
	        $configuration, //Service configuration
                                    false
                                    );
        $configuration->setEntitySetAccessRule('Customers', EntitySetRights::ALL);
        $configuration->setEntitySetAccessRule('Orders', EntitySetRights::ALL);
        $customersEntitySetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $this->assertNotNull($customersEntitySetWrapper);
        $customerEntityType = $providersWrapper->resolveResourceType('Customer');
        $this->assertNotNull($customerEntityType);

        $ordersProperty = $customerEntityType->tryResolvePropertyTypeByName('Orders');
        $this->assertNotNull($ordersProperty);

        $associationSet = $providersWrapper->getResourceAssociationSet($customersEntitySetWrapper, $customerEntityType, $ordersProperty);
        $this->assertNotNull($associationSet);

        $associationSetEnd1 = $associationSet->getEnd1();
        $this->assertNotNull($associationSetEnd1);

        $associationSetEnd2 = $associationSet->getEnd2();
        $this->assertNotNull($associationSetEnd2);

        $this->assertEquals($associationSetEnd1->getResourceSet()->getName(), 'Customers');
        $this->assertEquals($associationSetEnd2->getResourceSet()->getName(), 'Orders');
        $this->assertEquals($associationSetEnd1->getResourceType()->getName(), 'Customer');
        $this->assertEquals($associationSetEnd2->getResourceType()->getName(), 'Order');

        //Try to get the association set where resource set in one end is invisible
        $configuration = null;
        $metadataProvider = $this->_createMetadataAndConfiguration1($configuration);
        $providersWrapper = new ProvidersWrapper(
                                    $metadataProvider, //IMetadataProvider implementation
	        $this->mockQueryProvider,
	        $configuration, //Service configuration
                                    false
                                    );
        $configuration->setEntitySetAccessRule('Customers', EntitySetRights::ALL);
        //Set orders entity set as invisible
        $configuration->setEntitySetAccessRule('Orders', EntitySetRights::NONE);
        $customersEntitySetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $this->assertNotNull($customersEntitySetWrapper);

        $customerEntityType = $providersWrapper->resolveResourceType('Customer');
        $this->assertNotNull($customerEntityType);

        $ordersProperty = $customerEntityType->tryResolvePropertyTypeByName('Orders');
        $this->assertNotNull($ordersProperty);

        $associationSet = $providersWrapper->getResourceAssociationSet($customersEntitySetWrapper, $customerEntityType, $ordersProperty);
        $this->assertNull($associationSet);

    }


	public function testGetResourceSets()
	{
		$fakeSets = array(
			$this->mockResourceSet,
		);

		Phockito::when($this->mockMetadataProvider->getResourceSets())
			->return($fakeSets);

		Phockito::when($this->mockResourceSet->getResourceType())
			->return($this->mockResourceType);

		Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet))
			->return(EntitySetRights::READ_SINGLE);

		$wrapper = $this->getMockedWrapper();

		$actual = $wrapper->getResourceSets();


		$expected = array(
			new ResourceSetWrapper($this->mockResourceSet, $this->mockServiceConfig)
		);
		$this->assertEquals($expected, $actual);

	}

	public function testGetResourceSetsDuplicateNames()
	{
		$fakeSets = array(
			$this->mockResourceSet,
			$this->mockResourceSet,
		);

		Phockito::when($this->mockMetadataProvider->getResourceSets())
			->return($fakeSets);

		Phockito::when($this->mockResourceSet->getResourceType())
			->return($this->mockResourceType);

		$fakeName = "Fake Set 1";
		Phockito::when($this->mockResourceSet->getName())
			->return($fakeName);

		Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet))
			->return(EntitySetRights::READ_SINGLE);

		$wrapper = $this->getMockedWrapper();

		try{
			$wrapper->getResourceSets();
			$this->fail('An expected ODataException for entity set repetition has not been thrown');
		} catch(ODataException $exception) {
			$this->assertEquals(Messages::providersWrapperEntitySetNameShouldBeUnique($fakeName), $exception->getMessage());
			$this->assertEquals(500, $exception->getStatusCode());
		}
	}

	public function testGetResourceSetsSecondOneIsNotVisible()
	{

		$fakeSets = array(
			$this->mockResourceSet,
			$this->mockResourceSet2,
		);

		Phockito::when($this->mockMetadataProvider->getResourceSets())
			->return($fakeSets);

		Phockito::when($this->mockResourceSet->getName())
			->return("fake name 1");

		Phockito::when($this->mockResourceSet2->getName())
			->return("fake name 2");

		Phockito::when($this->mockResourceSet->getResourceType())
			->return($this->mockResourceType);

		Phockito::when($this->mockResourceSet2->getResourceType())
			->return($this->mockResourceType);

		Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet))
			->return(EntitySetRights::NONE);

		Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet2))
			->return(EntitySetRights::READ_SINGLE);

		$wrapper = $this->getMockedWrapper();

		$actual = $wrapper->getResourceSets();


		$expected = array(
			new ResourceSetWrapper($this->mockResourceSet2, $this->mockServiceConfig)
		);
		$this->assertEquals($expected, $actual);

	}

	public function testGetTypes()
	{
		$fakeTypes = array(
			new ResourceType(new String(), ResourceTypeKind::PRIMITIVE, "FakeType1" ),
		);

		Phockito::when($this->mockMetadataProvider->getTypes())
			->return($fakeTypes);

		$wrapper = $this->getMockedWrapper();

		$this->assertEquals($fakeTypes, $wrapper->getTypes());

	}

	public function testGetTypesDuplicateNames()
	{
		$fakeTypes = array(
			new ResourceType(new String(), ResourceTypeKind::PRIMITIVE, "FakeType1" ),
			new ResourceType(new String(), ResourceTypeKind::PRIMITIVE, "FakeType1" ),
		);

		Phockito::when($this->mockMetadataProvider->getTypes())
			->return($fakeTypes);

		$wrapper = $this->getMockedWrapper();

		try {
			$wrapper->getTypes();
			$this->fail('An expected ODataException for entity type name repetition has not been thrown');
		} catch(ODataException $exception) {
			$this->assertEquals(Messages::providersWrapperEntityTypeNameShouldBeUnique("FakeType1"), $exception->getMessage());
			$this->assertEquals(500, $exception->getStatusCode());
		}

	}
    


    /**
     * Creates a valid IMetadataProvider implementation, and associated configuration
     * Note: This metadata is for positive testing
     * 
     * @param ServiceConfiguration $configuration On return, this will hold reference to configuration object
     * 
     * @return IMetadataProvider
     */
    private function _createMetadataAndConfiguration1(&$configuration)
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        return $northWindMetadata;
    }


}