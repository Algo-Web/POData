<?php
/** 
 * Type to represent DateTime
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
/**
 * Type to represent DateTime
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata_Type
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class DateTime implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode
     *   
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::DATETIME;
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
        return ($type->getTypeCode() == TypeCode::DATETIME);
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
        //1. The datetime value present in the $filter option should have 
        //   'datetime' prefix.
        //2. Month and day should be two digit
        if (!preg_match("/^datetime\'(\d{4})-(\d{2})-(\d{2})((\s|T)([0-1][0-9]|2[0-4]):([0-5][0-9])(:([0-5][0-9])([Z])?)?)?\'$/", strval($value), $matches)
        ) {
            return false;
        } 

        //stripoff prefix, and quotes from both ends
        $value = trim($value, 'datetime\'');
        //Validate the date using PHP DateTime class
        if (!self::validateWithoutPreFix($value)) {
            return false;
        }
    
        $outValue = "'" . $value . "'";
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
        return 'Edm.DateTime';
    }

    /**
     * Converts the given string value to datetime type.     
     * Note: This function will not perfrom any conversion.
     * 
     * @param string $stringValue Value to convert.
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
     * @param mixed $value Value to convert.
     * 
     * @return string
     */
    public function convertToOData($value)
    {
        return 'datetime\'' . urlencode($value) . '\'';
    }

    /**
     * Checks a value is valid datetime
     * 
     * @param string $dateTime value to validate. 
     * 
     * @return boolean
     */
    public static function validateWithoutPreFix($dateTime)
    {
        try {
                $dt = new \DateTime($dateTime, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
                return false;
        }
            
        return true;
    }

    /**
     * Gets year from datetime
     * 
     * @param string $dateTime datetime to get the year from
     * 
     * @return int
     */
    public static function year($dateTime)
    {
        $date = new \DateTime($dateTime);
        return $date->format("Y");
    }

    /**
     * Gets month from datetime
     * 
     * @param string $dateTime datetime to get the month from
     * 
     * @return int
     */
    public static function month($dateTime)
    {
        $date = new \DateTime($dateTime);
        return $date->format("m");
    }

    /**
     * Gets day from datetime
     * 
     * @param string $dateTime datetime to get the day from
     * 
     * @return int
     */
    public static function day($dateTime)
    {
        $date = new \DateTime($dateTime);
        return $date->format("d");
    }

    /**
     * Gets hour from datetime
     * 
     * @param string $dateTime datetime to get the hour from
     * 
     * @return int
     */
    public static function hour($dateTime)
    {
        $date = new \DateTime($dateTime);
        return $date->format("H");
    }

    /**
     * Gets minute from datetime
     * 
     * @param string $dateTime datetime to get the minute from
     * 
     * @return int
     */
    public static function minute($dateTime)
    {
        $date = new \DateTime($dateTime);
        return $date->format("i");
    }

    /**
     * Gets second from datetime
     * 
     * @param string $dateTime datetime to get the second from
     * 
     * @return int
     */
    public static function second($dateTime)
    {
        $date = new \DateTime($dateTime);
        return $date->format("s");
    }
    
    /**
     * Compare two dates. Note that this function will not perform any
     * validation on dates, one should use either validate or
     * validateWithoutPrefix to validate the date before calling this 
     * function
     * 
     * @param string $dateTime1 First date
     * @param string $dateTime2 Second date
     * 
     * @return int
     */
    public static function dateTimeCmp($dateTime1, $dateTime2)
    {
        return strtotime($dateTime1) - strtotime($dateTime2);
    }
}