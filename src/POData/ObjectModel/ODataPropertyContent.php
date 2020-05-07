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
    public function getPropertys()
    {
        return $this->properties;
    }

    /**
     * @param $newProperties ODataProperty[]
     */
    public function setPropertys(array $newProperties)
    {
        foreach ($newProperties as $key => $property) {
            $property->name = $key;
        }
        $this->properties = $newProperties;
    }
}
