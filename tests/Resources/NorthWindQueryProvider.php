<?php
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Query\IDataServiceQueryProvider;
require_once ("ODataProducer\Providers\Query\IDataServiceQueryProvider.php");

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
     * @return Object/NULL Returns entity instance if found else null
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
     * @return array(Objects)/array() Array of related resource if exists, if no related resources found returns empty array
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
     * @return Object/null 
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
?>