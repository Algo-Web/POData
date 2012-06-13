<?php
/**
 * A wrapper class over IDataServiceMetadataProvider and IDataServiceQueryProvider
 * implementations, All call to implemenation of methods of these interfaces should 
 * go through this wrapper class so that wrapper methods of this class can perform 
 * validations on data returned by IDSMP methods  
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers
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
namespace ODataProducer\Providers;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Common\NotImplementedException;
use ODataProducer\Providers\Metadata\ResourceSetWrapper;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Configuration\IDataServiceConfiguration;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\Messages;
use ODataProducer\Providers\Metadata\MetadataMapping;
/**
 * A wrapper class over IDataServiceMetadataProvider and 
 * IDataServiceQueryProvider implementations
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class MetadataQueryProviderWrapper
{
    /**
     * Holds reference to IDataServiceMetadataProvider implementation
     * 
     * @var IDataServiceMetadataProvider
     */
    private $_metadataProvider;

    /**
     * Holds reference to IDataServiceQueryProvider or IDataServiceQueryProvider2 implementation
     * 
     * @var IDataServiceQueryProvider
     * @var IDataServiceQueryProvider2
     * 
     */
    private $_queryProvider;

    /**
     * Holds reference to IDataServiceConfiguration implementation
     * 
     * @var IDataServiceConfiguration
     */
    private $_configuration;

    /**
     * Indicate the type of this::$_queryProvider, True if $_queryProvider is an implementation of
     * IDataServiceQueryProvider2, False if its an implementation of IDataServiceQueryProvider
     * 
     * @var bool
     */
    private $_isQP2;

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
     * @var array(string, ResourceSetWrapper/NULL)
     */
    private $_resourceSetWrapperCache;

    /**
     * Cache for ResourceTypes
     * 
     * @var array(string, ResourceType)
     */
    private $_resourceTypeCache;

    /**
     * Cache for ResourceAssociationSet. If ResourceAssociationSet is invisible 
     * value will be null. 
     * 
     * @var array(string, ResourceAssociationSet/NULL)
     */
    private $_resourceAssociationSetCache;

    /**
     * Creates a new instance of MetadataQueryProviderWrapper
     * 
     * @param IDataServiceMetadataProvider $metadataProvider Reference to IDataServiceMetadataProvider implementation
     * @param IDataServiceQueryProvider    $queryProvider    Reference to IDataServiceQueryProvider/IDataServiceQueryProvider2 implementation
     * @param IDataServiceConfiguration    $configuration    Reference to IDataServiceConfiguration implementation
     * @param bool                         $isQP2            True if $queryProvider is an instance of IDataServiceQueryProvider2
     *                                                       False if $queryProvider is an instance of IDataServiceQueryProvider
     */
    public function __construct($metadataProvider, $queryProvider, $configuration, $isQP2)
    {
        $this->_metadataProvider = $metadataProvider;
        $this->_queryProvider = $queryProvider;
        $this->_configuration = $configuration;
        $this->_isQP2 = $isQP2;
        $this->_resourceSetWrapperCache = array();
        $this->_resourceTypeCache = array();
        $this->_resourceAssociationSetCache = array();
        $this->_resourcePropertyCache = array();
    }

    //Wrappers for IDataServiceMetadataProvider methods
    
    /**     
     * To get the Container name for the data source,
     * Note: Wrapper for IDataServiceMetadataProvider::getContainerName method
     * implementation
     * 
     * @return string that contains the name of the container
     * 
     * @throws ODataException Exception if IDSMP implementation returns empty 
     *                                  container name
     */
    public function getContainerName()
    {
        $containerName = $this->_metadataProvider->getContainerName();
        if (empty($containerName)) {
            throw new ODataException(
                Messages::metadataQueryProviderWrapperContainerNameMustNotBeNullOrEmpty(), 
                500
            );
        }

        return $containerName;
    }

    /**
     * To get Namespace name for the data source,
     * Note: Wrapper for IDataServiceMetadataProvider::getContainerNamespace method 
     * implementation
     * 
     * @return string that contains the namespace name.
     * 
     * @throws ODataException Exception if IDSMP implementation returns empty 
     *                        container namespace
     */
    public function getContainerNamespace()
    {
        $containerNamespace = $this->_metadataProvider->getContainerNamespace();
        if (empty($containerNamespace)) {
            throw new ODataException(
                Messages::metadataQueryProviderWrapperContainerNamespaceMustNotBeNullOrEmpty(),
                500
            );
        }

        return $containerNamespace;
    }

    /**
     * To get the data service configuration
     * 
     * @return IDataServiceConfiguration
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     *  To get all entity set information, 
     *  Note: Wrapper for IDataServiceMetadataProvider::getResourceSets method 
     *  implementation,
     *  This method returns array of ResourceSetWrapper instances but the 
     *  corrosponsing IDSMP method returns array of ResourceSet instances
     *  
     *  @return array(ResourceSetWrapper) Array of ResourceSetWrapper for 
     *                                    ResourceSets which are visible
     */
    public function getResourceSets()
    {
        $resourceSets = $this->_metadataProvider->getResourceSets();
        $resourceSetWrappers = array();
        $resourceSetNames = array();
        foreach ($resourceSets as $resourceSet) {
            if (in_array($resourceSet->getName(), $resourceSetNames)) {
                throw new ODataException(
                    Messages::metadataQueryProviderWrapperEntitySetNameShouldBeUnique(
                        $resourceSet->getName()
                    ), 
                    500
                );
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
     * Note: Wrapper for IDataServiceMetadataProvider::getTypes method implementation
     * 
     * @return array(ResourceType)
     */
    public function getTypes()
    {
        $resourceTypes = $this->_metadataProvider->getTypes();
        $resourceTypeNames = array();
        foreach ($resourceTypes as $resourceType) {
            if (in_array($resourceType->getName(), $resourceTypeNames)) {
                throw new ODataException(
                    Messages::metadataQueryProviderWrapperEntityTypeNameShouldBeUnique(
                        $resourceType->getName()
                    ), 
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
     * Note: Wrapper for IDataServiceMetadataProvider::resolveResourceSet method 
     * implementation
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceSetWrapper/NULL Returns resource set with the given name 
     *                                         if found, NULL if resource set is 
     *                                         set to invisible or not found
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
     * Note: Wrapper for IDataServiceMetadataProvider::resolveResourceType 
     * method implementation
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceType/NULL resource type with the given resource set 
     *                           name if found else NULL
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
     * An implementer of the interface should return null if the type does 
     * not have any derived types, 
     * Note: Wrapper for IDataServiceMetadataProvider::getDerivedTypes 
     * method implementation
     * 
     * @param ResourceType $resourceType Resource to get derived resource types from
     * 
     * @return array(ResourceType)/NULL
     */
    public function getDerivedTypes(ResourceType $resourceType)
    {
        $derivedTypes = $this->_metadataProvider->getDerivedTypes($resourceType);
        if (is_null($derivedTypes)) {
            return null;
        }

        foreach ($derivedTypes as $derivedType) {
            $this->_validateResourceType($derivedType);
        }

        return $derivedTypes;
    }

    /**
     * Returns true if $resourceType represents an Entity Type which has derived 
     * Entity Types, else false.
     * Note: Wrapper for IDataServiceMetadataProvider::hasDerivedTypes method 
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
     * Note: Wrapper for IDataServiceMetadataProvider::getResourceAssociationSet 
     * method implementation
     * 
     * @param ResourceSetWrapper $resourceSetWrapper Resource set of the source 
     *                                               association end
     * @param ResourceType       $resourceType       Resource type of the source 
     *                                               association end
     * @param ResourceProperty   $resourceProperty   Resource property of the source 
     *                                               association end
     * 
     * @return ResourceAssociationSet/NULL Returns ResourceAssociationSet for the source
     *                                             association end, NULL if no such 
     *                                             association end or resource set in the
     *                                             other end of the association is invisible
     */
    public function getResourceAssociationSet(ResourceSetWrapper $resourceSetWrapper, 
        ResourceType $resourceType, ResourceProperty $resourceProperty
    ) {        
        $resourceType 
            = $this->_getResourceTypeWherePropertyIsDeclared(
                $resourceType, 
                $resourceProperty
            );
        $cacheKey 
            = $resourceSetWrapper->getName() 
                . '_' . $resourceType->getName() 
                . '_' . $resourceProperty->getName();
        if (array_key_exists($cacheKey,  $this->_resourceAssociationSetCache)) {
            return $this->_resourceAssociationSetCache[$cacheKey];
        }

        $associationSet
            = $this->_metadataProvider->getResourceAssociationSet(
                $resourceSetWrapper->getResourceSet(), 
                $resourceType, 
                $resourceProperty
            );
        if (!is_null($associationSet)) {
            $thisAssociationSetEnd
                = $associationSet->getResourceAssociationSetEnd(
                    $resourceSetWrapper->getResourceSet(), 
                    $resourceType, 
                    $resourceProperty
                );
            $relatedAssociationSetEnd 
                = $associationSet->getRelatedResourceAssociationSetEnd(
                    $resourceSetWrapper->getResourceSet(), 
                    $resourceType, 
                    $resourceProperty
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
                    Messages::metadataQueryProviderWrapperIDSMPGetResourceSetReturnsInvalidResourceSet(
                        $resourceSetWrapper->getName(), 
                        $resourceType->getFullName(), 
                        $resourceProperty->getName()
                    ), 
                    500
                );
            }

            $relatedResourceSetWrapper 
                = $this->_validateResourceSetAndGetWrapper(
                    $relatedAssociationSetEnd->getResourceSet()
                );
            if ($relatedResourceSetWrapper === null) {
                $associationSet = null;
            } else {
                $this->_validateResourceType(
                    $thisAssociationSetEnd->getResourceType()
                );
                $this->_validateResourceType(
                    $relatedAssociationSetEnd->getResourceType()
                );
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
     * @return ResourceSetWrapper/NULL Returns instance of ResourceSetWrapper 
     *     (describes the entity set and associated configuration) for the 
     *     given navigation property. returns NULL if resourceset for the 
     *     navigation property is invisible or if metadata provider returns 
     *     null resource association set
     */
    public function getResourceSetWrapperForNavigationProperty(
        ResourceSetWrapper $resourceSetWrapper, ResourceType $resourceType, 
        ResourceProperty $navigationResourceProperty
    ) {
        $associationSet 
            = $this->getResourceAssociationSet(
                $resourceSetWrapper, 
                $resourceType, 
                $navigationResourceProperty
            );
        if (!is_null($associationSet)) {
            $relatedAssociationSetEnd 
                = $associationSet->getRelatedResourceAssociationSetEnd(
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
     * Gets the visible resource properties for the given resource type from 
     * the given resource set wrapper.
     * 
     * @param ResourceSetWrapper &$resourceSetWrapper Resource set wrapper in 
     *                                                question.
     * @param ResourceType       &$resourceType       Resource type in question.
     * 
     * @return array(string, ResourceProperty) Collection of visible resource 
     *     properties from the given resource set wrapper and resource type.
     */
    public function getResourceProperties(ResourceSetWrapper &$resourceSetWrapper, 
        ResourceType &$resourceType
    ) {
        if ($resourceType->getResourceTypeKind() == ResourceTypeKind::ENTITY) {
            $cacheKey = $resourceSetWrapper->getName() . '_' . $resourceType->getFullName();
            if (array_key_exists($cacheKey,  $this->_resourcePropertyCache)) {
                return $this->_resourcePropertyCache[$cacheKey];
            }

            $this->_resourcePropertyCache[$cacheKey] = array();
            foreach ($resourceType->getAllProperties() as $resourceProperty) {
                //Check whether this is a visible navigation property
                if ($resourceProperty->getTypeKind() == ResourceTypeKind::ENTITY 
                    && !is_null($this->getResourceSetWrapperForNavigationProperty($resourceSetWrapper, $resourceType, $resourceProperty))
                ) {
                    $this->_resourcePropertyCache[$cacheKey][$resourceProperty->getName()] = $resourceProperty;
                } else {
                    //primitive, bag or complex property
                    $this->_resourcePropertyCache[$cacheKey][$resourceProperty->getName()] = $resourceProperty;
                }
            }

            return $this->_resourcePropertyCache[$cacheKey];
        } else {
            //Complex resource type
            return $resourceType->getAllProperties();
        }
    }

    /**
     * Wrapper function over _validateResourceSetAndGetWrapper function
     *  
     * @param ResourceSet $resourceSet see the comments of _validateResourceSetAndGetWrapper
     * 
     * @return ResourceSetWrapper/NULL see the comments of _validateResourceSetAndGetWrapper
     */
    public function validateResourceSetAndGetWrapper(ResourceSet $resourceSet)
    {
        return $this->_validateResourceSetAndGetWrapper($resourceSet);
    }

    /**
     * Gets the Edm Schema version compliance to the metadata
     * 
     * @return MetadataEdmSchemaVersion
     */
    public function getEdmSchemaVersion()
    {
        //The minimal schema version for custom provider is 1.1
        return MetadataEdmSchemaVersion::VERSION_1_DOT_1;
    }

    /**
     * This function perfrom the following operations
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
     * @return ResourceSetWrapper/NULL Returns an instance if ResourceSetWrapper 
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
     * function drilldown to the inheritance hierarchy of the given resource 
     * type to find out the baseclass in which the property is declared 
     * 
     * @param ResourceType     $resourceType     The resource type to start looking
     * @param ResourceProperty $resourceProperty The resource property in question
     * 
     * @return ResourceType/NULL Returns reference to the ResourceType on which 
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
     * To check whether the QueryProvider implements IDSQP or ODSQP2
     * 
     * @return boolean True if the QueryProvider implements IDataServiceQueryPorivder2
     *                 False in-case of IDataServiceQueryProvider.
     */
    public function isQP2()
    {
    	return $this->_isQP2;
    }

    /**
     * Gets the underlying custom expression provider, the end developer is 
     * responsible for implementing IExpressionProvider if he choose for 
     * IDataServiceQueryProvider2.
     * 
     * @return IExpressionProvider/NULL Instance of IExpressionProvider implementation
     *                                  in case of IDSQP2, else null in case of IDSQP.
     */
    public function getExpressionProvider()
    {
    if ($this->_isQP2) {
      $expressionProvider = $this->_queryProvider->getExpressionProvider();
      if (is_null($expressionProvider)) {
        ODataException::createInternalServerError(
            Messages::metadataQueryProviderExpressionProviderMustNotBeNullOrEmpty()
        );
      }

      if (!is_object($expressionProvider)
          || array_search('ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\IExpressionProvider', class_implements($expressionProvider)) === false
      ) {
        ODataException::createInternalServerError(
            Messages::metadataQueryProviderInvalidExpressionProviderInstance()
        );
      }

      return $expressionProvider;
    }
    
    return null;
    }

  /**
   * Library will use this function to check whether library has to
   * apply orderby, skip and top. This function always return true
   * incase of IDSQP, in case of IDSQP2 it simply 
   * calls IDSQP2::canApplyQueryOptions
   * 
   * @return Boolean True If user want library to apply the query options
   *                 False If user is going to take care of orderby, skip
   *                 top options
   */
  public function canApplyQueryOptions()
  {
      if ($this->_isQP2) {
          return $this->_queryProvider->canApplyQueryOptions();
      }

      // For IDataServiceQueryProvider library will always take care of 
      // applying query options
      return true;
  }

    //Wrappers for IDataServiceQueryProvider methods

    /**
     * Gets collection of entities belongs to an entity set
     * 
     * @param ResourceSet        $resourceSet        The entity set whose entities needs 
     *                                               to be fetched
     * @param InternalFilterInfo $internalFilterInfo An instance of InternalFilterInfo
     *                                               if the $filter option is submitted
     *                                               by the client, NULL if no $filter 
     *                                               option present in the client request
     * @param TODO               $select             The select information
     * @param TODO               $orderby            The orderby information
     * @param int                $top                The top count
     * @param int                $skip               The skip count
     * 
     * @return array(Object)/array()
     */
    public function getResourceSet(ResourceSet $resourceSet, $internalFilterInfo, $select, $orderby, $top, $skip)
    {
      // TODO Remove following string replacement
      $filterOption = null;
        if ($filterOption!==null) {
            if ($this->_metadataProvider->mappedDetails !== null) {
                $filterOption = $this->updateFilterInfo($resourceSet, $this->_metadataProvider->mappedDetails, $filterOption);
            }
        }

        $entityInstances = null;
        if ($this->_isQP2) {
          $customExpressionAsString = null;
          if (!is_null($internalFilterInfo)) {
            $this->assert($internalFilterInfo->isCustomExpression(), '$internalFilterInfo->isCustomExpression()');
            $customExpressionAsString = $internalFilterInfo->getExpressionAsString();
          }

          // Library will pass the $select, $roderby, $top, $skip information to IDSQP2
          // implementation, IDQP2 can make use of these information to perform optimized
          // query operations. Library will not perform $orderby, $top, $skip operation
          // on result set if the IDSQP2::canApplyQueryOptions returns false, Lib assumes
          // IDSQP2 already performed these operations.
          $entityInstances = $this->_queryProvider->getResourceSet(
              $resourceSet,
              $customExpressionAsString,
              $select,
              $orderby,
              $top,
              $skip
          );
        } else {
            $entityInstances = $this->_queryProvider->getResourceSet($resourceSet);
        }

        if (!is_array($entityInstances)) {
            ODataException::createInternalServerError(
                Messages::metadataQueryProviderWrapperIDSQPMethodReturnsNonArray(
                    'IDataServiceQueryProvider::getResourceSet'
                )
            );
        }

        return $entityInstances;
    }
 
    /**
      Update filter expression and replace field names present in the expression by their names in DB
     * 
     * @param ResourceSet     $resourceSet   The resource set
     * @param MetadataMapping $mappedDetails Contains all the mappedInfo
     * @param String          $filterOption  filterExpression Corresponding to underlying DB
     * 
     * @return String Modified filterOption
     */
    public function updateFilterInfo(ResourceSet $resourceSet, MetadataMapping $mappedDetails, $filterOption)
    {
        $metaEntityName = $resourceSet->getName();
        $tableNameInDB = $mappedDetails->getMappedInfoForEntity($resourceSet->getName());
        $patterns = array();
        $replacements = array();
        foreach (array_keys($mappedDetails->mappingDetail[$metaEntityName]) as $metaPropertyName) {
            $patterns[0] = "/\s$metaPropertyName\s/";
            $patterns[1] = "/\(\s*$metaPropertyName\s*\)/";
            $patterns[2] = "/\(\s*$metaPropertyName\s*\,/";
            $patterns[3] = "/\s*$metaPropertyName\s*=/";
            $patterns[4] = "/\s*,$metaPropertyName/";
            $patterns[5] = "/\s*$metaPropertyName\s*\)/";
            $propertyName = $mappedDetails->mappingDetail[$metaEntityName][$metaPropertyName];
            if (preg_match("/\./",$propertyName)) {
                $replacements[0] = $propertyName;
                $replacements[1] = "($propertyName)";
                $replacements[2] = "($propertyName,";
                $replacements[3]  = "$propertyName=";
                $replacements[4] = ",$propertyName";
                $replacements[5] = "$propertyName)";
            } else {
                $replacements[0] = $tableNameInDB.".$propertyName";
                $replacements[1] = "($tableNameInDB."."$propertyName)";
                $replacements[2] = "($tableNameInDB."."$propertyName,";
                $replacements[3] = "$tableNameInDB."."$propertyName=";
                $replacements[4] = ", $tableNameInDB."."$propertyName";
                $replacements[5] = "$tableNameInDB."."$propertyName)";
            }
            $filterOption = preg_replace($patterns, $replacements, $filterOption);
            unset($replacements);
        }
        return $filterOption;
    }
    
    /**
     * Gets an entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet   $resourceSet   The entity set from which an entity
     *                                     needs to be fetched
     * @param KeyDescriptor $keyDescriptor The key to identify the entity to be 
     *                                     fetched
     * 
     * @return Object/NULL Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {
        $entityInstance = $this->_queryProvider->getResourceFromResourceSet(
            $resourceSet, $keyDescriptor
        );
        $this->_validateEntityInstance(
            $entityInstance, 
            $resourceSet, 
            $keyDescriptor, 
            'IDataServiceQueryProvider::getResourceFromResourceSet'
        );
        return $entityInstance;
    }

    /**
     * Get related resource set for a resource
     * 
     * @param ResourceSet        $sourceResourceSet  The source resource set
     * @param mixed              $sourceEntity       The resource
     * @param ResourceSet        $targetResourceSet  The resource set of the navigation
     *                                               property
     * @param ResourceProperty   $targetProperty     The navigation property to be 
     *                                               retrieved
     * @param InternalFilterInfo $internalFilterInfo An instance of InternalFilterInfo
     *                                               if the $filter option is submitted
     *                                               by the client, NULL if no $filter 
     *                                               option present in the client request
     * @param TODO               $select             The select information
     * @param TODO               $orderby            The orderby information
     * @param int                $top                The top count
     * @param int                $skip               The skip count
     *                                               
     * @return array(Objects)/array() Array of related resource if exists, if no 
     *                                related resources found returns empty array
     */
    public function getRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntity, ResourceSet $targetResourceSet, 
        ResourceProperty $targetProperty, 
        $internalFilterInfo,
        $select,
        $orderby,
        $top,
        $skip
    ) {
      $filterOption = null;
        if ($filterOption!==null) {
            if ($this->_metadataProvider->mappedDetails !== null) {
                $filterOption = $this->updateFilterInfo($targetResourceSet, $this->_metadataProvider->mappedDetails, $filterOption);
            }
        }
        
        if ($this->_isQP2) {
          $customExpressionAsString = null;
          if (!is_null($internalFilterInfo)) {
            $this->assert($internalFilterInfo->isCustomExpression(), '$internalFilterInfo->isCustomExpression()');
            $customExpressionAsString = $internalFilterInfo->getExpressionAsString();
          }

          // Library will pass the $select, $roderby, $top, $skip information to IDSQP2
          // implementation, IDQP2 can make use of these information to perform optimized
          // query operations. Library will not perform $orderby, $top, $skip operation
          // on result set if the IDSQP2::canApplyQueryOptions returns false, Lib assumes
          // IDSQP2 already performed these operations.
          $entityInstances 
              = $this->_queryProvider->getRelatedResourceSet(
                  $sourceResourceSet, 
                  $sourceEntity, 
                  $targetResourceSet, 
                  $targetProperty,
                  $customExpressionAsString,
                  $select,
                  $orderby,
                  $top,
                  $skip
              );
        } else {
          $entityInstances
              = $this->_queryProvider->getRelatedResourceSet(
                  $sourceResourceSet,
                  $sourceEntity,
                  $targetResourceSet,
                  $targetProperty
              );
        }

        if (!is_array($entityInstances)) {
            ODataException::createInternalServerError(
                Messages::metadataQueryProviderWrapperIDSQPMethodReturnsNonArray(
                    'IDataServiceQueryProvider::getRelatedResourceSet'
                )
            );
        }

        return $entityInstances;
    }

    /**
     * Gets a related entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet      $sourceResourceSet The entity set related to
     *                                            the entity to be fetched.
     * @param object           $sourceEntity      The related entity instance.
     * @param ResourceSet      $targetResourceSet The entity set from which
     *                                            entity needs to be fetched.
     * @param ResourceProperty $targetProperty    The metadata of the target 
     *                                            property.
     * @param KeyDescriptor    $keyDescriptor     The key to identify the entity 
     *                                            to be fetched.
     * 
     * @return Object/NULL Returns entity instance if found else null
     */
    public function getResourceFromRelatedResourceSet(ResourceSet $sourceResourceSet,
        $sourceEntity, ResourceSet $targetResourceSet, ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        $entityInstance 
            = $this->_queryProvider->getResourceFromRelatedResourceSet(
                $sourceResourceSet, 
                $sourceEntity, 
                $targetResourceSet, 
                $targetProperty, 
                $keyDescriptor
            );
        $this->_validateEntityInstance(
            $entityInstance, $targetResourceSet, 
            $keyDescriptor, 
            'IDataServiceQueryProvider::getResourceFromRelatedResourceSet'
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
     * @return Object/null The related resource if exists else null
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
                    Messages::metadataQueryProviderWrapperIDSQPMethodReturnsUnExpectedType(
                        $entityName, 
                        'IDataServiceQueryProvider::getRelatedResourceReference'
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
                            Messages::metadataQueryProviderWrapperIDSQPMethodReturnsInstanceWithNullKeyProperties('IDSQP::getRelatedResourceReference')
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
                Messages::metadataQueryProviderWrapperIDSQPMethodReturnsUnExpectedType(
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
                        Messages::metadataQueryProviderWrapperIDSQPMethodReturnsInstanceWithNullKeyProperties($methodName)
                    );
                }

                $convertedValue 
                    = $valueDescription[1]->convert($valueDescription[0]);
                if ($keyValue != $convertedValue) {
                    ODataException::createInternalServerError(
                        Messages::metadataQueryProviderWrapperIDSQPMethodReturnsInstanceWithNonMatchingKeys($methodName)
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
     * @throws InvalidOperationException Incase if assertion failes.
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
?>