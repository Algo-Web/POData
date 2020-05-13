<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

use Exception;

/**
 * Class IMetadataProvider.
 *
 * The class which implements this interface is responsible for describing the
 * shape or "model" of the information in custom data source
 */
interface IMetadataProvider
{
    /**
     * To get the Container name for the data source.
     *
     * @return string that contains the name of the container
     */
    public function getContainerName(): string;

    /**
     * To get Namespace name for the data source.
     *
     * @return string that contains the namespace name
     */
    public function getContainerNamespace(): string;

    /**
     *  To get all entity set information.
     *
     * @return ResourceSet[]
     */
    public function getResourceSets(): array;

    /**
     * To get all resource types in the data source.
     *
     * @return ResourceType[]
     */
    public function getTypes(): array;

    /**
     * To get a resource set based on the specified resource set name.
     *
     * @param string $name Name of the resource set
     *
     * @return ResourceSet|null resource set with the given name if found else NULL
     */
    public function resolveResourceSet($name): ?ResourceSet;

    /**
     * To get a resource type based on the resource set name.
     *
     * @param string $name Name of the resource set
     *
     * @return ResourceType|null resource type with the given resource set name if found else NULL
     */
    public function resolveResourceType($name): ?ResourceType;

    /**
     * The method must return a collection of all the types derived from
     * $resourceType The collection returned should NOT include the type
     * passed in as a parameter.
     *
     * @param ResourceEntityType $resourceType Resource to get derived resource types from
     *
     * @return ResourceType[]
     */
    public function getDerivedTypes(ResourceEntityType $resourceType): array;

    /**
     * @param ResourceEntityType $resourceType Resource to check for derived resource types
     *
     * @return bool true if $resourceType represents an Entity Type which has derived Entity Types, else false
     */
    public function hasDerivedTypes(ResourceEntityType $resourceType): bool;

    /**
     * Gets the ResourceAssociationSet instance for the given source
     * association end.
     *
     * @param ResourceSet        $resourceSet      Resource set of the source
     *                                             association end
     * @param ResourceEntityType $resourceType     Resource type of the source
     *                                             association end
     * @param ResourceProperty   $resourceProperty Resource property of the source
     *                                             association end
     *
     * @return ResourceAssociationSet|null
     */
    public function getResourceAssociationSet(
        ResourceSet $resourceSet,
        ResourceEntityType $resourceType,
        ResourceProperty $resourceProperty
    ): ?ResourceAssociationSet;

    /**
     * Generate singleton wrapper.
     *
     * @param string       $name         Name of singleton
     * @param ResourceType $returnType   Return type wrapper
     * @param string|array $functionName Function call to be wrapped
     *
     * @return void
     */
    public function createSingleton($name, ResourceType $returnType, $functionName): void;

    /**
     * Get all singletons defined on this object.
     *
     * @return array
     */
    public function getSingletons(): array;

    /**
     * Call $name singleton and return result.
     *
     * @param string $name Singleton to call
     *
     * @return mixed
     */
    public function callSingleton($name);

    /**
     * @throws Exception
     * @return string|null
     */
    public function getXML(): ?string;
}
