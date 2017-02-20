<?php

namespace POData\Common\Messages;

trait resourceAssociationSet
{
    /**
     * Format a message to show error when target resource property
     * argument is not null or instance of ResourceProperty.
     *
     * @param string $argumentName The name of the target resource property argument
     *
     * @return string The formatted message
     */
    public static function resourceAssociationSetPropertyMustBeNullOrInstanceofResourceProperty($argumentName)
    {
        return "The argument '$argumentName' must be either null or instance of 'ResourceProperty'.";
    }

    /**
     * Format a message when a property is used as
     * navigation property of a resource type which is actually not.
     *
     * @param string $propertyName     Property
     * @param string $resourceTypeName Resource type
     *
     * @return string The formatted message
     */
    public static function resourceAssociationSetEndPropertyMustBeNavigationProperty($propertyName, $resourceTypeName)
    {
        return "The property $propertyName must be a navigation property of the resource type $resourceTypeName";
    }

    /**
     * Format a message for showing the error when a resource type is
     * not assignable to resource set.
     *
     * @param string $resourceTypeName Resource type
     * @param string $resourceSetName  Resource set name
     *
     * @return string The formatted message
     */
    public static function resourceAssociationSetEndResourceTypeMustBeAssignableToResourceSet($resourceTypeName, $resourceSetName)
    {
        return "The resource type $resourceTypeName must be assignable to the resource set $resourceSetName.";
    }

    /**
     * Format a message for showing the error when trying to
     * create an association set with both null resource property.
     *
     * @return string The formatted message
     */
    public static function resourceAssociationSetResourcePropertyCannotBeBothNull()
    {
        return 'Both the resource properties of the association set cannot be null.';
    }

    /**
     * Format a message for showing the error when trying to
     * create a self referencing bidirectional association.
     *
     * @return string The formatted message
     */
    public static function resourceAssociationSetSelfReferencingAssociationCannotBeBiDirectional()
    {
        return 'Bidirectional self referencing association is not allowed.';
    }
}
