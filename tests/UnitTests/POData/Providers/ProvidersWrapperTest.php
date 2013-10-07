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
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
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

    /** @var  ResourceType */
    protected $mockResourceType2;

    /** @var  ResourceAssociationSet */
    protected $mockResourceAssociationSet;

    /** @var  ResourceProperty */
    protected $mockResourceProperty;

    /** @var  ResourceAssociationSetEnd */
    protected $mockResourceAssociationSetEnd;
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


    public function testResolveResourceTypeNonExistent()
    {

        $fakeTypeName = 'SomeType';

        Phockito::when($this->mockMetadataProvider->resolveResourceType($fakeTypeName))
            ->return(null);

        $wrapper = $this->getMockedWrapper();

        $this->assertNull($wrapper->resolveResourceType($fakeTypeName));

    }


    public function testResolveResourceType()
    {

        $fakeTypeName = 'SomeType';

        Phockito::when($this->mockMetadataProvider->resolveResourceType($fakeTypeName))
            ->return($this->mockResourceType);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->resolveResourceType($fakeTypeName);

        $this->assertEquals($this->mockResourceType, $actual);

    }


    public function testGetDerivedTypesNonArrayReturnedThrows()
    {
        $fakeName = "FakeType";

        Phockito::when($this->mockMetadataProvider->getDerivedTypes($this->mockResourceType))
            ->return($this->mockResourceType);

        Phockito::when($this->mockResourceType->getName())
            ->return($fakeName);

        $wrapper = $this->getMockedWrapper();

        try{
            $wrapper->getDerivedTypes($this->mockResourceType);
            $this->fail("Expected exception not thrown");
        } catch(InvalidOperationException $ex) {
            $this->assertEquals(Messages::metadataAssociationTypeSetInvalidGetDerivedTypesReturnType($fakeName),$ex->getMessage());

        }

    }

    public function testGetDerivedTypes()
    {
        $fakeName = "FakeType";

        Phockito::when($this->mockMetadataProvider->getDerivedTypes($this->mockResourceType))
            ->return(array($this->mockResourceType2));

        Phockito::when($this->mockResourceType->getName())
            ->return($fakeName);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getDerivedTypes($this->mockResourceType);
        $this->assertEquals(array($this->mockResourceType2), $actual);

    }

    public function testHasDerivedTypes()
    {

        Phockito::when($this->mockMetadataProvider->hasDerivedTypes($this->mockResourceType))
            ->return(true);

        $wrapper = $this->getMockedWrapper();

        $this->assertTrue($wrapper->hasDerivedTypes($this->mockResourceType));

    }

    public function testGetResourceAssociationSet()
    {
        $fakePropName = "Fake Prop";
        Phockito::when($this->mockResourceProperty->getName())
            ->return($fakePropName);


        Phockito::when($this->mockResourceType->tryResolvePropertyTypeDeclaredOnThisTypeByName($fakePropName))
            ->return($this->mockResourceProperty);

        $fakeTypeName = "Fake Type";
        Phockito::when($this->mockResourceType->getName())
            ->return($fakeTypeName);

        $fakeSetName = "Fake Set";
        Phockito::when($this->mockResourceSet->getName())
            ->return($fakeSetName);

        Phockito::when($this->mockResourceSet->getResourceType())
            ->return($this->mockResourceType);

        Phockito::when($this->mockResourceSet2->getResourceType())
            ->return($this->mockResourceType2);

        //Indicate the resource set is visible
        Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet))
            ->return(EntitySetRights::READ_SINGLE);

        //Indicate the resource set is visible
        Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet2))
            ->return(EntitySetRights::READ_SINGLE);

        Phockito::when($this->mockMetadataProvider->getResourceAssociationSet($this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty))
            ->return($this->mockResourceAssociationSet);


        Phockito::when($this->mockResourceAssociationSet->getResourceAssociationSetEnd($this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty))
            ->return($this->mockResourceAssociationSetEnd);

        Phockito::when($this->mockResourceAssociationSet->getRelatedResourceAssociationSetEnd($this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty))
            ->return($this->mockResourceAssociationSetEnd);

        Phockito::when($this->mockResourceAssociationSetEnd->getResourceSet())
            ->return($this->mockResourceSet2);

        Phockito::when($this->mockResourceAssociationSetEnd->getResourceType())
            ->return($this->mockResourceType2);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getResourceAssociationSet($this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty);

        $this->assertEquals($this->mockResourceAssociationSet, $actual);

    }


    public function testGetResourceAssociationSetEndIsNotVisible()
    {
        $fakePropName = "Fake Prop";
        Phockito::when($this->mockResourceProperty->getName())
            ->return($fakePropName);


        Phockito::when($this->mockResourceType->tryResolvePropertyTypeDeclaredOnThisTypeByName($fakePropName))
            ->return($this->mockResourceProperty);

        $fakeTypeName = "Fake Type";
        Phockito::when($this->mockResourceType->getName())
            ->return($fakeTypeName);

        $fakeSetName = "Fake Set";
        Phockito::when($this->mockResourceSet->getName())
            ->return($fakeSetName);

        Phockito::when($this->mockResourceSet->getResourceType())
            ->return($this->mockResourceType);

        Phockito::when($this->mockResourceSet2->getResourceType())
            ->return($this->mockResourceType2);

        //Indicate the resource set is visible
        Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet))
            ->return(EntitySetRights::READ_SINGLE);

        //Indicate the resource set is visible
        Phockito::when($this->mockServiceConfig->getEntitySetAccessRule($this->mockResourceSet2))
            ->return(EntitySetRights::NONE);

        Phockito::when($this->mockMetadataProvider->getResourceAssociationSet($this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty))
            ->return($this->mockResourceAssociationSet);


        Phockito::when($this->mockResourceAssociationSet->getResourceAssociationSetEnd($this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty))
            ->return($this->mockResourceAssociationSetEnd);

        Phockito::when($this->mockResourceAssociationSet->getRelatedResourceAssociationSetEnd($this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty))
            ->return($this->mockResourceAssociationSetEnd);

        Phockito::when($this->mockResourceAssociationSetEnd->getResourceSet())
            ->return($this->mockResourceSet2);

        Phockito::when($this->mockResourceAssociationSetEnd->getResourceType())
            ->return($this->mockResourceType2);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getResourceAssociationSet($this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty);

        $this->assertNull($actual);

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



}