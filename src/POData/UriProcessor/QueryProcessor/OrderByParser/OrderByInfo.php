<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\OrderByParser;

use InvalidArgumentException;
use POData\Common\Messages;
use POData\Providers\Metadata\ResourceProperty;

/**
 * Class OrderByInfo.
 *
 * Type to hold information about the navigation properties used in the orderby clause (if any) and orderby
 * path if IDSQP implementor wants to perform sorting.
 */
class OrderByInfo
{
    /**
     * Collection of orderby path segments.
     *
     * @var OrderByPathSegment[]
     */
    private $orderByPathSegments;

    /**
     * The IReadQueryProvider implementation sets this to true using 'setSorted' function if it is going to perform
     * the sorting, a false value for this flag means the library is responsible for sorting.
     *
     * @var bool
     */
    private $isSorted;

    /**
     * Collection of navigation properties specified in the orderby clause, if no navigation (resource reference)
     * property used in the clause, then this property will be null.
     *
     * e.g. $orderby=NaviProp1/NaviProp2/PrimitiveProp,
     *      NaviPropA/NaviPropB/PrimitiveProp
     * In this case array will be as follows:
     * array(array(NaviProp1, NaviProp2), array(NaviPropA, NaviPropB))
     *
     * @var array<array<ResourceProperty>>|null
     */
    private $navigationPropertiesUsedInTheOrderByClause;

    /**
     * Constructs new instance of OrderByInfo.
     *
     * @param OrderByPathSegment[]                 $orderByPathSegments  Order by path segments
     * @param array <array<ResourceProperty>>|null $navigationProperties navigation properties used in the
     *                                                                   order by clause
     */
    public function __construct(array $orderByPathSegments, array $navigationProperties = null)
    {
        if (empty($orderByPathSegments)) {
            throw new InvalidArgumentException(
                Messages::orderByInfoPathSegmentsArgumentShouldBeNonEmptyArray()
            );
        }

        if (null !== $navigationProperties) {
            if (empty($navigationProperties)) {
                throw new InvalidArgumentException(
                    Messages::orderByInfoNaviUsedArgumentShouldBeNullOrNonEmptyArray()
                );
            }
        }

        $this->orderByPathSegments                        = $orderByPathSegments;
        $this->navigationPropertiesUsedInTheOrderByClause = $navigationProperties;
    }

    /**
     * Gets collection of path segments which made up the orderby clause.
     *
     * @return OrderByPathSegment[]
     */
    public function getOrderByPathSegments(): array
    {
        return $this->orderByPathSegments;
    }

    /**
     * Gets collection of navigation properties specified in the orderby clause
     * if no navigation (resource reference) properties are used in the clause then
     * this function returns null, IReadQueryProvider must check this
     * function and include these resource reference type navigation properties in the result.
     *
     * @return array<array<ResourceProperty>>|null
     */
    public function getNavigationPropertiesUsed(): ?array
    {
        return $this->navigationPropertiesUsedInTheOrderByClause;
    }

    /**
     * IReadQueryProvider implementation should use this function to let the library know that whether implementation
     * will be performing the sorting or will library perform the sorting.
     *
     * @param  bool $isSorted Set the flag so indicate that the result has been sorted
     * @return bool
     */
    public function setSorted(bool $isSorted = true): bool
    {
        $this->isSorted = $isSorted;
        return true;
    }

    /**
     * Whether library should do the sorting or not, if the IReadQueryProvider implementation already sorted the
     * entities, then library will not perform the sorting.
     *
     * @return bool
     */
    public function requireInternalSorting(): bool
    {
        return !$this->isSorted;
    }
}
