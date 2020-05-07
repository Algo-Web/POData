<?php

declare(strict_types=1);

namespace POData\Providers\Metadata\Type;

use POData\Common\NotImplementedException;

/**
 * Class Char.
 */
class Char implements IType
{
    const A               = 65;
    const Z               = 90;
    const SMALL_A         = 97;
    const SMALL_Z         = 122;
    const F               = 70;
    const SMALL_F         = 102;
    const ZERO            = 48;
    const NINE            = 57;
    const TAB             = 9;
    const NEWLINE         = 10;
    const CARRIAGE_RETURN = 13;
    const SPACE           = 32;

    /**
     * Checks a character is whitespace.
     *
     * @param char|string $char character to check
     *
     * @return bool
     */
    public static function isWhiteSpace($char)
    {
        $asciiVal = ord($char);

        return self::SPACE == $asciiVal
            || self::TAB == $asciiVal
            || self::CARRIAGE_RETURN == $asciiVal
            || self::NEWLINE == $asciiVal;
    }

    /**
     * Checks a character is letter or digit.
     *
     * @param char|string $char character to check
     *
     * @return bool
     */
    public static function isLetterOrDigit($char)
    {
        return self::isDigit($char) || self::isLetter($char);
    }

    /**
     * Checks a character is digit.
     *
     * @param char|string $char character to check
     *
     * @return bool
     */
    public static function isDigit($char)
    {
        $asciiVal = ord($char);

        return self::ZERO <= $asciiVal && self::NINE >= $asciiVal;
    }

    /**
     * Checks a character is letter.
     *
     * @param char|string $char character to check
     *
     * @return bool
     */
    public static function isLetter($char)
    {
        $asciiVal = ord($char);

        return ($asciiVal >= self::A && $asciiVal <= self::Z)
            || ($asciiVal >= self::SMALL_A && $asciiVal <= self::SMALL_Z);
    }

    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::CHAR();
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
            case TypeCode::BYTE():
            case TypeCode::CHAR():
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
     * @throws NotImplementedException
     * @return bool
     */
    public function validate($value, &$outValue)
    {
        //No EDM Char primitive type
        throw new NotImplementedException();
    }

    /**
     * Converts the given string value to char type.
     * Note: This function will not perform any conversion.
     *
     * @param string $stringValue The value to convert
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
     * @param mixed $value The value to convert
     *
     * @return string
     */
    public function convertToOData($value)
    {
        return $value;
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
        return 'System.Char';
    }
}
