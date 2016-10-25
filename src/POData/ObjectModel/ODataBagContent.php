<?php

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
     * @var string
     */
    public $type;
    /**
     * Represents elements of the bag.
     *
     * @var string[]|ODataPropertyContent[]
     */
    public $propertyContents;
}
