<?php

namespace POData\Providers\Query;

use POData\Providers\Metadata\ResourceProperty;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\Providers\Metadata\ResourceSet;


/**
 * Class IDataServiceQueryProvider
 *
 * The class which implements this interface is responsible responding the queries
 * for entity set, entity instance and related entities
 *
 * @package POData\Providers\Query
 */
interface IDataServiceQueryProvider
{
    /**
     * Gets collection of entities belongs to an entity set
     * 
     * @param ResourceSet $resourceSet The entity set whose entities needs 
     *                                 to be fetched
     * 
     * @return object[]
     */
    public function getResourceSet(ResourceSet $resourceSet);

    /**
     * Gets an entity instance from an entity set identified by a key
     * 
     * @param ResourceSet   $resourceSet   The entity set from which an entity
     *                                     needs to be fetched
     * @param KeyDescriptor $keyDescriptor The key to identify the entity to be 
     *                                     fetched
     * 
     * @return Object|null Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, 
        KeyDescriptor $keyDescriptor
    );

    /**
     * Gets a related entity instance from an entity set identified by a key
     * 
     * @param ResourceSet      $sourceResourceSet    The entity set related to the entity to be fetched.
     *
     * @param object           $sourceEntityInstance The related entity instance.
     * @param ResourceSet      $targetResourceSet    The entity set from which entity needs to be fetched.
     *
     * @param ResourceProperty $targetProperty       The metadata of the target property.
     *
     * @param KeyDescriptor    $keyDescriptor        The key to identify the entity to be fetched.
     *
     * 
     * @return Object|null Returns entity instance if found else null
     */
    public function getResourceFromRelatedResourceSet(ResourceSet $sourceResourceSet,
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    );

    /**
     * Get related resource set for a resource
     * 
     * @param ResourceSet      $sourceResourceSet    The source resource set
     * @param mixed            $sourceEntityInstance The resource
     * @param ResourceSet      $targetResourceSet    The resource set of the navigation property
     *
     * @param ResourceProperty $targetProperty       The navigation property to be retrieved
     *
     *                                               
     * @return object[] Array of related resource if exists, if no related resources found returns empty array
     *
     */
    public function  getRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    );

    /**
     * Get related resource for a resource
     * 
     * @param ResourceSet      $sourceResourceSet    The source resource set
     * @param mixed            $sourceEntityInstance The source resource
     * @param ResourceSet      $targetResourceSet    The resource set of the navigation property
     *
     * @param ResourceProperty $targetProperty       The navigation property to be retrieved
     *
     * 
     * @return object|null The related resource if exists else null
     */
    public function getRelatedResourceReference(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    );
}