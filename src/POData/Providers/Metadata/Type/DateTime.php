<?php

declare(strict_types=1);

namespace POData\Providers\Metadata\Type;

use DateTimeZone;
use Exception;

/**
 * Class DateTime.
 */
class DateTime implements IType
{
    protected static $comboRegex =
        "/^datetime\'(\d{4})-(\d{2})-(\d{2})((\s|T)([0-1][0-9]|2[0-4]):([0-5][0-9])(:([0-5][0-9])([Z]|[\+|-]\d{2}:\d{2})?)?)?\'$/";

    protected static $timeProvider = null;

    /**
     * Gets year from datetime.
     *
     * @param string $dateTime datetime to get the year from
     *
     * @return string
     * @throws Exception
     */
    public static function year($dateTime)
    {
        $date = new \DateTime($dateTime);

        return $date->format('Y');
    }

    /**
     * Gets month from datetime.
     *
     * @param string $dateTime datetime to get the month from
     *
     * @return string
     * @throws Exception
     */
    public static function month($dateTime)
    {
        $date = new \DateTime($dateTime);

        return $date->format('m');
    }

    /**
     * Gets day from datetime.
     *
     * @param string $dateTime datetime to get the day from
     *
     * @return string
     * @throws Exception
     */
    public static function day($dateTime)
    {
        $date = new \DateTime($dateTime);

        return $date->format('d');
    }

    /**
     * Gets hour from datetime.
     *
     * @param string $dateTime datetime to get the hour from
     *
     * @return string
     * @throws Exception
     */
    public static function hour($dateTime)
    {
        $date = new \DateTime($dateTime);

        return $date->format('H');
    }

    /**
     * Gets minute from datetime.
     *
     * @param string $dateTime datetime to get the minute from
     *
     * @return string
     * @throws Exception
     */
    public static function minute($dateTime)
    {
        $date = new \DateTime($dateTime);

        return $date->format('i');
    }

    /**
     * Gets second from datetime.
     *
     * @param string $dateTime datetime to get the second from
     *
     * @return string
     * @throws Exception
     */
    public static function second($dateTime)
    {
        $date = new \DateTime($dateTime);

        return $date->format('s');
    }

    /**
     * gets a datetime object for now either internally or from a time provider.
     *
     * @return \DateTime  a date time object that represents "now"
     * @throws Exception
     */
    public static function now(): \DateTime
    {
        return null === self::$timeProvider ? new \DateTime() : call_user_func(self::$timeProvider);
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
     * @throws Exception
     */
    public static function dateTimeCmp($dateTime1, $dateTime2)
    {
        $firstStamp = self::dateTimeCmpCheckInput($dateTime1, 'Invalid input - datetime1 must be DateTime or string');
        $secondStamp = self::dateTimeCmpCheckInput($dateTime2, 'Invalid input - datetime2 must be DateTime or string');

        if ($firstStamp == $secondStamp) {
            return 0;
        }
        return $firstStamp < $secondStamp ? -1 : 1;
    }

    /**
     * @param $dateTime
     * @param $msg
     * @return false|int
     * @throws Exception
     */
    protected static function dateTimeCmpCheckInput($dateTime, $msg)
    {
        if (is_object($dateTime) && $dateTime instanceof \DateTime) {
            $firstStamp = $dateTime->getTimestamp();
            return $firstStamp;
        }
        if (is_string($dateTime)) {
            $firstStamp = strtotime($dateTime);
            return $firstStamp;
        }
        throw new Exception($msg);
    }

    public static function setTimeProvider(?callable $timeProvider): void
    {
        self::$timeProvider = $timeProvider;
    }

    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::DATETIME();
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
        return TypeCode::DATETIME() == $type->getTypeCode();
    }

    /**
     * Validate a value in Astoria uri is in a format for this type
     * Note: implementation of IType::validate.
     *
     * @param string $value The value to validate
     * @param string &$outValue The stripped form of $value that can
     *                          be used in PHP expressions
     *
     * @return bool
     */
    public function validate($value, &$outValue)
    {
        //1. The datetime value present in the $filter option should have 'datetime' prefix.
        //2. Month and day should be two digit
        if (!preg_match(
            self::$comboRegex,
            strval($value),
            $matches
        )) {
            return false;
        }

        //strip off prefix, and quotes from both ends
        $value = trim($value, 'datetime\'');
        $valLen = strlen($value) - 6;
        $offsetChek = $value[$valLen];
        if (18 < $valLen && ('-' == $offsetChek || '+' == $offsetChek)) {
            $value = substr($value, 0, $valLen);
        }

        //Validate the date using PHP DateTime class
        try {
            new \DateTime($value, new DateTimeZone('UTC'));
        } catch (Exception $e) {
            return false;
        }

        $outValue = "'" . $value . "'";

        return true;
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
     * Gets full name of the type implementing this interface in EDM namespace
     * Note: implementation of IType::getFullTypeName.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getFullTypeName();
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
}
