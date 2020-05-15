<?php

declare(strict_types=1);

namespace POData\Providers\Metadata\Type;

/**
 * Class Int16.
 */
class Int16 implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode(): TypeCode
    {
        return TypeCode::INT16();
    }

    /**
     * Checks this type (Int16) is compatible with another type
     * Note: implementation of IType::isCompatibleWith.
     *
     * @param IType $type Type to check compatibility
     *
     * @return bool
     */
    public function isCompatibleWith(IType $type): bool
    {
        switch ($type->getTypeCode()) {
            case TypeCode::BYTE():
            case TypeCode::SBYTE():
            case TypeCode::INT16():
                return true;
        }

        return false;
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
        if (1 !== preg_match('/^(\-)?\d+$/', $value)) {
            return false;
        }

        $outValue = $value;

        return true;
    }

    /**
     * Converts the given string value to int type.
     *
     * @param string $stringValue string value convert
     *
     * @return int
     */
    public function convert(string $stringValue): int
    {
        return intval($stringValue);
    }

    /**
     * Convert the given value to a form that can be used in OData uri.
     * Note: The calling function should not pass null value, as this
     * function will not perform any check for nullability.
     *
     * @param mixed $value value to convert
     *
     * @return string
     */
    public function convertToOData($value): string
    {
        return $value;
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
        return 'Edm.Int16';
    }
}
