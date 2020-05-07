<?php

declare(strict_types=1);

namespace POData\Providers\Metadata\Type;

use MyCLabs\Enum\Enum;

/**
 * Class TypeCode.
 * @method static OBJECT()
 * @method static VOID()
 * @method static DECIMAL()
 * @method static BINARY()
 * @method static BOOLEAN()
 * @method static BYTE()
 * @method static CHAR()
 * @method static DATETIME()
 * @method static DOUBLE()
 * @method static STRING()
 * @method static GUID()
 * @method static INT16()
 * @method static INT32()
 * @method static INT64()
 * @method static NULL1()
 * @method static SBYTE()
 * @method static SINGLE()
 * @method static NAVIGATION()
 */
class TypeCode extends Enum
{
    const BINARY = 1;
    const BOOLEAN = 2;
    const BYTE = 3;
    const CHAR = 4;
    const NAVIGATION = 5;
    const DATETIME = 6;
    const DECIMAL = 7;
    const DOUBLE = 8;
    const GUID = 9;
    const INT16 = 10;
    const INT32 = 11;
    const INT64 = 12;
    const OBJECT = 13;
    const SBYTE = 14;
    const SINGLE = 15;
    const STRING = 16;
    const VOID = 17;
    const NULL1 = 18;
}
