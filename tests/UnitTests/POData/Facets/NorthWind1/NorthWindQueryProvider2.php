<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Query\IDataServiceQueryProvider;

class NorthWindQueryProvider2 implements IDataServiceQueryProvider
{

    /**
     * Constructs a new instance of NorthWindQueryProvider
     * 
     */
    public function __construct()
    {
    }

    /**
     * Gets collection of entities belongs to an entity set
     * 
     * @param ResourceSet $resourceSet The entity set whose entities needs to be fetched
     * 
     * @return array(Object)
     */
    public function getResourceSet(ResourceSet $resourceSet)
    {   
    }

    /**
     * Gets an entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet $resourceSet     The entity set from which an entity needs to be fetched
     * @param KeyDescriptor $keyDescriptor The key to identify the entity to be fetched
     * 
     * @return Object|null Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {   
    }

    public function  getResourceFromRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {        
    }

    /**
     * TODO
     * 
     * @return object[] Array of related resource if exists, if no related resources found returns empty array
     */
    public function  getRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ){    
    }

    /**
     * TODO
     * 
     * @return Object|null 
     */
    public function getRelatedResourceReference(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    )
    {
        }

    
    /**
     * The destructor     
     */
    public function __destruct()
    {
    }
}