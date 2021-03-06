<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

/**
 * Class ResourceAssociationType.
 */
class ResourceAssociationType
{
    /**
     * Full name of the association.
     *
     * @var string
     */
    private $fullName;

    /**
     * Name of the association.
     *
     * @var string
     */
    private $name;

    /**
     * end1 for this association.
     *
     * @var ResourceAssociationTypeEnd
     */
    private $end1;

    /**
     * end2 for this association.
     *
     * @var ResourceAssociationTypeEnd
     */
    private $end2;

    /**
     * Construct new instance of ResourceAssociationType.
     *
     * @param string                     $name          Name of the association
     * @param string                     $namespaceName NamespaceName of the
     *                                                  association
     * @param ResourceAssociationTypeEnd $end1          First end of the association
     * @param ResourceAssociationTypeEnd $end2          Second end of the association
     */
    public function __construct(
        string $name,
        string $namespaceName,
        ResourceAssociationTypeEnd $end1,
        ResourceAssociationTypeEnd $end2
    ) {
        $this->name     = $name;
        $this->fullName = null !== $namespaceName ? $namespaceName . '.' . $name : $name;
        $this->end1     = $end1;
        $this->end2     = $end2;
    }

    /**
     * Gets name of the association.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets full-name of the association.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * Gets reference to first end.
     *
     * @return ResourceAssociationTypeEnd
     */
    public function getEnd1(): ResourceAssociationTypeEnd
    {
        return $this->end1;
    }

    /**
     * Gets reference to second end.
     *
     * @return ResourceAssociationTypeEnd
     */
    public function getEnd2(): ResourceAssociationTypeEnd
    {
        return $this->end2;
    }

    /**
     * Retrieve the end for the given resource type and property.
     *
     * @param ResourceEntityType $resourceType     Resource type for the source end
     * @param ResourceProperty   $resourceProperty Resource property for the source end
     *
     * @return ResourceAssociationTypeEnd|null Association type end for the given parameters
     */
    public function getResourceAssociationTypeEnd(
        ResourceEntityType $resourceType,
        ResourceProperty $resourceProperty
    ): ?ResourceAssociationTypeEnd {
        if ($this->end1->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->end1;
        }

        if ($this->end2->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->end2;
        }
        return null;
    }

    /**
     * Retrieve the related end for the given resource set, type and property.
     *
     * @param ResourceEntityType $resourceType     Resource type for the source end
     * @param ResourceProperty   $resourceProperty Resource property for the source end
     *
     * @return ResourceAssociationTypeEnd|null Association type end for the given parameters
     */
    public function getRelatedResourceAssociationSetEnd(
        ResourceEntityType $resourceType,
        ResourceProperty $resourceProperty
    ): ?ResourceAssociationTypeEnd {
        if ($this->end1->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->end2;
        }

        if ($this->end2->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->end1;
        }
        return null;
    }
}
