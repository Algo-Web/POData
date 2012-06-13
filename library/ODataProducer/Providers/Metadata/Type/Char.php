<?php
/** 
 * Type to represent Char
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata_Type
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
namespace ODataProducer\Providers\Metadata\Type;
use ODataProducer\Common\NotImplementedException;
/**
 * Type to represent Char
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata_Type
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class Char implements IType
{
    const A = 65;
    const Z = 90;
    const SMALL_A = 97;
    const SMALL_Z = 122;
    const F = 70;
    const SMALL_F = 102;
    const ZERO = 48;
    const NINE = 57;
    const TAB = 9;
    const NEWLINE = 10;
    const CARRIAGE_RETURN = 13;
    const SPACE = 32;
    
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode
     *   
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::CHAR;
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
        case TypeCode::CHAR:
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
        //No EDM Char primitive type
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
        return 'System.Char';
    }

    /**
     * Converts the given string value to char type.     
     * Note: This function will not perfrom any conversion.
     * 
     * @param string $stringValue The value to convert.
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
     * @param mixed $value The value to convert.
     * 
     * @return string
     */
    public function convertToOData($value)
    {
        return $value;
    }

    /**
     * Checks a character is whilespace
     * 
     * @param char $char character to check
     * 
     * @return boolean
     */
    public static function isWhiteSpace($char)
    {
        $asciiVal = ord($char);
        return $asciiVal == Char::SPACE 
            || $asciiVal == Char::TAB 
            || $asciiVal == Char::CARRIAGE_RETURN 
            || $asciiVal == Char::NEWLINE;
    }

    /**
     * Checks a character is letter 
     * 
     * @param char $char character to check
     * 
     * @return boolean
     */
    public static function isLetter($char)
    {
        $asciiVal = ord($char);
        return ($asciiVal >= Char::A && $asciiVal <= Char::Z) 
            || ($asciiVal >= Char::SMALL_A && $asciiVal <= Char::SMALL_Z);
    }

    /**
     * Checks a character is digit 
     * 
     * @param char $char character to check
     * 
     * @return boolean
     */
    public static function isDigit($char)
    {
        $asciiVal = ord($char);
        return $asciiVal >= Char::ZERO 
            && $asciiVal <= Char::NINE;
    }

    /**
     * Checks a character is hexadecimal digit 
     * 
     * @param char $char character to check
     * 
     * @return boolean
     */
    public static function isHexDigit($char)
    {
        $asciiVal = ord($char);
        return self::isDigit($char) 
            || ($asciiVal >= Char::A && $asciiVal <= Char::F) 
            || ($asciiVal >= Char::SMALL_A && $asciiVal <= Char::SMALL_F);
    }

    /**
     * Checks a character is letter or digit
     * 
     * @param char $char character to check
     * 
     * @return boolean
     */
    public static function isLetterOrDigit($char)
    { 
        return self::isDigit($char) || self::isLetter($char);
    }
}