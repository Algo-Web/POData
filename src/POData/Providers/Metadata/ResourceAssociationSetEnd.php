<?php

namespace POData\Providers\Metadata;

use POData\Common\Messages;

/**
 * Class ResourceAssociationSetEnd represents association (relationship) set end.
 */
class ResourceAssociationSetEnd
{
    /**
     * Resource set for the association end.
     *
     * @var ResourceSet
     */
    private $resourceSet;

    /**
     * Resource type for the association end.
     *
     * @var ResourceEntityType
     */
    private $resourceType;

    /**
     * Resource property for the association end.
     *
     * @var ResourceProperty
     */
    private $resourceProperty;

    /**
     * Construct new instance of ResourceAssociationSetEnd
     * Note: The $resourceSet represents collection of an entity, The
     * $resourceType can be this entity's type or type of any of the
     * base resource of this entity, on which the navigation property
     * represented by $resourceProperty is defined.
     *
     * @param ResourceSet               $resourceSet      Resource set for the association end
     * @param ResourceEntityType        $resourceType     Resource type for the association end
     * @param ResourceProperty          $resourceProperty Resource property for the association end
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        ResourceSet $resourceSet,
        ResourceEntityType $resourceType,
        ResourceProperty $resourceProperty = null
    ) {
        if (!is_null($resourceProperty)
            && (is_null($resourceType->resolveProperty($resourceProperty->getName()))
                || (($resourceProperty->getKind() != ResourcePropertyKind::RESOURCE_REFERENCE)
                    && ($resourceProperty->getKind() != ResourcePropertyKind::RESOURCESET_REFERENCE)))
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetEndPropertyMustBeNavigationProperty(
                    $resourceProperty->getName(),
                    $resourceType->getFullName()
                )
            );
        }

        if (!$resourceSet->getResourceType()->isAssignableFrom($resourceType)
            && !$resourceType->isAssignableFrom($resourceSet->getResourceType())
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetEndResourceTypeMustBeAssignableToResourceSet(
                    $resourceType->getFullName(),
                    $resourceSet->getName()
                )
            );
        }

        $this->resourceSet = $resourceSet;
        $this->resourceType = $resourceType;
        $this->resourceProperty = $resourceProperty;
    }

    /**
     * To check this relationship belongs to a specific resource set, type
     * and property.
     *
     * @param ResourceSet      $resourceSet      Resource set for the association
     *                                           end
     * @param ResourceType     $resourceType     Resource type for the association
     *                                           end
     * @param ResourceProperty $resourceProperty Resource property for the
     *                                           association end
     *
     * @return bool
     */
    public function isBelongsTo(
        ResourceSet $resourceSet,
        ResourceType $resourceType,
        ResourceProperty $resourceProperty
    ) {
        return strcmp($resourceSet->getName(), $this->resourceSet->getName()) == 0
            && $this->resourceType->isAssignableFrom($resourceType)
            && ((is_null($resourceProperty) && is_null($this->resourceProperty))
                || (!is_null($resourceProperty) && !is_null($this->resourceProperty)
                    && (strcmp($resourceProperty->getName(), $this->resourceProperty->getName()) == 0)));
    }

    /**
     * Gets reference to resource set.
     *
     * @return ResourceSet
     */
    public function getResourceSet()
    {
        return $this->resourceSet;
    }

    /**
     * Gets reference to resource type.
     *
     * @return ResourceEntityType
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Gets reference to resource property.
     *
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->resourceProperty;
    }
}
