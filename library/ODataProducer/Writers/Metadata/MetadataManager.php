<?php
/**
 * This class is used by the MetadataSerializer class while building 
 * CSDL document to get information about visible 
 * resource type, resource set, assoication type and association set.
 *
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer\Writers\Metadata;
use ODataProducer\Common\Version;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\MetadataEdmSchemaVersion;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
/** 
 * Meta data manager class
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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
     * @var MetadataQueryProviderWrapper
     */
    private $_metadataQueryproviderWrapper;

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
     * @param MetadataQueryProviderWrapper $provider Reference to the  
     * service metadata and query provider wrapper
     */
    private function __construct(MetadataQueryProviderWrapper $provider)
    {
        $this->_metadataQueryproviderWrapper = $provider;
    }

    /**
     * Gets reference to MetadataManager instance.
     * 
     * @param MetadataQueryProviderWrapper $provider Reference to the 
     * service metadata and query provider wrapper
     * 
     * @return MetadataManager
     * 
     * @throws InvalidOperationException
     * @throws ODataException
     */
    public static function create(MetadataQueryProviderWrapper $provider)
    {
        if (is_null(self::$_metadataManager)) {
            self::$_metadataManager = new MetadataManager($provider);
            try {
                self::$_metadataManager->_metadataResourceTypeSet = new MetadataResourceTypeSet($provider);
                self::$_metadataManager->_metadataAssociationTypeSet = new MetadataAssociationTypeSet($provider);
            } catch (\Exception $exception) {
                throw $exception;
            }
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
     * @return array(ResourceSetWrapper)
     */
    public function getResourceSets()
    {
        return $this->_metadataQueryproviderWrapper->getResourceSets();
    }

    /**
     * Gets collection of all resource type in the service.
     * 
     * @return array(ResourceType)
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
     * @return array(string, ResourceType)
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
     * @return array(ResourceProperty)
     */
    public function getAllVisiblePropertiesDeclaredOnThisType(ResourceType $resourceType)
    {
        return $this->_metadataResourceTypeSet->getAllVisiblePropertiesDeclaredOnThisType($resourceType);
    }

    /**
     * Gets collection of association set
     * 
     * @return array(ResourceAssociationSet)
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
     * @return array(ResourceAssociationType)
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
     * @return array(string, ResourceAssociationType)
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
            if ($edmSchemaVersion < MetadataEdmSchemaVersion::VERSION_2_DOT_0) {
                $edmSchemaVersion = MetadataEdmSchemaVersion::VERSION_2_DOT_0;
            }
        }

        if ($this->_metadataResourceTypeSet->hasBagProperty()) {
            $dsVersion->raiseVersion(3, 0);
            if ($edmSchemaVersion < MetadataEdmSchemaVersion::VERSION_2_DOT_2) {
                    $edmSchemaVersion = MetadataEdmSchemaVersion::VERSION_2_DOT_2;
            }
        }

    }
}
?>