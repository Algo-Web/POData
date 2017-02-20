<?php

namespace POData\Common\Messages;

trait resourceSet
{
    /**
     * The error message to show when tyring to
     * associate resource set with non-entity.
     *
     * @return string The message
     */
    public static function resourceSetContainerMustBeAssociatedWithEntityType()
    {
        return 'The ResourceTypeKind property of a ResourceType instance associated with a ResourceSet must be equal to \'EntityType\'';
    }
}
