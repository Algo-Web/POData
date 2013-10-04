<?php

namespace POData\Providers;

use POData\Providers\Metadata\ResourceTypeKind;
use POData\Common\NotImplementedException;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Configuration\IServiceConfiguration;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\Common\ODataException;
use POData\Common\Messages;
use POData\Providers\Metadata\MetadataMapping;
use POData\Providers\Metadata\EdmSchemaVersion;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Metadata\IMetadataProvider;
use POData\UriProcessor\QueryProcessor\ExpressionParser\IExpressionProvider;
use POData\Common\InvalidOperationException;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;

/**
 * Class ProvidersWrapper
 *
 * A wrapper class over IMetadataProvider and IQueryProvider implementations, All calls to implementation of methods
 * of these interfaces should go through this wrapper class so that wrapper methods of this class can perform validations
 * on data returned by IDSMP methods
 *
 * @package POData\Providers
 */
class ProvidersWrapper
{
    /**
     * Holds reference to IMetadataProvider implementation
     * 
     * @var IMetadataProvider
     */
    private $_metadataProvider;

    /**
     * Holds reference to IQueryProvider implementation
     * 
     * @var IQueryProvider
     *
     */
    private $_queryProvider;

    /**
     * Holds reference to IServiceConfiguration implementation
     * 
     * @var IServiceConfiguration
     */
    private $_configuration;


    /**
     * Cache for ResourceProperties of a resource type that belongs to a 
     * resource set. An entry (ResourceProperty collection) in this cache 
     * contains only the visible properties of ResourceType.
     * 
     * @var array(string, array(string, ResourceProperty))
     */
    private $_resourcePropertyCache;

    /**
     * Cache for ResourceSetWrappers. If ResourceSet is invisible value will 
     * be null.
     * 
     * @var ResourceSetWrapper[] indexed by resource set name
     */
    private $_resourceSetWrapperCache;

    /**
     * Cache for ResourceTypes
     * 
     * @var ResourceType[] indexed by resource type name
     */
    private $_resourceTypeCache;

    /**
     * Cache for ResourceAssociationSet. If ResourceAssociationSet is invisible 
     * value will be null. 
     * 
     * @var ResourceAssociationSet[] indexed by name
     */
    private $_resourceAssociationSetCache;

    /**
     * Creates a new instance of ProvidersWrapper
     * 
     * @param IMetadataProvider $metadataProvider Reference to IMetadataProvider implementation
     * @param IQueryProvider    $queryProvider    Reference to IQueryProvider implementation
     * @param IServiceConfiguration    $configuration    Reference to IServiceConfiguration implementation
     */
    public function __construct(IMetadataProvider $metadataProvider, IQueryProvider $queryProvider, IServiceConfiguration $configuration)
    {
        $this->_metadataProvider = $metadataProvider;
        $this->_queryProvider = $queryProvider;
        $this->_configuration = $configuration;
        $this->_resourceSetWrapperCache = array();
        $this->_resourceTypeCache = array();
        $this->_resourceAssociationSetCache = array();
        $this->_resourcePropertyCache = array();
    }

    //Wrappers for IMetadataProvider methods
    
    /**     
     * To get the Container name for the data source,
     * Note: Wrapper for IMetadataProvider::getContainerName method
     * implementation
     * 
     * @return string that contains the name of the container
     * 
     * @throws ODataException Exception if IDSMP implementation returns empty container name
     *
     */
    public function getContainerName()
    {
        $containerName = $this->_metadataProvider->getContainerName();
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
     * Note: Wrapper for IMetadataProvider::getContainerNamespace method implementation
     *
     * @return string that contains the namespace name.
     * 
     * @throws ODataException Exception if IDSMP implementation returns empty container namespace
     *
     */
    public function getContainerNamespace()
    {
        $containerNamespace = $this->_metadataProvider->getContainerNamespace();
        if (empty($containerNamespace)) {
            throw new ODataException(
                Messages::providersWrapperContainerNamespaceMustNotBeNullOrEmpty(),
                500
            );
        }

        return $containerNamespace;
    }

    /**
     * To get the data service configuration
     * 
     * @return IServiceConfiguration
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     *  To get all entity set information, 
     *  Note: Wrapper for IMetadataProvider::getResourceSets method implementation,
     *  This method returns array of ResourceSetWrapper instances but the corresponding IDSMP method returns array of ResourceSet instances
     *
     *  @return ResourceSetWrapper[] The ResourceSetWrappers for the visible ResourceSets
     *  @throws ODataException when two resource sets with the same name are encountered
     *
     */
    public function getResourceSets()
    {
        $resourceSets = $this->_metadataProvider->getResourceSets();
        $resourceSetWrappers = array();
        $resourceSetNames = array();
        foreach ($resourceSets as $resourceSet) {
            if (in_array($resourceSet->getName(), $resourceSetNames)) {
                throw new ODataException(Messages::providersWrapperEntitySetNameShouldBeUnique($resourceSet->getName()), 500 );
            }

            $resourceSetNames[] = $resourceSet->getName();
            $resourceSetWrapper = $this->_validateResourceSetAndGetWrapper($resourceSet);
            if (!is_null($resourceSetWrapper)) {
                $resourceSetWrappers[] = $resourceSetWrapper;
            }
        }

        return $resourceSetWrappers;
    }

    /**
     * To get all resource types in the data source,
     * Note: Wrapper for IMetadataProvider::getTypes method implementation
     * 
     * @return ResourceType[]
     */
    public function getTypes()
    {
        $resourceTypes = $this->_metadataProvider->getTypes();
        $resourceTypeNames = array();
        foreach ($resourceTypes as $resourceType) {
            if (in_array($resourceType->getName(), $resourceTypeNames)) {
                throw new ODataException(
                    Messages::providersWrapperEntityTypeNameShouldBeUnique($resourceType->getName()),
                    500
                );
            }

            $resourceTypeNames[] = $resourceType->getName();
            $this->_validateResourceType($resourceType);
        }

        return $resourceTypes;
    }

    /**
     * To get a resource set based on the specified resource set name which is 
     * visible,
     * Note: Wrapper for IMetadataProvider::resolveResourceSet method
     * implementation
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceSetWrapper|null Returns resource set with the given name if found, NULL if resource set is set to invisible or not found
     *
     */
    public function resolveResourceSet($name)
    {
        if (array_key_exists($name, $this->_resourceSetWrapperCache)) {
            return $this->_resourceSetWrapperCache[$name];
        }
        
        $resourceSet = $this->_metadataProvider->resolveResourceSet($name);
        if (is_null($resourceSet)) {
            return null;
        }

        return $this->_validateResourceSetAndGetWrapper($resourceSet);
    }

    /**
     * To get a resource type based on the resource set name,
     * Note: Wrapper for IMetadataProvider::resolveResourceType
     * method implementation
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceType|null resource type with the given resource set name if found else NULL
     *
     * 
     * @throws ODataException If the ResourceType is invalid
     */
    public function resolveResourceType($name)
    {
        $resourceType = $this->_metadataProvider->resolveResourceType($name);
        if (is_null($resourceType)) {
            return null;
        }

        return $this->_validateResourceType($resourceType);
    }

    /**
     * The method must return a collection of all the types derived from 
     * $resourceType The collection returned should NOT include the type 
     * passed in as a parameter
     * Note: Wrapper for IMetadataProvider::getDerivedTypes
     * method implementation
     * 
     * @param ResourceType $resourceType Resource to get derived resource types from
     * 
     * @return ResourceType[]
     *
     * @throws InvalidOperationException when the meat provider doesn't return an array
     */
    public function getDerivedTypes(ResourceType $resourceType)
    {
        $derivedTypes = $this->_metadataProvider->getDerivedTypes($resourceType);
	    if (!is_array($derivedTypes)) {
		    throw new InvalidOperationException(Messages::metadataAssociationTypeSetInvalidGetDerivedTypesReturnType($resourceType->getName()));
	    }

        foreach ($derivedTypes as $derivedType) {
            $this->_validateResourceType($derivedType);
        }

        return $derivedTypes;
    }

    /**
     * Returns true if $resourceType represents an Entity Type which has derived 
     * Entity Types, else false.
     * Note: Wrapper for IMetadataProvider::hasDerivedTypes method
     * implementation
     * 
     * @param ResourceType $resourceType Resource to check for derived resource 
     *                                   types.
     * 
     * @return boolean
     * 
     * @throws ODataException If the ResourceType is invalid
     */
    public function hasDerivedTypes(ResourceType $resourceType)
    {
        $this->_validateResourceType($resourceType);
        return $this->_metadataProvider->hasDerivedTypes($resourceType);
    }

    /**
     * Gets the ResourceAssociationSet instance for the given source association end,
     * Note: Wrapper for IMetadataProvider::getResourceAssociationSet
     * method implementation
     * 
     * @param ResourceSetWrapper $wrapper Resource set of the source association end
     * @param ResourceType       $type       Resource type of the source association end
     * @param ResourceProperty   $property   Resource property of the source association end
     *
     * 
     * @return ResourceAssociationSet|null Returns ResourceAssociationSet for the source
     *                                             association end, NULL if no such 
     *                                             association end or resource set in the
     *                                             other end of the association is invisible
     */
    public function getResourceAssociationSet(
	    ResourceSetWrapper $wrapper,
        ResourceType $type,
        ResourceProperty $property
    ) {        
        $type = $this->_getResourceTypeWherePropertyIsDeclared($type, $property);
        $cacheKey = $wrapper->getName() . '_' . $type->getName() . '_' . $property->getName();

        if (array_key_exists($cacheKey,  $this->_resourceAssociationSetCache)) {
            return $this->_resourceAssociationSetCache[$cacheKey];
        }

        $associationSet = $this->_metadataProvider->getResourceAssociationSet(
            $wrapper->getResourceSet(),
            $type,
            $property
        );

        if (!is_null($associationSet)) {
            $thisAssociationSetEnd = $associationSet->getResourceAssociationSetEnd(
				$wrapper->getResourceSet(),
                $type,
                $property
            );

            $relatedAssociationSetEnd = $associationSet->getRelatedResourceAssociationSetEnd(
                $wrapper->getResourceSet(),
                $type,
                $property
            );

            //If $thisAssociationSetEnd or $relatedAssociationSetEnd
            //is null means the associationset
            //we got from the IDSMP::getResourceAssociationSet is invalid. 
            //AssociationSet::getResourceAssociationSetEnd
            //return null, if AssociationSet's End1 or End2's resourceset name 
            //is not matching with the name of
            //resource set wrapper (param1) and resource type is not assignable 
            //from given resource type (param2)   
            if (is_null($thisAssociationSetEnd) || is_null($relatedAssociationSetEnd)) {
                throw new ODataException(
                    Messages::providersWrapperIDSMPGetResourceSetReturnsInvalidResourceSet(
                        $wrapper->getName(),
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
                $this->_validateResourceType($thisAssociationSetEnd->getResourceType());
                $this->_validateResourceType($relatedAssociationSetEnd->getResourceType());
            }
        }

        $this->_resourceAssociationSetCache[$cacheKey] = $associationSet;
        return $associationSet;
    }
 
    /**
     * Gets the target resource set wrapper for the given navigation property, 
     * source resource set wrapper and the source resource type
     * 
     * @param ResourceSetWrapper $resourceSetWrapper         Source resource set.
     * @param ResourceType       $resourceType               Source resource type.
     * @param ResourceProperty   $navigationResourceProperty Navigation property.
     * 
     * @return ResourceSetWrapper|null Returns instance of ResourceSetWrapper 
     *     (describes the entity set and associated configuration) for the 
     *     given navigation property. returns NULL if resourceset for the 
     *     navigation property is invisible or if metadata provider returns 
     *     null resource association set
     */
    public function getResourceSetWrapperForNavigationProperty(
        ResourceSetWrapper $resourceSetWrapper,
        ResourceType $resourceType,
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
     * Gets the visible resource properties for the given resource type from the given resource set wrapper.
     *
     * @param ResourceSetWrapper $setWrapper Resource set wrapper in question.
     * @param ResourceType       $resourceType       Resource type in question.
     * @return ResourceProperty[] Collection of visible resource properties from the given resource set wrapper and resource type.
     */
    public function getResourceProperties(ResourceSetWrapper $setWrapper, ResourceType $resourceType) {
        if ($resourceType->getResourceTypeKind() != ResourceTypeKind::ENTITY) {
	        //Complex resource type
	        return $resourceType->getAllProperties();
        }
	    //TODO: move this to doctrine annotations
	    $cacheKey = $setWrapper->getName() . '_' . $resourceType->getFullName();
        if (!array_key_exists($cacheKey,  $this->_resourcePropertyCache)) {
	        //Fill the cache
	        $this->_resourcePropertyCache[$cacheKey] = array();
	        foreach ($resourceType->getAllProperties() as $resourceProperty) {
	            //Check whether this is a visible navigation property
		        //TODO: is this broken?? see #87
	            if ($resourceProperty->getTypeKind() == ResourceTypeKind::ENTITY
	                && !is_null($this->getResourceSetWrapperForNavigationProperty($setWrapper, $resourceType, $resourceProperty))
	            ) {
	                $this->_resourcePropertyCache[$cacheKey][$resourceProperty->getName()] = $resourceProperty;
	            } else {
	                //primitive, bag or complex property
	                $this->_resourcePropertyCache[$cacheKey][$resourceProperty->getName()] = $resourceProperty;
	            }
	        }
        }
        return $this->_resourcePropertyCache[$cacheKey];

    }

    /**
     * Wrapper function over _validateResourceSetAndGetWrapper function
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
     * Gets the Edm Schema version compliance to the metadata
     * 
     * @return EdmSchemaVersion
     */
    public function getEdmSchemaVersion()
    {
        //The minimal schema version for custom provider is 1.1
        return EdmSchemaVersion::VERSION_1_DOT_1;
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
     *  or not using configuration
     *  
     * @param ResourceSet $resourceSet The resourceset to validate and get the 
     *                                 wrapper for
     * 
     * @return ResourceSetWrapper|null Returns an instance if ResourceSetWrapper 
     *     if resourceset is visible else NULL
     */
    private function _validateResourceSetAndGetWrapper(ResourceSet $resourceSet)
    {
        $cacheKey = $resourceSet->getName();
        if (array_key_exists($cacheKey, $this->_resourceSetWrapperCache)) {
            return $this->_resourceSetWrapperCache[$cacheKey];
        }

        $this->_validateResourceType($resourceSet->getResourceType());
        $resourceSetWrapper = new ResourceSetWrapper(
            $resourceSet, 
            $this->_configuration
        );
        if ($resourceSetWrapper->isVisible()) {
            $this->_resourceSetWrapperCache[$cacheKey] = $resourceSetWrapper;
        } else {
            $this->_resourceSetWrapperCache[$cacheKey] = null;
        }

        return $this->_resourceSetWrapperCache[$cacheKey];
    }

    /**
     * Validates the given instance of ResourceType
     * 
     * @param ResourceType $resourceType The ResourceType to validate
     * 
     * @return ResourceType
     * 
     * @throws ODataException Exception if $resourceType is invalid
     */
    private function _validateResourceType(ResourceType $resourceType)
    {
        $cacheKey = $resourceType->getName();
        if (array_key_exists($cacheKey, $this->_resourceTypeCache)) {
            return $this->_resourceTypeCache[$cacheKey];
        }

        //TODO: Do validation if any for the ResourceType
        $this->_resourceTypeCache[$cacheKey] = $resourceType;
        return $resourceType;
    }

    /**
     * Gets the resource type on which the resource property is declared on, 
     * If property is not declared in the given resource type, then this 
     * function drill down to the inheritance hierarchy of the given resource
     * type to find out the base class in which the property is declared
     * 
     * @param ResourceType     $resourceType     The resource type to start looking
     * @param ResourceProperty $resourceProperty The resource property in question
     * 
     * @return ResourceType|null Returns reference to the ResourceType on which 
     *                                   the $resourceProperty is declared, NULL if 
     *                                   $resourceProperty is not declared anywhere 
     *                                   in the inheritance hierarchy
     */
    private function _getResourceTypeWherePropertyIsDeclared(ResourceType $resourceType, 
        ResourceProperty $resourceProperty
    ) {
        $type = $resourceType;
        while ($type !== null) {
            if ($type->tryResolvePropertyTypeDeclaredOnThisTypeByName($resourceProperty->getName()) !== null) {
                break;
            }

            $type = $type->getBaseType();
        }

        return $type;
    }

    /**
     * Gets the underlying custom expression provider, the end developer is 
     * responsible for implementing IExpressionProvider if he choose for
     * 
     * @return IExpressionProvider Instance of IExpressionProvider implementation.
     *
     */
    public function getExpressionProvider()
    {
	    $expressionProvider = $this->_queryProvider->getExpressionProvider();
        if (is_null($expressionProvider)) {
            ODataException::createInternalServerError(Messages::providersWrapperExpressionProviderMustNotBeNullOrEmpty());
        }

        if (!$expressionProvider instanceof IExpressionProvider)
        {
			ODataException::createInternalServerError( Messages::providersWrapperInvalidExpressionProviderInstance() );
        }

        return $expressionProvider;
    }

	/**
	 * Indicates if the QueryProvider can handle ordered paging, this means respecting order, skip, and top parameters
	 * If the query provider can not handle ordered paging, it must return the entire result set and POData will
	 * perform the ordering and paging
	 *
	 * @return Boolean True if the query provider can handle ordered paging, false if POData should perform the paging
	 */
	public function handlesOrderedPaging()
	{
		return $this->_queryProvider->handlesOrderedPaging();
	}


    /**
     * Gets collection of entities belongs to an entity set
     *
     * @param ResourceSet $resourceSet The entity set containing the entities that need to be fetched
     * @param FilterInfo $filterInfo represents the $filter parameter of the OData query.  NULL if no $filter specified
     * @param InternalOrderByInfo $orderBy The orderBy information
     * @param int $top The top count
     * @param int $skip The skip count
     * 
     * @return \stdClass[]
     */
    public function getResourceSet(ResourceSet $resourceSet, FilterInfo $filterInfo, $orderBy, $top, $skip)
    {
		$customExpressionAsString = $filterInfo->getExpressionAsString();

		$entityInstances = $this->_queryProvider->getResourceSet(
			$resourceSet,
			$customExpressionAsString,
			$orderBy,
			$top,
			$skip
		);


        if (!is_array($entityInstances)) {
            ODataException::createInternalServerError(
                Messages::providersWrapperIDSQPMethodReturnsNonArray('IQueryProvider::getResourceSet')
            );
        }

        return $entityInstances;
    }
 

    
    /**
     * Gets an entity instance from an entity set identified by a key
     *
     * @param ResourceSet $resourceSet The entity set containing the entity to fetch
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to fetch
     *
     * @return \stdClass|null Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {
        $entityInstance = $this->_queryProvider->getResourceFromResourceSet( $resourceSet, $keyDescriptor );
        $this->_validateEntityInstance(
            $entityInstance, 
            $resourceSet, 
            $keyDescriptor, 
            'IQueryProvider::getResourceFromResourceSet'
        );
        return $entityInstance;
    }

    /**
     * Get related resource set for a resource
     * 
     * @param ResourceSet        $sourceResourceSet  The source resource set
     * @param mixed              $sourceEntity       The resource
     * @param ResourceSet        $targetResourceSet  The resource set of the navigation property
     *
     * @param ResourceProperty   $targetProperty     The navigation property to be retrieved
     *
     * @param FilterInfo $filterInfo An instance of FilterInfo if the $filter option is present, null otherwise
     * @param TODO               $orderBy            The orderby information
     * @param int                $top                The top count
     * @param int                $skip               The skip count
     *                                               
     * @return \stdClass[] Array of related resource if exists, if no related resources found returns empty array
     *
     */
	public function getRelatedResourceSet(
	    ResourceSet $sourceResourceSet,
        $sourceEntity,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty, 
        $filterInfo,
        $orderBy,
        $top,
        $skip
    ) {

		$customExpressionAsString = $filterInfo->getExpressionAsString();

		$entityInstances = $this->_queryProvider->getRelatedResourceSet(
		    $sourceResourceSet,
		    $sourceEntity,
		    $targetResourceSet,
		    $targetProperty,
		    $customExpressionAsString,
		    $orderBy,
		    $top,
		    $skip
		);


        if (!is_array($entityInstances)) {
            ODataException::createInternalServerError(
                Messages::providersWrapperIDSQPMethodReturnsNonArray('IQueryProvider::getRelatedResourceSet')
            );
        }

        return $entityInstances;
    }

    /**
     * Gets a related entity instance from an entity set identified by a key
     * 
     * @param ResourceSet      $sourceResourceSet The entity set related to the entity to be fetched.
     * @param object           $sourceEntity      The related entity instance.
     * @param ResourceSet      $targetResourceSet The entity set from which entity needs to be fetched.
     * @param ResourceProperty $targetProperty    The metadata of the target property.
     * @param KeyDescriptor    $keyDescriptor     The key to identify the entity to be fetched.
     *
     * 
     * @return \stdClass|null Returns entity instance if found else null
     */
    public function getResourceFromRelatedResourceSet(ResourceSet $sourceResourceSet,
        $sourceEntity, ResourceSet $targetResourceSet, ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        $entityInstance = $this->_queryProvider->getResourceFromRelatedResourceSet(
			$sourceResourceSet,
			$sourceEntity,
			$targetResourceSet,
			$targetProperty,
			$keyDescriptor
		);

	    $this->_validateEntityInstance(
            $entityInstance, $targetResourceSet, 
            $keyDescriptor, 
            'IQueryProvider::getResourceFromRelatedResourceSet'
        );
        return $entityInstance;
    }

    /**
     * Get related resource for a resource
     * 
     * @param ResourceSet      $sourceResourceSet The source resource set
     * @param mixed            $sourceEntity      The source resource
     * @param ResourceSet      $targetResourceSet The resource set of the navigation
     *                                            property
     * @param ResourceProperty $targetProperty    The navigation property to be 
     *                                            retrieved
     * 
     * @return \stdClass|null The related resource if exists else null
     */
    public function getRelatedResourceReference(ResourceSet $sourceResourceSet, 
        $sourceEntity, ResourceSet $targetResourceSet, 
        ResourceProperty $targetProperty
    ) {
        $entityInstance = $this->_queryProvider->getRelatedResourceReference(
            $sourceResourceSet, 
            $sourceEntity, 
            $targetResourceSet, 
            $targetProperty
        );

        // we will not throw error if the resource reference is null
        // e.g. Orders(1234)/Customer => Customer can be null, this is 
        // allowed if Customer is last segment. consider the following:
        // Orders(1234)/Customer/Orders => here if Customer is null then 
        // the UriProcessor will throw error.
        if (!is_null($entityInstance)) {
            $entityName 
                = $targetResourceSet
                    ->getResourceType()
                    ->getInstanceType()
                    ->getName();
            if (!is_object($entityInstance) 
                || !($entityInstance instanceof $entityName)
            ) {
                ODataException::createInternalServerError(
                    Messages::providersWrapperIDSQPMethodReturnsUnExpectedType(
                        $entityName, 
                        'IQueryProvider::getRelatedResourceReference'
                    )
                );
            }

            foreach ($targetProperty->getResourceType()->getKeyProperties() 
            as $keyName => $resourceProperty) {
                try {
                    $keyProperty = new \ReflectionProperty(
                        $entityInstance, 
                        $keyName
                    );
                    $keyValue = $keyProperty->getValue($entityInstance);
                    if (is_null($keyValue)) {
                        ODataException::createInternalServerError(
                            Messages::providersWrapperIDSQPMethodReturnsInstanceWithNullKeyProperties('IDSQP::getRelatedResourceReference')
                        );
                    }
                } catch (\ReflectionException $reflectionException) {
                    //throw ODataException::createInternalServerError(
                    //    Messages::orderByParserFailedToAccessOrInitializeProperty(
                    //        $resourceProperty->getName(), $resourceType->getName()
                    //    )
                    //);
                }
            }
        }

        return $entityInstance;
    }

    /**
     * Validate the given entity instance.
     * 
     * @param object        $entityInstance Entity instance to validate
     * @param ResourceSet   &$resourceSet   Resource set to which the entity 
     *                                      instance belongs to.
     * @param KeyDescriptor &$keyDescriptor The key descriptor.
     * @param string        $methodName     Method from which this function 
     *                                      invoked.
     *
     * @return void
     * 
     * @throws ODataException
     */
    private function _validateEntityInstance($entityInstance, 
        ResourceSet &$resourceSet, 
        KeyDescriptor &$keyDescriptor, 
        $methodName
    ) {
        if (is_null($entityInstance)) {
            ODataException::createResourceNotFoundError($resourceSet->getName());
        }

        $entityName = $resourceSet->getResourceType()->getInstanceType()->getName();
        if (!is_object($entityInstance) 
            || !($entityInstance instanceof $entityName)
        ) {
            ODataException::createInternalServerError(
                Messages::providersWrapperIDSQPMethodReturnsUnExpectedType(
                    $entityName, 
                    $methodName
                )
            );
        }

        foreach ($keyDescriptor->getValidatedNamedValues() 
            as $keyName => $valueDescription) {
            try {
                $keyProperty = new \ReflectionProperty($entityInstance, $keyName);
                $keyValue = $keyProperty->getValue($entityInstance);
                if (is_null($keyValue)) {
                    ODataException::createInternalServerError(
                        Messages::providersWrapperIDSQPMethodReturnsInstanceWithNullKeyProperties($methodName)
                    );
                }

                $convertedValue 
                    = $valueDescription[1]->convert($valueDescription[0]);
                if ($keyValue != $convertedValue) {
                    ODataException::createInternalServerError(
                        Messages::providersWrapperIDSQPMethodReturnsInstanceWithNonMatchingKeys($methodName)
                    );
                }
            } catch (\ReflectionException $reflectionException) {
                //throw ODataException::createInternalServerError(
                //  Messages::orderByParserFailedToAccessOrInitializeProperty(
                //      $resourceProperty->getName(), $resourceType->getName()
                //  )
                //);
            }
        }
    }

    /**
     * Assert that the given condition is true.
     *
     * @param boolean $condition         Condition to be asserted.
     * @param string  $conditionAsString String containing message incase
     *                                   if assertion fails.
     *
     * @throws InvalidOperationException Incase if assertion fails.
     *
     * @return void
     */
    protected function assert($condition, $conditionAsString)
    {
    	if (!$condition) {
    		throw new InvalidOperationException("Unexpected state, expecting $conditionAsString");
    	}
    }
}