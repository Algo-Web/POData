<?php

namespace ODataProducer\Providers\Metadata\Type;

/**
 * Class INavigationType
 *
 * Navigation types (Complex, Reference, ReferenceSet) should
 * implements this interface
 *
 * @package ODataProducer\Providers\Metadata\Type
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