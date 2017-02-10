<?php

namespace POData\Providers\Metadata\Type;

/**
 * Class IType.
 */
interface IType
{
    /**
     * Gets the type code for the type implementing this interface.
     *
     * @return TypeCode
     */
    public function getTypeCode();

    /**
     * Checks the type implementing this interface is compatible with another type.
     *
     * @param IType $type Type to check compatibility
     *
     * @return bool
     */
    public function isCompatibleWith(IType $type);

    /**
     * Validate a value in Astoria uri is in a format for the type implementing this
     * interface
     * Note: implementation of IType::validate.
     *
     * @param string $value     The value to validate
     * @param string &$outValue The stripped form of $value that can be used in PHP
     *                          expressions
     *
     * @return bool
     */
    public function validate($value, &$outValue);

    /**
     * Gets full name of the type implementing this interface in EDM namespace
     * Note: implementation of IType::getFullTypeName.
     *
     * @return string
     */
    public function getFullTypeName();

    /**
     * Convers the given string value to this type.
     *
     * @param string $stringValue value to convert
     *
     * @return mixed
     */
    public function convert($stringValue);

    /**
     * Convert the given value to a form that can be used in OData uri.
     *
     * @param mixed $value value to convert
     *
     * @return string
     */
    public function convertToOData($value);

    /**
     * Gets full name of the type implementing this interface in EDM namespace
     * Note: implementation of IType::getFullTypeName.
     *
     * @return string
     */
    public function getName();
}
