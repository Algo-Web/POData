<?php

namespace POData\OperationContext;

use MyCLabs\Enum\Enum;

/**
 * Class HTTPRequestMethod.
 *
 * @method static \POData\OperationContext\HTTPRequestMethod GET()
 * @method static \POData\OperationContext\HTTPRequestMethod POST()
 * @method static \POData\OperationContext\HTTPRequestMethod PUT()
 * @method static \POData\OperationContext\HTTPRequestMethod DELETE()
 * @method static \POData\OperationContext\HTTPRequestMethod PATCH()
 * @method static \POData\OperationContext\HTTPRequestMethod MERGE()
 * @method static \POData\OperationContext\HTTPRequestMethod NONE()
 */
class HTTPRequestMethod extends Enum
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const PATCH = 'PATCH';
    const MERGE = 'MERGE';
    const NONE = 'NONE';
}
