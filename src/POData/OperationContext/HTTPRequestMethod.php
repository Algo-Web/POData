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
    protected const GET    = 'GET';
    protected const POST   = 'POST';
    protected const PUT    = 'PUT';
    protected const DELETE = 'DELETE';
    protected const PATCH  = 'PATCH';
    protected const MERGE  = 'MERGE';
    protected const NONE   = 'NONE';
}
