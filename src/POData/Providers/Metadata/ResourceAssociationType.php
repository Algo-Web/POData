<?php

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
    private $_fullName;

    /**
     * Name of the association.
     *
     * @var string
     */
    private $_name;

    /**
     * end1 for this association.
     *
     * @var ResourceAssociationTypeEnd
     */
    private $_end1;

    /**
     * end2 for this association.
     *
     * @var ResourceAssociationTypeEnd
     */
    private $_end2;

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
        $name,
        $namespaceName,
        ResourceAssociationTypeEnd $end1,
        ResourceAssociationTypeEnd $end2
    ) {
        $this->_name = $name;
        $this->_fullName = !is_null($namespaceName)
                          ? $namespaceName . '.' . $name : $name;
        $this->_end1 = $end1;
        $this->_end2 = $end2;
    }

    /**
     * Gets name of the association.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Gets full-name of the association.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->_fullName;
    }

    /**
     * Gets reference to first end.
     *
     * @return ResourceAssociationTypeEnd
     */
    public function getEnd1()
    {
        return $this->_end1;
    }

    /**
     * Gets reference to second end.
     *
     * @return ResourceAssociationTypeEnd
     */
    public function getEnd2()
    {
        return $this->_end2;
    }

    /**
     * Retrieve the end for the given resource type and property.
     *
     * @param ResourceType     $resourceType     Resource type for the source end
     * @param ResourceProperty $resourceProperty Resource property for the source end
     *
     * @return ResourceAssociationTypeEnd Association type end for the
     *                                    given parameters
     */
    public function getResourceAssociationTypeEnd(
        ResourceType $resourceType,
        $resourceProperty
    ) {
        if ($this->_end1->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->_end1;
        }

        if ($this->_end2->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->_end2;
        }
    }

    /**
     * Retrieve the related end for the given resource set, type and property.
     *
     * @param ResourceType     $resourceType     Resource type for the source end
     * @param ResourceProperty $resourceProperty Resource property for the source end
     *
     * @return ResourceAssociationTypeEndRelated Association type end for the
     *                                           given parameters
     */
    public function getRelatedResourceAssociationSetEnd(
        ResourceType $resourceType,
        $resourceProperty
    ) {
        if ($this->_end1->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->_end2;
        }

        if ($this->_end2->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->_end1;
        }
    }
}
