<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

use InvalidArgumentException;
use POData\Common\Messages;

/**
 * Class ResourceAssociationSet.
 */
class ResourceAssociationSet
{
    /**
     * Note: This property will be populated by the library,
     * so IDSMP implementor should not set this.
     * The association type hold by this association set.
     *
     * @var ResourceAssociationType
     */
    public $resourceAssociationType;
    /**
     * name of the association set.
     *
     * @var string
     */
    private $name;
    /**
     * End1 of association set.
     *
     * @var ResourceAssociationSetEnd
     */
    private $end1;
    /**
     * End2 of association set.
     *
     * @var ResourceAssociationSetEnd
     */
    private $end2;

    /**
     * Construct new instance of ResourceAssociationSet.
     *
     * @param string                    $name Name of the association set
     * @param ResourceAssociationSetEnd $end1 First end set participating
     *                                        in the association set
     * @param ResourceAssociationSetEnd $end2 Second end set participating
     *                                        in the association set
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        $name,
        ResourceAssociationSetEnd $end1,
        ResourceAssociationSetEnd $end2
    ) {
        $prop1 = $end1->getResourceProperty();
        $prop2 = $end2->getResourceProperty();
        if (null === $prop1 && null === $prop2
        ) {
            throw new InvalidArgumentException(
                Messages::resourceAssociationSetResourcePropertyCannotBeBothNull()
            );
        }
        $type1 = $end1->getResourceType();
        $type2 = $end2->getResourceType();

        if ($type1 === $type2  && $prop1 === $prop2) {
            throw new InvalidArgumentException(
                Messages::resourceAssociationSetSelfReferencingAssociationCannotBeBiDirectional()
            );
        }

        $this->name = $name;
        $this->end1 = $end1;
        $this->end2 = $end2;
    }

    /**
     * @param  ResourceEntityType $sourceType
     * @param  string             $linkName
     * @param  ResourceSet        $targetResourceSet
     * @return string
     */
    public static function keyName(ResourceEntityType $sourceType, $linkName, ResourceSet $targetResourceSet): string
    {
        return $sourceType->getName() . '_' . $linkName . '_' . $targetResourceSet->getResourceType()->getName();
    }

    /**
     * @param  ResourceEntityType $sourceType
     * @param  ResourceProperty   $property
     * @return string
     */
    public static function keyNameFromTypeAndProperty(
        ResourceEntityType $sourceType,
        ResourceProperty $property
    ): string {
        return $sourceType->getName() . '_' . $property->getName() . '_' . $property->getResourceType()->getName();
    }

    /**
     * Retrieve the end for the given resource set, type and property.
     *
     * @param ResourceSet        $resourceSet      Resource set for the end
     * @param ResourceEntityType $resourceType     Resource type for the end
     * @param ResourceProperty   $resourceProperty Resource property for the end
     *
     * @return ResourceAssociationSetEnd|null Resource association set end for the
     *                                        given parameters
     */
    public function getResourceAssociationSetEnd(
        ResourceSet $resourceSet,
        ResourceEntityType $resourceType,
        ResourceProperty $resourceProperty
    ): ?ResourceAssociationSetEnd {
        if ($this->end1->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->end1;
        }

        if ($this->end2->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->end2;
        }
        return null;
    }

    /**
     * Retrieve the related end for the given resource set, type and property.
     *
     * @param ResourceSet        $resourceSet      Resource set for the end
     * @param ResourceEntityType $resourceType     Resource type for the end
     * @param ResourceProperty   $resourceProperty Resource property for the end
     *
     * @return ResourceAssociationSetEnd|null Related resource association set end
     *                                        for the given parameters
     */
    public function getRelatedResourceAssociationSetEnd(
        ResourceSet $resourceSet,
        ResourceEntityType $resourceType,
        ResourceProperty $resourceProperty
    ): ?ResourceAssociationSetEnd {
        if ($this->end1->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->end2;
        }

        if ($this->end2->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->end1;
        }
        return null;
    }

    /**
     * Get name of the association set.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get first end of the association set.
     *
     * @return ResourceAssociationSetEnd
     */
    public function getEnd1(): ResourceAssociationSetEnd
    {
        return $this->end1;
    }

    /**
     * Get second end of the association set.
     *
     * @return ResourceAssociationSetEnd
     */
    public function getEnd2(): ResourceAssociationSetEnd
    {
        return $this->end2;
    }

    /**
     * Whether this association set represents a two way relationship between
     * resource sets.
     *
     * @return bool true if relationship is bidirectional, otherwise false
     */
    public function isBidirectional(): bool
    {
        return null !== $this->end1->getResourceProperty()
            && null !== $this->end2->getResourceProperty();
    }
}
