<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

// Note: This QP2 implementation is to test IDSQP2::getExpressionProvider functionality
// we will not test the actual data, instead the sql query generated.

class NorthWindQueryProvider implements IQueryProvider
{
    /**
     * The not implemented error message.
     *
     * @var string
     */
    private $_message = 'This functionality is not implemented as the class is only for testing IExpressionProvider for SQL-Server';

    public function handlesOrderedPaging()
    {
        throw ODataException::createNotImplementedError($this->_message);
    }

    public function getExpressionProvider()
    {
        return new NorthWindExpressionProvider();
    }

    /**
     * Gets collection of entities belongs to an entity set
     * IE: http://host/EntitySet
     *  http://host/EntitySet?$skip=10&$top=5&filter=Prop gt Value.
     *
     * @param QueryType                 $queryType   indicates if this is a query for a count, entities, or entities with a count
     * @param ResourceSet               $resourceSet The entity set containing the entities to fetch
     * @param FilterInfo                $filterInfo  represents the $filter parameter of the OData query.  NULL if no $filter specified
     * @param null|InternalOrderByInfo  $orderBy     sorted order if we want to get the data in some specific order
     * @param int                       $top         number of records which need to be retrieved
     * @param int                       $skip        number of records which need to be skipped
     * @param SkipTokenInfo|null        $skipToken   value indicating what records to skip
     *
     * @return QueryResult
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        $filter = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null
    ) {
        // TODO: Implement getResourceSet() method.
    }

    /**
     * Gets an entity instance from an entity set identified by a key
     * IE: http://host/EntitySet(1L)
     * http://host/EntitySet(KeyA=2L,KeyB='someValue').
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to fetch
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to fetch
     *
     * @return object|null Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor
    ) {
        // TODO: Implement getResourceFromResourceSet() method.
    }

    /**
     * Get related resource set for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection
     * http://host/EntitySet?$expand=NavigationPropertyToCollection.
     *
     * @param QueryType             $queryType            indicates if this is a query for a count, entities, or entities with a count
     * @param ResourceSet           $sourceResourceSet    The entity set containing the source entity
     * @param object                $sourceEntityInstance The source entity instance
     * @param ResourceSet           $targetResourceSet    The resource set of containing the target of the navigation property
     * @param ResourceProperty      $targetProperty       The navigation property to retrieve
     * @param FilterInfo            $filter               represents the $filter parameter of the OData query.  NULL if no $filter specified
     * @param mixed                 $orderBy              sorted order if we want to get the data in some specific order
     * @param int                   $top                  number of records which need to be retrieved
     * @param int                   $skip                 number of records which need to be skipped
     * @param SkipTokenInfo|null    $skipToken            value indicating what records to skip
     *
     * @return QueryResult
     */
    public function getRelatedResourceSet(
        QueryType $queryType,
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        $filter = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null
    ) {
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
     * @param object        $data                 The New data for the entity instance.
     * @param bool          $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object|null The new resource value if it is assignable or throw exception for null.
     */
    public function updateResource(ResourceSet $sourceResourceSet, $sourceEntityInstance, KeyDescriptor $keyDescriptor, $data, $shouldUpdate = false)
    {
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
}
