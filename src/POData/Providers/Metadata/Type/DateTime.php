<?php

namespace POData\Providers\Metadata\Type;

use Carbon\Carbon;

/**
 * Class DateTime.
 */
class DateTime implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::DATETIME;
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
        return TypeCode::DATETIME == $type->getTypeCode();
    }

    /**
     * Validate a value in Astoria uri is in a format for this type
     * Note: implementation of IType::validate.
     *
     * @param string $value     The value to validate
     * @param string &$outValue The stripped form of $value that can
     *                          be used in PHP expressions
     *
     * @return bool
     */
    public function validate($value, &$outValue)
    {
        //1. The datetime value present in the $filter option should have
        //   'datetime' prefix.
        //2. Month and day should be two digit
        if (!preg_match(
            "/^datetime\'(\d{4})-(\d{2})-(\d{2})((\s|T)([0-1][0-9]|2[0-4]):([0-5][0-9])(:([0-5][0-9])([Z])?)?)?\'$/",
            strval($value),
            $matches
        )) {
            return false;
        }

        //stripoff prefix, and quotes from both ends
        $value = trim($value, 'datetime\'');

        //Validate the date using PHP Carbon class
        try {
            new Carbon($value, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return false;
        }

        $outValue = "'" . $value . "'";

        return true;
    }

    /**
     * Gets full name of this type in EDM namespace
     * Note: implementation of IType::getFullTypeName.
     *
     * @return string
     */
    public function getFullTypeName()
    {
        return 'Edm.DateTime';
    }

    /**
     * Converts the given string value to datetime type.
     * Note: This function will not perform any conversion.
     *
     * @param string $stringValue Value to convert
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
     * @param mixed $value Value to convert
     *
     * @return string
     */
    public function convertToOData($value)
    {
        return 'datetime\'' . urlencode($value) . '\'';
    }

    /**
     * Gets year from datetime.
     *
     * @param string $dateTime datetime to get the year from
     *
     * @return string
     */
    public static function year($dateTime)
    {
        $date = new Carbon($dateTime);

        return $date->format('Y');
    }

    /**
     * Gets month from datetime.
     *
     * @param string $dateTime datetime to get the month from
     *
     * @return string
     */
    public static function month($dateTime)
    {
        $date = new Carbon($dateTime);

        return $date->format('m');
    }

    /**
     * Gets day from datetime.
     *
     * @param string $dateTime datetime to get the day from
     *
     * @return string
     */
    public static function day($dateTime)
    {
        $date = new Carbon($dateTime);

        return $date->format('d');
    }

    /**
     * Gets hour from datetime.
     *
     * @param string $dateTime datetime to get the hour from
     *
     * @return string
     */
    public static function hour($dateTime)
    {
        $date = new Carbon($dateTime);

        return $date->format('H');
    }

    /**
     * Gets minute from datetime.
     *
     * @param string $dateTime datetime to get the minute from
     *
     * @return string
     */
    public static function minute($dateTime)
    {
        $date = new Carbon($dateTime);

        return $date->format('i');
    }

    /**
     * Gets second from datetime.
     *
     * @param string $dateTime datetime to get the second from
     *
     * @return string
     */
    public static function second($dateTime)
    {
        $date = new Carbon($dateTime);

        return $date->format('s');
    }

    /**
     * Compare two dates. Note that this function will not perform any
     * validation on dates, one should use either validate or
     * validateWithoutPrefix to validate the date before calling this
     * function.
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
}
