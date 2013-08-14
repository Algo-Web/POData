<?php


namespace ODataProducer\Providers\Metadata\Type;

/**
 * Class SByte represents a Signed Byte
 *
 * @package ODataProducer\Providers\Metadata\Type
 */
class SByte implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode
     *   
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::SBYTE;
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
        return ($type->getTypeCode() == TypeCode::SBYTE);
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
        if (strlen($value) != 1) {
            return false;
        }
        
        $outValue = $value;
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
        return 'Edm.SByte';
    }

    /**
     * Converts the given string value to sbyte type.
     * Note: This function will not perfrom any conversion.
     * 
     * @param string $stringValue value to convert
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
     * @param mixed $value value to convert.
     * 
     * @return string
     */
    public function convertToOData($value)
    {
        return $value;
    }
}