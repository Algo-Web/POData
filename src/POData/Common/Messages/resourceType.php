<?php

namespace POData\Common\Messages;

trait resourceType
{
    /**
     * Format a message to show error when a tyring to set a
     * base class for primitive type.
     *
     * @return string The message
     */
    public static function resourceTypeNoBaseTypeForPrimitive()
    {
        return 'Primitive type cannot have base type';
    }

    /**
     * Format a message to show error when tyring to
     * set a primitive type as abstract.
     *
     * @return string The message
     */
    public static function resourceTypeNoAbstractForPrimitive()
    {
        return 'Primitive type cannot be abstract';
    }

    /**
     * Format a message to show error when a primitive instance type
     * is not IType implementation.
     *
     * @param string $argument The name of instance type argument
     *
     * @return string The message
     */
    public static function resourceTypeTypeShouldImplementIType($argument)
    {
        return "For primitive type the '$argument' argument should be an 'IType' implementor instance";
    }

    /**
     * Format a message to show error when instance type of a
     * complex or entity type is not instance of ReflectionClass.
     *
     * @param string $argument The name of instance type argument
     *
     * @return string The message
     */
    public static function resourceTypeTypeShouldReflectionClass($argument)
    {
        return "For entity type the '$argument' argument should be an 'ReflectionClass' instance";
    }

    /**
     * Format a message to show error when an entity type missing key properties.
     *
     * @param string $entityName The name of instance type argument
     *
     * @return string The formatted message
     */
    public static function resourceTypeMissingKeyPropertiesForEntity($entityName)
    {
        return "The entity type '$entityName' does not have any key properties. Please make sure the key properties are defined for this entity type";
    }

    /**
     * The message to show error when trying to add
     * property to 'Primitive' resource type.
     *
     * @return string The message
     */
    public static function resourceTypeNoAddPropertyForPrimitive()
    {
        return 'Properties cannot be added to ResourceType instances with a ResourceTypeKind equal to \'Primitive\'';
    }

    /**
     * The message to show error when trying to
     * add key property to non-entity resource type.
     *
     * @return string The message
     */
    public static function resourceTypeKeyPropertiesOnlyOnEntityTypes()
    {
        return 'Key properties can only be added to ResourceType instances with a ResourceTypeKind equal to \'EntityType\'';
    }

    /**
     * The message to show error when trying to add an
     * etag property to non-entity resource type.
     *
     * @return string The message
     */
    public static function resourceTypeETagPropertiesOnlyOnEntityTypes()
    {
        return 'ETag properties can only be added to ResourceType instances with a ResourceTypeKind equal to \'EntityType\'';
    }

    /**
     * Format a message to show error for
     * duplication of resource property on resource type.
     *
     * @param string $propertyName     The property name
     * @param string $resourceTypeName The rtesource type name
     *
     * @return string The formatted message
     */
    public static function resourceTypePropertyWithSameNameAlreadyExists($propertyName, $resourceTypeName)
    {
        return "Property with same name '$propertyName' already exists in type '$resourceTypeName'. Please make sure that there is no property with the same name defined in one of the ancestor types";
    }

    /**
     * The message to show error when trying to add a key property to derived type.
     *
     * @return string The message
     */
    public static function resourceTypeNoKeysInDerivedTypes()
    {
        return 'Key properties cannot be defined in derived types';
    }

    /**
     * The message to show error when trying to set a non-entity resource type as MLE.
     *
     * @return string The message
     */
    public static function resourceTypeHasStreamAttributeOnlyAppliesToEntityType()
    {
        return 'Cannot apply the HasStreamAttribute, HasStreamAttribute is only applicable to entity types.';
    }

    /**
     * The message to show error when trying to add a named stream on non-entity type.
     *
     * @return string The message
     */
    public static function resourceTypeNamedStreamsOnlyApplyToEntityType()
    {
        return 'Named streams can only be added to entity types.';
    }

    /**
     * Format a message to show error for
     * duplication of named stream property on resource type.
     *
     * @param string $namedStreamName  The named stream name
     * @param string $resourceTypeName The resource Property
     *
     * @return string The formatted message
     */
    public static function resourceTypeNamedStreamWithSameNameAlreadyExists($namedStreamName, $resourceTypeName)
    {
        return "Named stream with the name '$namedStreamName' already exists in type '$resourceTypeName'. Please make sure that there is no named stream with the same name defined in one of the ancestor types";
    }
}
