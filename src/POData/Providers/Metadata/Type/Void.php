<?php

namespace POData\Providers\Metadata\Type;

use POData\Common\NotImplementedException;

/**
 * Class Void
 * @package POData\Providers\Metadata\Type
 */
class Void implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode
     *   
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::VOID;
    }

    /**
     * Checks this type (Void) is compatible with another type
     * Note: implementation of IType::isCompatibleWith
     * 
     * @param IType $type Type to check compatibility
     * 
     * @return boolean 
     */
    public function isCompatibleWith(IType $type)
    {
        return ($type->getTypeCode() == TypeCode::VOID);
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
        //No EDM void primitive type
        throw new NotImplementedException();
    }

    /**
     * Gets full name of this type in EDM namespace
     * Note: implementation of IType::getFullTypeName
     * 
     * @return string
     */
    public function getFullTypeName()
    {
        return 'System.Void';
    }

    /**
     * Converts the given string value to void type.
     * 
     * @param string $stringValue value to convert
     * 
     * @return void
     * 
     * @throws NotImplementedException
     */
    public function convert($stringValue)
    {
        throw new NotImplementedException();
    }

    /**
     * Convert the given value to a form that can be used in OData uri.
     * 
     * @param string $value value to convert to OData
     * 
     * @return void
     * 
     * @throws NotImplementedException
     */
    public function convertToOData($value)
    {
        throw new NotImplementedException();
    }
}