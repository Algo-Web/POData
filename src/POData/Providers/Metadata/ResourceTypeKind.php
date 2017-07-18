<?php

namespace POData\Providers\Metadata;

use MyCLabs\Enum\Enum;

/**
 * @method static ResourceTypeKind COMPLEX()
 * @method static ResourceTypeKind ENTITY()
 * @method static ResourceTypeKind PRIMITIVE()
 */
class ResourceTypeKind extends Enum
{
    /**
     * A complex type resource.
     */
    const COMPLEX = 1;

    /**
     * An entity type resource.
     */
    const ENTITY = 2;

    /**
     * A primitive type resource.
     */
    const PRIMITIVE = 3;
}
