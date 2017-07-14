<?php

namespace POData\UriProcessor\QueryProcessor\SkipTokenParser;

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
    private $_orderByInfo;

    /**
     * Holds collection of values in the skiptoken corresponds to the orderby
     * path segments.
     *
     * @var array<array<IType>>
     */
    private $_orderByValuesInSkipToken;

    /**
     * Constructs a new instance of SkipTokenInfo.
     *
     * @param OrderByInfo                      &$orderByInfo             Type holding information about the navigation properties
     *                                                                   used in the orderby clause (if any) and orderby path
     * @param array<array<IType>>             $orderByValuesInSkipToken Collection of values in the skiptoken corresponds
     *                                                                   to the orderby path segments
     */
    public function __construct(OrderByInfo & $orderByInfo, $orderByValuesInSkipToken)
    {
        $this->_orderByInfo = $orderByInfo;
        $this->_orderByValuesInSkipToken = $orderByValuesInSkipToken;
    }

    /**
     * Get reference to the OrderByInfo instance holdint information about the
     * navigation properties used in the orderby clause (if any) and orderby path.
     *
     * @return OrderByInfo
     */
    public function getOrderByInfo()
    {
        return $this->_orderByInfo;
    }

    /**
     * Gets collection of values in the skiptoken corresponds to the orderby
     * path segments.
     *
     * @return array<array<IType>>
     */
    public function getOrderByKeysInToken()
    {
        return $this->_orderByValuesInSkipToken;
    }
}
