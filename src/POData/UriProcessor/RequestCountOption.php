<?php

namespace POData\UriProcessor;

use MyCLabs\Enum\Enum;

/**
 * Class RequestCountOption
 * @package POData\UriProcessor
 *
 *
 * @method static \POData\UriProcessor\RequestCountOption NONE()
 * @method static \POData\UriProcessor\RequestCountOption VALUE_ONLY()
 * @method static \POData\UriProcessor\RequestCountOption INLINE()
 */
class RequestCountOption extends Enum
{
    /**
     * No count option specified
     */
    const NONE = 0;

    /**
     * $count option specified
     */
    const VALUE_ONLY = 1;

    /**
     * $inlinecount option specified.
     */
    const INLINE = 2;
}