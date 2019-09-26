<?php

namespace POData\Providers\Metadata\Type;

use MyCLabs\Enum\Enum;

/**
 * Class EdmPrimitiveType.
 * @method static STRING()
 * @method static INT32()
 * @method static DATETIME()
 * @method static DECIMAL()
 * @method static INT16()
 * @method static SINGLE()
 * @method static BINARY()
 * @method static BOOLEAN()
 * @method static GUID()
 * @method static OBJECT()
 * @method static INT64()
 * @method static BYTE()
 * @method static SBYTE()
 * @method static DOUBLE()
 * @method static VOID()
 */
class EdmPrimitiveType extends Enum
{
    const BINARY = TypeCode::BINARY;
    const BOOLEAN = TypeCode::BOOLEAN;
    const BYTE = TypeCode::BYTE;
    const DATETIME = TypeCode::DATETIME;
    const DECIMAL = TypeCode::DECIMAL;
    const DOUBLE = TypeCode::DOUBLE;
    const GUID = TypeCode::GUID;
    const INT16 = TypeCode::INT16;
    const INT32 = TypeCode::INT32;
    const INT64 = TypeCode::INT64;
    const SBYTE = TypeCode::SBYTE;
    const SINGLE = TypeCode::SINGLE;
    const STRING = TypeCode::STRING;
    const OBJECT = TypeCode::OBJECT;
    const VOID = TypeCode::VOID;
}
