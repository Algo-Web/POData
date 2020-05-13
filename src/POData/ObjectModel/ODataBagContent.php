<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataBagContent
 *  Represents value of a bag (collection) property. Bag can be of two types:
 *  (1) Primitive Bag
 *  (2) Complex Bag.
 */
class ODataBagContent
{
    /**
     * The type name of the element.
     *
     * @var string|null
     */
    private $type;
    /**
     * Represents elements of the bag.
     *
     * @var string[]|ODataPropertyContent[]|null
     */
    public $propertyContents;

    /**
     * ODataBagContent constructor.
     * @param string $type
     * @param ODataPropertyContent[]|string[] $propertyContents
     */
    public function __construct(string $type = null, array $propertyContents = null)
    {
        $this->type = $type;
        $this->propertyContents = $propertyContents;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     * @return ODataBagContent
     */
    public function setType(?string $type): ODataBagContent
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return ODataPropertyContent[]|string[]
     */
    public function getPropertyContents()
    {
        return $this->propertyContents;
    }

    /**
     * @param ODataPropertyContent[]|string[] $propertyContents
     * @return ODataBagContent
     */
    public function setPropertyContents($propertyContents)
    {
        $this->propertyContents = $propertyContents;
        return $this;
    }

    public function addPropertyContent($propertyContent){
        $this->propertyContents[] = $propertyContent;
        return $this;

    }

}
