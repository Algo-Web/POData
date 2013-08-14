<?php

namespace ODataProducer\Providers\Metadata\Type;

/**
 * Class Binary
 * @package ODataProducer\Providers\Metadata\Type
 */
class Binary implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode
     *   
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::BINARY;
    }

    /**
     * Checks this type is compactible with another type
     * Note: implementation of IType::isCompatibleWith
     * 
     * @param IType $type Type to check compactibility
     * 
     * @return boolean 
     */
    public function isCompatibleWith(IType $type)
    {
        return ($type->getTypeCode() == TypeCode::BINARY);
    }

    /**
     * Validate a value in Astoria uri is in a format for this type
     * Note: implementation of IType::validate
     * 
     * @param string $value     The value to validate 
     * @param string &$outValue The stripped form of $value that can 
     *                          be used in PHP expressions
     * 
     * @return boolean
     */
    public function validate($value, &$outValue)
    {
        $length = strlen($value);
        if ((strpos($value, 'binary\'') === 0) && ($length > 7)) {
            $value = substr($value, 7, $length - 7);
            $length -= 7;
        } else if ((strpos($value, 'X\'') === 0 
            || strpos($value, 'x\'') === 0) && ($length > 2)
        ) {
            $value = substr($value, 2, $length - 2);
            $length -= 2;
        } else {
            return false;
        }
        
        if ($value[$length - 1] != '\'') {
            return false;    
        }
        
        $value = rtrim($value, "'");
        
        if (!self::validateWithoutPrefix($value, $outValue)) {
            $outValue = null;
            return false;
        }
        
        return true;
    }

    /**
     * Gets full name of this type in EDM namespace
     * Note: implementation of IType::getFullTypeName
     * 
     * @return string
     */
    public function getFullTypeName()
    {
        return 'Edm.Binary';
    }

    /**
     * Converts the given string value to binary type.
     * Note: This function will not perfrom any conversion.
     * 
     * @param string $stringValue The string value to convert.
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
     * function will not perform any check for nullability 
     * 
     * @param mixed $value The binary data
     * 
     * @return string Hexa decimal represenation of the binary data
     *                     prefixed with the 'binary'
     */
    public function convertToOData($value)
    {
        return 'binary\'' . bin2hex($value). '\'';
    }

    /**
     * Checks a value is binary
     * 
     * @param string $value     value to check in base64 form
     * @param string &$outValue Processed value 
     * 
     * @return boolean
     */
    public static function validateWithoutPrefix($value, &$outValue)
    {
        $length = strlen($value);
        if ($length == 0 || $length%2 != 0) {
            return false;    
        }
        
        $outValue = array();
        $outValIndex = 0;
        $valueIndex = 0;
        while ($valueIndex < $length) {
            $ch0 = $value[$valueIndex];
            $ch1 = $value[$valueIndex + 1];
            if (!Char::isHexDigit($ch0) || !Char::isHexDigit($ch1)) {
                $outValue = null;
                return false;
            }
            
            $ch0 = self::hexCharToNibble($ch0);
            $ch1 = self::hexCharToNibble($ch1);
            if ($ch0 == -1 || $ch1 == -1) {
                $outValue = null;
                return false;
            }
            
            $outValue[$outValIndex] = $ch0 << 4 + $ch1;
            $valueIndex += 2;
            $outValIndex++;
        }
        
        return true;
    }

    /**
     * Checks equality of binary values
     *     
     * @param string $binary1 First binary value
     * @param string $binary2 Second binary value
     * 
     * @return boolean
     */
    public static function binaryEqual($binary1, $binary2) 
    {    
        if (is_null($binary1) || is_null($binary2)) {
            return false;
        }
        
        $length1 = length($binary1);
        $length2 = length($binary2);

        if ($length1 != $length2) {
            return false;
        }

        for ($i = 0; $i < $length1; $i++) {
            if ($binary1[$i] != $binary2[$i]) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Gets nibble of a hexa char
     * 
     * @param char $char The hexa char
     * 
     * @return int
     */
    protected static function hexCharToNibble($char) 
    {
        switch ($char) {
        case '0':
            return 0;
        case '1':
            return 1;
        case '2':
            return 2;
        case '3':
            return 3;
        case '4':
            return 4;
        case '5':
            return 5;
        case '6':
            return 6;
        case '7':
            return 7;
        case '8':
            return 8;
        case '9':
            return 9;
        case 'a':
        case 'A':
            return 10;
        case 'b':    
        case 'B':
            return 11;
        case 'c':
        case 'C':
            return 12;
        case 'd':
        case 'D':
            return 13;
        case 'e':
        case 'E':
            return 14;
        case 'f':
        case 'F':
            return 15;
        default:
            return -1;
        }
    }
}