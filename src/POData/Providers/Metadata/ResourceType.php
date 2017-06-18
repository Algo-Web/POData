<?php

namespace POData\Providers\Metadata;

use InvalidArgumentException;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\Byte;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Decimal;
use POData\Providers\Metadata\Type\Double;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use POData\Providers\Metadata\Type\Guid;
use POData\Providers\Metadata\Type\Int16;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\Int64;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\SByte;
use POData\Providers\Metadata\Type\Single;
use POData\Providers\Metadata\Type\StringType;

/**
 * Class ResourceType.
 *
 * A type to describe an entity type, complex type or primitive type.
 */
abstract class ResourceType
{
    /**
     * Name of the resource described by this class instance.
     *
     * @var string
     */
    protected $name;

    /**
     * Namespace name in which resource described by this class instance
     * belongs to.
     *
     * @var string
     */
    protected $namespaceName;

    /**
     * The fully qualified name of the resource described by this class instance.
     *
     * @var string
     */
    protected $fullName;

    /**
     * The type the resource described by this class instance.
     * Note: either Entity or Complex Type.
     *
     * @var ResourceTypeKind
     */
    protected $resourceTypeKind;

    /**
     * @var bool
     */
    protected $abstractType;

    /**
     * Refrence to ResourceType instance for base type, if any.
     *
     * @var ResourceType
     */
    protected $baseType;

    /**
     * Collection of ResourceProperty for all properties declared on the
     * resource described by this class instance (This does not include
     * base type properties).
     *
     * @var ResourceProperty[] indexed by name
     */
    protected $propertiesDeclaredOnThisType = [];

    /**
     * Collection of ResourceStreamInfo for all named streams declared on
     * the resource described by this class instance (This does not include
     * base type properties).
     *
     * @var ResourceStreamInfo[] indexed by name
     */
    protected $namedStreamsDeclaredOnThisType = [];

    /**
     * Collection of ResourceProperty for all properties declared on this type.
     * and base types.
     *
     * @var ResourceProperty[] indexed by name
     */
    protected $allProperties = [];

    /**
     * Collection of ResourceStreamInfo for all named streams declared on this type.
     * and base types.
     *
     * @var ResourceStreamInfo[]
     */
    protected $allNamedStreams = [];

    /**
     * Collection of properties which has etag defined subset of $_allProperties.
     *
     * @var ResourceProperty[]
     */
    protected $eTagProperties = [];

    /**
     * Collection of key properties subset of $_allProperties.
     *
     * @var ResourceProperty[]
     */
    protected $keyProperties = [];

    /**
     * Whether the resource type described by this class instance is a MLE or not.
     *
     * @var bool
     */
    protected $isMediaLinkEntry = false;

    /**
     * Whether the resource type described by this class instance has bag properties
     * Note: This has been initialized with null, later in hasBagProperty method,
     * this flag will be set to boolean value.
     *
     * @var bool
     */
    protected $hasBagProperty = null;

    /**
     * Whether the resource type described by this class instance has named streams
     * Note: This has been intitialized with null, later in hasNamedStreams method,
     * this flag will be set to boolean value.
     *
     * @var bool
     */
    protected $hasNamedStreams = null;

    /**
     * ReflectionClass (for complex/Entity) or IType (for Primitive) instance for
     * the resource (type) described by this class instance.
     *
     * @var \ReflectionClass|IType|string
     */
    protected $type;

    /**
     * To store any custom information related to this class instance.
     *
     * @var object
     */
    protected $customState;

    /**
     * Array to detect looping in bag's complex type.
     *
     * @var array
     */
    protected $arrayToDetectLoopInComplexBag;

    /**
     * Create new instance of ResourceType.
     *
     * @param \ReflectionClass|IType $instanceType     Instance type for the resource,
     *                                                 for entity and
     *                                                 complex this will
     *                                                 be 'ReflectionClass' and for
     *                                                 primitive type this
     *                                                 will be IType
     * @param ResourceTypeKind       $resourceTypeKind Kind of resource (Entity, Complex or Primitive)
     * @param string                 $name             Name of the resource
     * @param string                 $namespaceName    Namespace of the resource
     * @param ResourceType           $baseType         Base type of the resource, if exists
     * @param bool                   $isAbstract       Whether resource is abstract
     *
     * @throws \InvalidArgumentException
     */
    protected function __construct(
        $instanceType,
        $resourceTypeKind,
        $name,
        $namespaceName = null,
        ResourceType $baseType = null,
        $isAbstract = false
    ) {
        $this->type = $instanceType;
        $this->resourceTypeKind = $resourceTypeKind;
        $this->name = $name;
        $this->baseType = $baseType;
        $this->namespaceName = $namespaceName;
        $this->fullName = is_null($namespaceName) ? $name : $namespaceName . '.' . $name;
        $this->abstractType = $isAbstract;
        $this->isMediaLinkEntry = false;
        $this->customState = null;
        $this->arrayToDetectLoopInComplexBag = [];
        //TODO: Set MLE if base type has MLE Set
    }

    /**
     * Get reference to ResourceType for base class.
     *
     * @return ResourceType
     */
    public function getBaseType()
    {
        return $this->baseType;
    }

    /**
     * To check whether this resource type has base type.
     *
     * @return bool True if base type is defined, false otherwise
     */
    public function hasBaseType()
    {
        return !is_null($this->baseType);
    }

    /**
     * To get custom state object for this type.
     *
     * @return object
     */
    public function getCustomState()
    {
        return $this->customState;
    }

    /**
     * To set custom state object for this type.
     *
     * @param ResourceSet $object The custom object
     */
    public function setCustomState($object)
    {
        $this->customState = $object;
    }

    /**
     * Get the instance type. If the resource type describes a complex or entity type,
     * then this function returns reference to ReflectionClass instance for the type.
     * If resource type describes a primitive type, then this function returns ITYpe.
     *
     * @return \ReflectionClass|IType
     */
    public function getInstanceType()
    {
        if (is_string($this->type)) {
            $this->__wakeup();
        }

        return $this->type;
    }

    /**
     * Get name of the type described by this resource type.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the namespace under which the type described by this resource type is
     * defined.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespaceName;
    }

    /**
     * Get full name (namespacename.typename) of the type described by this resource
     * type.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * To check whether the type described by this resource type is abstract or not.
     *
     * @return bool True if type is abstract else False
     */
    public function isAbstract()
    {
        return $this->abstractType;
    }

    /**
     * To get the kind of type described by this resource class.
     *
     * @return ResourceTypeKind
     */
    public function getResourceTypeKind()
    {
        return $this->resourceTypeKind;
    }

    /**
     * To check whether the type described by this resource type is MLE.
     *
     * @return bool True if type is MLE else False
     */
    public function isMediaLinkEntry()
    {
        return $this->isMediaLinkEntry;
    }

    /**
     * Set the resource type as MLE or non-MLE.
     *
     * @param bool $isMLE True to set as MLE, false for non-MLE
     */
    public function setMediaLinkEntry($isMLE)
    {
        if (ResourceTypeKind::ENTITY != $this->resourceTypeKind) {
            throw new InvalidOperationException(
                Messages::resourceTypeHasStreamAttributeOnlyAppliesToEntityType()
            );
        }

        $this->isMediaLinkEntry = $isMLE;
    }

    /**
     * Add a property belongs to this resource type instance.
     *
     * @param ResourceProperty $property Property to add
     * @param bool             $throw    Throw exception on name collision?
     *
     * @throws InvalidOperationException
     */
    public function addProperty(ResourceProperty $property, $throw = true)
    {
        if (ResourceTypeKind::PRIMITIVE == $this->resourceTypeKind) {
            throw new InvalidOperationException(
                Messages::resourceTypeNoAddPropertyForPrimitive()
            );
        }

        $name = $property->getName();
        foreach (array_keys($this->propertiesDeclaredOnThisType) as $propertyName) {
            if (0 == strcasecmp($propertyName, $name)) {
                if (false === $throw) {
                    return;
                }
                throw new InvalidOperationException(
                    Messages::resourceTypePropertyWithSameNameAlreadyExists(
                        $propertyName,
                        $this->name
                    )
                );
            }
        }

        if ($property->isKindOf(ResourcePropertyKind::KEY)) {
            if (ResourceTypeKind::ENTITY != $this->resourceTypeKind) {
                throw new InvalidOperationException(
                    Messages::resourceTypeKeyPropertiesOnlyOnEntityTypes()
                );
            }

            if (null != $this->baseType) {
                throw new InvalidOperationException(
                    Messages::resourceTypeNoKeysInDerivedTypes()
                );
            }
        }

        if ($property->isKindOf(ResourcePropertyKind::ETAG)
            && (ResourceTypeKind::ENTITY != $this->resourceTypeKind)
        ) {
            throw new InvalidOperationException(
                Messages::resourceTypeETagPropertiesOnlyOnEntityTypes()
            );
        }

        //Check for Base class properties
        $this->propertiesDeclaredOnThisType[$name] = $property;
        // Set $this->allProperties to null, this is very important because the
        // first call to getAllProperties will initilaize $this->allProperties,
        // further call to getAllProperties will not reinitialize _allProperties
        // so if addProperty is called after calling getAllProperties then the
        // property just added will not be reflected in $this->allProperties
        unset($this->allProperties);
        $this->allProperties = [];
    }

    /**
     * Get collection properties belongs to this resource type (excluding base class
     * properties). This function returns  empty array in case of resource type
     * for primitive types.
     *
     * @return ResourceProperty[]
     */
    public function getPropertiesDeclaredOnThisType()
    {
        return $this->propertiesDeclaredOnThisType;
    }

    /**
     * Get collection properties belongs to this resource type including base class
     * properties. This function returns  empty array in case of resource type
     * for primitive types.
     *
     * @return ResourceProperty[]
     */
    public function getAllProperties()
    {
        if (empty($this->allProperties)) {
            if (null != $this->baseType) {
                $this->allProperties = $this->baseType->getAllProperties();
            }

            $this->allProperties = array_merge(
                $this->allProperties, $this->propertiesDeclaredOnThisType
            );
        }

        return $this->allProperties;
    }

    /**
     * Get collection key properties belongs to this resource type. This
     * function returns non-empty array only for resource type representing
     * an entity type.
     *
     * @return ResourceProperty[]
     */
    public function getKeyProperties()
    {
        if (empty($this->keyProperties)) {
            $baseType = $this;
            while (null != $baseType->baseType) {
                $baseType = $baseType->baseType;
            }

            foreach ($baseType->propertiesDeclaredOnThisType as $propertyName => $resourceProperty) {
                if ($resourceProperty->isKindOf(ResourcePropertyKind::KEY)) {
                    $this->keyProperties[$propertyName] = $resourceProperty;
                }
            }
        }

        return $this->keyProperties;
    }

    /**
     * Get collection of e-tag properties belongs to this type.
     *
     * @return ResourceProperty[]
     */
    public function getETagProperties()
    {
        if (empty($this->eTagProperties)) {
            foreach ($this->getAllProperties() as $propertyName => $resourceProperty) {
                if ($resourceProperty->isKindOf(ResourcePropertyKind::ETAG)) {
                    $this->eTagProperties[$propertyName] = $resourceProperty;
                }
            }
        }

        return $this->eTagProperties;
    }

    /**
     * To check this type has any eTag properties.
     *
     * @return bool
     */
    public function hasETagProperties()
    {
        $properties = $this->getETagProperties();

        return !empty($properties);
    }

    /**
     * Try to get ResourceProperty for a property defined for this resource type
     * excluding base class properties.
     *
     * @param string $propertyName The name of the property to resolve
     *
     * @return ResourceProperty|null
     */
    public function resolvePropertyDeclaredOnThisType($propertyName)
    {
        if (array_key_exists($propertyName, $this->propertiesDeclaredOnThisType)) {
            return $this->propertiesDeclaredOnThisType[$propertyName];
        }
        return null;
    }

    /**
     * Try to get ResourceProperty for a property defined for this resource type
     * including base class properties.
     *
     * @param string $propertyName The name of the property to resolve
     *
     * @return ResourceProperty|null
     */
    public function resolveProperty($propertyName)
    {
        if (array_key_exists($propertyName, $this->getAllProperties())) {
            return $this->allProperties[$propertyName];
        }
        return null;
    }

    /**
     * Add a named stream belongs to this resource type instance.
     *
     * @param ResourceStreamInfo $namedStream ResourceStreamInfo instance describing the named stream to add
     *
     * @throws InvalidOperationException
     */
    public function addNamedStream(ResourceStreamInfo $namedStream)
    {
        if ($this->resourceTypeKind != ResourceTypeKind::ENTITY) {
            throw new InvalidOperationException(
                Messages::resourceTypeNamedStreamsOnlyApplyToEntityType()
            );
        }

        $name = $namedStream->getName();
        foreach (array_keys($this->namedStreamsDeclaredOnThisType) as $namedStreamName) {
            if (0 == strcasecmp($namedStreamName, $name)) {
                throw new InvalidOperationException(
                    Messages::resourceTypeNamedStreamWithSameNameAlreadyExists(
                        $name,
                        $this->name
                    )
                );
            }
        }

        $this->namedStreamsDeclaredOnThisType[$name] = $namedStream;
        // Set $this->allNamedStreams to null, the first call to getAllNamedStreams
        // will initialize $this->allNamedStreams, further call to
        // getAllNamedStreams will not reinitialize _allNamedStreams
        // so if addNamedStream is called after calling getAllNamedStreams then the
        // property just added will not be reflected in $this->allNamedStreams
        unset($this->allNamedStreams);
        $this->allNamedStreams = [];
    }

    /**
     * Get collection of ResourceStreamInfo describing the named streams belongs
     * to this resource type (excluding base class properties).
     *
     * @return ResourceStreamInfo[]
     */
    public function getNamedStreamsDeclaredOnThisType()
    {
        return $this->namedStreamsDeclaredOnThisType;
    }

    /**
     * Get collection of ResourceStreamInfo describing the named streams belongs
     * to this resource type including base class named streams.
     *
     * @return ResourceStreamInfo[]
     */
    public function getAllNamedStreams()
    {
        if (empty($this->allNamedStreams)) {
            if (null != $this->baseType) {
                $this->allNamedStreams = $this->baseType->getAllNamedStreams();
            }

            $this->allNamedStreams
                = array_merge(
                    $this->allNamedStreams,
                    $this->namedStreamsDeclaredOnThisType
                );
        }

        return $this->allNamedStreams;
    }

    /**
     * Try to get ResourceStreamInfo for a named stream defined for this
     * resource type excluding base class named streams.
     *
     * @param string $namedStreamName Name of the named stream to resolve
     *
     * @return ResourceStreamInfo|null
     */
    public function tryResolveNamedStreamDeclaredOnThisTypeByName($namedStreamName)
    {
        if (array_key_exists($namedStreamName, $this->namedStreamsDeclaredOnThisType)) {
            return $this->namedStreamsDeclaredOnThisType[$namedStreamName];
        }
        return null;
    }

    /**
     * Try to get ResourceStreamInfo for a named stream defined for this resource
     * type including base class named streams.
     *
     * @param string $namedStreamName Name of the named stream to resolve
     *
     * @return ResourceStreamInfo|null
     */
    public function tryResolveNamedStreamByName($namedStreamName)
    {
        if (array_key_exists($namedStreamName, $this->getAllNamedStreams())) {
            return $this->allNamedStreams[$namedStreamName];
        }
        return null;
    }

    /**
     * Check this resource type instance has named stream associated with it
     * Note: This is an internal method used by library. Devs don't use this.
     *
     * @return bool true if resource type instance has named stream else false
     */
    public function hasNamedStream()
    {
        // Note: Calling this method will initialize _allNamedStreams
        // and _hasNamedStreams flag to a boolean value
        // from null depending on the current state of _allNamedStreams
        // array, so method should be called only after adding all
        // named streams
        if (is_null($this->hasNamedStreams)) {
            $this->getAllNamedStreams();
            $this->hasNamedStreams = !empty($this->allNamedStreams);
        }

        return $this->hasNamedStreams;
    }

    /**
     * Check this resource type instance has bag property associated with it
     * Note: This is an internal method used by library. Devs don't use this.
     *
     * @param array &$arrayToDetectLoopInComplexType array for detecting loop
     *
     * @return bool|null true if resource type instance has bag property else false
     */
    public function hasBagProperty(&$arrayToDetectLoopInComplexType)
    {
        // Note: Calling this method will initialize _bagProperties
        // and _hasBagProperty flag to a boolean value
        // from null depending on the current state of
        // _propertiesDeclaredOnThisType array, so method
        // should be called only after adding all properties
        if (!is_null($this->hasBagProperty)) {
            return $this->hasBagProperty;
        }

        if (null != $this->baseType && $this->baseType->hasBagProperty($arrayToDetectLoopInComplexType)) {
            $this->hasBagProperty = true;
        } else {
            foreach ($this->propertiesDeclaredOnThisType as $resourceProperty) {
                $hasBagInComplex = false;
                if ($resourceProperty->isKindOf(ResourcePropertyKind::COMPLEX_TYPE)) {
                    //We can say current ResouceType ("this")
                    //is contains a bag property if:
                    //1. It contain a property of kind bag.
                    //2. It contains a normal complex property
                    //with a sub property of kind bag.
                    //The second case can be further expanded, i.e.
                    //if the normal complex property
                    //has a normal complex sub property with a
                    //sub property of kind bag.
                    //So for complex type we recursively call this
                    //function to check for bag.
                    //Shown below how looping can happen in complex type:
                    //Customer ResourceType (id1)
                    //{
                    //  ....
                    //  Address: Address ResourceType (id2)
                    //  {
                    //    .....
                    //    AltAddress: Address ResourceType (id2)
                    //    {
                    //      ...
                    //    }
                    //  }
                    //}

                    //Here the resource type of Customer::Address and
                    //Customer::Address::AltAddress
                    //are same, this is a loop, we need to detect
                    //this and avoid infinite recursive loop.

                    $count = count($arrayToDetectLoopInComplexType);
                    $foundLoop = false;
                    for ($i = 0; $i < $count; ++$i) {
                        if ($arrayToDetectLoopInComplexType[$i] === $resourceProperty->getResourceType()) {
                            $foundLoop = true;
                            break;
                        }
                    }

                    if (!$foundLoop) {
                        $arrayToDetectLoopInComplexType[$count] = $resourceProperty->getResourceType();
                        $hasBagInComplex = $resourceProperty
                            ->getResourceType()
                            ->hasBagProperty($arrayToDetectLoopInComplexType);
                        unset($arrayToDetectLoopInComplexType[$count]);
                    }
                }

                if ($resourceProperty->isKindOf(ResourcePropertyKind::BAG) || $hasBagInComplex) {
                    $this->hasBagProperty = true;
                    break;
                }
            }
        }

        return $this->hasBagProperty;
    }

    /**
     * Validate the type.
     *
     *
     * @throws InvalidOperationException
     */
    public function validateType()
    {
        $keyProperties = $this->getKeyProperties();
        if (($this->resourceTypeKind == ResourceTypeKind::ENTITY) && empty($keyProperties)) {
            throw new InvalidOperationException(
                Messages::resourceTypeMissingKeyPropertiesForEntity(
                    $this->getFullName()
                )
            );
        }
    }

    /**
     * To check the type described by this resource type is assignable from
     * a type described by another resource type. Or this type is a sub-type
     * of (derived from the) given resource type.
     *
     * @param ResourceType $resourceType Another resource type
     *
     * @return bool
     */
    public function isAssignableFrom(ResourceType $resourceType)
    {
        $base = $this;
        while (null != $base) {
            if ($resourceType == $base) {
                return true;
            }

            $base = $base->baseType;
        }

        return false;
    }

    /**
     * Get predefined ResourceType for a primitive type.
     *
     * @param EdmPrimitiveType $typeCode Typecode of primitive type
     *
     * @throws InvalidArgumentException
     *
     * @return ResourceType
     */
    public static function getPrimitiveResourceType($typeCode)
    {
        switch ($typeCode) {
            case EdmPrimitiveType::BINARY:
                return new ResourcePrimitiveType(new Binary());
            case EdmPrimitiveType::BOOLEAN:
                return new ResourcePrimitiveType(new Boolean());
            case EdmPrimitiveType::BYTE:
                return new ResourcePrimitiveType(new Byte());
            case EdmPrimitiveType::DATETIME:
                return new ResourcePrimitiveType(new DateTime());
            case EdmPrimitiveType::DECIMAL:
                return new ResourcePrimitiveType(new Decimal());
            case EdmPrimitiveType::DOUBLE:
                return new ResourcePrimitiveType(new Double());
            case EdmPrimitiveType::GUID:
                return new ResourcePrimitiveType(new Guid());
            case EdmPrimitiveType::INT16:
                return new ResourcePrimitiveType(new Int16());
            case EdmPrimitiveType::INT32:
                return new ResourcePrimitiveType(new Int32());
            case EdmPrimitiveType::INT64:
                return new ResourcePrimitiveType(new Int64());
            case EdmPrimitiveType::SBYTE:
                return new ResourcePrimitiveType(new SByte());
            case EdmPrimitiveType::SINGLE:
                return new ResourcePrimitiveType(new Single());
            case EdmPrimitiveType::STRING:
                return new ResourcePrimitiveType(new StringType());
            default:
                throw new \InvalidArgumentException(
                    Messages::commonNotValidPrimitiveEDMType(
                        '$typeCode',
                        'getPrimitiveResourceType'
                    )
                );
        }
    }

    /**
     * @param string $property
     */
    public function setPropertyValue($entity, $property, $value)
    {
        \POData\Common\ReflectionHandler::setProperty($entity, $property, $value);

        return $this;
    }

    public function getPropertyValue($entity, $property)
    {
        return \POData\Common\ReflectionHandler::getProperty($entity, $property);
    }

    public function __sleep()
    {
        if (null == $this->type || $this->type instanceof \POData\Providers\Metadata\Type\IType) {
            return array_keys(get_object_vars($this));
        }
        if (is_object($this->type)) {
            $this->type = $this->type->name;
        }
        assert(is_string($this->type), "Type name should be a string at end of serialisation");
        $result = array_keys(get_object_vars($this));

        return $result;
    }

    public function __wakeup()
    {
        if (is_string($this->type)) {
            $this->type = new \ReflectionClass($this->type);
        }

        assert(
            $this->type instanceof \ReflectionClass || $this->type instanceof IType,
            '_type neither instance of reflection class nor IType'
        );
    }
}
