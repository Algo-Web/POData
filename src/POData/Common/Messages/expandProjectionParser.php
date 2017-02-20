<?php

namespace POData\Common\Messages;

trait expandProjectionParser
{
    /**
     * Message to show error when type of 'ExpandedProjectionNode::addNode'
     * parameter is neither ProjectionNode nor ExpandedProjectionNode.
     *
     * @return string The error message
     */
    public static function expandedProjectionNodeArgumentTypeShouldBeProjection()
    {
        return 'The argument to ExpandedProjectionNode::addNode should be either ProjectionNode or ExpandedProjectionNode';
    }

    /**
     * Format a message to show error when parser failed to
     * resolve a property in select or expand path.
     *
     * @param string $resourceTypeName The name of resource type
     * @param string $propertyName     Sub path segment, that comes after
     *                                 the segment of type  $resourceTypeName
     * @param bool   $isSelect         True if error found while parsing select
     *                                 clause, false for expand
     *
     * @return string The formatted message
     */
    public static function expandProjectionParserPropertyNotFound($resourceTypeName, $propertyName, $isSelect)
    {
        $clause = $isSelect ? 'select' : 'expand';

        return  "Error in the $clause clause. Type '$resourceTypeName' does not have a property named '$propertyName'.";
    }

    /**
     * Format a message to show error when expand path
     * contain non-navigation property.
     *
     * @param string $resourceTypeName The resource type name
     * @param string $propertyName     The proeprty name
     *
     * @return string The formatted message
     */
    public static function expandProjectionParserExpandCanOnlyAppliedToEntity($resourceTypeName, $propertyName)
    {
        return  "Error in the expand clause. Expand path can contain only navigation property, the property '$propertyName' defined in '$resourceTypeName' is not a navigation property";
    }

    /**
     * Format a message to show error when a primitive property is used as
     * navigation property in select clause.
     *
     * @param string $resourceTypeName     The resource type on which the
     *                                     primitive property defined
     * @param string $primitvePropertyName The primitive property used as
     *                                     navigation property
     *
     * @return string The formatted message
     */
    public static function expandProjectionParserPrimitivePropertyUsedAsNavigationProperty($resourceTypeName, $primitvePropertyName)
    {
        return "Property '$primitvePropertyName' on type '$resourceTypeName' is of primitive type and cannot be used as a navigation property.";
    }

    /**
     * Format a message to show error when a complex type is used as
     * navigation property in select clause.
     *
     * @param string $resourceTypeName The name of the resource type on which
     *                                 complex property is defined
     * @param string $complextTypeName The name of complex type
     *
     * @return string The formatted message
     */
    public static function expandProjectionParserComplexPropertyAsInnerSelectSegment($resourceTypeName, $complextTypeName)
    {
        return "select doesn't support selection of properties of complex type. The property '$complextTypeName' on type '$resourceTypeName' is a complex type.";
    }

    /**
     * Format a message to show error when a bag type is used as
     * navigation property in select clause.
     *
     * @param string $resourceTypeName The name of the resource type on which
     *                                 bag property is defined
     * @param string $bagPropertyName  The name of the bag property
     *
     * @return string The formatted message
     */
    public static function expandProjectionParserBagPropertyAsInnerSelectSegment($resourceTypeName, $bagPropertyName)
    {
        return "The selection from property '$bagPropertyName' on type '$resourceTypeName' is not valid. The select query option does not support selection items from a bag property.";
    }

    /**
     * Message to show error when parser come across a type which is expected
     * to be Entity type, but actually it is not.
     *
     * @return string The message
     */
    public static function expandProjectionParserUnexpectedPropertyType()
    {
        return 'Property type unexpected, expecting navigation property (ResourceReference or ResourceTypeReference).';
    }

    /**
     * Format a message to show error when found selection traversal of a
     * navigation property with out expansion.
     *
     * @param string $propertyName The navigation property in select path
     *                             which is not in expand path
     *
     * @return string The formatted message
     */
    public static function expandProjectionParserPropertyWithoutMatchingExpand($propertyName)
    {
        return 'Only navigation properties specified in expand option can be travered in select option,In order to treaverse the navigation property \'' . $propertyName . '\', it should be first expanded';
    }
}
