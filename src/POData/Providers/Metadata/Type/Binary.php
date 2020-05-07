<?php

declare(strict_types=1);

namespace POData\Providers\Metadata\Type;

/**
 * Class Binary.
 */
class Binary implements IType
{
    /**
     * Checks equality of binary values.
     *
     * @param string $binary1 First binary value
     * @param string $binary2 Second binary value
     *
     * @return bool
     */
    public static function binaryEqual($binary1, $binary2)
    {
        //str cmp will return true if they are both null, so check short circuit that..
        if (null === $binary1 || null === $binary2) {
            return false;
        }

        return 0 == strcmp($binary1, $binary2);
    }

    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::BINARY();
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
        return TypeCode::BINARY() == $type->getTypeCode();
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
        $length  = strlen($value);
        $trimVal = $value;
        if ((0 === strpos($trimVal, 'binary\'')) && (7 < $length)) {
            $trimVal = substr($trimVal, 7, $length - 7);
            $length -= 7;
        } elseif ((0 === strpos($trimVal, 'X\'') || 0 === strpos($trimVal, 'x\'')) && (2 < $length)
        ) {
            $trimVal = substr($trimVal, 2, $length - 2);
            $length -= 2;
        } else {
            return false;
        }

        if ('\'' != $trimVal[$length - 1]) {
            return false;
        }

        $trimVal = rtrim($trimVal, "'");

        if (!self::validateWithoutPrefix($trimVal, $outValue)) {
            $outValue = null;

            return false;
        }

        return true;
    }

    /**
     * Checks a value is binary.
     *
     * @param string $value     value to check in base64 form
     * @param string &$outValue Processed value
     *
     * @return bool
     */
    public static function validateWithoutPrefix($value, &$outValue)
    {
        $length = strlen($value);
        if (0 == $length || 0 != $length % 2) {
            return false;
        }

        if (!ctype_xdigit($value)) {
            $outValue = null;

            return false;
        }

        $outValue    = [];
        $outValIndex = 0;
        $valueIndex  = 0;
        while ($valueIndex < $length) {
            $ch0 = $value[$valueIndex];
            $ch1 = $value[$valueIndex + 1];

            $outValue[$outValIndex] = hexdec($ch0) << 4 + hexdec($ch1);
            $valueIndex += 2;
            ++$outValIndex;
        }

        return true;
    }

    /**
     * Converts the given string value to binary type.
     * Note: This function will not perform any conversion.
     *
     * @param string $stringValue The string value to convert
     *
     * @return string
     */
    public function convert($stringValue)
    {
        return $stringValue;
    }

    /**
     * Convert the given value to a form that can be used in OData uri.
     * Note: The calling function should not pass null value, as this
     * function will not perform any check for nullability.
     *
     * @param mixed $value The binary data
     *
     * @return string Hexadecimal representation of the binary data prefixed with the 'binary'
     */
    public function convertToOData($value)
    {
        return 'binary\'' . bin2hex($value) . '\'';
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

    /**
     * Gets full name of this type in EDM namespace
     * Note: implementation of IType::getFullTypeName.
     *
     * @return string
     */
    public function getFullTypeName()
    {
        return 'Edm.Binary';
    }
}
