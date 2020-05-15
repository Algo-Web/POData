<?php

declare(strict_types=1);

namespace POData\Providers\Metadata\Type;

/**
 * Class Byte.
 */
class Byte implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode(): TypeCode
    {
        return TypeCode::BYTE();
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
        return TypeCode::BYTE() == $type->getTypeCode();
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
    public function validate(string $value, ?string &$outValue): bool
    {
        if (1 != strlen($value)) {
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
     * @param mixed $value The value to convert
     *
     * @return string
     */
    public function convertToOData($value): string
    {
        return $value;
    }

    /**
     * Converts the given string value to byte type.
     * Note: This function will not perform any conversion.
     *
     * @param string $stringValue string value to convert
     *
     * @return string
     */
    public function convert(string $stringValue): string
    {
        return $stringValue;
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
        return 'Edm.Byte';
    }
}
