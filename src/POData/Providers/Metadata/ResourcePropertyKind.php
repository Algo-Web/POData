<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

use Cruxinator\BitMask\BitMask;

/**
 * Class ResourcePropertyKind.
 * @method static KEY()
 * @method static ETAG()
 * @method static COMPLEX_TYPE()
 * @method static BAG()
 * @method static PRIMITIVE()
 * @method static RESOURCE_REFERENCE()
 * @method static RESOURCESET_REFERENCE()
 * @method static NONE()
 */
class ResourcePropertyKind extends BitMask
{
    const NONE = 0;

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
