<?php

declare(strict_types=1);

namespace POData\OperationContext;

use MyCLabs\Enum\Enum;

/**
 * Class HTTPRequestMethod.
 *
 * @method static HTTPRequestMethod GET()
 * @method static HTTPRequestMethod POST()
 * @method static HTTPRequestMethod PUT()
 * @method static HTTPRequestMethod DELETE()
 * @method static HTTPRequestMethod PATCH()
 * @method static HTTPRequestMethod MERGE()
 * @method static HTTPRequestMethod NONE()
 */
class HTTPRequestMethod extends Enum
{
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const DELETE = 'DELETE';
    const PATCH  = 'PATCH';
    const MERGE  = 'MERGE';
    const NONE   = 'NONE';
}
