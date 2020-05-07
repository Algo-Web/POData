<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

use AlgoWeb\ODataMetadata\MetadataManager;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TComplexTypeType;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TEntityTypeType;
use ErrorException;
use Exception;
use Illuminate\Support\Str;
use InvalidArgumentException;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use POData\Providers\Metadata\Type\IType;
use ReflectionClass;
use ReflectionException;
use stdClass;

/**
 * Class SimpleMetadataProvider.
 */
class SimpleMetadataProvider implements IMetadataProvider
{
    public $oDataEntityMap     = [];
    protected $resourceSets    = [];
    protected $resourceTypes   = [];
    protected $associationSets = [];
    protected $containerName;
    protected $namespaceName;
    protected $singletons = [];
    private $metadataManager;
    private $typeSetMapping = [];
    private $baseTypes      = [];

    /**
     * @param string $containerName container name for the data source
     * @param string $namespaceName namespace for the data source
     */
    public function __construct($containerName, $namespaceName)
    {
        $this->containerName   = $containerName;
        $this->namespaceName   = $namespaceName;
        $this->metadataManager = new MetadataManager($namespaceName, $containerName);
    }

    //Begin Implementation of IMetadataProvider

    /**
     * @throws Exception
     * @return string|null
     */
    public function getXML()
    {
        return $this->getMetadataManager()->getEdmxXML();
    }

    /**
     * @return MetadataManager
     */
    public function getMetadataManager()
    {
        return $this->metadataManager;
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
     * @param null|mixed $params
     *
     * @throws ErrorException
     * @return ResourceSet[]
     */
    public function getResourceSets($params = null)
    {
        $parameters = [];
        if (is_string($params)) {
            $parameters[] = $params;
        } elseif (isset($params) && !is_array($params)) {
            throw new ErrorException('Input parameter must be absent, null, string or array');
        } else {
            $parameters = $params;
        }
        if (!is_array($parameters) || 0 == count($parameters)) {
            return array_values($this->resourceSets);
        }
        assert(is_array($parameters));
        $return  = [];
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
     * get a singleton based on the specified singleton name.
     *
     * @param string $name Name of the resource set
     *
     * @return ResourceFunctionType|null singleton with the given name if found else NULL
     */
    public function resolveSingleton($name)
    {
        if (array_key_exists($name, $this->singletons)) {
            return $this->singletons[$name];
        }
        return null;
    }

    /**
     * get a resource set based on the specified resource association set name.
     *
     * @param string $name Name of the resource association set
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

    /**
     * Get number of association sets hooked up.
     *
     * @return int
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
        $ret = [];
        foreach ($this->resourceTypes as $rType) {
            if ($rType->getBaseType() == $resourceType) {
                $ret[] = $rType;
            }
        }
        return $ret;
    }

    /**
     * @param ResourceEntityType $resourceType Resource to check for derived resource types
     *
     * @return bool true if $resourceType represents an Entity Type which has derived Entity Types, else false
     */
    public function hasDerivedTypes(ResourceEntityType $resourceType)
    {
        if (in_array($resourceType, $this->baseTypes)) {
            return true;
        }
        return false;
    }

    //End Implementation of IMetadataProvider

    /**
     * Gets the ResourceAssociationSet instance for the given source
     * association end.
     *
     * @param ResourceSet        $sourceResourceSet      Resource set of the source association end
     * @param ResourceEntityType $sourceResourceType     Resource type of the source association end
     * @param ResourceProperty   $targetResourceProperty Resource property of the target association end
     *
     * @throws InvalidOperationException
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
        //We have created ResourceAssociationSet while adding
        //ResourceSetReference or ResourceReference
        //and kept in $this->associationSets
        //$metadata->addResourceSetReferenceProperty(
        //             $customersEntityType,
        //             'Orders',
        //             $ordersResourceSet
        //             );

        $targetResourceSet = $targetResourceProperty->getResourceType()->getCustomState();
        if (null === $targetResourceSet) {
            throw new InvalidOperationException(
                'Failed to retrieve the custom state from ' . $targetResourceProperty->getResourceType()->getName()
            );
        }
        assert($targetResourceSet instanceof ResourceSet);

        //Customer_Orders_Orders, Order_Customer_Customers
        $key = ResourceAssociationSet::keyName(
            $sourceResourceType,
            $targetResourceProperty->getName(),
            $targetResourceSet
        );

        $associationSet = array_key_exists($key, $this->associationSets) ? $this->associationSets[$key] : null;
        assert(
            null == $associationSet || $associationSet instanceof ResourceAssociationSet,
            'Retrieved resource association must be either null or an instance of ResourceAssociationSet'
        );
        return $associationSet;
    }

    /**
     * Add an entity type.
     *
     * @param  ReflectionClass           $refClass   reflection class of the entity
     * @param  string                    $name       name of the entity
     * @param  null|string               $pluralName Optional custom resource set name
     * @param  mixed                     $isAbstract
     * @param  null|mixed                $baseType
     * @throws InvalidOperationException when the name is already in use
     * @return ResourceEntityType        when the name is already in use
     * @internal param string $namespace namespace of the data source
     */
    public function addEntityType(
        ReflectionClass $refClass,
        $name,
        $pluralName = null,
        $isAbstract = false,
        $baseType = null
    ) {
        $result = $this->createResourceType(
            $refClass,
            $name,
            ResourceTypeKind::ENTITY(),
            $isAbstract,
            $baseType,
            $pluralName
        );
        assert($result instanceof ResourceEntityType);
        return $result;
    }

    /**
     * @param ReflectionClass $refClass
     * @param string          $name
     * @param $typeKind
     * @param  mixed                                  $isAbstract
     * @param  null|mixed                             $baseType
     * @param  null|mixed                             $pluralName
     * @throws InvalidOperationException
     * @return ResourceEntityType|ResourceComplexType
     * @internal param null|string $namespace
     * @internal param null|ResourceType $baseResourceType
     */
    private function createResourceType(
        ReflectionClass $refClass,
        $name,
        $typeKind,
        $isAbstract = false,
        $baseType = null,
        $pluralName = null
    ) {
        if (array_key_exists($name, $this->resourceTypes)) {
            throw new InvalidOperationException('Type with same name already added');
        }
        if (null !== $baseType) {
            $baseTEntityType   = $this->oDataEntityMap[$baseType->getFullName()];
            $this->baseTypes[] = $baseType;
        } else {
            $baseTEntityType = null;
        }

        $type = null;
        if ($typeKind == ResourceTypeKind::ENTITY()) {
            list($oet, $entitySet) = $this->getMetadataManager()
                ->addEntityType($name, $baseTEntityType, $isAbstract, 'Public', null, null, $pluralName);
            assert($oet instanceof TEntityTypeType, 'Entity type ' . $name . ' not successfully added');
            $type                              = new ResourceEntityType($refClass, $oet, $this);
            $typeName                          = $type->getFullName();
            $returnName                        = MetadataManager::getResourceSetNameFromResourceType($typeName);
            $this->oDataEntityMap[$typeName]   = $oet;
            $this->typeSetMapping[$name]       = $entitySet;
            $this->typeSetMapping[$typeName]   = $entitySet;
            $this->typeSetMapping[$returnName] = $entitySet;
        } elseif ($typeKind == ResourceTypeKind::COMPLEX()) {
            $complex = new TComplexTypeType();
            $complex->setName($name);
            $type = new ResourceComplexType($refClass, $complex);
        }
        assert(null != $type, 'Type variable must not be null');

        $this->resourceTypes[$name] = $type;
        ksort($this->resourceTypes);

        return $type;
    }

    /**
     * Add a complex type.
     *
     * @param  ReflectionClass           $refClass reflection class of the complex entity type
     * @param  string                    $name     name of the entity
     * @throws InvalidOperationException when the name is already in use
     * @return ResourceComplexType
     *
     * @internal param string $namespace namespace of the data source
     * @internal param ResourceType $baseResourceType base resource type
     */
    public function addComplexType(ReflectionClass $refClass, $name)
    {
        $result = $this->createResourceType($refClass, $name, ResourceTypeKind::COMPLEX());
        assert($result instanceof ResourceComplexType);
        return $result;
    }

    /**
     * @param string             $name         name of the resource set (now taken from resource type)
     * @param ResourceEntityType $resourceType resource type
     *
     * @throws InvalidOperationException
     * @return ResourceSet
     */
    public function addResourceSet($name, ResourceEntityType $resourceType)
    {
        $typeName   = $resourceType->getFullName();
        $pluralName = Str::plural($typeName);
        if (null == $name) {
            $returnName = $pluralName;
        } else {
            // handle case where $name is a fully-qualified PHP class name
            $nameBits = explode('\\', $name);
            $numBits  = count($nameBits);

            if ($typeName == $nameBits[$numBits - 1]) {
                $returnName = $pluralName;
            } else {
                $returnName = $name;
            }
        }

        if (array_key_exists($returnName, $this->resourceSets)) {
            throw new InvalidOperationException('Resource Set already added');
        }
        $resourceType->validateType();

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
     * @param  ResourceType              $resourceType resource type to which key property
     *                                                 is to be added
     * @param  string                    $name         name of the key property
     * @param  EdmPrimitiveType          $typeCode     type of the key property
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    public function addKeyProperty($resourceType, $name, EdmPrimitiveType $typeCode)
    {
        $this->addPrimitivePropertyInternal($resourceType, $name, $typeCode, true);
    }

    /**
     * To add a Key/NonKey-primitive property to a resource (complex/entity).
     *
     * @param ResourceType     $resourceType   Resource type
     * @param string           $name           name of the property
     * @param EdmPrimitiveType $typeCode       type of property
     * @param bool             $isKey          property is key or not
     * @param bool             $isBag          property is bag or not
     * @param bool             $isETagProperty property is etag or not
     * @param null|mixed       $defaultValue
     * @param mixed            $nullable
     *
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    private function addPrimitivePropertyInternal(
        $resourceType,
        $name,
        EdmPrimitiveType $typeCode = null,
        $isKey = false,
        $isBag = false,
        $isETagProperty = false,
        $defaultValue = null,
        $nullable = false
    ) {
        if ($isETagProperty && $isBag) {
            throw new InvalidOperationException(
                'Only primitive property can be etag property, bag property cannot be etag property.'
            );
        }

        $this->checkInstanceProperty($name, $resourceType);

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($name) == strtolower($resourceType->getName())) {
            throw new InvalidOperationException(
                'Property name must be different from resource name.'
            );
        }

        $primitiveResourceType = ResourceType::getPrimitiveResourceType($typeCode);

        $kind = $isKey ? ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY : ResourcePropertyKind::PRIMITIVE;
        if ($isBag) {
            $kind = $kind | ResourcePropertyKind::BAG;
        }

        if ($isETagProperty) {
            $kind = $kind | ResourcePropertyKind::ETAG;
        }

        $kind = new ResourcePropertyKind($kind);

        $resourceProperty = new ResourceProperty(
            $name,
            null,
            $kind,
            $primitiveResourceType
        );
        $resourceType->addProperty($resourceProperty);
        if (array_key_exists($resourceType->getFullName(), $this->oDataEntityMap)) {
            $this->metadataManager->addPropertyToEntityType(
                $this->oDataEntityMap[$resourceType->getFullName()],
                $name,
                $primitiveResourceType->getFullName(),
                $defaultValue,
                $nullable,
                $isKey
            );
        }
    }

    /**
     * @param string       $name
     * @param ResourceType $resourceType
     *
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    private function checkInstanceProperty($name, ResourceType $resourceType)
    {
        $instance       = $resourceType->getInstanceType();
        $hasMagicGetter = $instance instanceof IType || $instance->hasMethod('__get');
        if ($instance instanceof ReflectionClass) {
            $hasMagicGetter |= $instance->isInstance(new stdClass());
        }

        if (!$hasMagicGetter) {
            try {
                if ($instance instanceof ReflectionClass) {
                    $instance->getProperty($name);
                }
            } catch (ReflectionException $exception) {
                throw new InvalidOperationException(
                    'Can\'t add a property which does not exist on the instance type.'
                );
            }
        }
    }

    /**
     * To add a NonKey-primitive property (Complex/Entity).
     *
     * @param  ResourceType              $resourceType resource type to which key property
     *                                                 is to be added
     * @param  string                    $name         name of the key property
     * @param  EdmPrimitiveType          $typeCode     type of the key property
     * @param  bool                      $isBag        property is bag or not
     * @param  null|mixed                $defaultValue
     * @param  mixed                     $nullable
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    public function addPrimitiveProperty(
        $resourceType,
        $name,
        EdmPrimitiveType $typeCode,
        $isBag = false,
        $defaultValue = null,
        $nullable = false
    ) {
        $this->addPrimitivePropertyInternal(
            $resourceType,
            $name,
            $typeCode,
            false,
            $isBag,
            false,
            $defaultValue,
            $nullable
        );
    }

    /**
     * To add a non-key etag property.
     *
     * @param  ResourceType              $resourceType resource type to which key property
     *                                                 is to be added
     * @param  string                    $name         name of the property
     * @param  EdmPrimitiveType          $typeCode     type of the etag property
     * @param  null|mixed                $defaultValue
     * @param  mixed                     $nullable
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    public function addETagProperty(
        $resourceType,
        $name,
        EdmPrimitiveType $typeCode,
        $defaultValue = null,
        $nullable = false
    ) {
        $this->addPrimitivePropertyInternal(
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
     * @param  ResourceEntityType        $resourceType      The resource type to add the resource
     *                                                      reference property to
     * @param  string                    $name              The name of the property to add
     * @param  ResourceSet               $targetResourceSet The resource set the resource reference
     *                                                      property points to
     * @param  mixed                     $flip
     * @param  mixed                     $many
     * @param  ResourceEntityType|null   $concreteType      Underlying concrete resource reference type, if set
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    public function addResourceReferenceProperty(
        ResourceEntityType $resourceType,
        $name,
        ResourceSet $targetResourceSet,
        $flip = false,
        $many = false,
        ResourceEntityType $concreteType = null
    ) {
        $this->addReferencePropertyInternal(
            $resourceType,
            $name,
            $targetResourceSet,
            $flip ? '0..1' : '1',
            $many,
            $concreteType
        );
    }

    /**
     * To add a navigation property (resource set or resource reference)
     * to a resource type.
     *
     * @param ResourceEntityType $sourceResourceType The resource type to add the resource reference
     *                                               or resource reference set property to
     * @param string             $name               The name of the property to add
     * @param ResourceSet        $targetResourceSet  The resource set the
     *                                               resource reference or reference
     *                                               set property points to
     * @param string             $resourceMult       The multiplicity of relation being added
     * @param mixed              $many
     *
     * @param  ResourceEntityType|null   $concreteType
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    private function addReferencePropertyInternal(
        ResourceEntityType $sourceResourceType,
        string $name,
        ResourceSet $targetResourceSet,
        string $resourceMult,
        $many = false,
        ResourceEntityType $concreteType = null
    ) {
        $allowedMult   = ['*', '1', '0..1'];
        $backMultArray = ['*' => '*', '1' => '0..1', '0..1' => '1'];
        $this->checkInstanceProperty($name, $sourceResourceType);

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($name) == strtolower($sourceResourceType->getName())) {
            throw new InvalidOperationException(
                'Property name must be different from resource name.'
            );
        }
        assert(in_array($resourceMult, $allowedMult), 'Supplied multiplicity ' . $resourceMult . ' not valid');

        $resourcePropertyKind = ('*' == $resourceMult)
            ? ResourcePropertyKind::RESOURCESET_REFERENCE()
            : ResourcePropertyKind::RESOURCE_REFERENCE();
        $targetResourceType     = $targetResourceSet->getResourceType();
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
            new ResourceAssociationSetEnd(
                $sourceResourceSet,
                $sourceResourceType,
                $sourceResourceProperty
            ),
            new ResourceAssociationSetEnd($targetResourceSet, $targetResourceType, null, $concreteType)
        );
        $mult     = $resourceMult;
        $backMult = $many ? '*' : $backMultArray[$resourceMult];
        if (false === $many && '*' == $backMult && '*' == $resourceMult) {
            $backMult = '1';
        }
        $this->getMetadataManager()->addNavigationPropertyToEntityType(
            $this->oDataEntityMap[$sourceResourceType->getFullName()],
            $mult,
            $name,
            $this->oDataEntityMap[$targetResourceType->getFullName()],
            $backMult
        );
        $this->associationSets[$setKey] = $set;
    }

    /**
     * To add a 1:N resource reference property.
     *
     * @param  ResourceEntityType        $sourceResourceType The resource type to add the resource
     *                                                       reference property from
     * @param  ResourceEntityType        $targetResourceType The resource type to add the resource
     *                                                       reference property to
     * @param  string                    $sourceProperty     The name of the property to add, on source type
     * @param  string                    $targetProperty     The name of the property to add, on target type
     * @param  bool                      $nullable           Is singleton side of relation nullable?
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    public function addResourceReferencePropertyBidirectional(
        ResourceEntityType $sourceResourceType,
        ResourceEntityType $targetResourceType,
        $sourceProperty,
        $targetProperty,
        $nullable = false
    ) {
        $this->addReferencePropertyInternalBidirectional(
            $sourceResourceType,
            $targetResourceType,
            $sourceProperty,
            $targetProperty,
            '*',
            true === $nullable ? '0..1' : '1'
        );
        // verify resource property types are what we expect them to be
        $sourceResourceKind = $sourceResourceType->resolveProperty($sourceProperty)->getKind();
        assert(
            ResourcePropertyKind::RESOURCE_REFERENCE() == $sourceResourceKind,
            '1 side of 1:N relationship not pointing to resource reference'
        );
        $targetResourceKind = $targetResourceType->resolveProperty($targetProperty)->getKind();
        assert(
            ResourcePropertyKind::RESOURCESET_REFERENCE() == $targetResourceKind,
            'N side of 1:N relationship not pointing to resource set reference'
        );
    }

    /**
     * To add a navigation property (resource set or resource reference)
     * to a resource type.
     *
     * @param ResourceEntityType $sourceResourceType The source resource type to add
     *                                               the resource reference
     *                                               or resource reference set property to
     * @param ResourceEntityType $targetResourceType The target resource type to add
     *                                               the resource reference
     *                                               or resource reference set property to
     * @param string             $sourceProperty     The name of the
     *                                               property to add to source type
     * @param string             $targetProperty     The name of the
     *                                               property to add to target type
     * @param string             $sourceMultiplicity The multiplicity at the source end of relation
     * @param string             $targetMultiplicity The multiplicity at the target end of relation
     *
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    private function addReferencePropertyInternalBidirectional(
        ResourceEntityType $sourceResourceType,
        ResourceEntityType $targetResourceType,
        string $sourceProperty,
        string $targetProperty,
        string $sourceMultiplicity,
        string $targetMultiplicity
    ) {
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
        if (isset($this->associationSets[$fwdSetKey]) && isset($this->associationSets[$revSetKey])) {
            return;
        }
        $sourceKind = ('*' == $sourceMultiplicity)
            ? ResourcePropertyKind::RESOURCESET_REFERENCE()
            : ResourcePropertyKind::RESOURCE_REFERENCE();
        $targetKind = ('*' == $targetMultiplicity)
            ? ResourcePropertyKind::RESOURCESET_REFERENCE()
            : ResourcePropertyKind::RESOURCE_REFERENCE();

        $sourceResourceProperty = new ResourceProperty($sourceProperty, null, $targetKind, $targetResourceType);
        assert(
            $targetKind == $sourceResourceProperty->getKind(),
            'Resource property kind mismatch between $targetKind and $sourceResourceProperty'
        );
        $sourceResourceType->addProperty($sourceResourceProperty, false);
        $targetResourceProperty = new ResourceProperty($targetProperty, null, $sourceKind, $sourceResourceType);
        assert(
            $sourceKind == $targetResourceProperty->getKind(),
            'Resource property kind mismatch between $sourceKind and $targetResourceProperty'
        );
        $targetResourceType->addProperty($targetResourceProperty, false);

        //TODO: Audit this, figure out how it makes metadata go sproing
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
        $this->getMetadataManager()->addNavigationPropertyToEntityType(
            $this->oDataEntityMap[$sourceName],
            $sourceMultiplicity,
            $sourceProperty,
            $this->oDataEntityMap[$targetName],
            $targetMultiplicity,
            $targetProperty
        );
        $this->associationSets[$fwdSetKey] = $fwdSet;
        $this->associationSets[$revSetKey] = $revSet;
    }

    /**
     * To add a resource set reference property.
     *
     * @param  ResourceEntityType        $resourceType      The resource type to add the
     *                                                      resource reference set property to
     * @param  string                    $name              The name of the property to add
     * @param  ResourceSet               $targetResourceSet The resource set the resource
     *                                                      reference set property points to
     * @param  ResourceEntityType|null   $concreteType      Underlying concrete resource type, if set
     * @param  mixed                     $single
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    public function addResourceSetReferenceProperty(
        ResourceEntityType $resourceType,
        $name,
        ResourceSet $targetResourceSet,
        ResourceEntityType $concreteType = null,
        $single = false
    ) {
        $this->addReferencePropertyInternal(
            $resourceType,
            $name,
            $targetResourceSet,
            '*',
            (true === $single) ? false : null,
            $concreteType
        );
    }

    /**
     * To add a M:N resource reference property.
     *
     * @param  ResourceEntityType        $sourceResourceType The resource type to add the resource
     *                                                       reference property from
     * @param  ResourceEntityType        $targetResourceType The resource type to add the resource
     *                                                       reference property to
     * @param  string                    $sourceProperty     The name of the property to add, on source type
     * @param  string                    $targetProperty     The name of the property to add, on target type
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    public function addResourceSetReferencePropertyBidirectional(
        ResourceEntityType $sourceResourceType,
        ResourceEntityType $targetResourceType,
        $sourceProperty,
        $targetProperty
    ) {
        $this->addReferencePropertyInternalBidirectional(
            $sourceResourceType,
            $targetResourceType,
            $sourceProperty,
            $targetProperty,
            '*',
            '*'
        );
        // verify resource property types are what we expect them to be
        $sourceResourceKind = $sourceResourceType->resolveProperty($sourceProperty)->getKind();
        assert(
            ResourcePropertyKind::RESOURCESET_REFERENCE() == $sourceResourceKind,
            'M side of M:N relationship not pointing to resource set reference'
        );
        $targetResourceKind = $targetResourceType->resolveProperty($targetProperty)->getKind();
        assert(
            ResourcePropertyKind::RESOURCESET_REFERENCE() == $targetResourceKind,
            'N side of M:N relationship not pointing to resource set reference'
        );
    }

    /**
     * To add a 1-1 resource reference.
     *
     * @param  ResourceEntityType        $sourceResourceType The resource type to add the resource
     *                                                       reference property from
     * @param  ResourceEntityType        $targetResourceType The resource type to add the resource
     *                                                       reference property to
     * @param  string                    $sourceProperty     The name of the property to add, on source type
     * @param  string                    $targetProperty     The name of the property to add, on target type
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    public function addResourceReferenceSinglePropertyBidirectional(
        ResourceEntityType $sourceResourceType,
        ResourceEntityType $targetResourceType,
        $sourceProperty,
        $targetProperty
    ) {
        $this->addReferencePropertyInternalBidirectional(
            $sourceResourceType,
            $targetResourceType,
            $sourceProperty,
            $targetProperty,
            '1',
            '0..1'
        );
        // verify resource property types are what we expect them to be
        $sourceResourceKind = $sourceResourceType->resolveProperty($sourceProperty)->getKind();
        assert(
            ResourcePropertyKind::RESOURCE_REFERENCE() == $sourceResourceKind,
            '1 side of 1:1 relationship not pointing to resource reference'
        );
        $targetResourceKind = $targetResourceType->resolveProperty($targetProperty)->getKind();
        assert(
            ResourcePropertyKind::RESOURCE_REFERENCE() == $targetResourceKind,
            '0..1 side of 1:1 relationship not pointing to resource reference'
        );
    }

    /**
     * To add a complex property to entity or complex type.
     *
     * @param ResourceType        $targetResourceType  The resource type to which the complex property needs to add
     * @param string              $name                name of the complex property
     * @param ResourceComplexType $complexResourceType complex resource type
     * @param bool                $isBag               complex type is bag or not
     *
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return ResourceProperty
     */
    public function addComplexProperty(
        ResourceType $targetResourceType,
        $name,
        ResourceComplexType $complexResourceType,
        $isBag = false
    ) {
        if ($targetResourceType->getResourceTypeKind() != ResourceTypeKind::ENTITY()
            && $targetResourceType->getResourceTypeKind() != ResourceTypeKind::COMPLEX()
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

        $kind = new ResourcePropertyKind($kind);

        $resourceProperty = new ResourceProperty(
            $name,
            null,
            $kind,
            $complexResourceType
        );
        $targetResourceType->addProperty($resourceProperty);

        return $resourceProperty;
    }

    /**
     * @param  string       $name
     * @param  ResourceType $returnType
     * @param  array|string $functionName
     * @return mixed|void
     */
    public function createSingleton($name, ResourceType $returnType, $functionName)
    {
        $msg = null;
        if (array_key_exists($name, $this->singletons)) {
            $msg = 'Singleton name already exists';
            throw new InvalidArgumentException($msg);
        }
        if (array_key_exists($name, $this->resourceSets)) {
            $msg = 'Resource set with same name, ' . $name . ', exists';
            throw new InvalidArgumentException($msg);
        }
        $typeName = $returnType->getFullName();
        if (!array_key_exists($typeName, $this->oDataEntityMap)) {
            $msg = 'Mapping not defined for ' . $typeName;
            throw new InvalidArgumentException($msg);
        }
        $metaReturn   = $this->oDataEntityMap[$typeName];
        $anonymousSet = $this->typeSetMapping[$typeName];
        $singleton    = $this->getMetadataManager()->createSingleton($name, $metaReturn, $anonymousSet);
        assert($singleton->isOK($msg), $msg);
        $type = new ResourceFunctionType($functionName, $singleton, $returnType);
        // Since singletons should take no args, enforce it here
        assert(0 == count($type->getParms()));
        $this->singletons[$name] = $type;
    }

    /**
     * @return array
     */
    public function getSingletons()
    {
        return $this->singletons;
    }

    /**
     * @param  string $name
     * @return mixed
     */
    public function callSingleton($name)
    {
        if (!array_key_exists($name, $this->singletons)) {
            $msg = 'Requested singleton does not exist';
            throw new InvalidArgumentException($msg);
        }

        return $this->singletons[$name]->get();
    }
}
