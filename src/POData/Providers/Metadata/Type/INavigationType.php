<?php

namespace POData\Providers\Metadata\Type;

/**
 * Class INavigationType
 *
 * Navigation types (Complex, Reference, ReferenceSet) should
 * implements this interface
 *
 * @package POData\Providers\Metadata\Type
 */
interface INavigationType extends IType
{
    /**
     * Gets the resource type associated with the navigation type
     * 
     * @return ResourceType
     */
    public function getResourceType();
}