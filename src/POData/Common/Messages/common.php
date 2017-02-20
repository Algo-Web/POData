<?php

namespace POData\Common\Messages;

trait common
{
    /**
     * Format a message to show error when a non-integer value passed to
     * a function, which expects integer parameter.
     *
     * @param mixed  $argument     The non-integer argument
     * @param string $functionName The name of function
     *
     * @return string The formatted message
     */
    public static function commonArgumentShouldBeInteger($argument, $functionName)
    {
        return "The argument to the function '$functionName' should be integer, non-integer value '$argument' passed";
    }

    /**
     * Format a message to show error when a negative value passed to a
     * function, which expects non-negative parameter.
     *
     * @param mixed  $argument     The negative argument
     * @param string $functionName The name of function
     *
     * @return string The formatted message
     */
    public static function commonArgumentShouldBeNonNegative($argument, $functionName)
    {
        return "The argument to the function '$functionName' should be non-negative, negative value '$argument' passed";
    }

    /**
     * Format a message to show error when a function expect a
     * valid EdmPrimitiveType enum value, but it is not.
     *
     * @param string $argumentName The argument name
     * @param string $functionName The function name
     *
     * @return string The formatted message
     */
    public static function commonNotValidPrimitiveEDMType($argumentName, $functionName)
    {
        return "The argument '$argumentName' to $functionName is not a valid EdmPrimitiveType Enum value.";
    }

    /**
     * Message to show error when the requested resource instance
     * cannot be serialized to requested format.
     *
     * @return string The message
     */
    public static function unsupportedMediaType()
    {
        return 'Unsupported media type requested.';
    }

    /**
     * Format a message to show error when data service failed to
     * access some of the properties of dummy object.
     *
     * @param string $propertyName     Property name
     * @param string $parentObjectName Parent object name
     *
     * @return string The formatted message
     */
    public static function failedToAccessProperty($propertyName, $parentObjectName)
    {
        return "Data Service failed to access or initialize the property $propertyName of $parentObjectName.";
    }

    /**
     * Message to show error when found empty anscestor list.
     *
     * @return string The message
     */
    public static function orderByLeafNodeArgumentShouldBeNonEmptyArray()
    {
        return 'There should be atleast one anscestor for building the sort function';
    }

    /**
     * Format a message to show error when found a invalid property name.
     *
     * @param string $resourceTypeName The name of the resource type
     * @param string $propertyName     The name of the property
     *
     * @return string The formatted message
     */
    public static function badRequestInvalidPropertyNameSpecified($resourceTypeName, $propertyName)
    {
        return "Error processing request stream. The property name '$propertyName' specified for type '$resourceTypeName' is not valid. (Check the resource set of the navigation property '$propertyName' is visible)";
    }

    /**
     * Message to show error when parameter collection to
     * AnonymousFunction constructor includes parameter that does not start with '$'.
     *
     * @return string The message
     */
    public static function anonymousFunctionParameterShouldStartWithDollarSymbol()
    {
        return 'The parameter names in parameter array should start with dollar symbol';
    }
}
