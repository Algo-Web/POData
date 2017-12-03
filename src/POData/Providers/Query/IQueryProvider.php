<?php

namespace POData\Providers\Query;

use POData\Providers\Expression\IExpressionProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

/**
 * Class IQueryProvider.
 */
interface IQueryProvider
{
    /**
     * Indicates if the QueryProvider can handle ordered paging, this means respecting order, skip, and top parameters
     * If the query provider can not handle ordered paging, it must return the entire result set and POData will
     * perform the ordering and paging.
     *
     * @return bool True if the query provider can handle ordered paging, false if POData should perform the paging
     */
    public function handlesOrderedPaging();

    /**
     * Gets the expression provider used by to compile OData expressions into expression used by this query provider.
     *
     * @return IExpressionProvider
     */
    public function getExpressionProvider();

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
        $filterInfo = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null,
        array $eagerLoad = null
    );

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
    );

    /**
     * Get related resource set for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection
     * http://host/EntitySet?$expand=NavigationPropertyToCollection.
     *
     * @param QueryType          $queryType            Is this is a query for a count, entities, or entities-with-count
     * @param ResourceSet        $sourceResourceSet    The entity set containing the source entity
     * @param object             $sourceEntityInstance The source entity instance
     * @param ResourceSet        $targetResourceSet    The resource set pointed to by the navigation property
     * @param ResourceProperty   $targetProperty       The navigation property to retrieve
     * @param FilterInfo|null    $filter               The $filter parameter of the OData query.  NULL if none specified
     * @param mixed|null         $orderBy              sorted order if we want to get the data in some specific order
     * @param int|null           $top                  number of records which need to be retrieved
     * @param int|null           $skip                 number of records which need to be skipped
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
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null
    );

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
     * @return object|null Returns entity instance if found, else null
     */
    public function getResourceFromRelatedResourceSet(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    );

    /**
     * Get related resource for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToSingleEntity
     * http://host/EntitySet?$expand=NavigationPropertyToSingleEntity.
     *
     * @param ResourceSet      $sourceResourceSet    The entity set containing the source entity
     * @param object           $sourceEntityInstance The source entity instance
     * @param ResourceSet      $targetResourceSet    The entity set pointed to by the navigation property
     * @param ResourceProperty $targetProperty       The navigation property to fetch
     *
     * @return object|null The related resource if found, else null
     */
    public function getRelatedResourceReference(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    );

    /**
     * Updates a resource.
     *
     * @param ResourceSet   $sourceResourceSet    The entity set containing the source entity
     * @param object        $sourceEntityInstance The source entity instance
     * @param KeyDescriptor $keyDescriptor        The key identifying the entity to fetch
     * @param object        $data                 the New data for the entity instance
     * @param bool          $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object|null the new resource value if it is assignable, or throw exception for null
     */
    public function updateResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        KeyDescriptor $keyDescriptor,
        $data,
        $shouldUpdate = false
    );

    /**
     * Puts an entity instance to entity set identified by a key.
     *
     * @param ResourceSet   $sourceresourceSet The entity set containing the entity to update
     * @param KeyDescriptor $keyDescriptor     The key identifying the entity to update
     * @param $data
     *
     * @return bool|null Returns result of executing query
     */
    public function putResource(
        ResourceSet $sourceresourceSet,
        KeyDescriptor $keyDescriptor,
        $data
    );

    /**
     * Delete resource from a resource set.
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     *
     * @return bool true if resources successfully deleted, otherwise false
     */
    public function deleteResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance
    );

    /**
     * Create a new resource in a resource set.
     * @param ResourceSet $sourceresourceSet The entity set containing the entity to fetch
     * @param object|null $keyDescriptor
     * @param object      $data              the New data for the entity instance
     *
     * @return object|null returns the newly created model if successful, or null if model creation failed
     */
    public function createResourceforResourceSet(
        ResourceSet $sourceresourceSet,
        $keyDescriptor,
        $data
    );

    /**
     * Create multiple new resources in a resource set.
     * @param ResourceSet $sourceResourceSet The entity set containing the entity to fetch
     * @param object[]    $data              The new data for the entity instance
     *
     * @return object[]|null returns the newly created model if successful, or null if model creation failed
     */
    public function createBulkResourceforResourceSet(
        ResourceSet $sourceResourceSet,
        array $data
    );

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
    );

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
    );

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
    );

    /**
     * Start database transaction.
     *
     * @return void
     */
    public function startTransaction();

    /**
     * Commit database transaction.
     *
     * @return void
     */
    public function commitTransaction();

    /**
     * Abort database transaction.
     *
     * @return void
     */
    public function rollBackTransaction();
}
