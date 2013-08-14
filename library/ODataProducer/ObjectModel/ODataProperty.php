<?php
namespace ODataProducer\ObjectModel;

/**
 * Class ODataProperty
 * Represents a property that comes under "m:properties" node or entry
 * or under complex property
 *
 * @package ODataProducer\ObjectModel
 */
class ODataProperty
{
    /**
     * 
     * The name of the property
     * @var string
     */
    public $name;
    /**
     * 
     * The property type name
     * @var string
     */
    public $typeName;
    /**
     * 
     * The property attribute extensions
     * @var array<XMLAttribute>
     */
    public $attributeExtensions;
    /**
     * 
     * The value of the property. 
     * @var string/ODataPropertyContent/ODataBagContent
     */
    public $value;

    /**
     * Constructor for Initialization of Odata Property.
     */
    function __construct()
    {
    }
}