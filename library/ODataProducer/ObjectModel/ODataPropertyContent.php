<?php

namespace ODataProducer\ObjectModel;

use ODataProducer\Providers\Metadata\Type\Boolean;


/**
 * Class ODataPropertyContent represents properties of a Complex type or entity element instance.
 * @package ODataProducer\ObjectModel
 */
class ODataPropertyContent
{
    /**
     * 
     * The collection of properties
     * @var array<odataProperty>
     */
    public $odataProperty;
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
        $this->odataProperty = array();
    }
}