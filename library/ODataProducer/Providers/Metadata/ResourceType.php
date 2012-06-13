<?php
/**
 * A type to describe an entity type, complex type or primitive type
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
use ODataProducer\Providers\Metadata\Type\Binary;
use ODataProducer\Providers\Metadata\Type\Boolean;
use ODataProducer\Providers\Metadata\Type\Byte;
use ODataProducer\Providers\Metadata\Type\DateTime;
use ODataProducer\Providers\Metadata\Type\Decimal;
use ODataProducer\Providers\Metadata\Type\Double;
use ODataProducer\Providers\Metadata\Type\Guid;
use ODataProducer\Providers\Metadata\Type\Int16;
use ODataProducer\Providers\Metadata\Type\Int32;
use ODataProducer\Providers\Metadata\Type\Int64;
use ODataProducer\Providers\Metadata\Type\SByte;
use ODataProducer\Providers\Metadata\Type\Single;
use ODataProducer\Providers\Metadata\Type\String;
use ODataProducer\Providers\Metadata\Type\TypeCode;
use ODataProducer\Providers\Metadata\Type\EdmPrimitiveType;
use ODataProducer\Providers\Metadata\Type\IType;
use ODataProducer\Common\Messages;
use ODataProducer\Common\InvalidOperationException;
use ODataProducer\Common;
/**
 * A type to describe an entity type, complex type or primitive type.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ResourceType
{
    /**
     * Name of the resource described by this class instance.
     * 
     * @var string
     */
    private $_name;

    /**
     * Namespace name in which resource described by this class instance
     * belongs to.
     * 
     * @var string
     */
    private $_namespaceName;

    /**
     * The fully qualified name of the resource described by this class instance.
     * 
     * @var string
     */
    private $_fullName;

    /**
     * The type the resource described by this class instance.
     * Note: either Entity or Complex Type
     * 
     * @var  ResourceTypeKind
     */
    private $_resourceTypeKind;

    /**
     * @var boolean
     */
    private $_abstractType;

    /**
     * Refrence to ResourceType instance for base type, if any.
     * 
     * @var ResourceType
     */
    private $_baseType;

    /** 
     * Collection of ResourceProperty for all properties declared on the
     * resource described by this class instance (This does not include 
     * base type properties).
     * 
     * @var array(string, ResourceProperty)
     */
    private $_propertiesDeclaredOnThisType = array();

    /**
     * Collection of ResourceStreamInfo for all named streams declared on 
     * the resource described by this class instance (This does not include 
     * base type properties).
     * 
     * @var array(string, ResourceStreamInfo)
     */
    private $_namedStreamsDeclaredOnThisType = array();

    /**
     * Collection of ReflectionProperty instances for each property declared 
     * on this type
     * 
     * @var array(ResourceProperty, ReflectionProperty)
     */
    private $_propertyInfosDeclaredOnThisType = array();

    /**
     * Collection of ResourceProperty for all properties declared on this type.
     * and base types.
     * 
     * @var array(string, ResourceProperty)
     */    
    private $_allProperties = array();

    /**
     * Collection of ResourceStreamInfo for all named streams declared on this type.
     * and base types
     * 
     * @var array(string, ResourceStreamInfo)
     */
    private $_allNamedStreams = array();

    /**      
     * Collection of properies which has etag defined subeset of $_allProperies
     * @var array(ResourceProperty)
     */
    private $_etagProperties = array();

    /** 
     * Collection of key properies subeset of $_allProperies
     * 
     * @var array(ResourceProperty)
     */
    private $_keyProperties = array();

    /**      
     * Whether the resource type described by this class intance is a MLE or not
     * 
     * @var boolean
     */
    private $_isMediaLinkEntry = false;

    /**
     * Whether the resource type described by this class instance has bag properties
     * Note: This has been intitialized with null, later in hasBagProperty method, 
     * this flag will be set to boolean value
     * 
     * @var boolean
     */
    private $_hasBagProperty = null;

    /**
     * Whether the resource type described by this class instance has named streams
     * Note: This has been intitialized with null, later in hasNamedStreams method, 
     * this flag will be set to boolean value
     * 
     * @var boolean
     */
    private $_hasNamedStreams = null;

    /**     
     * ReflectionClass (for complex/Entity) or IType (for Primitive) instance for 
     * the resource (type) described by this class intstance
     * 
     * @var ReflectionClass/IType
     */
    private $_type;

    /**
     * To store any custom information related to this class intstance
     * 
     * @var Object
     */
    private $_customState; 

    /**
     * Array to detect looping in bag's complex type  
     * 
     * @var array(mixed)
     */
    private $_arrayToDetectLoopInComplexBag;

    /**
     * Create new instance of ResourceType
     * 
     * @param ReflectionClass/IType $instanceType     Instance type for the resource,
     *                                                for entity and 
     *                                                complex this will 
     *                                                be 'ReflectionClass' and for 
     *                                                primitive type this 
     *                                                will be IType
     * @param ResourceTypeKind      $resourceTypeKind Kind of resource 
     *                                               (Entity, Complex or Primitive)
     * @param string                $name             Name of the resource
     * @param string                $namespaceName    Namespace of the resource
     * @param ResourceType          $baseType         Base type of the 
     *                                                resource, if exists 
     * @param boolean               $isAbstract       Whether resource is abstract
     * 
     * @throws InvalidArgumentException
     */
    public function __construct($instanceType, $resourceTypeKind, $name, 
        $namespaceName = null, ResourceType $baseType = null, 
        $isAbstract = false
    ) {
        $this->_type = $instanceType;
        if ($resourceTypeKind == ResourceTypeKind::PRIMITIVE) {
            if ($baseType != null) {
                throw new \InvalidArgumentException(
                    Messages::resourceTypeNoBaseTypeForPrimitive()
                );
            }

            if ($isAbstract) {
                throw new \InvalidArgumentException(
                    Messages::resourceTypeNoAbstractForPrimitive()
                );
            }

            if (!($instanceType instanceof IType)) {
                throw new \InvalidArgumentException(
                    Messages::resourceTypeTypeShouldImplementIType('$instanceType')
                );
            }
        } else {
            if (!($instanceType instanceof \ReflectionClass)) {
                throw new \InvalidArgumentException(
                    Messages::resourceTypeTypeShouldReflectionClass('$instanceType')
                );
            }
        }

        $this->_resourceTypeKind = $resourceTypeKind;
        $this->_name = $name;
        $this->_baseType = $baseType;        
        $this->_namespaceName = $namespaceName; 
        $this->_fullName 
            = is_null($namespaceName) ? $name : $namespaceName . '.' . $name;
        $this->_abstractType = $isAbstract;
        $this->_isMediaLinkEntry = false;
        $this->_customState = null;
        $this->_arrayToDetectLoopInComplexBag = array();
        //TODO: Set MLE if base type has MLE Set
    }

    /**
     * Get reference to ResourceType for base class
     * 
     * @return ResourceType
     */
    public function getBaseType()
    {
        return $this->_baseType;
    }

    /**
     * To check whether this resource type has base type
     * 
     * @return boolean True if base type is defined, false otherwise
     */
    public function hasBaseType()
    {
        return !is_null($this->_baseType);
    }

    /**
     * To get custom state object for this type
     * 
     * @return Object
     */
    public function getCustomState()
    {
        return $this->_customState;
    }

    /**
     * To set custom state object for this type
     * 
     * @param Object $object The custom object.
     * 
     * @return void
     */
    public function setCustomState($object)
    {
        $this->_customState = $object;
    }

    /**
     * Get the instance type. If the resource type describes a complex or entity type
     * then this function returns refernece to ReflectionClass instance for the type.
     * If resource type describes a primitive type then this function returns ITYpe.
     * 
     * @return ReflectionClass/IType
     */
    public function getInstanceType()
    {
        return $this->_type;
    }

    /**
     * Get name of the type described by this resource type
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get the namespace under which the type described by this resource type is
     * defined.
     * 
     * @return string
     */
    public function getNamespace()
    {
        return $this->_namespaceName;
    }

    /**
     * Get full name (namespacename.typename) of the type described by this resource 
     * type.
     * 
     * @return string
     */
    public function getFullName()
    {
        return $this->_fullName;
    }

    /**
     * To check whether the type described by this resource type is abstract or not
     * 
     * @return boolean True if type is abstract else False
     */
    public function isAbstract()
    {
        return $this->_abstractType;
    }

    /**
     * To get the kind of type described by this resource class
     * 
     * @return ResourceTypeKind
     */
    public function getResourceTypeKind()
    {
        return $this->_resourceTypeKind;
    }

    /**
     * To check whether the type described by this resource type is MLE
     * 
     * @return boolean True if type is MLE else False
     */
    public function isMediaLinkEntry()
    {
        return $this->_isMediaLinkEntry;
    }

    /**
     * Set the resource type as MLE or non-MLE
     * 
     * @param boolean $isMLE True to set as MLE, false for non-MLE
     * 
     * @return void
     */
    public function setMediaLinkEntry($isMLE)
    {
        if ($this->_resourceTypeKind != ResourceTypeKind::ENTITY) {
            throw new InvalidOperationException(
                Messages::resourceTypeHasStreamAttributeOnlyAppliesToEntityType()
            );
        }

        $this->_isMediaLinkEntry = $isMLE;
    }

    /**      
     * Add a property belongs to this resource type instance
     * 
     * @param ResourceProperty $property Property to add
     * 
     * @throws InvalidOperationException
     * @return void
     */
    public function addProperty(ResourceProperty $property)
    {
        if ($this->_resourceTypeKind == ResourceTypeKind::PRIMITIVE) {
            throw new InvalidOperationException(
                Messages::resourceTypeNoAddPropertyForPrimitive()
            );
        }

        $name = $property->getName();
        foreach (array_keys($this->_propertiesDeclaredOnThisType) as $propertyName) {
            if (strcasecmp($propertyName, $name) == 0) {
                throw new InvalidOperationException(
                    Messages::resourceTypePropertyWithSameNameAlreadyExists(
                        $propertyName, $this->_name
                    )
                );
            }
        }

        if ($property->isKindOf(ResourcePropertyKind::KEY)) {
            if ($this->_resourceTypeKind != ResourceTypeKind::ENTITY) {
                throw new InvalidOperationException(
                    Messages::resourceTypeKeyPropertiesOnlyOnEntityTypes()
                );
            }

            if ($this->_baseType != null) {
                throw new InvalidOperationException(
                    Messages::resourceTypeNoKeysInDerivedTypes()
                );
            }
        }

        if ($property->isKindOf(ResourcePropertyKind::ETAG) 
            && ($this->_resourceTypeKind != ResourceTypeKind::ENTITY)
        ) {
            throw new InvalidOperationException(
                Messages::resourceTypeETagPropertiesOnlyOnEntityTypes()
            );
        }

        //Check for Base class properties
        $this->_propertiesDeclaredOnThisType[$name] = $property;
        // Set $this->_allProperties to null, this is very important because the 
        // first call to getAllProperties will initilaize $this->_allProperties, 
        // further call to getAllProperties will not reinitialize _allProperties
        // so if addProperty is called after calling getAllProperties then the 
        // property just added will not be reflected in $this->_allProperties
        unset($this->_allProperties);
        $this->_allProperties = array();
    }

    /**
     * Get collection properties belongs to this resource type (excluding base class
     * properties). This function returns  empty array in case of resource type
     * for primitive types.
     * 
     * @return array(name, ResourceProperty)
     */
    public function getPropertiesDeclaredOnThisType()
    {
        return $this->_propertiesDeclaredOnThisType;
    }

    /**
     * Get collection properties belongs to this resource type including base class
     * properties. This function returns  empty array in case of resource type
     * for primitive types.
     * 
     * @return array(name, ResourceProperty)
     */
    public function getAllProperties()
    {
        if (empty($this->_allProperties)) {
            if ($this->_baseType != null) {
                $this->_allProperties = $this->_baseType->getAllProperties();
            }

            $this->_allProperties = array_merge(
                $this->_allProperties, $this->_propertiesDeclaredOnThisType
            );
        }

        return $this->_allProperties;
    }

    /**     
     * Get collection key properties belongs to this resource type. This 
     * function returns non-empty array only for resource type representing 
     * an entity type.
     *  
     * @return array(name, ResourceProperty)
     */
    public function getKeyProperties()
    {
        if (empty($this->_keyProperties)) {
            $baseType = $this;
            while ($baseType->_baseType != null) {
                $baseType = $baseType->_baseType;
            }

            foreach ($baseType->_propertiesDeclaredOnThisType 
                as $propertyName => $resourceProperty
            ) {
                if ($resourceProperty->isKindOf(ResourcePropertyKind::KEY)) {
                    $this->_keyProperties[$propertyName] = $resourceProperty;
                }
            }
        }

        return $this->_keyProperties;
    }

    /**
     * Get collection of e-tag properties belongs to this type.
     * 
     * @return array(name, ResourceProperty)
     */
    public function getETagProperties()
    {
        if (empty ($this->_etagProperties)) {
            foreach ($this->getAllProperties() 
                as $propertyName => $resourceProperty
            ) {
                if ($resourceProperty->isKindOf(ResourcePropertyKind::ETAG)) {
                    $this->_etagProperties[$propertyName] = $resourceProperty;
                }
            }
        }

        return $this->_etagProperties;
    }

    /**
     * To check this type has any eTag properties
     * 
     * @return boolean
     */
    public function hasETagProperties()
    {
        $this->getETagProperties();
        return !empty($this->_etagProperties);
    }

    /**
     * Try to get ResourceProperty for a property defined for this resource type
     * excluding base class properties
     * 
     * @param string $propertyName The name of the property to resolve.
     * 
     * @return ResourceProperty or NULL
     */
    public function tryResolvePropertyTypeDeclaredOnThisTypeByName($propertyName)
    {
        if (array_key_exists($propertyName, $this->_propertiesDeclaredOnThisType)) {
            return $this->_propertiesDeclaredOnThisType[$propertyName];
        }

        return null;
    }

    /** 
     * Try to get ResourceProperty for a property defined for this resource type
     * including base class properties
     * 
     * @param string $propertyName The name of the property to resolve.
     * 
     * @return ResourceProperty or NULL
     */
    public function tryResolvePropertyTypeByName($propertyName)
    {
        if (array_key_exists($propertyName, $this->getAllProperties())) {
            return $this->_allProperties[$propertyName];
        }

        return null;
    }

    /**
     * Add a named stream belongs to this resource type instance
     * 
     * @param ResourceStreamInfo $namedStream ResourceStreamInfo instance describing
     *                                        the named stream to add.
     * 
     * @return void
     * 
     * @throws InvalidOperationException
     */
    public function addNamedStream(ResourceStreamInfo $namedStream)
    {
        if ($this->_resourceTypeKind != ResourceTypeKind::ENTITY) {
            throw new InvalidOperationException(
                Messages::resourceTypeNamedStreamsOnlyApplyToEntityType()
            );
        }

        $name = $namedStream->getName();
        foreach (array_keys($this->_namedStreamsDeclaredOnThisType) 
            as $namedStreamName
        ) {
            if (strcasecmp($namedStreamName, $name) == 0) {
                throw new InvalidOperationException(
                    Messages::resourceTypeNamedStreamWithSameNameAlreadyExists(
                        $name, $this->_name
                    )
                );
            }
        }

        $this->_namedStreamsDeclaredOnThisType[$name] = $namedStream;
        // Set $this->_allNamedStreams to null, the first call to getAllNamedStreams
        // will initilaize $this->_allNamedStreams, further call to 
        // getAllNamedStreams will not reinitialize _allNamedStreams
        // so if addNamedStream is called after calling getAllNamedStreams then the
        // property just added will not be reflected in $this->_allNamedStreams
        unset($this->_allNamedStreams);
        $this->_allNamedStreams = array();
    }

    /**
     * Get collection of ResourceStreamInfo describing the named streams belongs 
     * to this resource type (excluding base class properties)
     * 
     * @return array(name, ResourceStreamInfo)
     */
    public function getNamedStreamsDeclaredOnThisType()
    {
        return $this->_namedStreamsDeclaredOnThisType;
    }

    /**
     * Get collection of ResourceStreamInfo describing the named streams belongs 
     * to this resource type including base class named streams.
     * 
     * @return array(name, ResourceStreamInfo)
     */
    public function getAllNamedStreams()
    {
        if (empty($this->_allNamedStreams)) {
            if ($this->_baseType != null) {
                $this->_allNamedStreams = $this->_baseType->getAllNamedStreams();
            }

            $this->_allNamedStreams 
                = array_merge(
                    $this->_allNamedStreams, 
                    $this->_namedStreamsDeclaredOnThisType
                );
        }

        return $this->_allNamedStreams;
    }

    /** 
     * Try to get ResourceStreamInfo for a named stream defined for this
     * resource type excluding base class named streams
     * 
     * @param string $namedStreamName Name of the named stream to resolve.
     * 
     * @return ResourceStreamInfo or NULL
     */
    public function tryResolveNamedStreamDeclaredOnThisTypeByName($namedStreamName)
    {
        if (array_key_exists($namedStreamName, $this->_namedStreamsDeclaredOnThisType)) {
            return $this->_namedStreamsDeclaredOnThisType[$namedStreamName];
        }

        return null;
    }

    /** 
     * Try to get ResourceStreamInfo for a named stream defined for this resource 
     * type including base class named streams
     * 
     * @param string $namedStreamName Name of the named stream to resolve.
     * 
     * @return ResourceStreamInfo or NULL
     */
    public function tryResolveNamedStreamByName($namedStreamName)
    {
        if (array_key_exists($namedStreamName, $this->getAllNamedStreams())) {
            return $this->_allNamedStreams[$namedStreamName];
        }

        return null;
    }

    /**
     * Check this resource type instance has named stream associated with it
     * Note: This is an internal method used by library. Devs don't use this.
     * 
     * @return boolean true if resource type instance has named stream else false
     */
    public function hasNamedStream()
    {   
        // Note: Calling this method will initialize _allNamedStreams 
        // and _hasNamedStreams flag to a boolean value
        // from null depending on the current state of _allNamedStreams 
        // array, so method should be called only after adding all 
        // named streams
        if (is_null($this->_hasNamedStreams)) {
            $this->getAllNamedStreams();
            $this->_hasNamedStreams = !empty($this->_allNamedStreams);
        }
        
        return $this->_hasNamedStreams;
    }

    /**
     * Check this resource type instance has bag property associated with it
     * Note: This is an internal method used by library. Devs don't use this.
     * 
     * @param array(mixed) &$arrayToDetectLoopInComplexType array for detecting loop.
     * 
     * @return boolean true if resource type instance has bag property else false
     */
    public function hasBagProperty(&$arrayToDetectLoopInComplexType)
    {        
        // Note: Calling this method will initialize _bagProperties 
        // and _hasBagProperty flag to a boolean value
        // from null depending on the current state of 
        // _propertiesDeclaredOnThisType array, so method
        // should be called only after adding all properties
        if (is_null($this->_hasBagProperty)) {
            if ($this->_baseType != null 
                && $this->_baseType->hasBagProperty($arrayToDetectLoopInComplexType)
            ) {
                        $this->_hasBagProperty = true;
            } else {
                foreach ($this->_propertiesDeclaredOnThisType as $resourceProperty) {
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
                        //
                        //Here the resource type of Customer::Address and 
                        //Customer::Address::AltAddress
                        //are same, this is a loop, we need to detect 
                        //this and avoid infinite recursive loop.
                        //
                        $count = count($arrayToDetectLoopInComplexType);
                        $foundLoop = false;
                        for ($i = 0; $i < $count; $i++) {
                            if ($arrayToDetectLoopInComplexType[$i] === $resourceProperty->getResourceType()) {
                                $foundLoop = true;
                                break;
                            }
                        }

                        if (!$foundLoop) {
                            $arrayToDetectLoopInComplexType[$count] = $resourceProperty->getResourceType();
                            $hasBagInComplex = $resourceProperty->getResourceType()->hasBagProperty($arrayToDetectLoopInComplexType);
                            unset($arrayToDetectLoopInComplexType[$count]);
                        }
                    }

                    if ($resourceProperty->isKindOf(ResourcePropertyKind::BAG) 
                        || $hasBagInComplex
                    ) {
                        $this->_hasBagProperty = true;
                        break;
                    }
                }
            }
        }
        
        return $this->_hasBagProperty;
    }
    
    /** 
     * Validate the type
     * 
     * @return void
     * 
     * @throws InvalidOperationException
     */
    public function validateType()
    {
        $keyProperties = $this->getKeyProperties();
        if (($this->_resourceTypeKind == ResourceTypeKind::ENTITY) && empty($keyProperties)) {
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
     * of (derived from the) given resource type
     * 
     * @param ResourceType $resourceType Another resource type.
     * 
     * @return boolean
     */
    public function isAssignableFrom(ResourceType $resourceType)
    {        
        $base = $this;
        while ($base != null) {
            if ($resourceType == $base) {
                return true;
            }
            
            $base = $base->_baseType;
        }

        return false;
    }

    /**     
     * Get predefined ResourceType for a primitive type
     *  
     * @param EdmPrimitiveType $typeCode Typecode of primitive type
     *
     * @return ResourceType
     * 
     * @throws InvalidArgumentException
     */
    public static function getPrimitiveResourceType($typeCode)
    {
        switch($typeCode) {
        case EdmPrimitiveType::BINARY:
            return new ResourceType(
                new Binary(), ResourceTypeKind::PRIMITIVE, 
                'Binary', 'Edm'
            );
            break;
        case EdmPrimitiveType::BOOLEAN:
            return new ResourceType(
                new Boolean(), 
                ResourceTypeKind::PRIMITIVE, 
                'Boolean', 'Edm'
            );
            break;
        case EdmPrimitiveType::BYTE:
            return new ResourceType(
                new Byte(), 
                ResourceTypeKind::PRIMITIVE, 
                'Byte', 'Edm'
            );
            break;
        case EdmPrimitiveType::DATETIME:
            return new ResourceType(
                new DateTime(), 
                ResourceTypeKind::PRIMITIVE, 
                'DateTime', 'Edm'
            );
            break;
        case EdmPrimitiveType::DECIMAL:
            return new ResourceType(
                new Decimal(), 
                ResourceTypeKind::PRIMITIVE, 
                'Decimal', 'Edm'
            );
            break;
        case EdmPrimitiveType::DOUBLE:
            return new ResourceType(
                new Double(), 
                ResourceTypeKind::PRIMITIVE, 
                'Double', 'Edm'
            );
            break;
        case EdmPrimitiveType::GUID:
            return new ResourceType(
                new Guid(), 
                ResourceTypeKind::PRIMITIVE, 
                'Guid', 'Edm'
            );
            break;
        case EdmPrimitiveType::INT16:
            return new ResourceType(
                new Int16(), 
                ResourceTypeKind::PRIMITIVE, 
                'Int16', 'Edm'
            );
            break;
        case EdmPrimitiveType::INT32:
            return new ResourceType(
                new Int32(), 
                ResourceTypeKind::PRIMITIVE, 
                'Int32', 'Edm'
            );
            break;
        case EdmPrimitiveType::INT64:
            return new ResourceType(
                new Int64(), 
                ResourceTypeKind::PRIMITIVE, 
                'Int64', 'Edm'
            );
            break;
        case EdmPrimitiveType::SBYTE:
            return new ResourceType(
                new SByte(), 
                ResourceTypeKind::PRIMITIVE, 
                'SByte', 'Edm'
            );
            break;
        case EdmPrimitiveType::SINGLE:
            return new ResourceType(
                new Single(), 
                ResourceTypeKind::PRIMITIVE, 
                'Single', 'Edm'
            );
            break;
        case EdmPrimitiveType::STRING:
            return new ResourceType(
                new String(), 
                ResourceTypeKind::PRIMITIVE, 
                'String', 'Edm'
            );
            break;
        default:
            throw new \InvalidArgumentException(
                Messages::commonNotValidPrimitiveEDMType(
                    '$typeCode', 'getPrimitiveResourceType'
                )
            );
        }    
    }
}

?>