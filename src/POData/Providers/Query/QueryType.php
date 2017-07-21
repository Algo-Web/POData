<?php

namespace POData\Providers\Query;

use MyCLabs\Enum\Enum;

/**
 * @method static QueryType ENTITIES()
 * @method static QueryType COUNT()
 * @method static QueryType ENTITIES_WITH_COUNT()
 */
class QueryType extends Enum
{
    const ENTITIES = 'ENTITIES';
    const COUNT = 'COUNT';
    const ENTITIES_WITH_COUNT = 'ENTITIES_WITH_COUNT';
}
