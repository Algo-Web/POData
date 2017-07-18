<?php

namespace POData\Providers\Metadata\Type;

use POData\Common\Messages;
use POData\Common\NotImplementedException;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;

/**
 * Class Navigation.
 */
class Navigation implements INavigationType
{
    /**
     * The type describing this navigation.
     *
     * @var ResourceType
     */
    private $resourceType;

    /**
     * Creates new instance of Navigation.
     *
     * @param ResourceType $resourceType The resource type for this navigation
     *
     * @throws \InvalidArgumentException when the resource type kind is not complex or entity
     */
    public function __construct($resourceType)
    {
        if (ResourceTypeKind::COMPLEX() != $resourceType->getResourceTypeKind()
            && ResourceTypeKind::ENTITY() != $resourceType->getResourceTypeKind()
        ) {
            throw new \InvalidArgumentException(Messages::navigationInvalidResourceType());
        }

        $this->resourceType = $resourceType;
    }

    //Begin implementation of IType interface

    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::NAVIGATION;
    }

    /**
     * Checks this type (Navigation) is compatible with another type
     * Note: implementation of IType::isCompatibleWith.
     *
     * @param IType $type Type to check compatibility
     *
     * @return bool
     */
    public function isCompatibleWith(IType $type)
    {
        if (!($type instanceof self)) {
            return false;
        }

        return 0 == strcmp($type->resourceType->getFullName(), $this->resourceType->getFullName());
    }

    /**
     * Validate a value in Astoria uri is in a format for this type
     * Note: implementation of IType::validate.
     *
     * @param string $value     The value to validate
     * @param string &$outValue The stripped form of $value that can
     *                          be used in PHP expressions
     *
     * @return bool
     */
    public function validate($value, &$outValue)
    {
        if (!$value instanceof self) {
            return false;
        }

        $outValue = $value;

        return true;
    }

    /**
     * Gets full name of this type in EDM namespace
     * Note: implementation of IType::getFullTypeName.
     *
     * @return string
     */
    public function getFullTypeName()
    {
        return $this->resourceType->getFullName();
    }

    /**
     * Converts the given string value to navigation type.
     *
     * @param string $stringValue value to convert
     *
     * @throws NotImplementedException
     * @return void
     */
    public function convert($stringValue)
    {
        throw new NotImplementedException();
    }

    /**
     * Convert the given value to a form that can be used in OData uri.
     *
     * @param mixed $value value to convert
     *
     * @throws NotImplementedException
     * @return void
     */
    public function convertToOData($value)
    {
        throw new NotImplementedException();
    }

    /**
     * Gets full name of the type implementing this interface in EDM namespace
     * Note: implementation of IType::getFullTypeName.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getFullTypeName();
    }

    //End implementation of IType interface

    //Begin implementation of INavigationType interface

    /**
     * Gets the resource type associated with the navigation type.
     *
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    //End implementation of INavigationType interface
}
