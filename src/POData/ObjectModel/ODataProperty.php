<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataProperty
 * Represents a property that comes under "m:properties" node or entry
 * or under complex property.
 */
class ODataProperty
{
    /**
     * The name of the property.
     *
     * @var string
     */
    public $name;
    /**
     * The property type name.
     *
     * @var string
     */
    public $typeName;
    /**
     * The property attribute extensions.
     *
     * @var XMLAttribute[]
     */
    public $attributeExtensions;
    /**
     * The value of the property.
     *
     * @var string|ODataPropertyContent|ODataBagContent
     */
    public $value;

    /**
     * @return bool|null
     */
    public function isNull()
    {
        return null === $this->value ? true : null;
    }
}
