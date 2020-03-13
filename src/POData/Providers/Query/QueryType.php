<?php

declare(strict_types=1);

namespace POData\Providers\Query;

use MyCLabs\Enum\Enum;

/**
 * @method static QueryType ENTITIES()
 * @method static QueryType COUNT()
 * @method static QueryType ENTITIES_WITH_COUNT()
 */
class QueryType extends Enum
{
    const ENTITIES            = 'ENTITIES';
    const COUNT               = 'COUNT';
    const ENTITIES_WITH_COUNT = 'ENTITIES_WITH_COUNT';

    /**
     * Check if supplied query type covers entities.
     *
     * @param  QueryType $queryType
     * @return bool
     */
    public static function hasEntities(QueryType $queryType)
    {
        return QueryType::ENTITIES() == $queryType || QueryType::ENTITIES_WITH_COUNT() == $queryType;
    }

    /**
     * Check if supplied query type covers record counts.
     *
     * @param  QueryType $queryType
     * @return bool
     */
    public static function hasCount(QueryType $queryType)
    {
        return QueryType::COUNT() == $queryType || QueryType::ENTITIES_WITH_COUNT() == $queryType;
    }
}
