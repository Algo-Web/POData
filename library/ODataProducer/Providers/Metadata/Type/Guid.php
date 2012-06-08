<?php
/** 
 * Type to represent Guid
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
 * Type to represent Guid
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata_Type
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class Guid implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode
     *   
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::GUID;
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
        return ($type->getTypeCode() == TypeCode::GUID);
    }

    /**
     * Validate a value in Astoria uri is in a format for this type
     * Note: implementation of IType::validate
     * 
     * @param string $value     The value to validate 
     * @param string &$outValue The stripped form of $value that can be 
     *                          used in PHP expressions
     * 
     * @return boolean
     */
    public function validate($value, &$outValue)
    {
        ////The GUID value present in the $filter option should have one of the following pattern.
        //1. '/^guid\'([0-9a-fA-F]{32}\')?$/'; 
        //2. '/^guid\'([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\')?$/';
        //3. '/^guid\'\{?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\}?\')?$/';
        //4. '/^guid\'\(?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\)?\')?$/';
         
        $length = strlen($value);
        if ($length != 38 && $length != 42 && $length != 44) {
            return false;
        }
        
        if (strpos($value, 'guid\'') !== 0 && $value[$length - 1] != '\'') {
            return false;    
        }
        
        $value = substr($value, 4, $length - 4);
        if (!self::validateWithoutPrefix($value, true)) {
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
        return 'Edm.Guid';
    }

    /**
     * Converts the given string value to guid type.
     * 
     * @param string $stringValue value to convert.
     * 
     * @return string
     */
    public function convert($stringValue)
    {
        $len = strlen($stringValue);
        if ($len < 2) {
            return $stringValue;
        }

        return substr($stringValue, 1, $len - 2);
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
        return 'guid\'' . urlencode($value) . '\'';
    }

    /**
     * Validates guid
     * 
     * @param string  $guid       The guid to validate
     * @param boolean $withQuotes Whether the above guid have quote as delimiter
     * 
     * @return boolean
     */
    public static function validateWithoutPrefix($guid, $withQuotes = false)
    {
        $patterns = null;
        if ($withQuotes) {
            $patterns = array('/^(\'[0-9a-fA-F]{32}\')?$/',
                            '/^(\'[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\')?$/',
                            '/^\'\{?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\}?\')?$/',
                            '/^\'\(?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\)?\')?$/');
        } else {
            $patterns = array('/^([0-9a-fA-F]{32})?$/',
                            '/^([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})?$/',
                            '/^\{?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\}?)?$/',
                            '/^\(?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\)?)?$/');
            
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $guid) == 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check the equality of two guids. This function will not validate the
     * guids one should use validate or validateWithoutPrefix to validate the
     * guids before using them with this function
     * 
     * @param string $guid1 First guid
     * @param string $guid2 Second guid
     * 
     * @return boolean True if both guids are same else false
     */
    public static function guidEqual($guid1, $guid2)
    {
        $guid1 = str_replace(array('{', '}', '(', ')', '-'), '', $guid1);
        $guid2 = str_replace(array('{', '}', '(', ')', '-'), '', $guid2);
        return strcasecmp($guid1, $guid2) === 0;
    }
}