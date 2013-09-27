<?php

namespace POData\Writers\Metadata;

use POData\Common\Version;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\EdmSchemaVersion;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceAssociationType;
use POData\Providers\Metadata\ResourceProperty;

/**
 * Class MetadataManager
 *
 * used by the MetadataSerializer class while building
 * CSDL document to get information about visible
 * resource type, resource set, assoication type and association set.
 *
 * @package POData\Writers\Metadata
 */
class MetadataManager
{
    /**
     * Holds reference to MetadataManager instance
     *      
     * @var MetadataManager
     */
    private static $_metadataManager = null;

    /**
     * Holds reference to the wrapper over service metadata and 
     * query provider implemenations
     * In this context this provider will be used for 
     * gathering metadata informations only.
     *      
     * @var ProvidersWrapper
     */
    private $providersWrapper;

    /**
     * Helps to get details about all visible resource types defined in the service 
     * 
     * @var MetadataResourceTypeSet
     */
    private $_metadataResourceTypeSet;

    /**
     * Helps to get details about all association types and 
     * association sets defined for the service
     *
     * @var MetadataAssociationTypeSet
     */
    private $_metadataAssociationTypeSet;

    /**
     * Creates new instance of MetadataManager
     * 
     * @param ProvidersWrapper $provider Reference to the service metadata and query provider wrapper
     *
     */
    private function __construct(ProvidersWrapper $provider)
    {
        $this->providersWrapper = $provider;
    }

    /**
     * Gets reference to MetadataManager instance.
     * 
     * @param ProvidersWrapper $provider Reference to the
     * service metadata and query provider wrapper
     * 
     * @return MetadataManager
     * 
     * @throws InvalidOperationException
     * @throws ODataException
     */
    public static function create(ProvidersWrapper $provider)
    {
        if (is_null(self::$_metadataManager)) {
            self::$_metadataManager = new MetadataManager($provider);
            self::$_metadataManager->_metadataResourceTypeSet = new MetadataResourceTypeSet($provider);
            self::$_metadataManager->_metadataAssociationTypeSet = new MetadataAssociationTypeSet($provider);
        }

        return  self::$_metadataManager;
    }

    /**
     * To check is there any MLE resource type 
     * 
     * @return boolean
     */
    public function hasMediaLinkEntry()
    {
        return $this->_metadataResourceTypeSet->hasMediaLinkEntry();
    }

    /**
     * To check is there any resource type with named stream prtoperty
     *  
     * @return boolean
     */
    public function hasNamedStreams()
    {
        return $this->_metadataResourceTypeSet->hasNamedStreams();
    }

    /**
     * Gets resource sets which are visible
     * 
     * @return ResourceSetWrapper[]
     */
    public function getResourceSets()
    {
        return $this->providersWrapper->getResourceSets();
    }

    /**
     * Gets collection of all resource type in the service.
     * 
     * @return ResourceType[]
     */
    public function getResourceTypes()
    {
        $this->_metadataResourceTypeSet->getResourceTypes();
    }

    /**
     * Gets collection of resource types belongs to the given namespace
     * 
     * @param string $namespace The namespace name to get the 
     * resource types belongs to
     * 
     * @return ResourceType[]
     */
    public function getResourceTypesForNamespace($namespace)
    {
        return $this->_metadataResourceTypeSet->getResourceTypesForNamespace($namespace);
    }

    /**
     * Gets collection of resource types with their namespace
     * 
     * @return  array(string, array(string, ResourceType))
     */
    public function getResourceTypesAlongWithNamespace()
    {
        return $this->_metadataResourceTypeSet->getResourceTypesAlongWithNamespace();
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
        return $this->_metadataResourceTypeSet->getAllVisiblePropertiesDeclaredOnThisType($resourceType);
    }

    /**
     * Gets collection of association set
     * 
     * @return ResourceAssociationSet[]
     */
    public function getAssociationSets()
    {
        return $this->_metadataAssociationTypeSet->getAssociationSets();
    }

    /**
     * Gets collection of unique association type for the given namespace, 
     * the 'getResourceAssociationTypesForNamespace' 
     * will also returns collection of association type for a given namespace 
     * but will contain duplicate association type in case of 
     * bi-directional relationship.
     * 
     * @param string $namespace Namespace name to get the 
     * association type belongs to 
     * 
     * @return ResourceAssociationType[]
     */
    public function getUniqueResourceAssociationTypesForNamespace($namespace)
    {
        return $this->_metadataAssociationTypeSet->getUniqueResourceAssociationTypesForNamespace($namespace);
    }

    /**
     * Gets collection of association types belongs to the given namespace, 
     * creates a collection for the namespace if its not already there, 
     * This array of association types in a namespace will contains one entry per 
     * direction, so for a bidirectional relationship same AssociationType 
     * (having same association type name) will appear twice 
     * with different cache (lookup) key.
     * 
     * @param string $namespace Namespace name to get the 
     * association type belongs to 
     * 
     * @return ResourceAssociationType[]
     */
    public function getResourceAssociationTypesForNamespace($namespace)
    {
        return $this->_metadataAssociationTypeSet->getResourceAssociationTypesForNamespace($namespace);
    }

    /**
     * Get appropriate data service and edm schema version
     * 
     * @param Version &$dsVersion        On return, this parmater will contain 
     *                                   data service version for the metadata
     * @param string  &$edmSchemaVersion On return, this parmater will contain 
     *                                   edm schema version for the metadata
     * 
     * @return void 
     */
    public function getDataServiceAndEdmSchemaVersions(Version &$dsVersion, &$edmSchemaVersion)
    {
        if ($this->_metadataResourceTypeSet->hasNamedStreams()) {
            $dsVersion->raiseVersion(3, 0);
            if ($edmSchemaVersion < EdmSchemaVersion::VERSION_2_DOT_0) {
                $edmSchemaVersion = EdmSchemaVersion::VERSION_2_DOT_0;
            }
        }

        if ($this->_metadataResourceTypeSet->hasBagProperty()) {
            $dsVersion->raiseVersion(3, 0);
            if ($edmSchemaVersion < EdmSchemaVersion::VERSION_2_DOT_2) {
                    $edmSchemaVersion = EdmSchemaVersion::VERSION_2_DOT_2;
            }
        }

    }
}