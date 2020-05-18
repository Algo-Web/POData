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
    private $name;
    /**
     * The property type name.
     *
     * @var string|null
     */
    private $typeName;

    /**
     * The value of the property.
     *
     * @var mixed|ODataPropertyContent|ODataBagContent|null
     */
    private $value;
    /**
     * The property attribute extensions.
     *
     * @var XMLAttribute[]|null
     */
    public $attributeExtensions;
    /**
     * ODataProperty constructor.
     * @param string $name
     * @param string $typeName
     * @param XMLAttribute[] $attributeExtensions
     * @param ODataBagContent|ODataPropertyContent|string $value
     */
    public function __construct(string $name, ?string $typeName, $value, array $attributeExtensions = [])
    {
        $this
            ->setName($name)
            ->setTypeName($typeName)
            ->setValue($value)
            ->setAttributeExtensions($attributeExtensions);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ODataProperty
     */
    public function setName(string $name): ODataProperty
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTypeName(): ?string
    {
        return $this->typeName;
    }

    /**
     * @param string $typeName
     * @return ODataProperty
     */
    public function setTypeName(?string $typeName): ODataProperty
    {
        $this->typeName = $typeName;
        return $this;
    }

    /**
     * @return XMLAttribute[]|null
     */
    public function getAttributeExtensions(): ?array
    {
        return $this->attributeExtensions;
    }

    /**
     * @param XMLAttribute[]|null $attributeExtensions
     * @return ODataProperty
     */
    public function setAttributeExtensions(?array $attributeExtensions): ODataProperty
    {
        $this->attributeExtensions = $attributeExtensions;
        return $this;
    }

    /**
     * @return ODataBagContent|ODataPropertyContent|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param ODataBagContent|ODataPropertyContent|string $value
     * @return ODataProperty
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isNull(): ?bool
    {
        return null === $this->value ? true : null;
    }
}
