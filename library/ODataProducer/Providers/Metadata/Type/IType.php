<?php
/** 
 * The Type interface
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
 * The Type interface
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata_Type
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
interface IType
{
    /**
     * Gets the type code for the type implementing this interface
     *   
     * @return TypeCode
     */
    public function getTypeCode();

    /**
     * Checks the type implementing this interface is compactible with another type
     * 
     * @param IType $type Type to check compactibility
     * 
     * @return boolean 
     */
    public function isCompatibleWith(IType $type);

    /**
     * Validate a value in Astoria uri is in a format for the type implementing this
     * interface
     * Note: implementation of IType::validate
     * 
     * @param string $value     The value to validate 
     * @param string &$outValue The stripped form of $value that can be used in PHP 
     *                          expressions
     * 
     * @return boolean
     */
    public function validate($value, &$outValue);

    /**
     * Gets full name of the type implementing this interface in EDM namespace
     * Note: implementation of IType::getFullTypeName
     * 
     * @return string
     */
    public function getFullTypeName();

    /**
     * Convers the given string value to this type.
     * 
     * @param string $stringValue value to convert.
     * 
     * @return mixed
     */
    public function convert($stringValue);

    /**
     * Convert the given value to a form that can be used in OData uri. 
     * 
     * @param mixed $value value to convert.
     * 
     * @return string
     */
    public function convertToOData($value);
}