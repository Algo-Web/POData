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
     * ODataProperty constructor.
     * @param string $name
     * @param string $typeName
     * @param XMLAttribute[] $attributeExtensions
     * @param ODataBagContent|ODataPropertyContent|string $value
     */
    public function __construct(string $name, ?string $typeName, $value, array $attributeExtensions = null)
    {
        $this->name = $name;
        $this->typeName = $typeName;
        $this->attributeExtensions = $attributeExtensions;
        $this->value = $value;
    }

    /**
     * @return bool|null
     */
    public function isNull(): ?bool
    {
        return null === $this->value ? true : null;
    }
}
