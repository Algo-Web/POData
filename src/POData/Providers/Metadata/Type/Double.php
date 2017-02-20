<?php

namespace POData\Providers\Metadata\Type;

/**
 * Class Double.
 */
class Double implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::DOUBLE;
    }

    /**
     * Checks this type is compatible with another type
     * Note: implementation of IType::isCompatibleWith.
     *
     * @param IType $type Type to check compatibility
     *
     * @return bool
     */
    public function isCompatibleWith(IType $type)
    {
        switch ($type->getTypeCode()) {
            case TypeCode::BYTE:
            case TypeCode::SBYTE:
            case TypeCode::INT16:
            case TypeCode::INT32:
            case TypeCode::INT64:
            case TypeCode::SINGLE:
            case TypeCode::DOUBLE:
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
    public function validate($value, &$outValue)
    {
        // By default all real numbers are considered as 'Double'.
        // One can explicitly force an integral value to 'Double' with D or d postfix
        if (1 !== preg_match('/^(\-)?\d+(\.{1}\d+)?([Ee]{1}([\+\-]{1})?\d+)?([dD]{1})?$/', $value)) {
            return false;
        }

        $outValue = rtrim($value, 'dD');

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
        return 'Edm.Double';
    }

    /**
     * Converts the given string value to double type.
     *
     * @param string $stringValue value to convert
     *
     * @return float
     */
    public function convert($stringValue)
    {
        return floatval($stringValue);
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
    public function convertToOData($value)
    {
        return $value . 'D';
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
}
