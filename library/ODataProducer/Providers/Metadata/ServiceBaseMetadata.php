<?php
/** 
 * Implementation of IDataServiceMetadataProvider.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
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
namespace ODataProducer\Providers\Metadata;
use ODataProducer\Providers\Metadata\ResourceStreamInfo;
use ODataProducer\Providers\Metadata\ResourceAssociationSetEnd;
use ODataProducer\Providers\Metadata\ResourceAssociationSet;
use ODataProducer\Common\NotImplementedException;
use ODataProducer\Providers\Metadata\Type\EdmPrimitiveType;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Providers\Metadata\ResourcePropertyKind;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Common\InvalidOperationException;
use ODataProducer\Providers\Metadata\IDataServiceMetadataProvider;

/**
 * Custom IDataServiceMetadata implementation
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ServiceBaseMetadata implements IDataServiceMetadataProvider
{
    protected $resourceSets = array();
    protected $resourceTypes = array();
    protected $associationSets = array();
    protected $containerName;
    protected $namespaceName;
    public $mappedDetails = null;
    
    //Begin Implementation of IDataServiceMetadataProvider
    /**
     * get the Container name for the data source.
     * 
     * @return String container name
     */
    public function getContainerName()
    {
        return $this->containerName;
    }
    
    /**
     * get Namespace name for the data source.
     * 
     * @return String namespace
     */
    public function getContainerNamespace()
    {
        return $this->namespaceName;
    }
    
    /**
     * get all entity set information.
     * 
     * @return array(ResourceSet)
     */
    public function getResourceSets()
    {
        return array_values($this->resourceSets);
    }
    
    /**
     * get all resource types in the data source.
     * 
     * @return array(ResourceType)
     */
    public function getTypes()
    {
        return array_values($this->resourceTypes);
    }
    
    /**
     * get a resource set based on the specified resource set name.
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceSet/NULL resource set with the given name if found 
     *                          else NULL
     */
    public function resolveResourceSet($name)
    {
        if (array_key_exists($name, $this->resourceSets)) {
            return $this->resourceSets[$name];
        }
        
        return null;
    }
    
    /**
     * get a resource type based on the resource set name.
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceType/NULL resource type with the given resource set
     *                           name if found else NULL
     */
    public function resolveResourceType($name)
    {
        if (array_key_exists($name, $this->resourceTypes)) {
            return $this->resourceTypes[$name];
        }
        
        return null;
    }
    
    /**
     * The method must return a collection of all the types derived from 
     * $resourceType The collection returned should NOT include the type 
     * passed in as a parameter An implementer of the interface should 
     * return null if the type does not have any derived types.
     * 
     * @param ResourceType $resourceType Resource to get derived resource 
     *                                   types from
     * 
     * @return array(ResourceType)/NULL
     */
    public function getDerivedTypes(ResourceType $resourceType)
    {
        return null;
    }
    
    /**
     * Returns true if $resourceType represents an Entity Type which has derived
     *                               Entity Types, else false.
     * 
     * @param ResourceType $resourceType Resource to check for derived resource 
     *                                   types.
     * 
     * @return boolean
     */
    public function hasDerivedTypes(ResourceType $resourceType)
    {
        return false;
    }
    
    /**
     * Gets the ResourceAssociationSet instance for the given source 
     * association end.
     * 
     * @param ResourceSet      $sourceResourceSet      Resource set 
     *                                                 of the source
     *                                                 association end
     * @param ResourceType     $sourceResourceType     Resource type of the source
     *                                                 association end
     * @param ResourceProperty $targetResourceProperty Resource property of 
     *                                                 the source
     *                                                 association end
     * 
     * @return ResourceAssociationSet
     */
    public function getResourceAssociationSet(ResourceSet $sourceResourceSet, ResourceType $sourceResourceType, ResourceProperty $targetResourceProperty)
    {
        //e.g.
        //ResourceSet => Representing 'Customers' entity set
        //ResourceType => Representing'Customer' entity type
        //ResourceProperty => Representing 'Orders' property
        //We have created ResourceAssoicationSet while adding 
        //ResourceSetReference or ResourceReference
        //and kept in $this->associationSets
        //$metadata->addResourceSetReferenceProperty(
        //             $customersEntityType, 
        //             'Orders', 
        //             $ordersResourceSet
        //             );
        
        $targetResourceSet = $targetResourceProperty->getResourceType()->getCustomState();
        if (is_null($targetResourceSet)) {
            throw new InvalidOperationException('Failed to retrieve the custom state from ' . $resourceProperty->getResourceType()->getName());
        }

        //Customer_Orders_Orders, Order_Customer_Customers
        $key = $sourceResourceType->getName() . '_' . $targetResourceProperty->getName() . '_' . $targetResourceSet->getName();
        if (array_key_exists($key, $this->associationSets)) {
            return $this->associationSets[$key];
        }

        return null;
    }

    //End Implementation of IDataServiceMetadataProvider
    
    /** 
     * Construct new instance of NorthWindMetadata
     * 
     * @param string $containerName container name for the datasource.
     * @param string $namespaceName namespace for the datasource.
     * 
     * @return void
     */
    public function __construct($containerName, $namespaceName)
    {
        $this->containerName = $containerName;
        $this->namespaceName = $namespaceName;
    }
    
    /**
     * Add an entity type
     * 
     * @param ReflectionClass $refClass  reflection class of the entity
     * @param string          $name      name of the entity
     * @param string          $namespace namespace of the datasource
     * 
     * @return ResourceType
     */
    public function addEntityType($refClass, $name, $namespace=null)
    {
        if (array_key_exists($name, $this->resourceTypes)) {
            throw new InvalidOperationException('Type with same name already added');
        }
        
        $entityType = new ResourceType($refClass, ResourceTypeKind::ENTITY, $name, $namespace);
        $this->resourceTypes[$name] = $entityType;
        return $entityType;
    }
    
    /**
     * Add a complex type
     * 
     * @param ReflectionClass $refClass         reflection class of the 
     *                                          complex entity type
     * @param string          $name             name of the entity
     * @param string          $namespace        namespace of the datasource.
     * @param ResourceType    $baseResourceType base resource type
     * 
     * @return ResourceType
     */
    public function addComplexType($refClass, $name, $namespace=null, $baseResourceType = null)
    {
        if (array_key_exists($name, $this->resourceTypes)) {
            throw new InvalidOperationException('Type with same name already added');
        }
        
        $complexType = new ResourceType($refClass, ResourceTypeKind::COMPLEX, $name, $namespace, $baseResourceType);    	
        $this->resourceTypes[$name] = $complexType;
        return $complexType;
    }
    
    /**
     * Add a resouce set
     * 
     * @param string      $name         name of the resource set
     * @param ResouceType $resourceType resource type
     * 
     * @throws InvalidOperationException
     * 
     * @return ResourceSet
     */
    public function addResourceSet($name, $resourceType)
    {
        if (array_key_exists($name, $this->resourceSets)) {
            throw new InvalidOperationException('Resource Set already added');
        }
        
        $this->resourceSets[$name] = new ResourceSet($name, $resourceType);
        //No support for multiple ResourceSet with same EntityType
        //So keeping reference to the 'ResourceSet' with the entity type
        $resourceType->setCustomState($this->resourceSets[$name]);
        return $this->resourceSets[$name];
    }
    
    /**
     * To add a Key-primitive property to a resouce (Complex/Entuty)
     * 
     * @param ResourceType $resourceType resource type to which key property
     *                                   is to be added
     * @param string       $name         name of the key property
     * @param TypeCode     $typeCode     type of the key property
     * 
     * @return void
     */
    public function addKeyProperty($resourceType, $name, $typeCode)
    {
        $this->_addPrimitivePropertyInternal($resourceType, $name, $typeCode, true);
    }
    
    /**
     * To add a NonKey-primitive property (Complex/Entity)
     * 
     * @param ResourceType $resourceType resource type to which key property
     *                                   is to be added
     * @param string       $name         name of the key property
     * @param TypeCode     $typeCode     type of the key property
     * @param Boolean      $isBag        property is bag or not
     * 
     * @return void
     */
    public function addPrimitiveProperty($resourceType, $name, $typeCode, $isBag = false)
    {
        $this->_addPrimitivePropertyInternal($resourceType, $name, $typeCode, false, $isBag);
    }

    /**
     * To add a non-key etag property
     * 
     * @param ResourceType $resourceType resource type to which key property
     *                                   is to be added
     * @param String       $name         name of the property
     * @param String       $typeCode     type of the etag property
     * 
     * @return void
     */
    public function addETagProperty($resourceType, $name, $typeCode)
    {
        $this->_addPrimitivePropertyInternal($resourceType, $name, $typeCode, false, false, true);
    }

    /**
     * To add a resource reference property
     * 
     * @param ResourceType $resourceType      The resource type to add the resource
     *                                        reference property to
     * @param string       $name              The name of the property to add
     * @param ResourceSet  $targetResourceSet The resource set the resource reference
     *                                        property points to
     *                    
     * @return void                  
     */
    public function addResourceReferenceProperty($resourceType, $name, $targetResourceSet)
    {
        $this->_addReferencePropertyInternal(
            $resourceType, 
            $name, 
            $targetResourceSet,
            ResourcePropertyKind::RESOURCE_REFERENCE
        );
    }

    /**
     * To add a resource set reference property
     *      
     * @param ResourceType $resourceType      The resource type to add the 
     *                                        resource reference set property to
     * @param string       $name              The name of the property to add
     * @param ResourceSet  $targetResourceSet The resource set the resource 
     *                                        reference set property points to
     *                                        
     * @return void                                      
     */
    public function addResourceSetReferenceProperty($resourceType, $name, $targetResourceSet)
    {
        $this->_addReferencePropertyInternal(
            $resourceType, 
            $name, 
            $targetResourceSet,
            ResourcePropertyKind::RESOURCESET_REFERENCE
        );
    }
    
    /**
     * To add a complex property to entity or complex type
     * 
     * @param ResourceType $resourceType        The resource type to which the 
     *                                          complex property needs to add
     * @param string       $name                name of the complex property
     * @param ResourceType $complexResourceType complex resource type
     * @param Boolean      $isBag               complex type is bag or not
     * 
     * @return ResourceProperty
     */
    public function addComplexProperty($resourceType, $name, $complexResourceType, $isBag = false)
    {
        if ($resourceType->getResourceTypeKind() != ResourceTypeKind::ENTITY 
            && $resourceType->getResourceTypeKind() != ResourceTypeKind::COMPLEX
        ) {
            throw new InvalidOperationException('complex property can be added to an entity or another complex type');
        }
        
        try 
        {
            $resourceType->getInstanceType()->getProperty($name);
        }
        catch (ReflectionException $ex)
        {
            throw new InvalidOperationException(
                'Can\'t add a property which does not exist on the instance type.'
            );
        }

        $kind = ResourcePropertyKind::COMPLEX_TYPE;
        if ($isBag) {    	   
            $kind = $kind | ResourcePropertyKind::BAG;
        }

        $resourceProperty = new ResourceProperty($name, null, $kind, $complexResourceType);
        $resourceType->addProperty($resourceProperty);
        return $resourceProperty;
    }
    
    /**
     * To add a Key/NonKey-primitive property to a resource (complex/entity)
     * 
     * @param ResourceType $resourceType   Resource type
     * @param string       $name           name of the property
     * @param TypeCode     $typeCode       type of property
     * @param boolean      $isKey          property is key or not
     * @param boolean      $isBag          property is bag or not
     * @param boolean      $isETagProperty property is etag or not
     * 
     * @return void
     */
    private function _addPrimitivePropertyInternal($resourceType, $name, $typeCode, $isKey = false, $isBag = false, $isETagProperty = false)
    {
        try 
        {
            $resourceType->getInstanceType()->getProperty($name);
        }
        catch (ReflectionException $ex)
        {
            throw new InvalidOperationException(
                'Can\'t add a property which does not exist on the instance type.'
            );
        }
        
        $primitiveResourceType = null;
        try 
        {
            $primitiveResourceType = ResourceType::getPrimitiveResourceType($typeCode);
        }
        catch (InvalidArgumentException $ex)
        {
            throw $ex;
        }

        if ($isETagProperty && $isBag) {
            throw new InvalidOperationException('Only primitve property can be etag property, bag property cannot be etag property');
        }

        $kind = $isKey ?  ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY : ResourcePropertyKind::PRIMITIVE;
        if ($isBag) {    	   
            $kind = $kind | ResourcePropertyKind::BAG;
        }

        if ($isETagProperty) {
            $kind = $kind | ResourcePropertyKind::ETAG;
        }

        $resourceProperty = new ResourceProperty($name, null, $kind, $primitiveResourceType);
        $resourceType->addProperty($resourceProperty);
    }
    
    /**
     * To add a navigation property (resource set or resource reference)
     * to a resource type
     * 
     * @param ResourceType         $resourceType         The resource type to add 
     *                                                   the resource reference 
     *                                                   or resource 
     *                                                   reference set property to
     * @param string               $name                 The name of the 
     *                                                   property to add
     * @param ResourceSet          $targetResourceSet    The resource set the 
     *                                                   resource reference
     *                                                   or reference 
     *                                                   set property 
     *                                                   ponits to
     * @param ResourcePropertyKind $resourcePropertyKind The property kind
     * 
     * @return void
     */
    private function _addReferencePropertyInternal(ResourceType $resourceType, $name, 
        ResourceSet $targetResourceSet,
        $resourcePropertyKind
    ) {
        try {
            $resourceType->getInstanceType()->getProperty($name);
                  
        } catch (ReflectionException $exception) {
            throw new InvalidOperationException(
                'Can\'t add a property which does not exist on the instance type.'
            );
        }
          
        if (!($resourcePropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE 
            || $resourcePropertyKind == ResourcePropertyKind::RESOURCE_REFERENCE)
        ) {
            throw new InvalidOperationException(
                'Property kind should be ResourceSetReference or ResourceReference'
            );
        }

        $targetResourceType = $targetResourceSet->getResourceType();
        $resourceProperty = new ResourceProperty($name, null, $resourcePropertyKind, $targetResourceType);
        $resourceType->addProperty($resourceProperty);
        
        //Create instance of AssociationSet for this relationship        
        $sourceResourceSet = $resourceType->getCustomState();
        if (is_null($sourceResourceSet)) {
            throw new InvalidOperationException('Failed to retrieve the custom state from ' . $resourceType->getName());
        }

        //Customer_Orders_Orders, Order_Customer_Customers 
        //(source type::name _ source property::name _ target set::name)
        $assoicationSetKey = $resourceType->getName() . '_' .  $name . '_' . $targetResourceSet->getName();
        $associationSet = new ResourceAssociationSet(
            $assoicationSetKey, 
            new ResourceAssociationSetEnd($sourceResourceSet, $resourceType, $resourceProperty),
            new ResourceAssociationSetEnd($targetResourceSet, $targetResourceSet->getResourceType(), null)
        );
        $this->associationSets[$assoicationSetKey] = $associationSet;
    }
    
}
