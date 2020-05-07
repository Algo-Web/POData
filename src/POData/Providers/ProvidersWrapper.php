<?php

declare(strict_types=1);

namespace POData\Providers;

use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Configuration\IServiceConfiguration;
use POData\Providers\Expression\IExpressionProvider;
use POData\Providers\Metadata\EdmSchemaVersion;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceFunctionType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

/**
 * Class ProvidersWrapper.
 *
 * A wrapper class over IMetadataProvider and IQueryProvider implementations, All calls to implementation of methods
 * of these interfaces should go through this wrapper class so that wrapper methods of this class can perform validation
 */
class ProvidersWrapper
{
    /**
     * Holds reference to IMetadataProvider implementation.
     *
     * @var IMetadataProvider
     */
    private $metaProvider;

    /**
     * Holds reference to IServiceConfiguration implementation.
     *
     * @var IServiceConfiguration
     */
    private $config;

    /**
     * Holds reference to ProvidersQueryWrapper implementation.
     *
     * @var ProvidersQueryWrapper
     */
    private $providerWrapper;

    /**
     * Cache for ResourceProperties of a resource type that belongs to a
     * resource set. An entry (ResourceProperty collection) in this cache
     * contains only the visible properties of ResourceType.
     *
     * @var array<array>
     */
    private $propertyCache = [];

    /**
     * Cache for ResourceSetWrappers. If ResourceSet is invisible value will
     * be null.
     *
     * @var ResourceSetWrapper[] indexed by resource set name
     */
    private $setWrapperCache = [];

    /**
     * Cache for ResourceTypes.
     *
     * @var ResourceType[] indexed by resource type name
     */
    private $typeCache = [];

    /**
     * Creates a new instance of ProvidersWrapper.
     *
     * @param IMetadataProvider     $meta   Reference to IMetadataProvider implementation
     * @param IQueryProvider        $query  Reference to IQueryProvider implementation
     * @param IServiceConfiguration $config Reference to IServiceConfiguration implementation
     */
    public function __construct(IMetadataProvider $meta, IQueryProvider $query, IServiceConfiguration $config)
    {
        $this->metaProvider    = $meta;
        $this->config          = $config;
        $this->providerWrapper = new ProvidersQueryWrapper($query);
    }

    /**
     * @return ProvidersQueryWrapper
     */
    public function getProviderWrapper()
    {
        assert(null != $this->providerWrapper, 'Provider wrapper must be set');
        return $this->providerWrapper;
    }

    /**
     * @return IMetadataProvider
     */
    public function getMetaProvider()
    {
        assert(null != $this->metaProvider, 'Metadata provider must be set');
        return $this->metaProvider;
    }

    //Wrappers for IMetadataProvider methods

    /**
     * To get the Container name for the data source,
     * Note: Wrapper for IMetadataProvider::getContainerName method
     * implementation.
     *
     * @throws ODataException Exception if implementation returns empty container name
     *
     * @return string that contains the name of the container
     */
    public function getContainerName()
    {
        $containerName = $this->getMetaProvider()->getContainerName();
        if (empty($containerName)) {
            throw new ODataException(
                Messages::providersWrapperContainerNameMustNotBeNullOrEmpty(),
                500
            );
        }

        return $containerName;
    }

    /**
     * To get Namespace name for the data source,
     * Note: Wrapper for IMetadataProvider::getContainerNamespace method implementation.
     *
     * @throws ODataException Exception if implementation returns empty container namespace
     *
     * @return string that contains the namespace name
     */
    public function getContainerNamespace()
    {
        $containerNamespace = $this->getMetaProvider()->getContainerNamespace();
        if (empty($containerNamespace)) {
            throw new ODataException(
                Messages::providersWrapperContainerNamespaceMustNotBeNullOrEmpty(),
                500
            );
        }

        return $containerNamespace;
    }

    /**
     * To get the data service configuration.
     *
     * @return IServiceConfiguration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     *  To get all entity set information,
     *  Note: Wrapper for IMetadataProvider::getResourceSets method implementation,
     *  This method returns array of ResourceSetWrapper instances but the corresponding IDSMP method
     *  returns array of ResourceSet instances.
     *
     *  @throws ODataException when two resource sets with the same name are encountered
     *
     *  @return ResourceSetWrapper[] The ResourceSetWrappers for the visible ResourceSets
     */
    public function getResourceSets()
    {
        $resourceSets        = $this->getMetaProvider()->getResourceSets();
        $resourceSetWrappers = [];
        $resourceSetNames    = [];
        foreach ($resourceSets as $resourceSet) {
            $name = $resourceSet->getName();
            if (in_array($name, $resourceSetNames)) {
                throw new ODataException(Messages::providersWrapperEntitySetNameShouldBeUnique($name), 500);
            }

            $resourceSetNames[] = $name;
            $resourceSetWrapper = $this->validateResourceSetAndGetWrapper($resourceSet);
            if (null !== $resourceSetWrapper) {
                $resourceSetWrappers[] = $resourceSetWrapper;
            }
        }

        return $resourceSetWrappers;
    }

    /**
     * This function perform the following operations
     *  (1) If the cache contain an entry [key, value] for the resourceset then
     *      return the entry-value
     *  (2) If the cache not contain an entry for the resourceset then validate
     *      the resourceset
     *            (a) If valid add entry as [resouceset_name, resourceSetWrapper]
     *            (b) if not valid add entry as [resouceset_name, null]
     *  Note: validating a resourceset means checking the resourceset is visible
     *  or not using configuration.
     *
     * @param ResourceSet $resourceSet The resourceset to validate and get the
     *                                 wrapper for
     *
     * @throws ODataException
     * @return ResourceSetWrapper|null Returns an instance if a resource set with the given name is visible
     */
    private function validateResourceSetAndWrapper(ResourceSet $resourceSet)
    {
        $cacheKey = $resourceSet->getName();
        if (array_key_exists($cacheKey, $this->setWrapperCache)) {
            return $this->setWrapperCache[$cacheKey];
        }

        $this->validateResourceType($resourceSet->getResourceType());
        $wrapper                          = new ResourceSetWrapper($resourceSet, $this->config);
        $nuVal                            = $wrapper->isVisible() ? $wrapper : null;
        $this->setWrapperCache[$cacheKey] = $nuVal;

        return $this->setWrapperCache[$cacheKey];
    }

    /**
     * Validates the given instance of ResourceType.
     *
     * @param ResourceType $resourceType The ResourceType to validate
     *
     * @throws ODataException Exception if $resourceType is invalid
     *
     * @return ResourceType
     */
    private function validateResourceType(ResourceType $resourceType)
    {
        $cacheKey = $resourceType->getName();
        if (array_key_exists($cacheKey, $this->typeCache)) {
            return $this->typeCache[$cacheKey];
        }

        //TODO: Do validation if any for the ResourceType
        $this->typeCache[$cacheKey] = $resourceType;

        return $resourceType;
    }

    /**
     * To get all resource types in the data source,
     * Note: Wrapper for IMetadataProvider::getTypes method implementation.
     *
     * @throws ODataException
     * @return ResourceType[]
     */
    public function getTypes()
    {
        $resourceTypes     = $this->getMetaProvider()->getTypes();
        $resourceTypeNames = [];
        foreach ($resourceTypes as $resourceType) {
            if (in_array($resourceType->getName(), $resourceTypeNames)) {
                throw new ODataException(
                    Messages::providersWrapperEntityTypeNameShouldBeUnique($resourceType->getName()),
                    500
                );
            }

            $resourceTypeNames[] = $resourceType->getName();
            $this->validateResourceType($resourceType);
        }

        return $resourceTypes;
    }

    /**
     * @return ResourceFunctionType[]
     */
    public function getSingletons()
    {
        $singletons = $this->getMetaProvider()->getSingletons();
        return (null == $singletons) ? [] : $singletons;
    }

    /**
     * To get a resource set based on the specified resource set name which is
     * visible,
     * Note: Wrapper for IMetadataProvider::resolveResourceSet method
     * implementation.
     *
     * @param string $name Name of the resource set
     *
     * @throws ODataException
     * @return ResourceSetWrapper|null Returns resource set with the given name if found,
     *                                 NULL if resource set is set to invisible or not found
     */
    public function resolveResourceSet($name)
    {
        if (array_key_exists($name, $this->setWrapperCache)) {
            return $this->setWrapperCache[$name];
        }

        $resourceSet = $this->getMetaProvider()->resolveResourceSet($name);
        if (null === $resourceSet) {
            return null;
        }

        return $this->validateResourceSetAndWrapper($resourceSet);
    }

    /**
     * To get a resource type based on the resource set name,
     * Note: Wrapper for IMetadataProvider::resolveResourceType
     * method implementation.
     *
     * @param string $name Name of the resource set
     *
     * @throws ODataException If the ResourceType is invalid
     *
     * @return ResourceType|null resource type with the given resource set name if found else NULL
     */
    public function resolveResourceType($name)
    {
        $resourceType = $this->getMetaProvider()->resolveResourceType($name);
        if (null === $resourceType) {
            return null;
        }

        return $this->validateResourceType($resourceType);
    }

    /**
     * Try to resolve named singleton.
     *
     * @param  string     $name
     * @return mixed|null
     */
    public function resolveSingleton($name)
    {
        $singletons = $this->getMetaProvider()->getSingletons();
        if (array_key_exists($name, $singletons)) {
            return $singletons[$name];
        }
        return null;
    }

    /**
     * The method must return a collection of all the types derived from
     * $resourceType The collection returned should NOT include the type
     * passed in as a parameter
     * Note: Wrapper for IMetadataProvider::getDerivedTypes
     * method implementation.
     *
     * @param ResourceEntityType $resourceType Resource to get derived resource types from
     *
     * @throws InvalidOperationException when the meat provider doesn't return an array
     * @throws ODataException
     *
     * @return ResourceType[]
     */
    public function getDerivedTypes(ResourceEntityType $resourceType)
    {
        $derivedTypes = $this->getMetaProvider()->getDerivedTypes($resourceType);
        if (!is_array($derivedTypes)) {
            throw new InvalidOperationException(
                Messages::metadataAssociationTypeSetInvalidGetDerivedTypesReturnType($resourceType->getName())
            );
        }

        foreach ($derivedTypes as $derivedType) {
            $this->validateResourceType($derivedType);
        }

        return $derivedTypes;
    }

    /**
     * Returns true if $resourceType represents an Entity Type which has derived
     * Entity Types, else false.
     * Note: Wrapper for IMetadataProvider::hasDerivedTypes method implementation.
     *
     * @param ResourceEntityType $resourceType Resource to check for derived resource types
     *
     * @throws ODataException If the ResourceType is invalid
     *
     * @return bool
     */
    public function hasDerivedTypes(ResourceEntityType $resourceType)
    {
        $this->validateResourceType($resourceType);

        return $this->getMetaProvider()->hasDerivedTypes($resourceType);
    }

    /**
     * Gets the visible resource properties for the given resource type from the given resource set wrapper.
     *
     * @param ResourceSetWrapper $setWrapper   Resource set wrapper in question
     * @param ResourceType       $resourceType Resource type in question
     *
     * @return ResourceProperty[] Collection of visible resource properties from the given resource set wrapper
     *                            and resource type
     */
    public function getResourceProperties(ResourceSetWrapper $setWrapper, ResourceType $resourceType)
    {
        if (!$resourceType instanceof ResourceEntityType) {
            //Complex resource type
            return $resourceType->getAllProperties();
        }
        //TODO: move this to doctrine annotations
        $cacheKey = $setWrapper->getName() . '_' . $resourceType->getFullName();
        if (!array_key_exists($cacheKey, $this->propertyCache)) {
            //Fill the cache
            $this->propertyCache[$cacheKey] = [];
            foreach ($resourceType->getAllProperties() as $resourceProperty) {
                $this->propertyCache[$cacheKey][$resourceProperty->getName()] = $resourceProperty;
            }
        }

        return $this->propertyCache[$cacheKey];
    }

    /**
     * Gets the target resource set wrapper for the given navigation property,
     * source resource set wrapper and the source resource type.
     *
     * @param ResourceSetWrapper $resourceSetWrapper         Source resource set
     * @param ResourceEntityType $resourceType               Source resource type
     * @param ResourceProperty   $navigationResourceProperty Navigation property
     *
     * @throws ODataException
     * @return ResourceSetWrapper|null Returns instance of ResourceSetWrapper
     *                                 (describes the entity set and associated configuration) for the
     *                                 given navigation property. returns NULL if resourceset for the
     *                                 navigation property is invisible or if metadata provider returns
     *                                 null resource association set
     */
    public function getResourceSetWrapperForNavigationProperty(
        ResourceSetWrapper $resourceSetWrapper,
        ResourceEntityType $resourceType,
        ResourceProperty $navigationResourceProperty
    ) {
        $associationSet = $this->getResourceAssociationSet(
            $resourceSetWrapper,
            $resourceType,
            $navigationResourceProperty
        );

        if (null !== $associationSet) {
            $relatedAssociationSetEnd = $associationSet->getRelatedResourceAssociationSetEnd(
                $resourceSetWrapper->getResourceSet(),
                $resourceType,
                $navigationResourceProperty
            );

            return $this->validateResourceSetAndWrapper(
                $relatedAssociationSetEnd->getResourceSet()
            );
        }
        return null;
    }

    /**
     * Gets the ResourceAssociationSet instance for the given source association end,
     * Note: Wrapper for IMetadataProvider::getResourceAssociationSet
     * method implementation.
     *
     * @param ResourceSet        $set      Resource set of the source association end
     * @param ResourceEntityType $type     Resource type of the source association end
     * @param ResourceProperty   $property Resource property of the source association end
     *
     * @throws ODataException
     * @return ResourceAssociationSet|null Returns ResourceAssociationSet for the source
     *                                     association end, NULL if no such
     *                                     association end or resource set in the
     *                                     other end of the association is invisible
     */
    public function getResourceAssociationSet(
        ResourceSet $set,
        ResourceEntityType $type,
        ResourceProperty $property
    ) {
        $type = $this->getResourceTypeWherePropertyIsDeclared($type, $property);
        // usage below requires $type to not be null - so kaboom as early as possible
        assert(null != $type, 'Resource type obtained from property must not be null.');
        assert($type instanceof ResourceEntityType);

        $associationSet = $this->getMetaProvider()->getResourceAssociationSet(
            $set,
            $type,
            $property
        );
        assert(
            null == $associationSet || $associationSet instanceof ResourceAssociationSet,
            'Retrieved resource association must be either null or an instance of ResourceAssociationSet'
        );

        if (null !== $associationSet) {
            $thisAssociationSetEnd = $associationSet->getResourceAssociationSetEnd(
                $set,
                $type,
                $property
            );

            $relatedAssociationSetEnd = $associationSet->getRelatedResourceAssociationSetEnd(
                $set,
                $type,
                $property
            );

            //If either $thisAssociationSetEnd and/or $relatedAssociationSetEnd
            //is null means the associationset we got from the IDSMP::getResourceAssociationSet is invalid.

            //Return null, if either AssociationSet's End1 or End2's resourceset name
            //doesn't match the name of resource set wrapper (param1) and resource type is not assignable
            //from given resource type (param2)
            if (null === $thisAssociationSetEnd || null === $relatedAssociationSetEnd) {
                throw new ODataException(
                    Messages::providersWrapperIDSMPGetResourceSetReturnsInvalidResourceSet(
                        $set->getName(),
                        $type->getFullName(),
                        $property->getName()
                    ),
                    500
                );
            }

            $relatedResourceSetWrapper = $this->validateResourceSetAndWrapper(
                $relatedAssociationSetEnd->getResourceSet()
            );
            if ($relatedResourceSetWrapper === null) {
                $associationSet = null;
            } else {
                $this->validateResourceType($thisAssociationSetEnd->getResourceType());
                $this->validateResourceType($relatedAssociationSetEnd->getResourceType());
            }
        }
        assert(
            null == $associationSet || $associationSet instanceof ResourceAssociationSet,
            'Retrieved resource assocation must be either null or an instance of ResourceAssociationSet'
        );

        return $associationSet;
    }

    /**
     * Gets the resource type on which the resource property is declared on,
     * If property is not declared in the given resource type, then this
     * function drill down to the inheritance hierarchy of the given resource
     * type to find out the base class in which the property is declared.
     *
     * @param ResourceType     $type     The resource type to start looking
     * @param ResourceProperty $property The resource property in question
     *
     * @return ResourceType|null Returns reference to the ResourceType on which
     *                           the $property is declared, NULL if
     *                           $property is not declared anywhere
     *                           in the inheritance hierarchy
     */
    private function getResourceTypeWherePropertyIsDeclared(ResourceType $type, ResourceProperty $property)
    {
        while (null !== $type) {
            if (null !== $type->resolvePropertyDeclaredOnThisType($property->getName())) {
                break;
            }

            $type = $type->getBaseType();
        }

        return $type;
    }

    /**
     * Wrapper function over _validateResourceSetAndGetWrapper function.
     *
     * @param ResourceSet $resourceSet see the comments of _validateResourceSetAndGetWrapper
     *
     * @throws ODataException
     * @return ResourceSetWrapper|null see the comments of _validateResourceSetAndGetWrapper
     */
    public function validateResourceSetAndGetWrapper(ResourceSet $resourceSet)
    {
        return $this->validateResourceSetAndWrapper($resourceSet);
    }

    /**
     * Gets the Edm Schema version compliance to the metadata.
     *
     * @return EdmSchemaVersion
     */
    public function getEdmSchemaVersion()
    {
        //The minimal schema version for custom provider is 1.1
        return EdmSchemaVersion::VERSION_1_DOT_1();
    }

    /**
     * Gets the underlying custom expression provider, the end developer is
     * responsible for implementing IExpressionProvider if he choose for.
     *
     * @throws ODataException
     * @return IExpressionProvider Instance of IExpressionProvider implementation
     */
    public function getExpressionProvider()
    {
        return $this->getProviderWrapper()->getExpressionProvider();
    }

    /**
     * Indicates if the QueryProvider can handle ordered paging, this means respecting order, skip, and top parameters
     * If the query provider can not handle ordered paging, it must return the entire result set and POData will
     * perform the ordering and paging.
     *
     * @return bool True if the query provider can handle ordered paging, false if POData should perform the paging
     */
    public function handlesOrderedPaging()
    {
        return $this->getProviderWrapper()->handlesOrderedPaging();
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
     * @throws ODataException
     * @return QueryResult
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        FilterInfo $filterInfo = null,
        InternalOrderByInfo $orderBy = null,
        $top = null,
        $skip = null,
        SkipTokenInfo $skipToken = null,
        array $eagerLoad = []
    ) {
        return $this->getProviderWrapper()->getResourceSet(
            $queryType,
            $resourceSet,
            $filterInfo,
            $orderBy,
            $top,
            $skip,
            $skipToken,
            $eagerLoad
        );
    }

    /**
     * Gets an entity instance from an entity set identified by a key.
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to fetch
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to fetch
     * @param string[]|null $eagerLoad     array of relations to eager load
     *
     * @throws ODataException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     * @return object|null                              Returns entity instance if found, else null
     */
    public function getResourceFromResourceSet(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor,
        array $eagerLoad = null
    ) {
        return $this->getProviderWrapper()->getResourceFromResourceSet($resourceSet, $keyDescriptor, $eagerLoad);
    }

    /**
     * Puts an entity instance to entity set identified by a key.
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to update
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to update
     * @param mixed         $data
     *
     * @return bool|null Returns result of executing query
     */
    public function putResource(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor,
        $data
    ) {
        return $this->getProviderWrapper()->putResource(
            $resourceSet,
            $keyDescriptor,
            $data
        );
    }

    /**
     * Get related resource set for a resource.
     *
     * @param QueryType          $queryType         Indicates if this is a query for a count, entities, or entities
     *                                              with a count
     * @param ResourceSet        $sourceResourceSet The entity set containing the source entity
     * @param object             $sourceEntity      The source entity instance
     * @param ResourceSet        $targetResourceSet The resource set containing the target of the navigation property
     * @param ResourceProperty   $targetProperty    The navigation property to retrieve
     * @param FilterInfo|null    $filterInfo        Represents the $filter parameter of the OData query.
     *                                              NULL if no $filter specified
     * @param mixed|null         $orderBy           sorted order if we want to get the data in some specific order
     * @param int|null           $top               number of records which need to be retrieved
     * @param int|null           $skip              number of records which need to be skipped
     * @param SkipTokenInfo|null $skipToken         value indicating what records to skip
     *
     * @throws ODataException
     *
     * @return QueryResult
     */
    public function getRelatedResourceSet(
        QueryType $queryType,
        ResourceSet $sourceResourceSet,
        $sourceEntity,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        FilterInfo $filterInfo = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        SkipTokenInfo $skipToken = null
    ) {
        return $this->getProviderWrapper()->getRelatedResourceSet(
            $queryType,
            $sourceResourceSet,
            $sourceEntity,
            $targetResourceSet,
            $targetProperty,
            $filterInfo,
            $orderBy,
            $top,
            $skip,
            $skipToken
        );
    }

    /**
     * Gets a related entity instance from an entity set identified by a key.
     *
     * @param ResourceSet      $sourceResourceSet The entity set related to the entity to be fetched
     * @param object           $sourceEntity      The related entity instance
     * @param ResourceSet      $targetResourceSet The entity set from which entity needs to be fetched
     * @param ResourceProperty $targetProperty    The metadata of the target property
     * @param KeyDescriptor    $keyDescriptor     The key to identify the entity to be fetched
     *
     * @throws ODataException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     * @return object|null                              Returns entity instance if found, else null
     */
    public function getResourceFromRelatedResourceSet(
        ResourceSet $sourceResourceSet,
        $sourceEntity,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        return $this->getProviderWrapper()->getResourceFromRelatedResourceSet(
            $sourceResourceSet,
            $sourceEntity,
            $targetResourceSet,
            $targetProperty,
            $keyDescriptor
        );
    }

    /**
     * Get related resource for a resource.
     *
     * @param ResourceSet      $sourceResourceSet The source resource set
     * @param object           $sourceEntity      The source resource
     * @param ResourceSet      $targetResourceSet The resource set of the navigation
     *                                            property
     * @param ResourceProperty $targetProperty    The navigation property to be
     *                                            retrieved
     *
     * @throws ODataException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     * @return object|null                              The related resource if exists, else null
     */
    public function getRelatedResourceReference(
        ResourceSet $sourceResourceSet,
        $sourceEntity,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
        return $this->getProviderWrapper()->getRelatedResourceReference(
            $sourceResourceSet,
            $sourceEntity,
            $targetResourceSet,
            $targetProperty
        );
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
     * @return object|null the new resource value if it is assignable, or throw exception for null
     */
    public function updateResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        KeyDescriptor $keyDescriptor,
        $data,
        $shouldUpdate = false
    ) {
        return $this->getProviderWrapper()->updateResource(
            $sourceResourceSet,
            $sourceEntityInstance,
            $keyDescriptor,
            $data,
            $shouldUpdate
        );
    }

    /**
     * Delete resource from a resource set.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     *
     * @return bool true if resources successfully deleted, otherwise false
     */
    public function deleteResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance
    ) {
        return $this->getProviderWrapper()->deleteResource(
            $sourceResourceSet,
            $sourceEntityInstance
        );
    }

    /**
     * @param ResourceSet $resourceSet          The entity set containing the entity to fetch
     * @param object|null $sourceEntityInstance The source entity instance
     * @param object      $data                 the New data for the entity instance
     *
     * @return object|null returns the newly created model if successful, or null if model creation failed
     */
    public function createResourceforResourceSet(
        ResourceSet $resourceSet,
        $sourceEntityInstance,
        $data
    ) {
        return $this->getProviderWrapper()->createResourceforResourceSet(
            $resourceSet,
            $sourceEntityInstance,
            $data
        );
    }

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
    ) {
        return $this->getProviderWrapper()->createBulkResourceforResourceSet(
            $sourceResourceSet,
            $data
        );
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
        return $this->getProviderWrapper()->updateBulkResource(
            $sourceResourceSet,
            $sourceEntityInstance,
            $keyDescriptor,
            $data,
            $shouldUpdate
        );
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
        return $this->getProviderWrapper()->hookSingleModel(
            $sourceResourceSet,
            $sourceEntityInstance,
            $targetResourceSet,
            $targetEntityInstance,
            $navPropName
        );
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
        return $this->getProviderWrapper()->unhookSingleModel(
            $sourceResourceSet,
            $sourceEntityInstance,
            $targetResourceSet,
            $targetEntityInstance,
            $navPropName
        );
    }

    /**
     * @throws \Exception
     * @return mixed
     */
    public function getMetadataXML()
    {
        return $this->getMetaProvider()->getXML();
    }

    /**
     * Start database transaction.
     *
     * @param  bool $isBulk Is this transaction inside a batch request?
     * @return void
     */
    public function startTransaction($isBulk = false)
    {
        $this->getProviderWrapper()->startTransaction($isBulk);
    }

    /**
     * Commit database transaction.
     *
     * @return void
     */
    public function commitTransaction()
    {
        $this->getProviderWrapper()->commitTransaction();
    }

    /**
     * Abort database transaction.
     *
     * @return void
     */
    public function rollBackTransaction()
    {
        $this->getProviderWrapper()->rollBackTransaction();
    }
}
