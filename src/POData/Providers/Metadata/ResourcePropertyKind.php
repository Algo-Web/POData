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
 * @method setPRIMITIVE()
 */
class ResourcePropertyKind extends BitMask
{
    protected const NONE = 0;

    /**
     * A bag of primitive or complex types.
     */
    protected const BAG = 1;

    /**
     * A complex (compound) property.
     */
    protected const COMPLEX_TYPE = 2;

    /**
     * Whether this property is a etag property.
     */
    protected const ETAG = 4;

    /**
     * A property that is part of the key.
     */
    protected const KEY = 8;

    /**
     * A primitive type property.
     */
    protected const PRIMITIVE = 16;

    /**
     * A reference to another resource.
     */
    protected const RESOURCE_REFERENCE = 32;

    /**
     * A reference to another resource set.
     */
    protected const RESOURCESET_REFERENCE = 64;
}
