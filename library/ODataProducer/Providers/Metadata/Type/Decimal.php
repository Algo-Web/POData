<?php
/** 
 * Type to represent Decimal
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata_Type
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Providers\Metadata\Type;
/**
 * Type to represent Decimal
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata_Type
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class Decimal implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode
     *   
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::DECIMAL;
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
        switch ($type->getTypeCode()) {
        case TypeCode::BYTE:
        case TypeCode::SBYTE:
        case TypeCode::INT16:
        case TypeCode::INT32:
        case TypeCode::INT64:
        case TypeCode::DOUBLE:
        case TypeCode::DECIMAL:
            return true;
        }

        return false;
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
        // By default all real numbers are considered as 'Double'. 
        // To consider a number 
        // (real or integer) as 'Decimal', it should postfix with M or m
        if (preg_match('/^(\-)?\d+(\.{1}\d+)?([Ee]{1}([\+\-]{1})?\d+)?[mM]{1}$/', $value) !== 1) {
            return false;
        }
        
        $outValue = rtrim($value, 'mM');
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
        return $value . 'M';
    }

    /**
     * Gets full name of this type in EDM namespace
     * Note: implementation of IType::getFullTypeName
     * 
     * @return string
     */
    public function getFullTypeName()
    {
        return 'Edm.Decimal';
    }

    /**
     * Converts the given string value to decimal type.
     * 
     * @param string $stringValue value to convert.
     * 
     * @return double
     */
    public function convert($stringValue)
    {
        return doubleval($stringValue);
    }
}