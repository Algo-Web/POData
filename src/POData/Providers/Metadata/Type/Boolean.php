<?php

declare(strict_types=1);

namespace POData\Providers\Metadata\Type;

/**
 * Class Boolean.
 */
class Boolean implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode(): TypeCode
    {
        return TypeCode::BOOLEAN();
    }

    /**
     * Checks this type is compatible with another type
     * Note: implementation of IType::isCompatibleWith.
     *
     * @param IType $type Type to check compatibility
     *
     * @return bool
     */
    public function isCompatibleWith(IType $type): bool
    {
        return TypeCode::BOOLEAN() == $type->getTypeCode();
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
    public function validate($value, &$outValue): bool
    {
        if (0 != strcmp($value, 'true') && 0 != strcmp($value, 'false')) {
            return false;
        }

        $outValue = $value;

        return true;
    }

    /**
     * Convert the given value to a form that can be used in OData uri.
     * Note: The calling function should not pass null value, as this
     * function will not perform any check for nullability.
     *
     * @param mixed $value Value to convert
     *
     * @return string
     */
    public function convertToOData($value): string
    {
        if ($value) {
            return 'true';
        }

        return 'false';
    }

    /**
     * Converts the given string value to boolean type.
     *
     * @param string $stringValue String value to convert
     *
     * @return bool
     */
    public function convert($stringValue): bool
    {
        if (0 == strcmp($stringValue, 'true')) {
            return true;
        }

        return false;
    }

    /**
     * Gets full name of the type implementing this interface in EDM namespace
     * Note: implementation of IType::getFullTypeName.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getFullTypeName();
    }

    /**
     * Gets full name of this type in EDM namespace
     * Note: implementation of IType::getFullTypeName.
     *
     * @return string
     */
    public function getFullTypeName(): string
    {
        return 'Edm.Boolean';
    }
}
