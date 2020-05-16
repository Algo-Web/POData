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
        foreach ($newProperties as $key => $property) {
            $property->name = $key;
        }
        $this->properties = $newProperties;
        return $this;
    }
}
