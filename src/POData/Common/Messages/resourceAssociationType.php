<?php

namespace POData\Common\Messages;

trait resourceAssociationType
{
    /**
     * Format a message to show error when target resource property argument is
     * not null or instance of ResourceProperty.
     *
     * @param string $argumentName The name of the target resource property argument
     *
     * @return string The formatted message
     */
    public static function resourceAssociationTypeEndPropertyMustBeNullOrInstanceofResourceProperty($argumentName)
    {
        return "The argument '".$argumentName."' must be either null or instance of 'ResourceProperty'.";
    }

    /**
     * Error message to show when both from and to property arguments are null.
     *
     * @return string The error message
     */
    public static function resourceAssociationTypeEndBothPropertyCannotBeNull()
    {
        return 'Both to and from property argument to ResourceAssociationTypeEnd constructor cannot be null.';
    }
}
