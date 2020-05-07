<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\SkipTokenParser;

use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByInfo;

/**
 * Class SkipTokenInfo.
 *
 * Type to hold the parsed skiptoken value. The IDSQP implementor
 * can use these details if they want to do custom paging.
 */
class SkipTokenInfo
{
    /**
     * Type to hold information about the navigation properties used in the
     * orderby clause (if any) and orderby path.
     *
     * @var OrderByInfo
     */
    private $orderByInfo;

    /**
     * Holds collection of values in the skiptoken corresponds to the orderby
     * path segments.
     *
     * @var array<array<IType>>
     */
    private $orderByValuesInSkipToken;

    /**
     * Constructs a new instance of SkipTokenInfo.
     *
     * @param OrderByInfo         &$orderByInfo Type holding information about the navigation properties
     *                                                      used in the orderby clause (if any) and orderby path
     * @param array<array<IType>> $orderByValuesInSkipToken Collection of values in the skiptoken corresponds
     *                                                      to the orderby path segments
     */
    public function __construct(OrderByInfo &$orderByInfo, $orderByValuesInSkipToken)
    {
        $this->orderByInfo = $orderByInfo;
        $this->orderByValuesInSkipToken = $orderByValuesInSkipToken;
    }

    /**
     * Get reference to the OrderByInfo instance holding information about the
     * navigation properties used in the orderby clause (if any) and orderby path.
     *
     * @return OrderByInfo
     */
    public function getOrderByInfo()
    {
        return $this->orderByInfo;
    }

    /**
     * Gets collection of values in the skiptoken corresponds to the orderby
     * path segments.
     *
     * @return array<array<IType>>
     */
    public function getOrderByKeysInToken()
    {
        return $this->orderByValuesInSkipToken;
    }
}
