<?php

namespace POData\Providers\Metadata;

use POData\Common\Messages;
use POData\Providers\Metadata\Type\IType;
use InvalidArgumentException;

class ResourceProperty
{
    /**
     * Property name.
     *
     * @var string
     */
    private $_name;

    /**
     * Property MIME type.
     *
     * @var string
     */
    private $_mimeType;

    /**
     * Property Kind, the possible values are:
     *  ResourceReference
     *  ResourceSetReference
     *  ComplexType
     *  ComplexType + Bag
     *  PrimitiveType
     *  PrimitiveType + Bag
     *  PrimitiveType + Key
     *  PrimitiveType + ETag.
     *
     * @var ResourcePropertyKind
     */
    private $_kind;

    /**
     * ResourceType describes this property.
     *
     * @var ResourceType
     */
    private $_propertyResourceType;

    /**
     * @param string               $name                 Name of the property
     * @param string               $mimeType             Mime type of the property
     * @param ResourcePropertyKind $kind                 The kind of property
     * @param ResourceType         $propertyResourceType ResourceType of the property
     *
     * @throws InvalidArgumentException
     */
    public function __construct($name, $mimeType, $kind, ResourceType $propertyResourceType)
    {
        if (!$this->_isValidPropertyName($name)) {
            throw new InvalidArgumentException(
                'Property name violates OData specification.'
            );
        }

        if (!$this->_isValidResourcePropertyKind($kind)) {
            throw new InvalidArgumentException(
                Messages::resourcePropertyInvalidKindParameter('$kind')
            );
        }

        if (!$this->_isResourceKindValidForPropertyKind($kind, $propertyResourceType->getResourceTypeKind())) {
            throw new InvalidArgumentException(
                Messages::resourcePropertyPropertyKindAndResourceTypeKindMismatch(
                    '$kind',
                    '$propertyResourceType'
                )
            );
        }

        $this->_name = $name;
        $this->_mimeType = $mimeType;
        $this->_kind = $kind;
        $this->_propertyResourceType = $propertyResourceType;
    }

    /**
     * Check whether current property is of kind specified by the parameter.
     *
     * @param ResourcePropertyKind $kind kind to check
     *
     * @return bool
     */
    public function isKindOf($kind)
    {
        return ($this->_kind & $kind) == $kind;
    }

    /**
     * Get the property name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get property MIME type.
     *
     * @return string
     */
    public function getMIMEType()
    {
        return $this->_mimeType;
    }

    /**
     * Get property kind.
     *
     * @return ResourcePropertyKind
     */
    public function getKind()
    {
        return $this->_kind;
    }

    /**
     * Get the resource type for this property.
     *
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_propertyResourceType;
    }

    /**
     * Get the kind of resource type.
     *
     * @return ResourceTypeKind
     */
    public function getTypeKind()
    {
        return $this->_propertyResourceType->getResourceTypeKind();
    }

    /**
     * Get the instance type. If the property is of kind 'Complex',
     * 'ResourceReference' or 'ResourceSetReference' then this function returns
     * refernece to ReflectionClass instance for the type. If the property of
     * kind 'Primitive' then this function returns ITYpe instance for the type.
     *
     * @return \ReflectionClass|IType
     */
    public function getInstanceType()
    {
        return $this->_propertyResourceType->getInstanceType();
    }

    /**
     * Check one kind is of another kind.
     *
     * @param ResourcePropertyKind $kind1 First kind
     * @param ResourcePropertyKind $kind2 second kind
     *
     * @return bool
     */
    public static function sIsKindOf($kind1, $kind2)
    {
        return ($kind1 & $kind2) == $kind2;
    }

    /**
     * Checks whether supplied name meets OData specification
     *
     * @param string $name  Field name to be validated
     *
     * @return bool
     */
    private function _isValidPropertyName($name)
    {
        if (!isset($name) || !is_string($name) || empty($name)) {
            return false;
        }
        if ('_' == substr($name, 0, 1)) {
            return false;
        }
        return true;
    }

    /**
     * Checks whether resource property kind is valid or not.
     *
     * @param ResourcePropertyKind $kind The kind to validate
     *
     * @return bool
     */
    private function _isValidResourcePropertyKind($kind)
    {
        return
            !($kind != ResourcePropertyKind::RESOURCE_REFERENCE &&
            $kind != ResourcePropertyKind::RESOURCESET_REFERENCE &&
            $kind != ResourcePropertyKind::COMPLEX_TYPE &&
            ($kind != (ResourcePropertyKind::COMPLEX_TYPE | ResourcePropertyKind::BAG)) &&
            $kind != ResourcePropertyKind::PRIMITIVE &&
            ($kind != (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::BAG)) &&
            ($kind != (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY)) &&
            ($kind != (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::ETAG)));
    }

    /**
     * Check the specified resource kind is valid resource kind for property kind.
     *
     * @param ResourcePropertyKind $pKind The kind of resource property
     * @param ResourceTypeKind     $rKind The kind of resource type
     *
     * @return bool True if resource type kind and property kind matches
     *              otherwise false
     */
    private function _isResourceKindValidForPropertyKind($pKind, $rKind)
    {
        if (self::sIsKindOf($pKind, ResourcePropertyKind::PRIMITIVE)
            && $rKind != ResourceTypeKind::PRIMITIVE
        ) {
            return false;
        }

        if (self::sIsKindOf($pKind, ResourcePropertyKind::COMPLEX_TYPE)
            && $rKind != ResourceTypeKind::COMPLEX
        ) {
            return false;
        }

        if ((self::sIsKindOf($pKind, ResourcePropertyKind::RESOURCE_REFERENCE)
            || self::sIsKindOf($pKind, ResourcePropertyKind::RESOURCESET_REFERENCE))
            && $rKind != ResourceTypeKind::ENTITY
        ) {
            return false;
        }

        return true;
    }
}
