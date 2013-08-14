<?php
/**
 * Enum for different resource types
 * 
*/
namespace ODataProducer\Providers\Metadata;
/**
 * Enum for resource types.
*
 */
class ResourceTypeKind
{
    /**
     * A complex type resource
     */
    const COMPLEX = 1;

    /**
     * An entity type resource
     */
    const ENTITY = 2;

    /**
     * A primitive type resource
     */
    const PRIMITIVE = 3;
}
?>