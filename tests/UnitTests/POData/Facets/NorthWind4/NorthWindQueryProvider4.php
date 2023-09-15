<?php

declare(strict_types=1);

namespace UnitTests\POData\Facets\NorthWind4;

use POData\Providers\Expression\IExpressionProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Query\IReadQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

// Note: This QP2 implementation is to test IDSQP2::getExpressionProvider functionality
// we will not test the actual data, instead the sql query generated.

class NorthWindQueryProvider4 implements IReadQueryProvider
{
    /**
     * Reference to the custom expression provider.
     *
     * @var \POData\Providers\Expression\IExpressionProvider
     */
    private $_northWindSQLSRVExpressionProvider;

    /**
     * (non-PHPdoc).
     *
     * @see POData\Providers\Query.IReadQueryProvider::getExpressionProvider()
     */
    public function getExpressionProvider(): IExpressionProvider
    {
        if (null === $this->_northWindSQLSRVExpressionProvider) {
            $this->_northWindSQLSRVExpressionProvider = new NorthWindDSExpressionProvider4();
        }

        return $this->_northWindSQLSRVExpressionProvider;
    }

    /**
     * Indicates if the QueryProvider can handle ordered paging, this means respecting order, skip, and top parameters
     * If the query provider can not handle ordered paging, it must return the entire result set and POData will
     * perform the ordering and paging.
     *
     * @return bool True if the query provider can handle ordered paging, false if POData should perform the paging
     */
    public function handlesOrderedPaging(): bool
    {
        // TODO: Implement handlesOrderedPaging() method.
    }

    /**
     * Gets collection of entities belongs to an entity set
     * IE: http://host/EntitySet
     *  http://host/EntitySet?$skip=10&$top=5&filter=Prop gt Value.
     *
     * @param QueryType                $queryType   Is this is a query for a count, entities, or entities-with-count
     * @param ResourceSet              $resourceSet The entity set containing the entities to fetch
     * @param FilterInfo|null          $filterInfo  The $filter parameter of the OData query.  NULL if none specified
     * @param null|InternalOrderByInfo $orderBy     sorted order if we want to get the data in some specific order
     * @param int|null                 $top         number of records which need to be retrieved
     * @param int|null                 $skip        number of records which need to be skipped
     * @param SkipTokenInfo|null       $skipToken   value indicating what records to skip
     * @param string[]|null            $eagerLoad   array of relations to eager load
     *
     * @return QueryResult
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        FilterInfo $filterInfo = null,
        InternalOrderByInfo $orderBy = null,
        int $top = null,
        int $skip = null,
        SkipTokenInfo $skipToken = null,
        array $eagerLoad = null
    ): QueryResult {
        // TODO: Implement getResourceSet() method.
    }

    /**
     * Gets an entity instance from an entity set identified by a key
     * IE: http://host/EntitySet(1L)
     * http://host/EntitySet(KeyA=2L,KeyB='someValue').
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to fetch
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to fetch
     * @param string[]|null $eagerLoad     array of relations to eager load
     *
     * @return object|null Returns entity instance if found, else null
     */
    public function getResourceFromResourceSet(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor,
        array $eagerLoad = null
    ) {
        // TODO: Implement getResourceFromResourceSet() method.
    }

    /**
     * Get related resource set for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection
     * http://host/EntitySet?$expand=NavigationPropertyToCollection.
     *
     * @param QueryType          $queryType            indicates if this is a query for a count, entities, or entities with a count
     * @param ResourceSet        $sourceResourceSet    The entity set containing the source entity
     * @param object             $sourceEntityInstance The source entity instance
     * @param ResourceSet        $targetResourceSet    The resource set of containing the target of the navigation property
     * @param ResourceProperty   $targetProperty       The navigation property to retrieve
     * @param FilterInfo|null    $filter               represents the $filter parameter of the OData query.  NULL if no $filter specified
     * @param mixed              $orderBy              sorted order if we want to get the data in some specific order
     * @param int                $top                  number of records which need to be retrieved
     * @param int                $skip                 number of records which need to be skipped
     * @param SkipTokenInfo|null $skipToken            value indicating what records to skip
     *
     * @return QueryResult
     */
    public function getRelatedResourceSet(
        QueryType $queryType,
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        FilterInfo $filter = null,
        InternalOrderByInfo $orderBy = null,
        int $top = null,
        int $skip = null,
        SkipTokenInfo $skipToken = null
    ): QueryResult {
        // TODO: Implement getRelatedResourceSet() method.
    }

    /**
     * Gets a related entity instance from an entity set identified by a key
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection(33).
     *
     * @param ResourceSet      $sourceResourceSet    The entity set containing the source entity
     * @param object           $sourceEntityInstance The source entity instance
     * @param ResourceSet      $targetResourceSet    The entity set containing the entity to fetch
     * @param ResourceProperty $targetProperty       The metadata of the target property
     * @param KeyDescriptor    $keyDescriptor        The key identifying the entity to fetch
     *
     * @return object|null Returns entity instance if found else null
     */
    public function getResourceFromRelatedResourceSet(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        // TODO: Implement getResourceFromRelatedResourceSet() method.
    }

    /**
     * Get related resource for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToSingleEntity
     * http://host/EntitySet?$expand=NavigationPropertyToSingleEntity.
     *
     * @param ResourceSet      $sourceResourceSet    The entity set containing the source entity
     * @param object           $sourceEntityInstance The source entity instance
     * @param ResourceSet      $targetResourceSet    The entity set containing the entity pointed to by the navigation property
     * @param ResourceProperty $targetProperty       The navigation property to fetch
     *
     * @return object|null The related resource if found else null
     */
    public function getRelatedResourceReference(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
        // TODO: Implement getRelatedResourceReference() method.
    }

    /**
     * Updates a resource.
     *
     * @param ResourceSet   $sourceResourceSet    The entity set containing the source entity
     * @param object        $sourceEntityInstance The source entity instance
     * @param KeyDescriptor $keyDescriptor        The key identifying the entity to fetch
     * @param object        $data                 the New data for the entity instance
     * @param bool          $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object|null the new resource value if it is assignable or throw exception for null
     */
    public function updateResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        KeyDescriptor $keyDescriptor,
        $data,
        $shouldUpdate = false
    ) {
    }

    /*
     * Delete resource from a resource set.
     * @param ResourceSet|null $resourceSet
     * @param object           $sourceEntityInstance
     *
     * return bool true if resources sucessfully deteled, otherwise false.
     */
    public function deleteResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance
    ) {
    }

    /*
     * @param ResourceSet      $resourceSet   The entity set containing the entity to fetch
     * @param object           $sourceEntityInstance The source entity instance
     * @param object           $data                 The New data for the entity instance.
     *
     * returns object|null returns the newly created model if sucessful or null if model creation failed.
     */
    public function createResourceforResourceSet(
        ResourceSet $resourceSet,
        $sourceEntityInstance,
        $data
    ) {
    }

    /**
     * Puts an entity instance to entity set identified by a key.
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to update
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to update
     * @param $data
     *
     * @return bool|null Returns result of executiong query
     */
    public function putResource(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor,
        $data
    ) {
        // TODO: Implement putResource() method.
    }

    /**
     * Create multiple new resources in a resource set.
     * @param ResourceSet $sourceResourceSet The entity set containing the entity to fetch
     * @param object[]    $data              The new data for the entity instance
     *
     * @return object|null returns the newly created model if successful, or null if model creation failed
     */
    public function createBulkResourceforResourceSet(
        ResourceSet $sourceResourceSet,
        array $data
    ) {
        // TODO: Implement createBulkResourceforResourceSet() method.
    }

    /**
     * Updates a group of resources in a resource set.
     *
     * @param ResourceSet     $sourceResourceSet    The entity set containing the source entity
     * @param object          $sourceEntityInstance The source entity instance
     * @param KeyDescriptor[] $keyDescriptor        The key identifying the entity to fetch
     * @param object[]        $data                 The new data for the entity instances
     * @param bool            $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object[]|null the new resource value if it is assignable, or throw exception for null
     */
    public function updateBulkResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        array $keyDescriptor,
        array $data,
        $shouldUpdate = false
    ) {
        // TODO: Implement updateBulkResource() method.
    }

    /**
     * Attaches child model to parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param object      $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     */
    public function hookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        // TODO: Implement hookSingleModel() method.
    }

    /**
     * Removes child model from parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param object      $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     */
    public function unhookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        // TODO: Implement unhookSingleModel() method.
    }

    /**
     * Start database transaction.
     * @param mixed $isBulk
     */
    public function startTransaction($isBulk = false)
    {
        // TODO: Implement startTransaction() method.
    }

    /**
     * Commit database transaction.
     */
    public function commitTransaction()
    {
        // TODO: Implement commitTransaction() method.
    }

    /**
     * Abort database transaction.
     */
    public function rollBackTransaction()
    {
        // TODO: Implement rollBackTransaction() method.
    }
}
