<?php

namespace POData\Configuration;

use MyCLabs\Enum\Enum;

/**
 * @method static \POData\Configuration\ProtocolVersion V1()
 * @method static \POData\Configuration\ProtocolVersion V2()
 * @method static \POData\Configuration\ProtocolVersion V3()
 */
class ProtocolVersion extends Enum
{
    /**
     * Version 1 of the OData protocol.
     */
    const V1 = 1;

    /**
     * Version 2 of the OData protocol.
     */
    const V2 = 2;

    /**
     * Version 3 of the OData protocol.
     */
    const V3 = 3;
}
