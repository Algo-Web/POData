<?php

namespace POData\Providers\Metadata;

use InvalidArgumentException;
use POData\Common\Messages;
use POData\Providers\Metadata\Type\IType;

class ResourceProperty
{
    /**
     * Property name.
     *
     * @var string
     */
    private $name;

    /**
     * Property MIME type.
     *
     * @var string
     */
    private $mimeType;

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
    private $kind;

    /**
     * ResourceType describes this property.
     *
     * @var ResourceType
     */
    private $propertyResourceType;

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
        if (!$this->isValidPropertyName($name)) {
            throw new InvalidArgumentException(
                'Property name violates OData specification.'
            );
        }

        if (!$this->isValidResourcePropertyKind($kind)) {
            throw new InvalidArgumentException(
                Messages::resourcePropertyInvalidKindParameter('$kind')
            );
        }

        if (!$this->isResourceKindValidForPropertyKind($kind, $propertyResourceType->getResourceTypeKind())) {
            throw new InvalidArgumentException(
                Messages::resourcePropertyPropertyKindAndResourceTypeKindMismatch(
                    '$kind',
                    '$propertyResourceType'
                )
            );
        }

        $this->name = $name;
        $this->mimeType = $mimeType;
        $this->kind = $kind;
        $this->propertyResourceType = $propertyResourceType;
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
        return ($this->kind & $kind) == $kind;
    }

    /**
     * Get the property name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get property MIME type.
     *
     * @return string
     */
    public function getMIMEType()
    {
        return $this->mimeType;
    }

    /**
     * Get property kind.
     *
     * @return ResourcePropertyKind
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Get the resource type for this property.
     *
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->propertyResourceType;
    }

    /**
     * Get the kind of resource type.
     *
     * @return ResourceTypeKind
     */
    public function getTypeKind()
    {
        return $this->propertyResourceType->getResourceTypeKind();
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
        $type = $this->propertyResourceType->getInstanceType();
        assert($type instanceof IType == static::sIsKindOf($this->getKind(), ResourcePropertyKind::PRIMITIVE));
        return $type;
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
     * Checks whether supplied name meets OData specification.
     *
     * @param string $name Field name to be validated
     *
     * @return bool
     */
    private function isValidPropertyName($name)
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
    private function isValidResourcePropertyKind($kind)
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
    private function isResourceKindValidForPropertyKind($pKind, $rKind)
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
