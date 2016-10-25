<?php

namespace POData\Providers\Query;

use MyCLabs\Enum\Enum;

/**
 * @method static \POData\Providers\Query\QueryType ENTITIES()
 * @method static \POData\Providers\Query\QueryType COUNT()
 * @method static \POData\Providers\Query\QueryType ENTITIES_WITH_COUNT()
 */
class QueryType extends Enum
{
    const ENTITIES = 'ENTITIES';
    const COUNT = 'COUNT';
    const ENTITIES_WITH_COUNT = 'ENTITIES_WITH_COUNT';
}
