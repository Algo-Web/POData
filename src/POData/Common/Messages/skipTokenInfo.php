<?php

namespace POData\Common\Messages;

trait skipTokenInfo
{
    /**
     * Format a message to show error when one of the argument orderByPaths or
     * orderByValues is set and not both.
     *
     * @param string $orderByPathsVarName  Name of the argument
     *                                     holding orderByPathSegment
     * @param string $orderByValuesVarName Name of the argument holding
     *                                     skip token values corresponding
     *                                     to orderby paths
     *
     * @return string The formatted message
     */
    public static function skipTokenInfoBothOrderByPathAndOrderByValuesShouldBeSetOrNotSet($orderByPathsVarName, $orderByValuesVarName)
    {
        return "Either both the arguments $orderByPathsVarName and $orderByValuesVarName should be null or not-null";
    }

    /**
     * Format a message to show error when internalSkipTokenInfo failed to
     * access some of the properties of key object.
     *
     * @param string $propertyName Property name
     *
     * @return string The formatted message
     */
    public static function internalSkipTokenInfoFailedToAccessOrInitializeProperty($propertyName)
    {
        return "internalSkipTokenInfo failed to access or initialize the property $propertyName";
    }

    /**
     * Format a message to show error when found a non-array passed to
     * InternalSkipTokenInfo::search function.
     *
     * @param string $argumentName The name of the argument expected to be array
     *
     * @return string The formatted message
     */
    public static function internalSkipTokenInfoBinarySearchRequireArray($argumentName)
    {
        return "The argument '$argumentName' should be an array to perfrom binary search";
    }
}
