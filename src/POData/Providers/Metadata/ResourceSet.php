<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

use InvalidArgumentException;

/**
 * Class ResourceSet Represents entity set.
 *
 * A type to represent entity set (resource set or container)
 */
class ResourceSet
{
    /**
     * name of the entity set (resource set, container).
     *
     * @var string
     */
    private $name;

    /**
     * The type hold by this container.
     *
     * @var ResourceEntityType
     */
    private $resourceType;

    /**
     * Creates new instance of ResourceSet.
     *
     * @param string             $name         Name of the resource set (entity set)
     * @param ResourceEntityType $resourceType Type ResourceType describing the resource
     *                                         this entity set holds
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $name, ResourceEntityType $resourceType)
    {
        $this->name         = $name;
        $this->resourceType = $resourceType;
    }

    /**
     * Get the container name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the type hold by this container.
     *
     * @return ResourceEntityType
     */
    public function getResourceType(): ResourceEntityType
    {
        return $this->resourceType;
    }
}
