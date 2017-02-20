<?php

namespace POData\Providers\Metadata\Type;

/**
 * Class Guid.
 */
class Guid implements IType
{
    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode.
     *
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::GUID;
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
        return TypeCode::GUID == $type->getTypeCode();
    }

    /**
     * Validate a value in Astoria uri is in a format for this type
     * Note: implementation of IType::validate.
     *
     * @param string $value     The value to validate
     * @param string &$outValue The stripped form of $value that can be
     *                          used in PHP expressions
     *
     * @return bool
     */
    public function validate($value, &$outValue)
    {
        ////The GUID value present in the $filter option should have one of the following pattern.
        //1. '/^guid\'([0-9a-fA-F]{32}\')?$/';
        //2. '/^guid\'([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\')?$/';
        //3. '/^guid\'\{?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\}?\')?$/';
        //4. '/^guid\'\(?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\)?\')?$/';

        $length = strlen($value);
        if (38 != $length && 42 != $length && 44 != $length) {
            return false;
        }

        if (0 !== strpos($value, 'guid\'') && '\'' != $value[$length - 1]) {
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
     * Note: implementation of IType::getFullTypeName.
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
     * @param string $stringValue value to convert
     *
     * @return string
     */
    public function convert($stringValue)
    {
        $len = strlen($stringValue);
        if (2 > $len) {
            return $stringValue;
        }

        return substr($stringValue, 1, $len - 2);
    }

    /**
     * Convert the given value to a form that can be used in OData uri.
     * Note: The calling function should not pass null value, as this
     * function will not perform any check for nullability.
     *
     * @param mixed $value value to convert
     *
     * @return string
     */
    public function convertToOData($value)
    {
        return 'guid\'' . urlencode($value) . '\'';
    }

    /**
     * Validates guid.
     *
     * @param string $guid       The guid to validate
     * @param bool   $withQuotes Whether the above guid have quote as delimiter
     *
     * @return bool
     */
    public static function validateWithoutPrefix($guid, $withQuotes = false)
    {
        $patterns = null;
        if ($withQuotes) {
            $patterns = ['/^(\'[0-9a-fA-F]{32}\')?$/',
                            '/^(\'[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\')?$/',
                            '/^\'\{?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\}?\')?$/',
                            '/^\'\(?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\)?\')?$/', ];
        } else {
            $patterns = ['/^([0-9a-fA-F]{32})?$/',
                            '/^([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})?$/',
                            '/^\{?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\}?)?$/',
                            '/^\(?([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\)?)?$/', ];
        }

        foreach ($patterns as $pattern) {
            if (1 == preg_match($pattern, $guid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check the equality of two guids. This function will not validate the
     * guids one should use validate or validateWithoutPrefix to validate the
     * guids before using them with this function.
     *
     * @param string $guid1 First guid
     * @param string $guid2 Second guid
     *
     * @return bool True if both guids are same else false
     */
    public static function guidEqual($guid1, $guid2)
    {
        $guid1 = str_replace(['{', '}', '(', ')', '-'], '', $guid1);
        $guid2 = str_replace(['{', '}', '(', ')', '-'], '', $guid2);

        return 0 === strcasecmp($guid1, $guid2);
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
