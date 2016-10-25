<?php

namespace POData\Providers\Metadata;

/**
 * Class ResourcePropertyKind.
 */
class ResourcePropertyKind
{
    /**
     * A bag of primitive or complex types.
     */
    const BAG = 1;

    /**
     * A complex (compound) property.
     */
    const COMPLEX_TYPE = 2;

    /**
     * Whether this property is a etag property.
     */
    const ETAG = 4;

    /**
     * A property that is part of the key.
     */
    const KEY = 8;

    /**
     * A primitive type property.
     */
    const PRIMITIVE = 16;

    /**
     * A reference to another resource.
     */
    const RESOURCE_REFERENCE = 32;

    /**
     * A reference to another resource set.
     */
    const RESOURCESET_REFERENCE = 64;
}
