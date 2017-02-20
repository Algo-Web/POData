<?php

namespace POData\Common\Messages;

trait objectModelSerializer
{
    /**
     * Format a message to show error when object model serializer found
     * in-inconsistency in resource type and current runtime information.
     *
     * @param string $typeName The name of the resource type for which
     *                         serializer found inconsistency
     *
     * @return string The formatted message
     */
    public static function badProviderInconsistentEntityOrComplexTypeUsage($typeName)
    {
        return "Internal Server Error. The type '$typeName' has inconsistent metadata and runtime type info.";
    }

    /**
     * Format a message to show error when object model serializer
     * found null key value.
     *
     * @param string $resourceTypeName The name of the resource type of the
     *                                 instance with null key
     * @param string $keyName          Name of the key with null value
     *
     * @return string The formatted message
     */
    public static function badQueryNullKeysAreNotSupported($resourceTypeName, $keyName)
    {
        return "The serialized resource of type $resourceTypeName has a null value in key member '$keyName'. Null values are not supported in key members.";
    }

    /**
     * Format a message to show error when object model serializer failed to
     * access some of the properties of a type instance.
     *
     * @param string $propertyName     The name of the property in question
     * @param string $parentObjectName The entity instance in question
     *
     * @return string The formatted message
     */
    public static function objectModelSerializerFailedToAccessProperty($propertyName, $parentObjectName)
    {
        return "objectModelSerializer failed to access or initialize the property $propertyName of $parentObjectName, Please contact provider.";
    }

    /**
     * Format a message to show error when object model serializer found loop
     * a in complex property instance.
     *
     * @param string $complexPropertyName The name of the complex property with loop
     *
     * @return string The formatted message
     */
    public static function objectModelSerializerLoopsNotAllowedInComplexTypes($complexPropertyName)
    {
        return 'A circular loop was detected while serializing the property \'' . $complexPropertyName . '\'. You must make sure that loops are not present in properties that return a bag or complex type.';
    }
}
