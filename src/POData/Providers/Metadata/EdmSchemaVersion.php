<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

use MyCLabs\Enum\Enum;

/**
 * Class EdmSchemaVersion.
 * @method static VERSION_1_DOT_0()
 * @method static VERSION_1_DOT_1()
 * @method static VERSION_1_DOT_2()
 * @method static VERSION_2_DOT_0()
 * @method static VERSION_2_DOT_2()
 */
class EdmSchemaVersion extends Enum
{
    /**
     * Edm Schema v1.0.
     */
    protected const VERSION_1_DOT_0 = 1.0;

    /**
     * Edm Schema v1.1.
     */
    protected const VERSION_1_DOT_1 = 1.1;

    /**
     * Edm Schema v1.2.
     */
    protected const VERSION_1_DOT_2 = 1.2;

    /**
     * Edm Schema v2.0.
     */
    protected const VERSION_2_DOT_0 = 2.0;

    /**
     * Edm Schema v2.2.
     */
    protected const VERSION_2_DOT_2 = 2.2;
}
