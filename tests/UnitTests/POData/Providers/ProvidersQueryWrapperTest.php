<?php

namespace UnitTests\POData\Providers;

use Mockery as m;
use POData\Common\ODataException;
use POData\Providers\Expression\IExpressionProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\ProvidersQueryWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use UnitTests\POData\ObjectModel\reusableEntityClass2;
use UnitTests\POData\TestCase;

class ProvidersQueryWrapperTest extends TestCase
{
    private $queryType;
    private $sourceResourceSet;
    private $targResourceSet;
    private $targProperty;
    private $filterInfo;

    public function setUp()
    {
        $this->queryType = m::mock(QueryType::class)->makePartial();
        $this->sourceResourceSet = m::mock(ResourceSet::class)->makePartial();
        $this->targResourceSet = m::mock(ResourceSet::class)->makePartial();
        $this->targProperty = m::mock(ResourceProperty::class)->makePartial();
        $this->filterInfo = m::mock(FilterInfo::class)->makePartial();
    }

    public function testGetRelatedResourceSetNotInstanceOfQueryResult()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getRelatedResourceSet')->andReturnNull()->once();

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'The implementation of the method IQueryProvider::getRelatedResourceSet'
                    .' must return a QueryResult instance.';
        $actual = null;

        try {
            $foo->getRelatedResourceSet(
                $this->queryType,
                $this->sourceResourceSet,
                new \StdClass(),
                $this->targResourceSet,
                $this->targProperty,
                $this->filterInfo,
                null,
                null,
                null,
                null
            );
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelatedResourceSetHandlesPagingYetNonNumericCount()
    {
        $this->queryType = QueryType::ENTITIES_WITH_COUNT();
        $result = m::mock(QueryResult::class)->makePartial();
        $result->count = 'BORK BORK BORK!';

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getRelatedResourceSet')->andReturn($result)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'The implementation of the method IQueryProvider::getRelatedResourceSet must return'
                    .' a QueryResult instance with a count for queries of type ENTITIES_WITH_COUNT.';
        $actual = null;

        try {
            $foo->getRelatedResourceSet(
                $this->queryType,
                $this->sourceResourceSet,
                new \StdClass(),
                $this->targResourceSet,
                $this->targProperty,
                $this->filterInfo,
                null,
                null,
                null,
                null
            );
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelatedResourceSetDoesNotHandlePagingYetNonArrayResult()
    {
        $this->queryType = QueryType::ENTITIES_WITH_COUNT();
        $result = m::mock(QueryResult::class)->makePartial();
        $result->results = 'BORK BORK BORK!';

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getRelatedResourceSet')->andReturn($result)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(false);

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'The implementation of the method IQueryProvider::getRelatedResourceSet must return'
                    .' a QueryResult instance with an array of results for queries of type ENTITIES_WITH_COUNT.';
        $actual = null;

        try {
            $foo->getRelatedResourceSet(
                $this->queryType,
                $this->sourceResourceSet,
                new \StdClass(),
                $this->targResourceSet,
                $this->targProperty,
                $this->filterInfo,
                null,
                null,
                null,
                null
            );
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelatedResourceSetHandlesPagingYetNonArrayResult()
    {
        $this->queryType = QueryType::ENTITIES();
        $result = m::mock(QueryResult::class)->makePartial();
        $result->results = 'BORK BORK BORK!';

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getRelatedResourceSet')->andReturn($result)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'The implementation of the method IQueryProvider::getRelatedResourceSet must return'
                    .' a QueryResult instance with an array of results for queries of type ENTITIES.';
        $actual = null;

        try {
            $foo->getRelatedResourceSet(
                $this->queryType,
                $this->sourceResourceSet,
                new \StdClass(),
                $this->targResourceSet,
                $this->targProperty,
                $this->filterInfo,
                null,
                null,
                null,
                null
            );
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelatedResourceSetHandlesPagingAndArrayResult()
    {
        $this->queryType = QueryType::ENTITIES();
        $result = m::mock(QueryResult::class)->makePartial();
        $result->results = ['BORK BORK BORK!'];

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getRelatedResourceSet')->andReturn($result)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);

        $result = $foo->getRelatedResourceSet(
            $this->queryType,
            $this->sourceResourceSet,
            new \StdClass(),
            $this->targResourceSet,
            $this->targProperty,
            $this->filterInfo,
            null,
            null,
            null,
            null
        );
        $this->assertTrue($result instanceof QueryResult);
        $this->assertTrue(is_array($result->results));
        $this->assertEquals(1, count($result->results));
        $this->assertEquals('BORK BORK BORK!', $result->results[0]);
    }

    public function testGetResourceSetHandlesPagingAndArrayResult()
    {
        $this->queryType = QueryType::ENTITIES();
        $result = m::mock(QueryResult::class)->makePartial();
        $result->results = ['BORK BORK BORK!'];

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getResourceSet')->andReturn($result)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);

        $result = $foo->getResourceSet($this->queryType, $this->sourceResourceSet);
        $this->assertTrue($result instanceof QueryResult);
        $this->assertTrue(is_array($result->results));
        $this->assertEquals(1, count($result->results));
        $this->assertEquals('BORK BORK BORK!', $result->results[0]);
    }

    public function testPutResource()
    {
        $key = m::mock(KeyDescriptor::class);

        $result = m::mock(QueryResult::class)->makePartial();
        $result->results = ['BORK BORK BORK!'];

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('putResource')->andReturn($result)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);
        $result = $foo->putResource($this->sourceResourceSet, $key, $result);
        $this->assertTrue($result instanceof QueryResult);
        $this->assertTrue(is_array($result->results));
        $this->assertEquals(1, count($result->results));
        $this->assertEquals('BORK BORK BORK!', $result->results[0]);
    }

    public function testGetNullExpressionProviderThrowException()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getExpressionProvider')->andReturnNull()->once();

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'The value returned by IQueryProvider::getExpressionProvider method must not be null or empty';
        $actual = null;

        try {
            $foo->getExpressionProvider();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetWrongTypeExpressionProviderThrowException()
    {
        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getExpressionProvider')->andReturn(new \stdClass())->once();

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'The value returned by IQueryProvider::getExpressionProvider method must be an'
                    .' implementation of IExpressionProvider';
        $actual = null;

        try {
            $foo->getExpressionProvider();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRightTypeExpressionProvider()
    {
        $expression = m::mock(IExpressionProvider::class);
        $expression->shouldReceive('getIteratorName')->andReturn('chairGoRound');

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getExpressionProvider')->andReturn($expression)->once();

        $foo = new ProvidersQueryWrapper($query);

        $result = $foo->getExpressionProvider();
        $this->assertTrue($expression instanceof IExpressionProvider);
        $this->assertEquals('chairGoRound', $result->getIteratorName());
    }

    public function testCreateResourceForResourceSet()
    {
        $key = m::mock(KeyDescriptor::class);

        $data = new reusableEntityClass2('hammer', 'time!');

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('createResourceforResourceSet')->andReturn($data)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);
        $result = $foo->createResourceforResourceSet($this->sourceResourceSet, $data, $key);
        $this->assertTrue($result instanceof reusableEntityClass2);
        $this->assertEquals('hammer', $data->name);
        $this->assertEquals('time!', $data->type);
    }

    public function testDeleteResource()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('deleteResource')->andReturn(true)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);
        $result = $foo->deleteResource($this->sourceResourceSet, $data);
        $this->assertTrue($result);
    }

    public function testUpdateResource()
    {
        $key = m::mock(KeyDescriptor::class);

        $data = new reusableEntityClass2('hammer', 'time!');

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('updateResource')->andReturn($data)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);
        $result = $foo->updateResource($this->sourceResourceSet, $data, $key, $data, false);
        $this->assertTrue($result instanceof reusableEntityClass2);
        $this->assertEquals('hammer', $data->name);
        $this->assertEquals('time!', $data->type);
    }

    public function testGetRelatedResourceReferenceResourceTypeMismatchThrowException()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType->getName')->andReturn('ResourceSet')->once();

        $key = m::mock(KeyDescriptor::class);

        $data = new reusableEntityClass2('hammer', 'time!');

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getRelatedResourceReference')->andReturn($data)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $this->targResourceSet->shouldReceive('getResourceType')->andReturn($type);

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'The implementation of the method IQueryProvider::getRelatedResourceReference must return an'
                    .' instance of type described by resource set\'s type(ResourceSet) or null if resource does'
                    .' not exist.';
        $actual = null;

        try {
            $foo->getRelatedResourceReference(
                $this->sourceResourceSet,
                $data,
                $this->targResourceSet,
                $this->targProperty
            );
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelatedResourceReferenceResourceNullKeysThrowException()
    {
        $data = new reusableEntityClass2('hammer', 'time!');
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType->getName')->andReturn(get_class($data))->once();
        $type->shouldReceive('getPropertyValue')->andReturnNull()->once();

        $keyProperties = ['foo' => 'bar'];
        $this->targProperty->shouldReceive('getResourceType->getKeyProperties')->andReturn($keyProperties);

        $key = m::mock(KeyDescriptor::class);

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getRelatedResourceReference')->andReturn($data)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $this->targResourceSet->shouldReceive('getResourceType')->andReturn($type);

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'The IDSQP::getRelatedResourceReference implementation returns an entity with'
                    .' null key propert(y|ies).';
        $actual = null;

        try {
            $foo->getRelatedResourceReference(
                $this->sourceResourceSet,
                $data,
                $this->targResourceSet,
                $this->targProperty
            );
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelatedResourceReferenceResourceNonNullKey()
    {
        $data = new reusableEntityClass2('hammer', 'time!');
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType->getName')->andReturn(get_class($data))->once();
        $type->shouldReceive('getPropertyValue')->andReturn('M.C.')->once();

        $keyProperties = ['foo' => 'bar'];
        $this->targProperty->shouldReceive('getResourceType->getKeyProperties')->andReturn($keyProperties);

        $key = m::mock(KeyDescriptor::class);

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getRelatedResourceReference')->andReturn($data)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $this->targResourceSet->shouldReceive('getResourceType')->andReturn($type);

        $foo = new ProvidersQueryWrapper($query);

        $result = $foo->getRelatedResourceReference(
            $this->sourceResourceSet,
            $data,
            $this->targResourceSet,
            $this->targProperty
        );
        $this->assertTrue($result instanceof reusableEntityClass2);
        $this->assertEquals('hammer', $data->name);
        $this->assertEquals('time!', $data->type);
    }

    public function testGetResourceFromRelatedResourceSetNullInstanceThrowException()
    {
        $data = new reusableEntityClass2('hammer', 'time!');
        $type = m::mock(ResourceType::class);
        $property = m::mock(ResourceProperty::class);

        $key = m::mock(KeyDescriptor::class);

        $this->targResourceSet->shouldReceive('getName')->andReturn('CynicProject');

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getResourceFromRelatedResourceSet')->andReturn(null)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'Resource not found for the segment \'CynicProject\'.';
        $actual = null;

        try {
            $foo->getResourceFromRelatedResourceSet(
                $this->sourceResourceSet,
                $data,
                $this->targResourceSet,
                $property,
                $key
            );
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceFromRelatedResourceSetNullKeysThrowException()
    {
        $data = new reusableEntityClass2('hammer', 'time!');
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType->getName')->andReturn(get_class($data))->once();
        $type->shouldReceive('getPropertyValue')->andReturnNull()->once();

        $property = m::mock(ResourceProperty::class);

        $keyProperties = ['foo' => 'bar'];

        $key = m::mock(KeyDescriptor::class);
        $key->shouldReceive('getValidatedNamedValues')->andReturn($keyProperties);

        $this->targProperty->shouldReceive('getResourceType->getKeyProperties')->andReturn($keyProperties);

        $this->targResourceSet->shouldReceive('getName')->andReturn('CynicProject');
        $this->targResourceSet->shouldReceive('getResourceType')->andReturn($type);

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getResourceFromRelatedResourceSet')->andReturn($data)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $foo = new ProvidersQueryWrapper($query);

        $expected = 'The IQueryProvider::getResourceFromRelatedResourceSet implementation returns an entity'
                    .' with null key propert(y|ies).';
        $actual = null;

        try {
            $foo->getResourceFromRelatedResourceSet(
                $this->sourceResourceSet,
                $data,
                $this->targResourceSet,
                $property,
                $key
            );
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetNullResourceFromResourceSet()
    {
        $expected = "Resource not found for the segment 'resourceSet'.";
        $actual = null;

        $query = m::mock(IQueryProvider::class);
        $query->shouldReceive('getResourceFromResourceSet')->andReturn(null)->once();
        $query->shouldReceive('handlesOrderedPaging')->andReturn(true);

        $key = m::mock(KeyDescriptor::class);

        $this->sourceResourceSet->shouldReceive('getName')->andReturn('resourceSet');

        $foo = new ProvidersQueryWrapper($query);

        try {
            $foo->getResourceFromResourceSet($this->sourceResourceSet, $key);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }

        $this->assertEquals($expected, $actual);
    }
}
