<?php

namespace POData\Providers\Metadata;

use POData\Common\Messages;

/**
 * Class ResourceSet Represents entity set.
 *
 * A type to represent entity set (resource set or container)
 *
 * @package POData\Providers\Metadata
 */
class ResourceSet
{
    /**
     * name of the entity set (resource set, container)
     * 
     * @var string
     */
    private $_name;

    /**
     * The type hold by this container
     * 
     * @var ResourceType
     */
    private $_resourceType;

    /**
     * Creates new intstance of ResourceSet
     * 
     * @param string       $name         Name of the resource set (entity set)  
     * @param ResourceType $resourceType ResourceType describing the resource 
     *                                   this entity set holds
     * 
     * @throws \InvalidArgumentException
     */
    public function __construct($name, ResourceType $resourceType)
    {
        if ($resourceType->getResourceTypeKind() != ResourceTypeKind::ENTITY) {
            throw new \InvalidArgumentException(
                Messages::resourceSetContainerMustBeAssociatedWithEntityType()
            );
        }

        $this->_name = $name;
        $this->_resourceType = $resourceType;
    }

    /**
     * Get the container name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get the type hold by this container
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_resourceType;
    }
}