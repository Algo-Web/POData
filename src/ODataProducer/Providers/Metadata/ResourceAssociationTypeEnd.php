<?php

namespace ODataProducer\Providers\Metadata;

use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;

/**
 * Class ResourceAssociationTypeEnd represents association (relationship) end.
 *
 * Entities (described using ResourceType) can have relationship between them.
 * A relationship (described using ResourceAssociationType) composed of two ends
 * (described using ResourceAssociationTypeEnd).
 * s
 * @package ODataProducer\Providers\Metadata
 */
class ResourceAssociationTypeEnd
{
    /**
     * Name of the association end
     * @var string
     */
    private $_name;
    
    /**
     * Type of the entity in the relationship end
     * @var ResourceType
     */
    private $_resourceType;

    /**
     * Entity property involved in the relationship end
     * @var ResourceProperty
     */
    private $_resourceProperty;

    /**
     * Property of the entity involved in the relationship end points to this end.
     * The multiplicity of this end is determined from the fromProperty.
     * @var ResourceProperty
     */
    private $_fromProperty;
    
    /**
     * Construct new instance of ResourceAssociationTypeEnd
     * 
     * @param string                $name             name of the end
     * @param ResourceType          $resourceType     resource type that the end 
     *                                                refers to
     * @param ResourceProperty|null $resourceProperty property of the end, can be 
     *                                                NULL if relationship is 
     *                                                uni-directional
     * @param ResourceProperty|null $fromProperty     Property on the related end 
     *                                                that points to this end, can 
     *                                                be NULL if relationship is 
     *                                                uni-directional
     */
    public function __construct($name, ResourceType $resourceType, 
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
                Messages::resourceAssociationTypeEndPropertyMustBeNullOrInsatnceofResourceProperty(
                    '$fromProperty'
                )
            );
        }

        if (!is_null($resourceProperty) 
            && !($resourceProperty instanceof ResourceProperty)
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationTypeEndPropertyMustBeNullOrInsatnceofResourceProperty(
                    '$$resourceProperty'
                )
            );
        }
        
        $this->_name = $name;
        $this->_resourceType = $resourceType;
        $this->_resourceProperty = $resourceProperty;
        $this->_fromProperty = $fromProperty;
    }

    /**
     * To check this relationship belongs to a specfic entity property
     *  
     * @param ResourceType          $resourceType     The type of the entity
     * @param ResourceProperty|null $resourceProperty The property in the entity
     * 
     * @return boolean
     */
    public function isBelongsTo(ResourceType $resourceType, $resourceProperty) 
    {
        $flag1 = is_null($resourceProperty);
        $flag2 = is_null($this->_resourceProperty);
        if ($flag1 != $flag2) {
            return false;
        }

        if ($flag1 === true) {
            return strcmp(
                $resourceType->getFullName(), 
                $this->_resourceType->getFullName()
            ) == 0;
        }

        return strcmp(
            $resourceType->getFullName(), $this->_resourceType->getFullName()
        ) == 0
        && (strcmp(
            $resourceProperty->getName(), $this->_resourceProperty->getName()
        ) == 0);
    }

    /**
     * Get the name of the end
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get the resource type that the end refers to
     * 
     * @return string
     */
    public function getResourceType()
    {
        return $this->_resourceType;
    }

    /**
     * Get the property of the end
     * 
     * @return string
     */
    public function getResourceProperty()
    {
        return $this->_resourceProperty;
    }

    /**
     * Get the Mulitplicity of the relationship end
     * 
     * @return string
     */
    public function getMultiplicity()
    {
        if (!is_null($this->_fromProperty) 
            && $this->_fromProperty->getKind() == ResourcePropertyKind::RESOURCE_REFERENCE
        ) {
            return ODataConstants::ZERO_OR_ONE;
        }
        
        return ODataConstants::MANY;
    }
}