<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\OrderByParser;

use POData\Common\Messages;

/**
 * Class OrderByPathSegment.
 *
 * A type to represent path segment in an order by clause
 * Syntax of orderBy clause is:
 *
 * OrderByClause         : OrderByPathSegment [, OrderByPathSegment]*
 * OrderByPathSegment    : OrderBySubPathSegment [/OrderBySubPathSegment]*[asc|desc]?
 * OrderBySubPathSegment : identifier
 */
class OrderByPathSegment
{
    /**
     * Collection of sub path in this path segment.
     *
     * @var OrderBySubPathSegment[]
     */
    private $orderBySubPathSegments;

    /**
     * Flag indicates order of sorting, ascending or descending, default is ascending.
     *
     * @var bool
     */
    private $isAscending;

    /**
     * Constructs a new instance of OrderByPathSegment.
     *
     * @param OrderBySubPathSegment[] $orderBySubPathSegments Collection of orderBy sub path segments for this
     *                                                        path segment
     * @param bool                    $isAscending            sort order,
     *                                                        True for ascending and false for descending
     */
    public function __construct(array $orderBySubPathSegments, bool $isAscending = true)
    {
        if (!is_array($orderBySubPathSegments)) {
            throw new \InvalidArgumentException(
                Messages::orderByPathSegmentOrderBySubPathSegmentArgumentShouldBeNonEmptyArray()
            );
        }

        if (empty($orderBySubPathSegments)) {
            throw new \InvalidArgumentException(
                Messages::orderByPathSegmentOrderBySubPathSegmentArgumentShouldBeNonEmptyArray()
            );
        }

        $this->orderBySubPathSegments = $orderBySubPathSegments;
        $this->isAscending            = $isAscending;
    }

    /**
     * Gets collection of sub path segments that made up this path segment.
     *
     * @return OrderBySubPathSegment[]
     */
    public function getSubPathSegments(): array
    {
        return $this->orderBySubPathSegments;
    }

    /**
     * Is sorting order is ascending or descending?
     *
     * @return bool Return true for ascending sort order, else false
     */
    public function isAscending(): bool
    {
        return $this->isAscending;
    }
}
