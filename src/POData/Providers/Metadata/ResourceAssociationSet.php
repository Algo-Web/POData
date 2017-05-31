<?php

namespace POData\Providers\Metadata;

use POData\Common\Messages;

/**
 * Class ResourceAssociationSet.
 */
class ResourceAssociationSet
{
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
     * Note: This property will be populated by the library,
     * so IDSMP implementor should not set this.
     * The association type hold by this association set.
     *
     * @var ResourceAssociationType
     */
    public $resourceAssociationType;

    /**
     * Construct new instance of ResourceAssociationSet.
     *
     * @param string                    $name Name of the association set
     * @param ResourceAssociationSetEnd $end1 First end set participating
     *                                        in the association set
     * @param ResourceAssociationSetEnd $end2 Second end set participating
     *                                        in the association set
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        $name,
        ResourceAssociationSetEnd $end1,
        ResourceAssociationSetEnd $end2
    ) {
        if (is_null($end1->getResourceProperty())
            && is_null($end2->getResourceProperty())
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetResourcePropertyCannotBeBothNull()
            );
        }

        if ($end1->getResourceType() == $end2->getResourceType()
            && $end1->getResourceProperty() == $end2->getResourceProperty()
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetSelfReferencingAssociationCannotBeBiDirectional()
            );
        }

        $this->name = $name;
        $this->end1 = $end1;
        $this->end2 = $end2;
    }

    /**
     * Retrieve the end for the given resource set, type and property.
     *
     * @param ResourceSet           $resourceSet      Resource set for the end
     * @param ResourceEntityType    $resourceType     Resource type for the end
     * @param ResourceProperty      $resourceProperty Resource property for the end
     *
     * @return ResourceAssociationSetEnd|null Resource association set end for the
     *                                   given parameters
     */
    public function getResourceAssociationSetEnd(
        ResourceSet $resourceSet,
        ResourceEntityType $resourceType,
        ResourceProperty $resourceProperty
    ) {
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
     * @param ResourceSet           $resourceSet      Resource set for the end
     * @param ResourceEntityType    $resourceType     Resource type for the end
     * @param ResourceProperty      $resourceProperty Resource property for the end
     *
     * @return ResourceAssociationSetEnd|null Related resource association set end
     *                                   for the given parameters
     */
    public function getRelatedResourceAssociationSetEnd(
        ResourceSet $resourceSet,
        ResourceEntityType $resourceType,
        ResourceProperty $resourceProperty
    ) {
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get first end of the association set.
     *
     *  @return ResourceAssociationSetEnd
     */
    public function getEnd1()
    {
        return $this->end1;
    }

    /**
     * Get second end of the association set.
     *
     *  @return ResourceAssociationSetEnd
     */
    public function getEnd2()
    {
        return $this->end2;
    }

    /**
     * Whether this association set represents a two way relationship between
     * resource sets.
     *
     * @return bool true if relationship is bidirectional, otherwise false
     */
    public function isBidirectional()
    {
        return !is_null($this->end1->getResourceProperty())
            && !is_null($this->end2->getResourceProperty());
    }


    public static function keyName(ResourceEntityType $sourceType, $linkName, ResourceSet $targetResourceSet)
    {
        return $sourceType->getName() . '_' . $linkName . '_' . $targetResourceSet->getResourceType()->getName();
    }
}
