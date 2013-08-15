<?php


namespace ODataProducer\ObjectModel;

/**
 * Class ODataBagContent
 *  Represents value of a bag (collection) property. Bag can be of two types:
 *  (1) Primitive Bag
 *  (2) Complex Bag
 *
 * @package ODataProducer\ObjectModel
 */
class ODataBagContent
{
    /**
     * The type name of the element
     * @var string
     */
    public $type;
    /**
     * 
     * Represents elements of the bag.
     * @var array<string/PropertyContent>
     */
    public $propertyContents;

    /**
     * Constructs a new instance of ODataBagContent
     */
    public function __construct()
    {
    }
}