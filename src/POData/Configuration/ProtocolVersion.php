<?php

declare(strict_types=1);

namespace POData\Configuration;

use MyCLabs\Enum\Enum;

/**
 * @method static ProtocolVersion V1()
 * @method static ProtocolVersion V2()
 * @method static ProtocolVersion V3()
 */
class ProtocolVersion extends Enum
{
    /**
     * Version 1 of the OData protocol.
     */
    protected const V1 = 1;

    /**
     * Version 2 of the OData protocol.
     */
    protected const V2 = 2;

    /**
     * Version 3 of the OData protocol.
     */
    protected const V3 = 3;
}
