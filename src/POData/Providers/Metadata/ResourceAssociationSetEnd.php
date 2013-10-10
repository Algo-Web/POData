<?php

namespace POData\Providers\Metadata;

use POData\Common\Messages;


/**
 * Class ResourceAssociationSetEnd represents association (relationship) set end.
 * @package POData\Providers\Metadata
 */
class ResourceAssociationSetEnd
{
    /**
     * Resource set for the association end.
     * @var ResourceSet
     */
    private $_resourceSet;

    /**
     * Resource type for the association end.
     * @var ResourceType
     */
    private $_resourceType;

    /**
     * Resource property for the association end.
     * @var ResourceProperty
     */
    private $_resourceProperty;

    /**
     * Construct new instance of ResourceAssociationSetEnd
     * Note: The $resourceSet represents collection of an entity, The 
     * $resourceType can be this entity's type or type of any of the 
     * base resource of this entity, on which the navigation property 
     * represented by $resourceProperty is defined.
     *   
     * @param ResourceSet      $resourceSet      Resource set for the association end
     * @param ResourceType     $resourceType     Resource type for the association end
     * @param ResourceProperty $resourceProperty Resource property for the association end
     * 
     * @throws \InvalidArgumentException
     */
    public function __construct(ResourceSet $resourceSet, 
        ResourceType $resourceType, $resourceProperty
    ) {
        if (!is_null($resourceProperty) 
            && !($resourceProperty instanceof ResourceProperty)
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetPropertyMustBeNullOrInstanceofResourceProperty(
                    '$resourceProperty'
                )
            );
        }

        if (!is_null($resourceProperty) 
            && (is_null($resourceType->resolveProperty($resourceProperty->getName())) || (($resourceProperty->getKind() != ResourcePropertyKind::RESOURCE_REFERENCE) && ($resourceProperty->getKind() != ResourcePropertyKind::RESOURCESET_REFERENCE)))
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetEndPropertyMustBeNavigationProperty(
                    $resourceProperty->getName(), $resourceType->getFullName()
                )
            );
        }
        
        if (!$resourceSet->getResourceType()->isAssignableFrom($resourceType) 
            && !$resourceType->isAssignableFrom($resourceSet->getResourceType())
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetEndResourceTypeMustBeAssignableToResourceSet(
                    $resourceType->getFullName(), $resourceSet->getName()
                )
            );
        }
        
        $this->_resourceSet = $resourceSet;
        $this->_resourceType = $resourceType;
        $this->_resourceProperty = $resourceProperty;
    }

    /**
     * To check this relationship belongs to a specific resource set, type
     * and property
     * 
     * @param ResourceSet      $resourceSet      Resource set for the association
     *                                           end
     * @param ResourceType     $resourceType     Resource type for the association
     *                                           end
     * @param ResourceProperty $resourceProperty Resource property for the 
     *                                           association end
     * 
     * @return boolean
     */
    public function isBelongsTo(ResourceSet $resourceSet, 
        ResourceType $resourceType, ResourceProperty $resourceProperty
    ) {
        return (strcmp($resourceSet->getName(), $this->_resourceSet->getName()) == 0 
            && $this->_resourceType->isAssignableFrom($resourceType) 
            && ((is_null($resourceProperty) && is_null($this->_resourceProperty)) ||
                  (!is_null($resourceProperty) && !is_null($this->_resourceProperty) && (strcmp($resourceProperty->getName(), $this->_resourceProperty->getName()) == 0)))
        );
    }

    /**
     * Gets reference to resource set
     * 
     * @return ResourceSet
     */
    public function getResourceSet()
    {
        return $this->_resourceSet;
    }

    /**
     * Gets reference to resource type 
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_resourceType;
    }

    /**
     * Gets reference to resource property
     * 
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->_resourceProperty;
    }
}