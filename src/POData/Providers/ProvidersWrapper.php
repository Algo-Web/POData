<?php

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
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\InternalSkipTokenInfo;
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

    /*
     * Holds reference to ProvidersQueryWrapper implementation
     *
     * @var ProvidersQueryWrapper
     */
    private $providerWrapper;

    /**
     * Cache for ResourceProperties of a resource type that belongs to a
     * resource set. An entry (ResourceProperty collection) in this cache
     * contains only the visible properties of ResourceType.
     *
     * @var array(string, array(string, ResourceProperty))
     */
    private $propertyCache;

    /**
     * Cache for ResourceSetWrappers. If ResourceSet is invisible value will
     * be null.
     *
     * @var ResourceSetWrapper[] indexed by resource set name
     */
    private $setWrapperCache;

    /**
     * Cache for ResourceTypes.
     *
     * @var ResourceType[] indexed by resource type name
     */
    private $typeCache;

    /**
     * Cache for ResourceAssociationSet. If ResourceAssociationSet is invisible
     * value will be null.
     *
     * @var ResourceAssociationSet[] indexed by name
     */
    private $associationSetCache;

    /**
     * Creates a new instance of ProvidersWrapper.
     *
     * @param IMetadataProvider     $meta   Reference to IMetadataProvider implementation
     * @param IQueryProvider        $query  Reference to IQueryProvider implementation
     * @param IServiceConfiguration $config Reference to IServiceConfiguration implementation
     */
    public function __construct(IMetadataProvider $meta, IQueryProvider $query, IServiceConfiguration $config)
    {
        $this->metaProvider = $meta;
        $this->config = $config;
        $this->providerWrapper = new ProvidersQueryWrapper($query);
        $this->setWrapperCache = [];
        $this->typeCache = [];
        $this->propertyCache = [];
    }

    public function getProviderWrapper()
    {
        assert(null != $this->providerWrapper, "Provider wrapper must be set");
        return $this->providerWrapper;
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
        $containerName = $this->metaProvider->getContainerName();
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
        $containerNamespace = $this->metaProvider->getContainerNamespace();
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
        $resourceSets = $this->metaProvider->getResourceSets();
        $resourceSetWrappers = [];
        $resourceSetNames = [];
        foreach ($resourceSets as $resourceSet) {
            $name = $resourceSet->getName();
            if (in_array($name, $resourceSetNames)) {
                throw new ODataException(Messages::providersWrapperEntitySetNameShouldBeUnique($name), 500);
            }

            $resourceSetNames[] = $name;
            $resourceSetWrapper = $this->_validateResourceSetAndGetWrapper($resourceSet);
            if (!is_null($resourceSetWrapper)) {
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
     * @return ResourceSetWrapper|null Returns an instance if a resource set with the given name is visible
     */
    private function _validateResourceSetAndGetWrapper(ResourceSet $resourceSet)
    {
        $cacheKey = $resourceSet->getName();
        if (array_key_exists($cacheKey, $this->setWrapperCache)) {
            return $this->setWrapperCache[$cacheKey];
        }

        $this->validateResourceType($resourceSet->getResourceType());
        $wrapper = new ResourceSetWrapper($resourceSet, $this->config);
        $nuVal = $wrapper->isVisible() ? $wrapper : null;
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
     * @return ResourceType[]
     */
    public function getTypes()
    {
        $resourceTypes = $this->metaProvider->getTypes();
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

    public function getSingletons()
    {
        $singletons = $this->metaProvider->getSingletons();
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
     * @return ResourceSetWrapper|null Returns resource set with the given name if found,
     *                                 NULL if resource set is set to invisible or not found
     */
    public function resolveResourceSet($name)
    {
        if (array_key_exists($name, $this->setWrapperCache)) {
            return $this->setWrapperCache[$name];
        }

        $resourceSet = $this->metaProvider->resolveResourceSet($name);
        if (is_null($resourceSet)) {
            return null;
        }

        return $this->_validateResourceSetAndGetWrapper($resourceSet);
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
        $resourceType = $this->metaProvider->resolveResourceType($name);
        if (is_null($resourceType)) {
            return null;
        }

        return $this->validateResourceType($resourceType);
    }


    public function resolveSingleton($name)
    {
        $singletons = $this->metaProvider->getSingletons();
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
     * @param ResourceType $resourceType Resource to get derived resource types from
     *
     * @throws InvalidOperationException when the meat provider doesn't return an array
     *
     * @return ResourceType[]
     */
    public function getDerivedTypes(ResourceType $resourceType)
    {
        $derivedTypes = $this->metaProvider->getDerivedTypes($resourceType);
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
     * Note: Wrapper for IMetadataProvider::hasDerivedTypes method
     * implementation.
     *
     * @param ResourceType $resourceType Resource to check for derived resource
     *                                   types
     *
     * @throws ODataException If the ResourceType is invalid
     *
     * @return bool
     */
    public function hasDerivedTypes(ResourceType $resourceType)
    {
        $this->validateResourceType($resourceType);

        return $this->metaProvider->hasDerivedTypes($resourceType);
    }

    /**
     * Gets the visible resource properties for the given resource type from the given resource set wrapper.
     *
     * @param ResourceSetWrapper $setWrapper Resource set wrapper in question
     * @param ResourceType $resourceType Resource type in question
     *
     * @return ResourceProperty[] Collection of visible resource properties from the given resource set wrapper
     *                            and resource type
     */
    public function getResourceProperties(ResourceSetWrapper $setWrapper, ResourceType $resourceType)
    {
        if ($resourceType->getResourceTypeKind() != ResourceTypeKind::ENTITY) {
            //Complex resource type
            return $resourceType->getAllProperties();
        }
        //TODO: move this to doctrine annotations
        $cacheKey = $setWrapper->getName() . '_' . $resourceType->getFullName();
        if (!array_key_exists($cacheKey, $this->propertyCache)) {
            //Fill the cache
            $this->propertyCache[$cacheKey] = [];
            foreach ($resourceType->getAllProperties() as $resourceProperty) {
                //Check whether this is a visible navigation property
                //TODO: is this broken?? see #87
                if ($resourceProperty->getTypeKind() == ResourceTypeKind::ENTITY
                    && !is_null($this->getResourceSetWrapperForNavigationProperty(
                        $setWrapper,
                        $resourceType,
                        $resourceProperty
                    ))
                ) {
                    $this->propertyCache[$cacheKey][$resourceProperty->getName()] = $resourceProperty;
                } else {
                    //primitive, bag or complex property
                    $this->propertyCache[$cacheKey][$resourceProperty->getName()] = $resourceProperty;
                }
            }
        }

        return $this->propertyCache[$cacheKey];
    }

    /**
     * Gets the target resource set wrapper for the given navigation property,
     * source resource set wrapper and the source resource type.
     *
     * @param ResourceSetWrapper $resourceSetWrapper Source resource set
     * @param ResourceEntityType $resourceType Source resource type
     * @param ResourceProperty   $navigationResourceProperty Navigation property
     *
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

        if (!is_null($associationSet)) {
            $relatedAssociationSetEnd = $associationSet->getRelatedResourceAssociationSetEnd(
                $resourceSetWrapper->getResourceSet(),
                $resourceType,
                $navigationResourceProperty
            );

            return $this->_validateResourceSetAndGetWrapper(
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
     * @param ResourceSet           $set      Resource set of the source association end
     * @param ResourceEntityType    $type     Resource type of the source association end
     * @param ResourceProperty      $property Resource property of the source association end
     *
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

        $associationSet = $this->metaProvider->getResourceAssociationSet(
            $set,
            $type,
            $property
        );
        assert(
            null == $associationSet || $associationSet instanceof ResourceAssociationSet,
            "Retrieved resource assocation must be either null or an instance of ResourceAssociationSet"
        );

        if (!is_null($associationSet)) {
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
            if (is_null($thisAssociationSetEnd) || is_null($relatedAssociationSetEnd)) {
                throw new ODataException(
                    Messages::providersWrapperIDSMPGetResourceSetReturnsInvalidResourceSet(
                        $set->getName(),
                        $type->getFullName(),
                        $property->getName()
                    ),
                    500
                );
            }

            $relatedResourceSetWrapper = $this->_validateResourceSetAndGetWrapper(
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
            "Retrieved resource assocation must be either null or an instance of ResourceAssociationSet"
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
     * @return ResourceSetWrapper|null see the comments of _validateResourceSetAndGetWrapper
     */
    public function validateResourceSetAndGetWrapper(ResourceSet $resourceSet)
    {
        return $this->_validateResourceSetAndGetWrapper($resourceSet);
    }

    /**
     * Gets the Edm Schema version compliance to the metadata.
     *
     * @return EdmSchemaVersion
     */
    public function getEdmSchemaVersion()
    {
        //The minimal schema version for custom provider is 1.1
        return EdmSchemaVersion::VERSION_1_DOT_1;
    }

    /**
     * Gets the underlying custom expression provider, the end developer is
     * responsible for implementing IExpressionProvider if he choose for.
     *
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
     * Gets collection of entities belongs to an entity set.
     *
     * @param QueryType             $queryType   Indicates if this is a query for a count, entities, or entities with a
     *                                           count
     * @param ResourceSet           $resourceSet The entity set containing the entities that need to be fetched
     * @param FilterInfo            $filterInfo  Represents the $filter parameter of the OData query.
     *                                           NULL if no $filter specified
     * @param InternalOrderByInfo   $orderBy     The orderBy information
     * @param int                   $top         The top count
     * @param int                   $skip        The skip count
     * @param InternalSkipTokenInfo $skipToken   The skip token
     *
     * @return QueryResult
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        FilterInfo $filterInfo = null,
        InternalOrderByInfo $orderBy = null,
        $top = null,
        $skip = null,
        SkipTokenInfo $skipToken = null
    ) {
        return $this->getProviderWrapper()->getResourceSet(
            $queryType,
            $resourceSet,
            $filterInfo,
            $orderBy,
            $top,
            $skip,
            $skipToken
        );
    }

    /**
     * Gets an entity instance from an entity set identified by a key.
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to fetch
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to fetch
     *
     * @return object|null Returns entity instance if found, else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {
        return $this->getProviderWrapper()->getResourceFromResourceSet($resourceSet, $keyDescriptor);
    }

    /**
     * Puts an entity instance to entity set identified by a key.
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to update
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to update
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
     * @param QueryType             $queryType         Indicates if this is a query for a count, entities, or entities
     *                                                 with a count
     * @param ResourceSet           $sourceResourceSet The entity set containing the source entity
     * @param object                $sourceEntity      The source entity instance
     * @param ResourceSet           $targetResourceSet The resource set containing the target of the navigation property
     * @param ResourceProperty      $targetProperty    The navigation property to retrieve
     * @param FilterInfo|null       $filterInfo        Represents the $filter parameter of the OData query.
     *                                                 NULL if no $filter specified
     * @param mixed                 $orderBy           sorted order if we want to get the data in some specific order
     * @param int                   $top                  number of records which need to be retrieved
     * @param int                   $skip                 number of records which need to be skipped
     * @param SkipTokenInfo|null    $skipToken            value indicating what records to skip
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
        $filterInfo,
        $orderBy,
        $top,
        $skip,
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
     * @return object|null Returns entity instance if found, else null
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
     * @return object|null The related resource if exists, else null
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
     * @param object        $data                 The New data for the entity instance.
     * @param bool          $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object|null The new resource value if it is assignable, or throw exception for null.
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
     * return bool true if resources successfully deleted, otherwise false.
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
     * @param object      $sourceEntityInstance The source entity instance
     * @param object      $data                 The New data for the entity instance.
     *
     * returns object|null returns the newly created model if successful, or null if model creation failed.
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

    public function getMetadataXML()
    {
        return $this->metaProvider->getXML();
    }
}
