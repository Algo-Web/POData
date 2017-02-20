<?php

namespace POData\Common\Messages;

trait metadataWriter
{
    /**
     * Message to show error when expecting entity or
     * complex type, but a different type found.
     *
     * @return string The error message
     */
    public static function metadataWriterExpectingEntityOrComplexResourceType()
    {
        return 'Unexpected resource type found, expecting either ResourceTypeKind::ENTITY or ResourceTypeKind::COMPLEX';
    }

    /**
     * Format a message to show error when no association set
     * found for a navigation property.
     *
     * @param string $navigationPropertyName The name of the navigation property
     * @param string $resourceTypeName       The resource type on which the
     *                                       navigation property is defined
     *
     * @return string The formatted message
     */
    public static function metadataWriterNoResourceAssociationSetForNavigationProperty($navigationPropertyName, $resourceTypeName)
    {
        return "No visible ResourceAssociationSet found for navigation property '$navigationPropertyName' on type '$resourceTypeName'. There must be at least one ResourceAssociationSet for each navigation property.";
    }
}
