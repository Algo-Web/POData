<?php

namespace POData\Common\Messages;

trait orderByInfo
{
    /**
     * Message to show error when the orderByPathSegments argument found as
     * not a non-empty array.
     *
     * @return string The message
     */
    public static function orderByInfoPathSegmentsArgumentShouldBeNonEmptyArray()
    {
        return 'The argument orderByPathSegments should be a non-empty array';
    }

    /**
     * Message to show error when the navigationPropertiesUsedInTheOrderByClause
     * argument found as neither null or a non-empty array.
     *
     * @return string The message
     */
    public static function orderByInfoNaviUsedArgumentShouldBeNullOrNonEmptyArray()
    {
        return 'The argument navigationPropertiesUsedInTheOrderByClause should be either null or a non-empty array';
    }

    /**
     * Message to show error when the orderBySubPathSegments argument found as
     * not a non-empty array.
     *
     * @return string The message
     */
    public static function orderByPathSegmentOrderBySubPathSegmentArgumentShouldBeNonEmptyArray()
    {
        return 'The argument orderBySubPathSegments should be a non-empty array';
    }

    /**
     * Format a message to show error when parser failed to resolve a
     * property in orderby path.
     *
     * @param string $resourceTypeName The name of resource type
     * @param string $propertyName     Sub path segment, that comes after the
     *                                 segment of type  $resourceTypeName
     *
     * @return string The formatted message
     */
    public static function orderByParserPropertyNotFound($resourceTypeName, $propertyName)
    {
        return  "Error in the 'orderby' clause. Type '$resourceTypeName' does not have a property named '$propertyName'.";
    }

    /**
     * Format a message to show error when found a bag property used
     * in orderby clause.
     *
     * @param string $bagPropertyName The name of the bag property
     *
     * @return string The formatted message
     */
    public static function orderByParserBagPropertyNotAllowed($bagPropertyName)
    {
        return "orderby clause does not support Bag property in the path, the property '$bagPropertyName' is a bag property";
    }

    /**
     * Format a message to show error when found a primitve property used as
     * intermediate segment in orderby clause.
     *
     * @param string $propertyName The name of primitive property
     *
     * @return string The formatted message
     */
    public static function orderByParserPrimitiveAsIntermediateSegment($propertyName)
    {
        return "The primitive property '$propertyName' cannnot be used as intermediate segment, it should be last segment";
    }

    /**
     * Format a message to show error when found binary property used as sort key.
     *
     * @param string $binaryPropertyName The name of binary property
     *
     * @return string The formatted message
     */
    public static function orderByParserSortByBinaryPropertyNotAllowed($binaryPropertyName)
    {
        return "Binary property is not allowed in orderby clause, '$binaryPropertyName'";
    }

    /**
     * Format a message to show error when found a resource set reference
     * property in the oriderby clause.
     *
     * @param string $propertyName The name of resource set reference property
     * @param string $definedType  Defined type
     *
     * @return string The formatted message
     */
    public static function orderByParserResourceSetReferenceNotAllowed($propertyName, $definedType)
    {
        return "Navigation property points to a collection cannot be used in orderby clause, The property '$propertyName' defined on type '$definedType' is such a property";
    }

    /**
     * Format a message to show error when a navigation property is used as
     * sort key in orderby clause.
     *
     * @param string $navigationPropertyName The name of the navigation property
     *
     * @return string The formatted message
     */
    public static function orderByParserSortByNavigationPropertyIsNotAllowed($navigationPropertyName)
    {
        return "Navigation property cannot be used as sort key, '$navigationPropertyName'";
    }

    /**
     * Format a message to show error when a complex property is used as
     * sort key in orderby clause.
     *
     * @param string $complexPropertyName The name of the complex property
     *
     * @return string The formatted message
     */
    public static function orderByParserSortByComplexPropertyIsNotAllowed($complexPropertyName)
    {
        return "Complex property cannot be used as sort key, the property '$complexPropertyName' is a complex property";
    }

    /**
     * Message to show error when orderby parser found unexpected state.
     *
     * @return string The error message
     */
    public static function orderByParserUnExpectedState()
    {
        return 'Unexpected state while parsing orderby clause';
    }

    /**
     * Message to show error when orderby parser come across a type
     * which is not expected.
     *
     * @return string The message
     */
    public static function orderByParserUnexpectedPropertyType()
    {
        return 'Property type unexpected';
    }

    /**
     * Format a message to show error when the orderby parser fails to
     * create an instance of request uri resource type.
     *
     * @return string The formatted message
     */
    public static function orderByParserFailedToCreateDummyObject()
    {
        return 'OrderBy Parser failed to create dummy object from request uri resource type';
    }

    /**
     * Format a message to show error when orderby parser failed to
     * access some of the properties of dummy object.
     *
     * @param string $propertyName     Property name
     * @param string $parentObjectName Parent object name
     *
     * @return string The formatted message
     */
    public static function orderByParserFailedToAccessOrInitializeProperty($propertyName, $parentObjectName)
    {
        return "OrderBy parser failed to access or initialize the property $propertyName of $parentObjectName";
    }
}
