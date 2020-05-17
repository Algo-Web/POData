<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataPropertyContent represents properties of a Complex type or entity element instance.
 */
class ODataPropertyContent
{
    /**
     * The collection of properties.
     *
     * @var ODataProperty[]
     */
    public $properties = [];

    /**
     * ODataPropertyContent constructor.
     * @param ODataProperty[] $properties
     */
    public function __construct(array $properties)
    {
        $this->setPropertys($properties);
    }

    /**
     * @return ODataProperty[]
     */
    public function getPropertys(): array
    {
        return $this->properties;
    }

    /**
     * @param $newProperties ODataProperty[]
     * @return ODataPropertyContent
     */
    public function setPropertys(array $newProperties): self
    {
        $this->properties = $newProperties;
        return $this;
    }
}
