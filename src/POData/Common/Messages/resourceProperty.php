<?php

namespace POData\Common\Messages;

trait resourceProperty
{
    /**
     * Format a message to show error for invalid ResourcePropertyKind enum argument.
     *
     * @param string $argumentName The argument name
     *
     * @return string The formatted message
     */
    public static function resourcePropertyInvalidKindParameter($argumentName)
    {
        return "The argument '$argumentName' is not a valid ResourcePropertyKind enum value or valid combination of ResourcePropertyKind enum values";
    }

    /**
     * Format a message to show error when ResourcePropertyKind and ResourceType's ResourceTypeKind mismatches.
     *
     * @param string $resourcePropertyKindArgName The ResourcePropertyKind argument name
     * @param string $resourceTypeArgName         The ResourceType argument name
     *
     * @return string The formatted message
     */
    public static function resourcePropertyPropertyKindAndResourceTypeKindMismatch($resourcePropertyKindArgName, $resourceTypeArgName)
    {
        return "The '$resourcePropertyKindArgName' parameter does not match with the type of the resource type in parameter '$resourceTypeArgName'";
    }
}
