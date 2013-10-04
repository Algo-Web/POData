<?php

namespace POData\Providers\Metadata;

use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Common\NotImplementedException;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\ResourceType;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\IMetadataProvider;


/**
 * Class SimpleMetadataProvider
 * @package POData\Providers\Metadata
 */
class SimpleMetadataProvider implements IMetadataProvider
{
    protected $resourceSets = array();
    protected $resourceTypes = array();
    protected $associationSets = array();
    protected $containerName;
    protected $namespaceName;
    public $mappedDetails = null;
    
    //Begin Implementation of IMetadataProvider
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
     * @return ResourceSet[]
     */
    public function getResourceSets()
    {
        return array_values($this->resourceSets);
    }
    
    /**
     * get all resource types in the data source.
     * 
     * @return ResourceType[]
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
     * @return ResourceSet|null resource set with the given name if found else NULL
     *
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
     * @return ResourceType|null resource type with the given resource set name if found else NULL
     *
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
     * passed in as a parameter
     * 
     * @param ResourceType $resourceType Resource to get derived resource types from
     *
     * 
     * @return ResourceType[]
     */
    public function getDerivedTypes(ResourceType $resourceType)
    {
        return array();
    }
    
    /**
     *
     * @param ResourceType $resourceType Resource to check for derived resource types.
     *
     * @return boolean true if $resourceType represents an Entity Type which has derived Entity Types, else false.
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
            throw new InvalidOperationException('Failed to retrieve the custom state from ' . $targetResourceProperty->getResourceType()->getName());
        }

        //Customer_Orders_Orders, Order_Customer_Customers
        $key = $sourceResourceType->getName() . '_' . $targetResourceProperty->getName() . '_' . $targetResourceSet->getName();
        if (array_key_exists($key, $this->associationSets)) {
            return $this->associationSets[$key];
        }

        return null;
    }

    //End Implementation of IMetadataProvider
    
    /** 
     *
     * @param string $containerName container name for the datasource.
     * @param string $namespaceName namespace for the datasource.
     * 
     */
    public function __construct($containerName, $namespaceName)
    {
        $this->containerName = $containerName;
        $this->namespaceName = $namespaceName;
    }
    
    /**
     * Add an entity type
     * 
     * @param \ReflectionClass $refClass  reflection class of the entity
     * @param string $name name of the entity
     * @param string $namespace namespace of the data source
     * 
     * @return ResourceType
     *
     * @throws InvalidOperationException when the name is already in use
     */
    public function addEntityType(\ReflectionClass $refClass, $name, $namespace = null)
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
     * @param \ReflectionClass $refClass         reflection class of the complex entity type
     * @param string          $name             name of the entity
     * @param string          $namespace        namespace of the data source.
     * @param ResourceType    $baseResourceType base resource type
     * 
     * @return ResourceType
     *
     * @throws InvalidOperationException when the name is already in use
     */
    public function addComplexType(\ReflectionClass $refClass, $name, $namespace = null, $baseResourceType = null)
    {
        if (array_key_exists($name, $this->resourceTypes)) {
            throw new InvalidOperationException('Type with same name already added');
        }
        
        $complexType = new ResourceType($refClass, ResourceTypeKind::COMPLEX, $name, $namespace, $baseResourceType);    	
        $this->resourceTypes[$name] = $complexType;
        return $complexType;
    }
    
    /**
     * @param string      $name         name of the resource set
     * @param ResourceType $resourceType resource type
     * 
     * @throws InvalidOperationException
     * 
     * @return ResourceSet
     */
    public function addResourceSet($name, ResourceType $resourceType)
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
        catch (\ReflectionException $ex)
        {
            throw new InvalidOperationException('Can\'t add a property which does not exist on the instance type.');
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
        catch (\ReflectionException $ex)
        {
            throw new InvalidOperationException(
                'Can\'t add a property which does not exist on the instance type.'
            );
        }
        
        $primitiveResourceType = ResourceType::getPrimitiveResourceType($typeCode);


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
    private function _addReferencePropertyInternal(
	    ResourceType $resourceType,
	    $name,
        ResourceSet $targetResourceSet,
        $resourcePropertyKind
    ) {
        try {
            $resourceType->getInstanceType()->getProperty($name);
                  
        } catch (\ReflectionException $exception) {
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
        $setKey = $resourceType->getName() . '_' .  $name . '_' . $targetResourceSet->getName();
        $set = new ResourceAssociationSet(
            $setKey,
            new ResourceAssociationSetEnd($sourceResourceSet, $resourceType, $resourceProperty),
            new ResourceAssociationSetEnd($targetResourceSet, $targetResourceSet->getResourceType(), null)
        );
        $this->associationSets[$setKey] = $set;
    }
    
}
