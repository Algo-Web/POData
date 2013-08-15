<?php

namespace ODataProducer\Providers\Metadata\Type;

/**
 * Class Boolean
 * @package ODataProducer\Providers\Metadata\Type
 */
class Boolean implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode
     *   
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::BOOLEAN;
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
        return ($type->getTypeCode() == TypeCode::BOOLEAN);
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
        if (strcmp($value, 'true') != 0 && strcmp($value, 'false') != 0) {
            return false;
        }
        
        $outValue = $value;
        return true;
    }

    /**
     * Convert the given value to a form that can be used in OData uri. 
     * Note: The calling function should not pass null value, as this 
     * function will not perform any check for nullability 
     * 
     * @param mixed $value Value to convert.
     * 
     * @return string
     */
    public function convertToOData($value)
    {
        if ($value) {
            return 'true';
        }

        return 'false';
    }

    /**
     * Gets full name of this type in EDM namespace
     * Note: implementation of IType::getFullTypeName
     * 
     * @return string
     */
    public function getFullTypeName()
    {
        return 'Edm.Boolean';
    }

    /**
     * Converts the given string value to boolean type.
     * 
     * @param string $stringValue String value to convert.
     * 
     * @return boolean
     */
    public function convert($stringValue)
    {
        if (strcmp($stringValue, 'true') == 0) {
            return true;
        }

        return false;
    }
}