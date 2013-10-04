<?php

namespace POData\Providers\Metadata;

/**
 * Class IMetadataProvider
 *
 * The class which implements this interface is responsible for describing the
 * shape or "model" of the information in custom data source
 *
 * @package POData\Providers\Metadata
 */
interface IMetadataProvider
{
    /**
     * To get the Container name for the data source.
     * 
     * @return string that contains the name of the container
     */
    public function getContainerName();

    /**
     * To get Namespace name for the data source.
     * 
     * @return string that contains the namespace name.
     */
    public function getContainerNamespace();

    /**
     *  To get all entity set information
     *  
     *  @return ResourceSet[]
     */
    public function getResourceSets();

    /**
     * To get all resource types in the data source.
     * 
     * @return ResourceType[]
     */
    public function getTypes();

    /**
     * To get a resource set based on the specified resource set name.
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceSet|null resource set with the given name if found else NULL
     *
     */
    public function resolveResourceSet($name);

    /**
     * To get a resource type based on the resource set name.
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceType|null resource type with the given resource set name if found else NULL
     *
     */
    public function resolveResourceType($name);

    /**
     * The method must return a collection of all the types derived from 
     * $resourceType The collection returned should NOT include the type 
     * passed in as a parameter
     *
     * @param ResourceType $resourceType Resource to get derived resource types from
     *
     * 
     * @return ResourceType[]
     */
    public function getDerivedTypes(ResourceType $resourceType);

    /**
     *
     * @param ResourceType $resourceType Resource to check for derived resource types.
     *
     * @return boolean  true if $resourceType represents an Entity Type which has derived Entity Types, else false.
     *
     */
    public function hasDerivedTypes(ResourceType $resourceType);

    /**
     * Gets the ResourceAssociationSet instance for the given source 
     * association end.
     * 
     * @param ResourceSet      $resourceSet      Resource set of the source
     *                                           association end
     * @param ResourceType     $resourceType     Resource type of the source
     *                                           association end
     * @param ResourceProperty $resourceProperty Resource property of the source
     *                                           association end
     * 
     * @return ResourceAssociationSet
     */
    public function getResourceAssociationSet(ResourceSet $resourceSet, 
        ResourceType $resourceType, ResourceProperty $resourceProperty
    );
}