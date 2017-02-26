<?php

namespace POData\ObjectModel;

use POData\Providers\Metadata\ResourceProperty;

/**
 * Class ODataNavigationPropertyInfo A type to hold navigation information.
 */
class ODataNavigationPropertyInfo
{
    public $resourceProperty;
    public $expanded;
    public $value;

    /**
     * Constructs a new instance of ODataNavigationPropertyInfo.
     *
     * @param ResourceProperty &$resourceProperty Metadata of the
     *                                            navigation property
     * @param bool             $expanded          Whether the navigation is expanded
     *                                            or not
     */
    public function __construct(ResourceProperty & $resourceProperty, $expanded)
    {
        $this->resourceProperty = $resourceProperty;
        $this->expanded = $expanded;
        $this->value = null;
    }
}
