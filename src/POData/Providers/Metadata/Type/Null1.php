<?php

namespace POData\Providers\Metadata\Type;

use POData\Common\NotImplementedException;

/**
 * Class Null1.
 */
class Null1 implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::NULL1;
    }

    /**
     * Checks this type (Null) is compatible with another type
     * Note: implementation of IType::isCompatibleWith.
     *
     * @param IType $type Type to check compatibility
     *
     * @return bool
     */
    public function isCompatibleWith(IType $type)
    {
        throw new NotImplementedException();
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
        if (0 != strcmp($value, 'null')) {
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
        return 'System.NULL';
    }

    /**
     * Converts the given string value to null type.
     *
     * @param string $stringValue value to convert
     *
     * @return string
     */
    public function convert($stringValue)
    {
        if (0 == strcmp($stringValue, 'null')) {
            return;
        }

        return $stringValue;
    }

    /**
     * Convert the given value to a form that can be used in OData uri.
     *
     * @param mixed $value value to convert
     *
     * @throws NotImplementedException
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
}
