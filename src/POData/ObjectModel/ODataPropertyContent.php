<?php

namespace POData\ObjectModel;

use POData\Providers\Metadata\Type\Boolean;


/**
 * Class ODataPropertyContent represents properties of a Complex type or entity element instance.
 * @package POData\ObjectModel
 */
class ODataPropertyContent
{
    /**
     * 
     * The collection of properties
     * @var ODataProperty[]
     */
    public $properties;
    /**
     * 
     * To check if top level or not
     * @var Boolean
     */
    public $isTopLevel;

    /**
     * Constructs a new instance of ODataPropertyContent
     * 
     * @param Boolean $isTopLevel Top level or not
     * 
     * @return void
     */
    public function __construct($isTopLevel = false)
    {
        $this->isTopLevel = $isTopLevel;
        $this->properties = array();
    }
}