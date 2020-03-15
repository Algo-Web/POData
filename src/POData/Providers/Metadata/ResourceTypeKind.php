<?php

declare(strict_types=1);

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
    protected const COMPLEX = 1;

    /**
     * An entity type resource.
     */
    protected const ENTITY = 2;

    /**
     * A primitive type resource.
     */
    protected const PRIMITIVE = 3;
}
