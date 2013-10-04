<?php

namespace POData\Writers\Metadata;

use POData\Common\Messages;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;

/**
 * Class MetadataResourceTypeSet
 *
 * Finds all visible resource sets and types from the given provider
 *
 * Iterate over all resource (entity) types belongs to
 * visible resource (entity) sets,for each entity type
 * retrieve its derived and base resource types,
 * group these resource types (base types, type, derived types)
 * based on the namespace in which it falls.
 * Note: ServiceConfiguration::setEntitySetAccessRule is used to
 * make a resource set visible
 *
 * Iterate through the properties of each resource (entity) type,
 * retrieve resource type of complex properties,
 * group these resource types based on namespace in which it falls.
 *
 * @package POData\Writers\Metadata
 */
class MetadataResourceTypeSet extends MetadataBase
{
    /**
     * Array of namespace along with the resource types in that namespace.
     * Namespace will be the key and value will be array of 'ResourceType'
     * in that namespace 
     * (as key value pair key: Resource type name, value:ResourceType))
     * array(namespace_name, array(resource_type_name, ResourceType))
     * 
     * @var array(string, array(string, ResourceType))
     */
    private $_resourceTypes = array();

    /**
     * Array of all resource types
     * 
     * @var ResourceType[]
     */
    private $_resourceTypesNoNamespace = null;

    /**
     * Set to true if found any visible MLE in the resource (entity) types
     * 
     * @var boolean
     */
    private $_hasVisibleMediaLinkEntry = false;

    /**
     * Set to true if found any visible named streams in the resource (entity) types
     * 
     * @var boolean
     */
    private $_hasVisibleNamedStreams = false;

    /**
     * Set to true if found any bag property in the resource (entity) types
     * 
     * @var boolean
     */
    
    private $_hasBagProperty = false;

    /**
     * Construct new instance of MetadataResourceTypeSet, this constructor 
     * finds and caches all resource types in the service
     * 
     * @param ProvidersWrapper $provider Reference to the
     * service metadata and query provider wrapper 
     */
    public function __construct(ProvidersWrapper $provider)
    {
        parent::__construct($provider);
        foreach ($this->providersWrapper->getResourceSets() as $resourceSetWrapper) {
            $this->_populateResourceTypeForSet($resourceSetWrapper);
        }
    }

    /**
     * To check is there any MLE resource type 
     * 
     * @return boolean
     */
    public function hasMediaLinkEntry()
    {
        return $this->_hasVisibleMediaLinkEntry;
    }

    /**
     * To check is there any resource type with named stream prtoperty
     *  
     * @return boolean
     */
    public function hasNamedStreams()
    {
        return $this->_hasVisibleNamedStreams;
    }

    /**
     * To check is there any resource type with bag prtoperty
     *  
     * @return boolean
     */
    public function hasBagProperty()
    {
        return $this->_hasBagProperty;
    }

    /**
     * Gets collection of resource types belongs to the 
     * given namespace, creates a collection
     * for the namespace, if its not already there.     
     * 
     * @param string $namespace The namespace name to get the 
     * resource types belongs to
     * 
     * @return ResourceType[]
     */
    public function &getResourceTypesForNamespace($namespace)
    {
        if (!array_key_exists($namespace, $this->_resourceTypes)) {
            $this->_resourceTypes[$namespace] = array();
        }

        return $this->_resourceTypes[$namespace];
    }

    /**
     * Gets collection of resource types with their namespace.
     * 
     * @return  array(string, array(string, ResourceType))
     */
    public function getResourceTypesAlongWithNamespace()
    {
        return $this->_resourceTypes;
    }

    /**
     * Gets collection of all resource type in the service.
     * 
     * @return ResourceType[]
     */
    public function getResourceTypes()
    {
        if (is_null($this->_resourceTypesNoNamespace)) {
            $this->_resourceTypesNoNamespace = array();
            foreach ($this->_resourceTypes as $nameSpace => $resourceTypeWithName) {
                foreach ($resourceTypeWithName as $typeName => $resourceType) {
                    $this->_resourceTypesNoNamespace[] = $resourceType;
                }
            }
        }

        return $this->_resourceTypesNoNamespace;
    }

    /**
     * Gets array of all visible resource properties from a resource type
     * 
     * @param ResourceType $resourceType The resource type to inspect
     * 
     * @return ResourceProperty[]
     */
    public function getAllVisiblePropertiesDeclaredOnThisType(ResourceType $resourceType)
    {
        $visibleProperties = array();
        foreach ($resourceType->getPropertiesDeclaredOnThisType() as $name => $resourceProperty) {
            if ($resourceProperty->getTypeKind() == ResourceTypeKind::ENTITY) {
                $resourceType = $resourceProperty->getResourceType();
                $resourceTypeNamespace = $this->getResourceTypeNamespace($resourceType);
                $resourceTypesInNamespace = $this->getResourceTypesForNamespace($resourceTypeNamespace);
                if (!array_key_exists($resourceTypeNamespace . '.' . $resourceType->getName(), $resourceTypesInNamespace)) {
                    continue;
                }
            }

            $visibleProperties[] = $resourceProperty;
        }

        return $visibleProperties;
    }

    /**
     * Iterate over the resource type of the given resource set, 
     * derived resource types base resource types and complex types 
     * used in these resource types and cache them.
     * 
     * @param ResourceSetWrapper $resourceSetWrapper The resource set to inspect
     * 
     * @return void
     * 
     * @throws InvalidOperationException Throws exception in following cases:
     * (1) If IDSMP::getDerivedTypes returns any type other than null or array
     * (2) If Named streams are found on derived types
     */
    private function _populateResourceTypeForSet(ResourceSetWrapper $resourceSetWrapper)
    {
        $derivedTypes = $this->providersWrapper->getDerivedTypes($resourceSetWrapper->getResourceType());

        //Populate Resource type for derived types and
        //complex types in derived types
        foreach ($derivedTypes as $derivedType) {
            if ($derivedType->hasNamedStream()) {
                throw new InvalidOperationException(Messages::metadataResourceTypeSetNamedStreamsOnDerivedEntityTypesNotSupported($resourceSetWrapper->getName(), $derivedType->getFullName()));
            }
            $this->_populateResourceTypes($derivedType);
            $this->_populateComplexTypes($derivedType);
        }

        //Populate Resource type for for this type and 
        //base types and complex types in this type and base types
        $resourceType = $resourceSetWrapper->getResourceType();
        while ($resourceType != null) {
            $this->_populateResourceTypes($resourceType);
            $this->_populateComplexTypes($resourceType);
            $resourceType = $resourceType->getBaseType();
        }
    }

    /**
     * Store the given resource type into the 
     * cache for the resource type namespace, if not already cached, 
     * also  check for MLE and named stream to set the corresponding 
     * class level properties.
     * 
     * @param ResourceType $resourceType The resource type to cache
     * 
     * @return boolean True if the resource type is already in the cache, 
     * false otherwise
     */
    private function _populateResourceTypes(ResourceType $resourceType)
    {
        $resourceTypeNamespace = $this->getResourceTypeNamespace($resourceType);
        $resourceTypesInNamespace = &$this->getResourceTypesForNamespace($resourceTypeNamespace);
        $resourceNameWithNamespace = $resourceTypeNamespace . '.' . $resourceType->getName();
        if (!array_key_exists($resourceNameWithNamespace, $resourceTypesInNamespace)) {
            if ($resourceType->isMediaLinkEntry()) {
                $this->_hasVisibleMediaLinkEntry = true;
            }

            if ($resourceType->hasNamedStream()) {
                $this->_hasVisibleNamedStreams = true;
            }

            $arrayToDetectLoop = array();
            if ($resourceType->hasBagProperty($arrayToDetectLoop)) {
                $this->_hasBagProperty = true;
            }

            $resourceTypesInNamespace[$resourceNameWithNamespace] = $resourceType;
            return true;
        }

        return false;
    }

    /**
     * Retrieve the complex type(s) used in the given resource type and cache them.
     * 
     * @param ResourceType $resourceType The resource type to inspect
     * 
     * @return void
     * 
     * @throws InvalidOperationException If found any complex type bag property 
     * with derived type(s) 
     */
    private function _populateComplexTypes(ResourceType $resourceType)
    {
        foreach ($resourceType->getPropertiesDeclaredOnThisType() as $property) {
            if ($property->isKindOf(ResourcePropertyKind::COMPLEX_TYPE)) {
                if ($property->isKindOf(ResourcePropertyKind::BAG)) {
                    //Validate the bag complex type 
                    //as it should not have derived type
                    if ($this->providersWrapper->hasDerivedTypes($resourceType)) {
                        throw new InvalidOperationException(Messages::metadataResourceTypeSetBagOfComplexTypeWithDerivedTypes($resourceType->getFullName()));
                    }
                }

                if ($this->_populateResourceTypes($property->getResourceType())) {
                    $this->_populateComplexTypes($property->getResourceType());
                }
            }
        }
    }
}