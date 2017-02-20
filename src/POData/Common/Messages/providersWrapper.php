<?php

namespace POData\Common\Messages;

trait providersWrapper
{
    /**
     * Message to show error service implementation returns null for IMetadataProvider or IQueryProvider.
     *
     * @return string The message
     */
    public static function providersWrapperNull()
    {
        return 'For custom providers, GetService should not return null for both IMetadataProvider and IQueryProvider types.';
    }

    /**
     * The error message to show when IQueryProvider::getExpressionProvider
     * method returns empty or null.
     *
     * @return string The message
     */
    public static function providersWrapperExpressionProviderMustNotBeNullOrEmpty()
    {
        return 'The value returned by IQueryProvider::getExpressionProvider method must not be null or empty';
    }

    /**
     * The error message to show when IQueryProvider::getExpressionProvider
     * method returns non-object or an object which does not implement IExpressionProvider.
     *
     * @return string The message
     */
    public static function providersWrapperInvalidExpressionProviderInstance()
    {
        return 'The value returned by IQueryProvider::getExpressionProvider method must be an implementation of IExpressionProvider';
    }

    /**
     * The error message to show when IMetadataProvider::getContainerName
     * method returns empty container name.
     *
     * @return string The message
     */
    public static function providersWrapperContainerNameMustNotBeNullOrEmpty()
    {
        return 'The value returned by IMetadataProvider::getContainerName method must not be null or empty';
    }

    /**
     * The error message to show when
     * IMetadataProvider::getContainerNamespace
     * method returns empty container name.
     *
     * @return string The message
     */
    public static function providersWrapperContainerNamespaceMustNotBeNullOrEmpty()
    {
        return 'The value returned by IMetadataProvider::getContainerNamespace method must not be null or empty';
    }

    /**
     * Format a message to show error when
     * more than one entity set with the same name found.
     *
     * @param string $entitySetName The name of the entity set
     *
     * @return string The formatted message
     */
    public static function providersWrapperEntitySetNameShouldBeUnique($entitySetName)
    {
        return "More than one entity set with the name '$entitySetName' was found. Entity set names must be unique";
    }

    /**
     * Format a message to show error when
     * more than one entity type with the same name found.
     *
     * @param string $entityTypeName The name of the entity type
     *
     * @return string The formatted message
     */
    public static function providersWrapperEntityTypeNameShouldBeUnique($entityTypeName)
    {
        return "More than one entity type with the name '$entityTypeName' was found. Entity type names must be unique.";
    }

    /**
     * Format a message to show error when IDSMP::getResourceSet
     * returns inconsistent instance of ResourceSet.
     *
     * @param string $resourceSetName      Name of the resource set
     * @param string $resourceTypeName     Name of the resource type
     * @param string $resourcePropertyName Name of the navigation property
     *
     * @return string The formatted message
     */
    public static function providersWrapperIDSMPGetResourceSetReturnsInvalidResourceSet($resourceSetName, $resourceTypeName, $resourcePropertyName)
    {
        return "IDSMP::GetResourceSet retruns invalid instance of ResourceSet when invoked with params {ResourceSet with name $resourceSetName, ResourceType with name $resourceTypeName, ResourceProperty with name $resourcePropertyName}.";
    }

    /**
     * Format a message to show error when IDSMP::getResourceFromResourceSet
     * returns an instnce which is not an instance of expected entity instance.
     *
     * @param string $entityTypeName The name of expected entity type
     * @param string $methodName     Method name
     *
     * @return string The formatted message
     */
    public static function providersWrapperIDSQPMethodReturnsUnExpectedType($entityTypeName, $methodName)
    {
        return 'The implementation of the method ' . $methodName . ' must return an instance of type described by resource set\'s type(' . $entityTypeName . ') or null if resource does not exist.';
    }

    /**
     * A message to show error when IDSQP::getResourceFromResourceSet
     * returns an entity instance with null key properties.
     *
     * @param string $methodName Method name
     *
     * @return string The message
     */
    public static function providersWrapperIDSQPMethodReturnsInstanceWithNullKeyProperties($methodName)
    {
        return 'The ' . $methodName . ' implementation returns an entity with null key propert(y|ies).';
    }

    /**
     * A message to show error when IDSQP::getResourceFromResourceSet
     * returns an entity instance with keys
     * not matching with the expected keys in the uri predicate.
     *
     * @param string $methodName Method name
     *
     * @return string The message
     */
    public static function providersWrapperIDSQPMethodReturnsInstanceWithNonMatchingKeys($methodName)
    {
        return 'The ' . $methodName . ' implementation returns an instance with non-matching key';
    }
}
