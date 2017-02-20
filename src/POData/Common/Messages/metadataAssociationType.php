<?php

namespace POData\Common\Messages;

trait metadataAssociationType
{
    /**
     * A message to show error when
     * IMetadataProvider::GetResourceAssociationSet() returns different
     * AssociationSet when called with 'ResourceAssociationSetEnd' instances that
     * are expected to the ends of same association set.
     *
     * @return string The error message
     */
    public static function metadataAssociationTypeSetBidirectionalAssociationMustReturnSameResourceAssociationSetFromBothEnd()
    {
        return 'When the ResourceAssociationSet is bidirectional, IMetadataProvider::getResourceAssociationSet() must return the same ResourceAssociationSet when call from both ends.';
    }

    /**
     * Format a message to show error when multiple ResourceAssociationSets
     * have a ResourceAssociationSetEnd referring to the
     * same EntitySet through the same AssociationType.
     *
     * @param string $resourceSet1Name Name of the first association set
     * @param string $resourceSet2Name Name of the second association set
     * @param string $entitySetName    Name of the entity set
     *
     * @return string The formatted message
     */
    public static function metadataAssociationTypeSetMultipleAssociationSetsForTheSameAssociationTypeMustNotReferToSameEndSets($resourceSet1Name, $resourceSet2Name, $entitySetName)
    {
        return "ResourceAssociationSets '$resourceSet1Name' and '$resourceSet2Name' have a ResourceAssociationSetEnd referring to the same EntitySet '$entitySetName' through the same AssociationType. Make sure that if two or more AssociationSets refer to the same AssociationType, the ends must not refer to the same EntitySet. (this could happen if multiple entity sets have entity types that have a common ancestor and the ancestor has a property of derived entity types)";
    }

    /**
     * Format a message to show error when IDSMP::getDerivedTypes returns a
     * type which is not null or array of ResourceType.
     *
     * @param string $resourceTypeName Resource type name
     *
     * @return string The formatted message
     */
    public static function metadataAssociationTypeSetInvalidGetDerivedTypesReturnType($resourceTypeName)
    {
        return "Return type of IDSMP::getDerivedTypes should be either null or array of 'ResourceType', check implementation of IDSMP::getDerivedTypes for the resource type '$resourceTypeName'.";
    }
}
