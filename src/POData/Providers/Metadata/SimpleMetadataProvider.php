<?php

namespace POData\Providers\Metadata;

use AlgoWeb\ODataMetadata\MetadataManager;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TComplexTypeType;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TEntityTypeType;
use Illuminate\Support\Str;
use POData\Common\InvalidOperationException;
use POData\Common\NotImplementedException;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\TypeCode;

/**
 * Class SimpleMetadataProvider.
 */
class SimpleMetadataProvider implements IMetadataProvider
{
    public $OdataEntityMap = [];
    protected $resourceSets = [];
    protected $resourceTypes = [];
    protected $associationSets = [];
    protected $containerName;
    protected $namespaceName;
    private $metadataManager;
    private $typeSetMapping = [];

    /**
     * @param string $containerName container name for the datasource
     * @param string $namespaceName namespace for the datasource
     */
    public function __construct($containerName, $namespaceName)
    {
        $this->containerName = $containerName;
        $this->namespaceName = $namespaceName;
        $this->metadataManager = new MetadataManager($namespaceName, $containerName);
    }

    //Begin Implementation of IMetadataProvider

    public function getXML()
    {
        return $this->metadataManager->getEdmxXML();
    }

    /**
     * get the Container name for the data source.
     *
     * @return string container name
     */
    public function getContainerName()
    {
        return $this->containerName;
    }

    /**
     * get Namespace name for the data source.
     *
     * @return string namespace
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
    public function getResourceSets($params = null)
    {
        $parameters = [];
        if (is_string($params)) {
            $parameters[] = $params;
        } elseif (isset($params) && !is_array($params)) {
            throw new \ErrorException('Input parameter must be absent, null, string or array');
        } else {
            $parameters = $params;
        }
        if (!is_array($parameters) || 0 == count($parameters)) {
            return array_values($this->resourceSets);
        }
        assert(is_array($parameters));
        $return = [];
        $counter = 0;
        foreach ($this->resourceSets as $resource) {
            $resName = $resource->getName();
            if (in_array($resName, $parameters)) {
                $return[] = $resource;
                $counter++;
            }
        }
        assert($counter == count($return));

        return $return;
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
     */
    public function resolveResourceSet($name)
    {
        if (array_key_exists($name, $this->resourceSets)) {
            return $this->resourceSets[$name];
        }
        return null;
    }

    /**
     * get a resource type based on the resource type name.
     *
     * @param string $name Name of the resource type
     *
     * @return ResourceType|null resource type with the given resource type name if found else NULL
     */
    public function resolveResourceType($name)
    {
        if (array_key_exists($name, $this->resourceTypes)) {
            return $this->resourceTypes[$name];
        }
        return null;
    }

    /**
     * get a resource set based on the specified resource association set name.
     *
     * @param string $name Name of the resource assocation set
     *
     * @return ResourceAssociationSet|null resource association set with the given name if found else NULL
     */
    public function resolveAssociationSet($name)
    {
        if (array_key_exists($name, $this->associationSets)) {
            return $this->associationSets[$name];
        }
        return null;
    }

    /*
     * Get number of association sets hooked up
     */
    public function getAssociationCount()
    {
        return count($this->associationSets);
    }

    /**
     * The method must return a collection of all the types derived from
     * $resourceType The collection returned should NOT include the type
     * passed in as a parameter.
     *
     * @param ResourceEntityType $resourceType Resource to get derived resource types from
     *
     * @return ResourceType[]
     */
    public function getDerivedTypes(ResourceEntityType $resourceType)
    {
        return [];
    }

    /**
     * @param ResourceType $resourceType Resource to check for derived resource types
     *
     * @return bool true if $resourceType represents an Entity Type which has derived Entity Types, else false
     */
    public function hasDerivedTypes(ResourceEntityType $resourceType)
    {
        return false;
    }

    //End Implementation of IMetadataProvider

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
     * @return ResourceAssociationSet|null
     */
    public function getResourceAssociationSet(
        ResourceSet $sourceResourceSet,
        ResourceEntityType $sourceResourceType,
        ResourceProperty $targetResourceProperty
    ) {
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
            throw new InvalidOperationException(
                'Failed to retrieve the custom state from ' . $targetResourceProperty->getResourceType()->getName()
            );
        }

        //Customer_Orders_Orders, Order_Customer_Customers
        $key = ResourceAssociationSet::keyName(
            $sourceResourceType,
            $targetResourceProperty->getName(),
            $targetResourceSet
        );

        $associationSet = array_key_exists($key, $this->associationSets) ? $this->associationSets[$key] : null;
        assert(
            null == $associationSet || $associationSet instanceof ResourceAssociationSet,
            "Retrieved resource assocation must be either null or an instance of ResourceAssociationSet"
        );
        return $associationSet;
    }

    /**
     * Add an entity type.
     *
     * @param \ReflectionClass $refClass reflection class of the entity
     * @param string $name name of the entity
     * @return ResourceType when the name is already in use
     *
     * @throws InvalidOperationException when the name is already in use
     * @internal param string $namespace namespace of the data source
     *
     */
    public function addEntityType(\ReflectionClass $refClass, $name)
    {
        return $this->createResourceType($refClass, $name, ResourceTypeKind::ENTITY);
    }

    /**
     * @param \ReflectionClass $refClass
     * @param string $name
     * @param $typeKind
     * @return ResourceType
     * @throws InvalidOperationException
     * @internal param null|string $namespace
     * @internal param null|ResourceType $baseResourceType
     *
     */
    private function createResourceType(
        \ReflectionClass $refClass,
        $name,
        $typeKind
    ) {
        if (array_key_exists($name, $this->resourceTypes)) {
            throw new InvalidOperationException('Type with same name already added');
        }

        $type = null;
        if ($typeKind == ResourceTypeKind::ENTITY) {
            list($oet, $entitySet) = $this->metadataManager->addEntityType($name);
            assert($oet instanceof TEntityTypeType, "Entity type ".$name. " not successfully added");
            $type = new ResourceEntityType($refClass, $oet, $this);
            $typeName = $type->getFullName();
            $returnName = Str::plural($typeName);
            $this->OdataEntityMap[$typeName] = $oet;
            $this->typeSetMapping[$name] = $entitySet;
            $this->typeSetMapping[$typeName] = $entitySet;
            $this->typeSetMapping[$returnName] = $entitySet;
        } elseif ($typeKind == ResourceTypeKind::COMPLEX) {
            $complex = new TComplexTypeType();
            $complex->setName($name);
            $type = new ResourceComplexType($refClass, $complex);
        }
        assert(null != $type, "Type variable must not be null");

        $this->resourceTypes[$name] = $type;
        ksort($this->resourceTypes);

        return $type;
    }

    /**
     * Add a complex type.
     *
     * @param \ReflectionClass $refClass reflection class of the complex entity type
     * @param string $name name of the entity
     * @return ResourceType when the name is already in use
     *
     * @throws InvalidOperationException when the name is already in use
     * @internal param string $namespace namespace of the data source
     * @internal param ResourceType $baseResourceType base resource type
     *
     */
    public function addComplexType(\ReflectionClass $refClass, $name)
    {
        return $this->createResourceType($refClass, $name, ResourceTypeKind::COMPLEX);
    }

    /**
     * @param string                $name           name of the resource set (now taken from resource type)
     * @param ResourceEntityType    $resourceType   resource type
     *
     * @throws InvalidOperationException
     *
     * @return ResourceSet
     */
    public function addResourceSet($name, ResourceEntityType $resourceType)
    {
        $returnName = Str::plural($resourceType->getFullName());
        if (array_key_exists($returnName, $this->resourceSets)) {
            throw new InvalidOperationException('Resource Set already added');
        }

        $this->resourceSets[$returnName] = new ResourceSet($returnName, $resourceType);

        //No support for multiple ResourceSet with same EntityType
        //So keeping reference to the 'ResourceSet' with the entity type
        $resourceType->setCustomState($this->resourceSets[$returnName]);
        ksort($this->resourceSets);

        return $this->resourceSets[$returnName];
    }

    /**
     * To add a Key-primitive property to a resource (Complex/Entity).
     *
     * @param ResourceType $resourceType resource type to which key property
     *                                   is to be added
     * @param string       $name         name of the key property
     * @param TypeCode     $typeCode     type of the key property
     */
    public function addKeyProperty($resourceType, $name, $typeCode)
    {
        $this->_addPrimitivePropertyInternal($resourceType, $name, $typeCode, true);
    }

    /**
     * To add a Key/NonKey-primitive property to a resource (complex/entity).
     *
     * @param ResourceType $resourceType   Resource type
     * @param string       $name           name of the property
     * @param TypeCode     $typeCode       type of property
     * @param bool         $isKey          property is key or not
     * @param bool         $isBag          property is bag or not
     * @param bool         $isETagProperty property is etag or not
     */
    private function _addPrimitivePropertyInternal(
        $resourceType,
        $name,
        $typeCode,
        $isKey = false,
        $isBag = false,
        $isETagProperty = false,
        $defaultValue = null,
        $nullable = false
    ) {
        $this->checkInstanceProperty($name, $resourceType);

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($name) == strtolower($resourceType->getName())) {
            throw new InvalidOperationException(
                'Property name must be different from resource name.'
            );
        }

        $primitiveResourceType = ResourceType::getPrimitiveResourceType($typeCode);

        if ($isETagProperty && $isBag) {
            throw new InvalidOperationException(
                'Only primitve property can be etag property, bag property cannot be etag property.'
            );
        }

        $kind = $isKey ? ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY : ResourcePropertyKind::PRIMITIVE;
        if ($isBag) {
            $kind = $kind | ResourcePropertyKind::BAG;
        }

        if ($isETagProperty) {
            $kind = $kind | ResourcePropertyKind::ETAG;
        }

        $resourceProperty = new ResourceProperty($name, null, $kind, $primitiveResourceType);
        $resourceType->addProperty($resourceProperty);
        if (array_key_exists($resourceType->getFullName(), $this->OdataEntityMap)) {
            $this->metadataManager->addPropertyToEntityType(
                $this->OdataEntityMap[$resourceType->getFullName()],
                $name,
                $primitiveResourceType->getFullName(),
                $defaultValue,
                $nullable,
                $isKey
            );
        }
    }

    /**
     * @param string $name
     * @param ResourceType $resourceType
     *
     * @throws InvalidOperationException
     */
    private function checkInstanceProperty($name, ResourceType $resourceType)
    {
        $instance = $resourceType->getInstanceType();
        $hasMagicGetter = $instance instanceof IType || $instance->hasMethod('__get');

        if (!$hasMagicGetter) {
            try {
                if ($instance instanceof \ReflectionClass) {
                    $instance->getProperty($name);
                }
            } catch (\ReflectionException $exception) {
                throw new InvalidOperationException(
                    'Can\'t add a property which does not exist on the instance type.'
                );
            }
        }
    }

    /**
     * To add a NonKey-primitive property (Complex/Entity).
     *
     * @param ResourceType $resourceType resource type to which key property
     *                                   is to be added
     * @param string $name name of the key property
     * @param TypeCode $typeCode type of the key property
     * @param bool $isBag property is bag or not
     */
    public function addPrimitiveProperty(
        $resourceType,
        $name,
        $typeCode,
        $isBag = false,
        $defaultValue = null,
        $nullable = false
    ) {
        $this->_addPrimitivePropertyInternal($resourceType, $name, $typeCode, false, $isBag, $defaultValue, $nullable);
    }

    /**
     * To add a non-key etag property.
     *
     * @param ResourceType $resourceType resource type to which key property
     *                                   is to be added
     * @param string $name name of the property
     * @param TypeCode $typeCode type of the etag property
     */
    public function addETagProperty($resourceType, $name, $typeCode, $defaultValue = null, $nullable = false)
    {
        $this->_addPrimitivePropertyInternal(
            $resourceType,
            $name,
            $typeCode,
            false,
            false,
            true,
            $defaultValue,
            $nullable
        );
    }

    /**
     * To add a resource reference property.
     *
     * @param ResourceEntityType    $resourceType   The resource type to add the resource
     *                                              reference property to
     * @param string                $name           The name of the property to add
     * @param ResourceSet           $targetResourceSet The resource set the resource reference
     *                                               property points to
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
     * To add a 1:N resource reference property.
     *
     * @param ResourceType $sourceResourceType  The resource type to add the resource
     *                                          reference property from
     * @param ResourceType $targetResourceType  The resource type to add the resource
     *                                          reference property to
     * @param string $sourceProperty            The name of the property to add, on source type
     * @param string $targetProperty            The name of the property to add, on target type
     */
    public function addResourceReferencePropertyBidirectional(
        ResourceType $sourceResourceType,
        ResourceType $targetResourceType,
        $sourceProperty,
        $targetProperty
    ) {
        $this->_addReferencePropertyInternalBidirectional(
            $sourceResourceType,
            $targetResourceType,
            $sourceProperty,
            $targetProperty,
            ResourcePropertyKind::RESOURCE_REFERENCE,
            ResourcePropertyKind::RESOURCESET_REFERENCE
        );
    }

    /**
     * To add a navigation property (resource set or resource reference)
     * to a resource type.
     *
     * @param ResourceEntityType   $sourceResourceType   The resource type to add
     *                                                   the resource reference
     *                                                   or resource
     *                                                   reference set property to
     * @param string               $name                 The name of the
     *                                                   property to add
     * @param ResourceSet          $targetResourceSet    The resource set the
     *                                                   resource reference
     *                                                   or reference
     *                                                   set property
     *                                                   points to
     * @param ResourcePropertyKind $resourcePropertyKind The property kind
     */
    private function _addReferencePropertyInternal(
        ResourceEntityType $sourceResourceType,
        $name,
        ResourceSet $targetResourceSet,
        $resourcePropertyKind
    ) {
        $this->checkInstanceProperty($name, $sourceResourceType);

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($name) == strtolower($sourceResourceType->getName())) {
            throw new InvalidOperationException(
                'Property name must be different from resource name.'
            );
        }

        $targetResourceType = $targetResourceSet->getResourceType();
        $sourceResourceProperty = new ResourceProperty($name, null, $resourcePropertyKind, $targetResourceType);
        $sourceResourceType->addProperty($sourceResourceProperty);

        //Create instance of AssociationSet for this relationship
        $sourceResourceSet = $sourceResourceType->getCustomState();
        if (!$sourceResourceSet instanceof ResourceSet) {
            throw new InvalidOperationException(
                'Failed to retrieve the custom state from '
                . $sourceResourceType->getName()
            );
        }

        //Customer_Orders_Orders, Order_Customer_Customers
        //(source type::name _ source property::name _ target set::name)
        $setKey = ResourceAssociationSet::keyName($sourceResourceType, $name, $targetResourceSet);
        //$setKey = $sourceResourceType->getName() . '_' . $name . '_' . $targetResourceType->getName();
        $set = new ResourceAssociationSet(
            $setKey,
            new ResourceAssociationSetEnd($sourceResourceSet, $sourceResourceType, $sourceResourceProperty),
            new ResourceAssociationSetEnd($targetResourceSet, $targetResourceType, null)
        );
        $mult = $resourcePropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE ? "*" : "0..1";
        $this->metadataManager->addNavigationPropertyToEntityType(
            $this->OdataEntityMap[$sourceResourceType->getFullName()],
            $mult,
            $name,
            $this->OdataEntityMap[$targetResourceType->getFullName()],
            $mult
        );
        $this->associationSets[$setKey] = $set;
    }

    /**
     * To add a navigation property (resource set or resource reference)
     * to a resource type.
     *
     * @param ResourceEntityType   $sourceResourceType   The source resource type to add
     *                                                   the resource reference
     *                                                   or resource reference set property to
     * @param ResourceEntityType   $targetResourceType   The target resource type to add
     *                                                   the resource reference
     *                                                   or resource reference set property to
     * @param string               $sourceProperty       The name of the
     *                                                   property to add to source type
     * @param string               $targetProperty       The name of the
     *                                                   property to add to target type
     * @param ResourcePropertyKind $sourcePropertyKind   The property kind on the source type
     * @param ResourcePropertyKind $targetPropertyKind   The property kind on the target type
     */
    private function _addReferencePropertyInternalBidirectional(
        ResourceEntityType $sourceResourceType,
        ResourceEntityType $targetResourceType,
        $sourceProperty,
        $targetProperty,
        $sourcePropertyKind,
        $targetPropertyKind
    ) {
        if (!is_string($sourceProperty) || !is_string($targetProperty)) {
            throw new InvalidOperationException("Source and target properties must both be strings");
        }

        $this->checkInstanceProperty($sourceProperty, $sourceResourceType);
        $this->checkInstanceProperty($targetProperty, $targetResourceType);

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($sourceProperty) == strtolower($sourceResourceType->getName())) {
            throw new InvalidOperationException(
                'Source property name must be different from source resource name.'
            );
        }
        if (strtolower($targetProperty) == strtolower($targetResourceType->getName())) {
            throw new InvalidOperationException(
                'Target property name must be different from target resource name.'
            );
        }

        //Create instance of AssociationSet for this relationship
        $sourceResourceSet = $sourceResourceType->getCustomState();
        if (!$sourceResourceSet instanceof ResourceSet) {
            throw new InvalidOperationException(
                'Failed to retrieve the custom state from '
                . $sourceResourceType->getName()
            );
        }
        $targetResourceSet = $targetResourceType->getCustomState();
        if (!$targetResourceSet instanceof ResourceSet) {
            throw new InvalidOperationException(
                'Failed to retrieve the custom state from '
                . $targetResourceType->getName()
            );
        }

        //Customer_Orders_Orders, Order_Customer_Customers
        $fwdSetKey = ResourceAssociationSet::keyName($sourceResourceType, $sourceProperty, $targetResourceSet);
        $revSetKey = ResourceAssociationSet::keyName($targetResourceType, $targetProperty, $sourceResourceSet);
        if (isset($this->associationSets[$fwdSetKey]) && $this->associationSets[$revSetKey]) {
            return;
        }

        $sourceResourceProperty = new ResourceProperty($sourceProperty, null, $sourcePropertyKind, $targetResourceType);
        $sourceResourceType->addProperty($sourceResourceProperty, false);
        $targetResourceProperty = new ResourceProperty($targetProperty, null, $targetPropertyKind, $sourceResourceType);
        $targetResourceType->addProperty($targetResourceProperty, false);


        $fwdSet = new ResourceAssociationSet(
            $fwdSetKey,
            new ResourceAssociationSetEnd($sourceResourceSet, $sourceResourceType, $sourceResourceProperty),
            new ResourceAssociationSetEnd($targetResourceSet, $targetResourceType, $targetResourceProperty)
        );
        $revSet = new ResourceAssociationSet(
            $revSetKey,
            new ResourceAssociationSetEnd($targetResourceSet, $targetResourceType, $targetResourceProperty),
            new ResourceAssociationSetEnd($sourceResourceSet, $sourceResourceType, $sourceResourceProperty)
        );
        $sourceName = $sourceResourceType->getFullName();
        $targetName = $targetResourceType->getFullName();
        $sourceMult = $sourcePropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE ? '*' : '0..1';
        $targetMult = $targetPropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE ? '*' : '0..1';
        $this->metadataManager->addNavigationPropertyToEntityType(
            $this->OdataEntityMap[$sourceName],
            $sourceMult,
            $sourceProperty,
            $this->OdataEntityMap[$targetName],
            $targetMult,
            $targetProperty
        );
        $this->associationSets[$fwdSetKey] = $fwdSet;
        $this->associationSets[$revSetKey] = $revSet;
    }

    /**
     * To add a resource set reference property.
     *
     * @param ResourceEntityType    $resourceType   The resource type to add the
     *                                              resource reference set property to
     * @param string                $name           The name of the property to add
     * @param ResourceSet           $targetResourceSet The resource set the resource
     *                                              reference set property points to
     */
    public function addResourceSetReferenceProperty(ResourceEntityType $resourceType, $name, $targetResourceSet)
    {
        $this->_addReferencePropertyInternal(
            $resourceType,
            $name,
            $targetResourceSet,
            ResourcePropertyKind::RESOURCESET_REFERENCE
        );
    }

    /**
     * To add a M:N resource reference property.
     *
     * @param ResourceEntityType    $sourceResourceType     The resource type to add the resource
     *                                                      reference property from
     * @param ResourceEntityType    $targetResourceType     The resource type to add the resource
     *                                                      reference property to
     * @param string                $sourceProperty         The name of the property to add, on source type
     * @param string                $targetProperty         The name of the property to add, on target type
     */
    public function addResourceSetReferencePropertyBidirectional(
        ResourceEntityType $sourceResourceType,
        ResourceEntityType $targetResourceType,
        $sourceProperty,
        $targetProperty
    ) {
        $this->_addReferencePropertyInternalBidirectional(
            $sourceResourceType,
            $targetResourceType,
            $sourceProperty,
            $targetProperty,
            ResourcePropertyKind::RESOURCESET_REFERENCE,
            ResourcePropertyKind::RESOURCESET_REFERENCE
        );
    }

    /**
     * To add a 1-1 resource reference.
     *
     * @param ResourceEntityType    $sourceResourceType     The resource type to add the resource
     *                                                      reference property from
     * @param ResourceEntityType    $targetResourceType     The resource type to add the resource
     *                                                      reference property to
     * @param string                $sourceProperty         The name of the property to add, on source type
     * @param string                $targetProperty         The name of the property to add, on target type
     */
    public function addResourceReferenceSinglePropertyBidirectional(
        ResourceEntityType $sourceResourceType,
        ResourceEntityType $targetResourceType,
        $sourceProperty,
        $targetProperty
    ) {
        $this->_addReferencePropertyInternalBidirectional(
            $sourceResourceType,
            $targetResourceType,
            $sourceProperty,
            $targetProperty,
            ResourcePropertyKind::RESOURCE_REFERENCE,
            ResourcePropertyKind::RESOURCE_REFERENCE
        );
    }

    /**
     * To add a complex property to entity or complex type.
     *
     * @param ResourceType          $targetResourceType     The resource type to which the complex property needs to add
     * @param string                $name                   name of the complex property
     * @param ResourceComplexType   $complexResourceType    complex resource type
     * @param bool                  $isBag                  complex type is bag or not
     *
     * @return ResourceProperty
     */
    public function addComplexProperty(
        ResourceType $targetResourceType,
        $name,
        ResourceComplexType $complexResourceType,
        $isBag = false
    ) {
        if ($targetResourceType->getResourceTypeKind() != ResourceTypeKind::ENTITY
            && $targetResourceType->getResourceTypeKind() != ResourceTypeKind::COMPLEX
        ) {
            throw new InvalidOperationException('Complex property can be added to an entity or another complex type');
        }

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($name) == strtolower($targetResourceType->getName())) {
            throw new InvalidOperationException(
                'Property name must be different from resource name.'
            );
        }

        $this->checkInstanceProperty($name, $targetResourceType);

        $kind = ResourcePropertyKind::COMPLEX_TYPE;
        if ($isBag) {
            $kind = $kind | ResourcePropertyKind::BAG;
        }

        $resourceProperty = new ResourceProperty($name, null, $kind, $complexResourceType);
        $targetResourceType->addProperty($resourceProperty);

        return $resourceProperty;
    }
}
