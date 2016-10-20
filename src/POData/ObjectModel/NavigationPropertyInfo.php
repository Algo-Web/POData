<?php


namespace POData\ObjectModel;

use POData\Providers\Metadata\ResourceProperty;

/**
 * Class NavigationPropertyInfo A type to hold navigation information.
 * @package POData\ObjectModel
 */
class NavigationPropertyInfo
{
    public $resourceProperty;
    public $expanded;
    public $value;

    /**
     * Constructs a new instance of NavigationPropertyInfo
     * 
     * @param ResourceProperty &$resourceProperty Metadata of the 
     *                                            navigation property.
     * @param boolean          $expanded          Whether the navigation is expanded
     *                                            or not.   
     */
    public function __construct(ResourceProperty & $resourceProperty, $expanded)
    {
        $this->resourceProperty = $resourceProperty;
        $this->expanded = $expanded;
        $this->value = null;
    }
}