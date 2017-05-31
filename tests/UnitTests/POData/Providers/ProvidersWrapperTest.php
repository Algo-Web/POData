<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\IServiceConfiguration;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use UnitTests\POData\TestCase;

class ProvidersWrapperTest extends TestCase
{
    /** @var IQueryProvider */
    protected $mockQueryProvider;

    /** @var IMetadataProvider */
    protected $mockMetadataProvider;

    /**
     * @var IServiceConfiguration
     */
    protected $mockServiceConfig;

    /** @var ResourceSet */
    protected $mockResourceSet;

    /** @var ResourceSet */
    protected $mockResourceSet2;

    /** @var ResourceEntityType */
    protected $mockResourceType;

    /** @var ResourceType */
    protected $mockResourceType2;

    /** @var ResourceAssociationSet */
    protected $mockResourceAssociationSet;

    /** @var ResourceProperty */
    protected $mockResourceProperty;

    /** @var ResourceAssociationSetEnd */
    protected $mockResourceAssociationSetEnd;

    public function setUp()
    {
        $this->mockMetadataProvider = m::mock(IMetadataProvider::class)->makePartial();
        $this->mockResourceSet = m::mock(ResourceSet::class)->makePartial();
        $this->mockResourceSet2 = m::mock(ResourceSet::class)->makePartial();
        $this->mockResourceType = m::mock(ResourceEntityType::class)->makePartial();
        $this->mockResourceType2 = m::mock(ResourceType::class)->makePartial();
        $this->mockQueryProvider = m::mock(IQueryProvider::class)->makePartial();
        $this->mockServiceConfig = m::mock(ServiceConfiguration::class)->makePartial();
        $this->mockResourceProperty = m::mock(ResourceProperty::class)->makePartial();
        $this->mockResourceAssociationSet = m::mock(ResourceAssociationSet::class)->makePartial();
        $this->mockResourceAssociationSetEnd = m::mock(ResourceAssociationSetEnd::class)->makePartial();
    }

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
        $fakeContainerName = 'BigBadContainer';
        $mockMeta = m::mock(IMetadataProvider::class)->makePartial();
        $mockMeta->shouldReceive('getContainerName')->andReturn($fakeContainerName);
        $this->mockMetadataProvider = $mockMeta;

        $wrapper = $this->getMockedWrapper();

        $this->assertEquals($fakeContainerName, $wrapper->getContainerName());
    }

    public function testGetContainerNameThrowsWhenNull()
    {
        $mockMeta = m::mock(IMetadataProvider::class)->makePartial();
        $mockMeta->shouldReceive('getContainerName')->andReturnNull();
        $this->mockMetadataProvider = $mockMeta;

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getContainerName();
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::providersWrapperContainerNameMustNotBeNullOrEmpty(), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetContainerNameThrowsWhenEmpty()
    {
        $mockMeta = m::mock(IMetadataProvider::class)->makePartial();
        $mockMeta->shouldReceive('getContainerName')->andReturn('');
        $this->mockMetadataProvider = $mockMeta;

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getContainerName();
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::providersWrapperContainerNameMustNotBeNullOrEmpty(), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetContainerNamespace()
    {
        $fakeContainerNamespace = 'BigBadNamespace';

        $mockMeta = m::mock(IMetadataProvider::class)->makePartial();
        $mockMeta->shouldReceive('getContainerNamespace')->andReturn($fakeContainerNamespace);
        $this->mockMetadataProvider = $mockMeta;

        $wrapper = $this->getMockedWrapper();

        $this->assertEquals($fakeContainerNamespace, $wrapper->getContainerNamespace());
    }

    public function testGetContainerNamespaceThrowsWhenNull()
    {
        $mockMeta = m::mock(IMetadataProvider::class)->makePartial();
        $mockMeta->shouldReceive('getContainerNamespace')->andReturnNull();
        $this->mockMetadataProvider = $mockMeta;

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getContainerNamespace();
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::providersWrapperContainerNamespaceMustNotBeNullOrEmpty(), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetContainerNamespaceThrowsWhenEmpty()
    {
        $mockMeta = m::mock(IMetadataProvider::class)->makePartial();
        $mockMeta->shouldReceive('getContainerNamespace')->andReturn('');
        $this->mockMetadataProvider = $mockMeta;

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getContainerNamespace();
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::providersWrapperContainerNamespaceMustNotBeNullOrEmpty(), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testResolveResourceSet()
    {
        $fakeSetName = 'SomeSet';

        $mockResource = m::mock(ResourceSet::class)->makePartial();
        $mockResource->shouldReceive('getResourceType')->andReturn($this->mockResourceType);
        $this->mockResourceSet = $mockResource;

        $mockMeta = m::mock(IMetadataProvider::class)->makePartial();
        $mockMeta->shouldReceive('resolveResourceSet')->andReturn($this->mockResourceSet);
        $this->mockMetadataProvider = $mockMeta;

        //Indicate the resource set is visible
        $mockConfig = m::mock(ServiceConfiguration::class)->makePartial();
        $mockConfig->shouldReceive('getEntitySetAccessRule')->withArgs([$this->mockResourceSet])
            ->andReturn(EntitySetRights::READ_SINGLE);
        $this->mockServiceConfig = $mockConfig;

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

        $this->mockResourceSet->shouldReceive('getName')->andReturn($fakeSetName);
        $this->mockResourceSet->shouldReceive('getResourceType')->andReturn($this->mockResourceType);

        //make sure the metadata provider was only called once
        $this->mockMetadataProvider->shouldReceive('resolveResourceSet')->andReturn($this->mockResourceSet)->once();

        //Indicate the resource set is NOT visible
        $this->mockServiceConfig->shouldReceive('getEntitySetAccessRule')->withArgs([$this->mockResourceSet])
            ->andReturn(EntitySetRights::NONE);

        $wrapper = $this->getMockedWrapper();

        $this->assertNull($wrapper->resolveResourceSet($fakeSetName));

        //verify it comes from cache
        $wrapper->resolveResourceSet($fakeSetName); //call it again
    }

    public function testResolveResourceSetNonExistent()
    {
        $fakeSetName = 'SomeSet';

        $this->mockMetadataProvider->shouldReceive('resolveResourceSet')->withArgs([$fakeSetName])->andReturn(null);

        $wrapper = $this->getMockedWrapper();

        $this->assertNull($wrapper->resolveResourceSet($fakeSetName));
    }

    public function testResolveResourceTypeNonExistent()
    {
        $fakeTypeName = 'SomeType';

        $this->mockMetadataProvider->shouldReceive('resolveResourceType')->withArgs([$fakeTypeName])->andReturn(null);

        $wrapper = $this->getMockedWrapper();

        $this->assertNull($wrapper->resolveResourceType($fakeTypeName));
    }

    public function testResolveResourceType()
    {
        $fakeTypeName = 'SomeType';

        $this->mockMetadataProvider->shouldReceive('resolveResourceType')->withArgs([$fakeTypeName])
            ->andReturn($this->mockResourceType);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->resolveResourceType($fakeTypeName);

        $this->assertEquals($this->mockResourceType, $actual);
    }

    public function testGetDerivedTypesNonArrayReturnedThrows()
    {
        $fakeName = 'FakeType';

        $this->mockResourceType->shouldReceive('getName')->andReturn($fakeName);
        $this->mockMetadataProvider->shouldReceive('getDerivedTypes')->withArgs([$this->mockResourceType])
            ->andReturn($this->mockResourceType);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getDerivedTypes($this->mockResourceType);
            $this->fail('Expected exception not thrown');
        } catch (InvalidOperationException $ex) {
            $this->assertEquals(
                Messages::metadataAssociationTypeSetInvalidGetDerivedTypesReturnType($fakeName),
                $ex->getMessage()
            );
        }
    }

    public function testGetDerivedTypes()
    {
        $fakeName = 'FakeType';

        $this->mockResourceType->shouldReceive('getName')->andReturn($fakeName);

        $this->mockMetadataProvider->shouldReceive('getDerivedTypes')->withArgs([$this->mockResourceType])
            ->andReturn([$this->mockResourceType2]);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getDerivedTypes($this->mockResourceType);
        $this->assertEquals([$this->mockResourceType2], $actual);
    }

    public function testHasDerivedTypes()
    {
        $this->mockMetadataProvider->shouldReceive('hasDerivedTypes')->withArgs([$this->mockResourceType])
            ->andReturn(true);

        $wrapper = $this->getMockedWrapper();

        $this->assertTrue($wrapper->hasDerivedTypes($this->mockResourceType));
    }

    public function testGetResourceAssociationSet()
    {
        $fakePropName = 'Fake Prop';

        $this->mockResourceProperty->shouldReceive('getName')->andReturn($fakePropName);

        $this->mockResourceType->shouldReceive('resolvePropertyDeclaredOnThisType')
            ->withArgs([$fakePropName])->andReturn($this->mockResourceProperty);

        $fakeTypeName = 'Fake Type';
        $this->mockResourceType->shouldReceive('getName')->andReturn($fakeTypeName);

        $fakeSetName = 'Fake Set';
        $this->mockResourceSet->shouldReceive('getName')->andReturn($fakeSetName);

        $this->mockResourceSet->shouldReceive('getResourceType')->andReturn($this->mockResourceType);

        $this->mockResourceSet2->shouldReceive('getResourceType')->andReturn($this->mockResourceType2);

        //Indicate the resource set is visible
        $this->mockServiceConfig->shouldReceive('getEntitySetAccessRule')->andReturn(EntitySetRights::READ_SINGLE);

        $this->mockMetadataProvider->shouldReceive('getResourceAssociationSet')
            ->withArgs([$this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty])
            ->andReturn($this->mockResourceAssociationSet);

        $this->mockResourceAssociationSet->shouldReceive('getResourceAssociationSetEnd')
            ->withArgs([$this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty])
            ->andReturn($this->mockResourceAssociationSetEnd);

        $this->mockResourceAssociationSet->shouldReceive('getRelatedResourceAssociationSetEnd')
            ->withArgs([$this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty])
            ->andReturn($this->mockResourceAssociationSetEnd);

        $this->mockResourceAssociationSetEnd->shouldReceive('getResourceSet')->andReturn($this->mockResourceSet2);

        $this->mockResourceAssociationSetEnd->shouldReceive('getResourceType')->andReturn($this->mockResourceType2);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getResourceAssociationSet(
            $this->mockResourceSet,
            $this->mockResourceType,
            $this->mockResourceProperty
        );

        $this->assertEquals($this->mockResourceAssociationSet, $actual);
    }

    public function testGetResourceAssociationSetEndIsNotVisible()
    {
        $fakePropName = 'Fake Prop';
        $this->mockResourceProperty->shouldReceive('getName')->andReturn($fakePropName);

        $fakeTypeName = 'Fake Type';
        $this->mockResourceType->shouldReceive('getName')->andReturn($fakeTypeName);
        $this->mockResourceType->shouldReceive('resolvePropertyDeclaredOnThisType')
            ->withArgs([$fakePropName])->andReturn($this->mockResourceProperty);

        $fakeSetName = 'Fake Set';
        $this->mockResourceSet->shouldReceive('getName')->andReturn($fakeSetName);

        $this->mockResourceSet->shouldReceive('getResourceType')->andReturn($this->mockResourceType);
        $this->mockResourceSet2->shouldReceive('getResourceType')->andReturn($this->mockResourceType2);

        //Indicate the resource set is visible
        $this->mockServiceConfig->shouldReceive('getEntitySetAccessRule')
            ->withArgs([$this->mockResourceSet])->andReturn(EntitySetRights::READ_SINGLE);

        //Indicate the resource set is not visible
        $this->mockServiceConfig->shouldReceive('getEntitySetAccessRule')
            ->withArgs([$this->mockResourceSet2])->andReturn(EntitySetRights::NONE);

        $this->mockMetadataProvider->shouldReceive('getResourceAssociationSet')
            ->withArgs([$this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty])
            ->andReturn($this->mockResourceAssociationSet);

        $this->mockResourceAssociationSet->shouldReceive('getResourceAssociationSetEnd')
            ->withArgs([$this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty])
            ->andReturn($this->mockResourceAssociationSetEnd);

        $this->mockResourceAssociationSet->shouldReceive('getRelatedResourceAssociationSetEnd')
            ->withArgs([$this->mockResourceSet, $this->mockResourceType, $this->mockResourceProperty])
            ->andReturn($this->mockResourceAssociationSetEnd);

        $this->mockResourceAssociationSetEnd->shouldReceive('getResourceSet')->andReturn($this->mockResourceSet2);

        $this->mockResourceAssociationSetEnd->shouldReceive('getResourceType')->andReturn($this->mockResourceType2);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getResourceAssociationSet(
            $this->mockResourceSet,
            $this->mockResourceType,
            $this->mockResourceProperty
        );

        $this->assertNull($actual);
    }

    public function testGetResourceSets()
    {
        $fakeSets = [
            $this->mockResourceSet,
        ];

        $this->mockMetadataProvider->shouldReceive('getResourceSets')->andReturn($fakeSets);
        $this->mockResourceSet->shouldReceive('getResourceType')->andReturn($this->mockResourceType);
        $this->mockServiceConfig->shouldReceive('getEntitySetAccessRule')
            ->withArgs([$this->mockResourceSet])
            ->andReturn(EntitySetRights::READ_SINGLE);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getResourceSets();

        $expected = [
            new ResourceSetWrapper($this->mockResourceSet, $this->mockServiceConfig),
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceSetsDuplicateNames()
    {
        $fakeSets = [
            $this->mockResourceSet,
            $this->mockResourceSet,
        ];

        $fakeName = 'Fake Set 1';

        $this->mockMetadataProvider->shouldReceive('getResourceSets')->andReturn($fakeSets);
        $this->mockResourceSet->shouldReceive('getResourceType')->andReturn($this->mockResourceType);
        $this->mockResourceSet->shouldReceive('getName')->andReturn($fakeName);
        $this->mockServiceConfig->shouldReceive('getEntitySetAccessRule')
            ->withArgs([$this->mockResourceSet])
            ->andReturn(EntitySetRights::READ_SINGLE);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getResourceSets();
            $this->fail('An expected ODataException for entity set repetition has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals(Messages::providersWrapperEntitySetNameShouldBeUnique($fakeName), $exception->getMessage());
            $this->assertEquals(500, $exception->getStatusCode());
        }
    }

    public function testGetResourceSetsSecondOneIsNotVisible()
    {
        $fakeSets = [
            $this->mockResourceSet,
            $this->mockResourceSet2,
        ];

        $this->mockMetadataProvider->shouldReceive('getResourceSets')->andReturn($fakeSets);
        $this->mockResourceSet->shouldReceive('getResourceType')->andReturn($this->mockResourceType);
        $this->mockResourceSet->shouldReceive('getName')->andReturn('fake name 1');
        $this->mockResourceSet2->shouldReceive('getName')->andReturn('fake name 2');
        $this->mockResourceSet2->shouldReceive('getResourceType')->andReturn($this->mockResourceType2);
        $this->mockServiceConfig->shouldReceive('getEntitySetAccessRule')
            ->withArgs([$this->mockResourceSet])
            ->andReturn(EntitySetRights::NONE);
        $this->mockServiceConfig->shouldReceive('getEntitySetAccessRule')
            ->withArgs([$this->mockResourceSet2])
            ->andReturn(EntitySetRights::READ_SINGLE);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getResourceSets();

        $expected = [
            new ResourceSetWrapper($this->mockResourceSet2, $this->mockServiceConfig),
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testGetTypes()
    {
        $fakeTypes = [
            new ResourcePrimitiveType(new StringType())
        ];

        $this->mockMetadataProvider->shouldReceive('getTypes')->andReturn($fakeTypes);

        $wrapper = $this->getMockedWrapper();

        $this->assertEquals($fakeTypes, $wrapper->getTypes());
    }

    public function testGetTypesDuplicateNames()
    {
        $fakeTypes = [
            new ResourcePrimitiveType(new StringType()),
            new ResourcePrimitiveType(new StringType())
        ];

        $this->mockMetadataProvider->shouldReceive('getTypes')->andReturn($fakeTypes);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getTypes();
            $this->fail('An expected ODataException for entity type name repetition has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals(
                Messages::providersWrapperEntityTypeNameShouldBeUnique('String'),
                $exception->getMessage()
            );
            $this->assertEquals(500, $exception->getStatusCode());
        }
    }

    /** @var FilterInfo */
    protected $mockFilterInfo;

    public function testGetResourceSetJustEntities()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = [];

        /* TODO: Audit this and see if it still applies
        * $this->mockQueryProvider->shouldReceive('getResourceSet')->withArgs([
        QueryType::ENTITIES(),
        $this->mockResourceSet,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip,
        null
        ]
        )->andReturn($fakeQueryResult);*/
        $this->mockQueryProvider->shouldReceive('getResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getResourceSet(
            QueryType::ENTITIES(),
            $this->mockResourceSet,
            $this->mockFilterInfo,
            $orderBy,
            $top,
            $skip
        );
        $this->assertEquals($fakeQueryResult, $actual);
    }

    public function testGetResourceSetReturnsNonQueryResult()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        /* TODO: Audit this and see if it still applies
        * $this->mockQueryProvider->shouldReceive('getResourceSet')->withArgs([
        QueryType::ENTITIES(),
        $this->mockResourceSet,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip,
        null
        ]
        )->andReturn($fakeQueryResult);*/
        $this->mockQueryProvider->shouldReceive('getResourceSet')->andReturn(null);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getResourceSet(
                QueryType::ENTITIES(),
                $this->mockResourceSet,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderReturnsNonQueryResult('IQueryProvider::getResourceSet'), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetResourceSetReturnsCountWhenQueryTypeIsCountProviderDoesNotHandlePaging()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;
        $SkipToken = 0;
        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = 123; //this is irrelevant
        $fakeQueryResult->results = null;

        //Because the provider doe NOT handle paging and this request needs a count, there must be results to calculate a count from
        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(false);

        /* TODO: Audit this and see if it still applies
        * $this->mockQueryProvider->shouldReceive('getResourceSet')->withArgs([
        QueryType::COUNT(),
        $this->mockResourceSet,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip,
        null
        ])->andReturn($fakeQueryResult);*/
        $this->mockQueryProvider->shouldReceive('getResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getResourceSet(
                QueryType::COUNT(),
                $this->mockResourceSet,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip,
                null
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultsMissing('IQueryProvider::getResourceSet', QueryType::COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetResourceSetReturnsCountWhenQueryTypeIsCountProviderHandlesPaging()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = null; //null is not numeric

        //Because the provider handles paging and this request needs a count, the count must be numeric
        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(true);

        /* TODO: Audit this and see if it still applies
         * $this->mockQueryProvider->shouldReceive('getResourceSet')->withArgs([
            QueryType::COUNT(),
            $this->mockResourceSet,
            $this->mockFilterInfo,
            $orderBy,
            $top,
            $skip,
            null])->andReturn($fakeQueryResult);*/
        $this->mockQueryProvider->shouldReceive('getResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getResourceSet(
                QueryType::COUNT(),
                $this->mockResourceSet,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultCountMissing('IQueryProvider::getResourceSet', QueryType::COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetResourceSetReturnsCountWhenQueryTypeIsEntitiesWithCountProviderHandlesPaging()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = null; //null is not numeric

        //Because the provider handles paging and this request needs a count, the count must be numeric
        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(true);

        /* TODO: Audit this and see if it still applies
         * $this->mockQueryProvider->shouldReceive('getResourceSet')->withArgs([
            QueryType::COUNT(),
            $this->mockResourceSet,
            $this->mockFilterInfo,
            $orderBy,
            $top,
            $skip,
            null])->andReturn($fakeQueryResult);*/
        $this->mockQueryProvider->shouldReceive('getResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockResourceSet,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultCountMissing('IQueryProvider::getResourceSet', QueryType::ENTITIES_WITH_COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetResourceSetReturnsCountWhenQueryTypeIsEntitiesWithCountProviderDoesNotHandlePaging()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = 444; //irrelevant
        $fakeQueryResult->results = null;

        //Because the provider does NOT handle paging and this request needs a count, the result must have results collection to calculate count from
        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(false);

        /* TODO: Audit this and see if it still applies
         * $this->mockQueryProvider->shouldReceive('getResourceSet')->withArgs([
            QueryType::ENTITIES_WITH_COUNT(),
            $this->mockResourceSet,
            $this->mockFilterInfo,
            $orderBy,
            $top,
            $skip,
            null])->andReturn($fakeQueryResult);*/
        $this->mockQueryProvider->shouldReceive('getResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockResourceSet,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultsMissing('IQueryProvider::getResourceSet', QueryType::ENTITIES_WITH_COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetResourceSetReturnsArrayWhenQueryTypeIsEntities()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = 2;
        $fakeQueryResult->results = null; //null is not an array

        /* TODO: Audit this and see if it still applies
         * $this->mockQueryProvider->shouldReceive('getResourceSet')->withArgs([
            QueryType::ENTITIES(),
            $this->mockResourceSet,
            $this->mockFilterInfo,
            $orderBy,
            $top,
            $skip,
            null])->andReturn($fakeQueryResult);*/
        $this->mockQueryProvider->shouldReceive('getResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getResourceSet(
                QueryType::ENTITIES(),
                $this->mockResourceSet,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultsMissing('IQueryProvider::getResourceSet', QueryType::ENTITIES()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetResourceSetReturnsArrayWhenQueryTypeIsEntitiesWithCount()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = 4;
        $fakeQueryResult->results = null; //null is not an array

        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(false);

        /* TODO: Audit this and see if it still applies
         * $this->mockQueryProvider->shouldReceive('getResourceSet')->withArgs([
            QueryType::ENTITIES_WITH_COUNT(),
            $this->mockResourceSet,
            $this->mockFilterInfo,
            $orderBy,
            $top,
            $skip,
            null])->andReturn($fakeQueryResult);
        */
        $this->mockQueryProvider->shouldReceive('getResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockResourceSet,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultsMissing('IQueryProvider::getResourceSet', QueryType::ENTITIES_WITH_COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetRelatedResourceSetJustEntities()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->results = [];

        $fakeSourceEntity = new \stdClass();

        /* TODO: Audit this to see if it still works
        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->withArgs([
            QueryType::ENTITIES(),
            $this->mockResourceSet,
            $fakeSourceEntity,
            $this->mockResourceSet2,
            $this->mockResourceProperty,
            $this->mockFilterInfo,
            $orderBy,
            $top,
            $skip
        ])->andReturn($fakeQueryResult); */

        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        $actual = $wrapper->getRelatedResourceSet(
            QueryType::ENTITIES(),
            $this->mockResourceSet,
            $fakeSourceEntity,
            $this->mockResourceSet2,
            $this->mockResourceProperty,
            $this->mockFilterInfo,
            $orderBy,
            $top,
            $skip
        );
        $this->assertEquals($fakeQueryResult, $actual);
    }

    public function testGetRelatedResourceSetReturnsNonQueryResult()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeSourceEntity = new \stdClass();

         /* TODO: Audit this to see if it still works
        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->withArgs([
        QueryType::ENTITIES(),
        $this->mockResourceSet,
        $fakeSourceEntity,
        $this->mockResourceSet2,
        $this->mockResourceProperty,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip
        ])->andReturn($fakeQueryResult); */

        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->andReturn(null);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getRelatedResourceSet(
                QueryType::ENTITIES(),
                $this->mockResourceSet,
                $fakeSourceEntity,
                $this->mockResourceSet2,
                $this->mockResourceProperty,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderReturnsNonQueryResult('IQueryProvider::getRelatedResourceSet'), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetRelatedResourceSetReturnsCountWhenQueryTypeIsCountProviderDoesNotHandlePaging()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = 123; //this is irrelevant
        $fakeQueryResult->results = null;

        //Because the provider does NOT handle paging and this request needs a count,
        // there must be results to calculate a count from
        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(false);

        $fakeSourceEntity = new \stdClass();

        /* TODO: Audit this to see if it still works
        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->withArgs([
        QueryType::ENTITIES(),
        $this->mockResourceSet,
        $fakeSourceEntity,
        $this->mockResourceSet2,
        $this->mockResourceProperty,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip
        ])->andReturn($fakeQueryResult); */

        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getRelatedResourceSet(
                QueryType::COUNT(),
                $this->mockResourceSet,
                $fakeSourceEntity,
                $this->mockResourceSet2,
                $this->mockResourceProperty,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultsMissing('IQueryProvider::getRelatedResourceSet', QueryType::COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetRelatedResourceSetReturnsCountWhenQueryTypeIsCountProviderHandlesPaging()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = null; //null is not numeric

        //Because the provider handles paging and this request needs a count, the count must be numeric
        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $fakeSourceEntity = new \stdClass();

        /* TODO: Audit this to see if it still works
        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->withArgs([
        QueryType::COUNT(),
        $this->mockResourceSet,
        $fakeSourceEntity,
        $this->mockResourceSet2,
        $this->mockResourceProperty,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip
        ])->andReturn($fakeQueryResult); */

        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getRelatedResourceSet(
                QueryType::COUNT(),
                $this->mockResourceSet,
                $fakeSourceEntity,
                $this->mockResourceSet2,
                $this->mockResourceProperty,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultCountMissing('IQueryProvider::getRelatedResourceSet', QueryType::COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetRelatedResourceSetReturnsCountWhenQueryTypeIsEntitiesWithCountProviderHandlesPaging()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = null; //null is not numeric

        //Because the provider handles paging and this request needs a count, the count must be numeric
        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $fakeSourceEntity = new \stdClass();

        /* TODO: Audit this to see if it still works
        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->withArgs([
        QueryType::ENTITIES_WITH_COUNT(),
        $this->mockResourceSet,
        $fakeSourceEntity,
        $this->mockResourceSet2,
        $this->mockResourceProperty,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip
        ])->andReturn($fakeQueryResult); */

        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getRelatedResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockResourceSet,
                $fakeSourceEntity,
                $this->mockResourceSet2,
                $this->mockResourceProperty,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultCountMissing('IQueryProvider::getRelatedResourceSet', QueryType::ENTITIES_WITH_COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetRelatedResourceSetReturnsCountWhenQueryTypeIsEntitiesWithCountProviderDoesNotHandlePaging()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = 444; //irrelevant
        $fakeQueryResult->results = null;

        //Because the provider does NOT handle paging and this request needs a count, the result must have results collection to calculate count from
        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(false);

        $fakeSourceEntity = new \stdClass();

        /* TODO: Audit this to see if it still works
        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->withArgs([
        QueryType::ENTITIES_WITH_COUNT(),
        $this->mockResourceSet,
        $fakeSourceEntity,
        $this->mockResourceSet2,
        $this->mockResourceProperty,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip
        ])->andReturn($fakeQueryResult); */

        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getRelatedResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockResourceSet,
                $fakeSourceEntity,
                $this->mockResourceSet2,
                $this->mockResourceProperty,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultsMissing('IQueryProvider::getRelatedResourceSet', QueryType::ENTITIES_WITH_COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetRelatedResourceSetReturnsArrayWhenQueryTypeIsEntities()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = 2;
        $fakeQueryResult->results = null; //null is not an array

        $fakeSourceEntity = new \stdClass();

        /* TODO: Audit this to see if it still works
        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->withArgs([
        QueryType::ENTITIES(),
        $this->mockResourceSet,
        $fakeSourceEntity,
        $this->mockResourceSet2,
        $this->mockResourceProperty,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip
        ])->andReturn($fakeQueryResult); */

        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getRelatedResourceSet(
                QueryType::ENTITIES(),
                $this->mockResourceSet,
                $fakeSourceEntity,
                $this->mockResourceSet2,
                $this->mockResourceProperty,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultsMissing('IQueryProvider::getRelatedResourceSet', QueryType::ENTITIES()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }

    public function testGetRelatedResourceSetReturnsArrayWhenQueryTypeIsEntitiesWithCount()
    {
        $orderBy = null;
        $top = 10;
        $skip = 10;

        $fakeQueryResult = new QueryResult();
        $fakeQueryResult->count = 4;
        $fakeQueryResult->results = null; //null is not an array

        $fakeSourceEntity = new \stdClass();

        $this->mockQueryProvider->shouldReceive('handlesOrderedPaging')->andReturn(true);

        /* TODO: Audit this to see if it still works
        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->withArgs([
        QueryType::ENTITIES_WITH_COUNT(),
        $this->mockResourceSet,
        $fakeSourceEntity,
        $this->mockResourceSet2,
        $this->mockResourceProperty,
        $this->mockFilterInfo,
        $orderBy,
        $top,
        $skip
        ])->andReturn($fakeQueryResult); */

        $this->mockQueryProvider->shouldReceive('getRelatedResourceSet')->andReturn($fakeQueryResult);

        $wrapper = $this->getMockedWrapper();

        try {
            $wrapper->getRelatedResourceSet(
                QueryType::ENTITIES_WITH_COUNT(),
                $this->mockResourceSet,
                $fakeSourceEntity,
                $this->mockResourceSet2,
                $this->mockResourceProperty,
                $this->mockFilterInfo,
                $orderBy,
                $top,
                $skip
            );
            $this->fail('expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(Messages::queryProviderResultsMissing('IQueryProvider::getRelatedResourceSet', QueryType::ENTITIES_WITH_COUNT()), $ex->getMessage());
            $this->assertEquals(500, $ex->getStatusCode());
        }
    }
}
