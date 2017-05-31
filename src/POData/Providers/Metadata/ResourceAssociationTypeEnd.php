<?php

namespace POData\Providers\Metadata;

use POData\Common\Messages;
use POData\Common\ODataConstants;

/**
 * Class ResourceAssociationTypeEnd represents association (relationship) end.
 *
 * Entities (described using ResourceType) can have relationship between them.
 * A relationship (described using ResourceAssociationType) composed of two ends
 * (described using ResourceAssociationTypeEnd).
 * s
 */
class ResourceAssociationTypeEnd
{
    /**
     * Name of the association end.
     *
     * @var string
     */
    private $name;

    /**
     * Type of the entity in the relationship end.
     *
     * @var ResourceEntityType
     */
    private $resourceType;

    /**
     * Entity property involved in the relationship end.
     *
     * @var ResourceProperty
     */
    private $resourceProperty;

    /**
     * Property of the entity involved in the relationship end points to this end.
     * The multiplicity of this end is determined from the fromProperty.
     *
     * @var ResourceProperty
     */
    private $fromProperty;

    /**
     * Construct new instance of ResourceAssociationTypeEnd.
     *
     * @param string                $name             name of the end
     * @param ResourceEntityType    $resourceType     resource type that the end
     *                                                refers to
     * @param ResourceProperty|null $resourceProperty property of the end, can be
     *                                                NULL if relationship is
     *                                                uni-directional
     * @param ResourceProperty|null $fromProperty     Property on the related end
     *                                                that points to this end, can
     *                                                be NULL if relationship is
     *                                                uni-directional
     */
    public function __construct(
        $name,
        ResourceEntityType $resourceType,
        $resourceProperty,
        $fromProperty
    ) {
        if (is_null($resourceProperty) && is_null($fromProperty)) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationTypeEndBothPropertyCannotBeNull()
            );
        }

        if (!is_null($fromProperty)
            && !($fromProperty instanceof ResourceProperty)
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationTypeEndPropertyMustBeNullOrInstanceofResourceProperty(
                    '$fromProperty'
                )
            );
        }

        if (!is_null($resourceProperty)
            && !($resourceProperty instanceof ResourceProperty)
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationTypeEndPropertyMustBeNullOrInstanceofResourceProperty(
                    '$resourceProperty'
                )
            );
        }

        $this->name = $name;
        $this->resourceType = $resourceType;
        $this->resourceProperty = $resourceProperty;
        $this->fromProperty = $fromProperty;
    }

    /**
     * To check this relationship belongs to a specfic entity property.
     *
     * @param ResourceEntityType    $resourceType     The type of the entity
     * @param ResourceProperty|null $resourceProperty The property in the entity
     *
     * @return bool
     */
    public function isBelongsTo(ResourceEntityType $resourceType, $resourceProperty)
    {
        $flag1 = is_null($resourceProperty);
        $flag2 = is_null($this->resourceProperty);
        if ($flag1 != $flag2) {
            return false;
        }

        $typeNameMatch = 0 == strcmp($resourceType->getFullName(), $this->resourceType->getFullName());

        if (true === $flag1) {
            return $typeNameMatch;
        }
        assert(isset($resourceProperty));
        $propertyNameMatch = 0 == strcmp($resourceProperty->getName(), $this->resourceProperty->getName());

        return $typeNameMatch && $propertyNameMatch;
    }

    /**
     * Get the name of the end.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the resource type that the end refers to.
     *
     * @return ResourceEntityType
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Get the property of the end.
     *
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->resourceProperty;
    }

    /**
     * Get the Multiplicity of the relationship end.
     *
     * @return string
     */
    public function getMultiplicity()
    {
        if (!is_null($this->fromProperty)
            && $this->fromProperty->getKind() == ResourcePropertyKind::RESOURCE_REFERENCE
        ) {
            return ODataConstants::ZERO_OR_ONE;
        }

        return ODataConstants::MANY;
    }
}
