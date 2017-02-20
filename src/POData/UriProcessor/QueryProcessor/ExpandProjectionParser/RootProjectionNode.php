<?php

namespace POData\UriProcessor\QueryProcessor\ExpandProjectionParser;

use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;

/**
 * Class RootProjectionNode.
 *
 * ExpandProjectParser will create a 'Projection Tree' from the $expand
 * and/or $select query options, this type is used to represent root of
 * the 'Projection Tree', the root holds details about the resource set
 * pointed by the resource path uri (ResourceSet, OrderInfo, skip, top,
 * pageSize etc..) and flags indicating whether projection and expansions
 * are specified.
 */
class RootProjectionNode extends ExpandedProjectionNode
{
    /**
     * Flag indicates whether expansions were specified in the query or not.
     *
     * @var bool
     */
    private $expansionSpecified = false;

    /**
     * Flag indicates whether selections were specified in the query or not.
     *
     * @var bool
     */
    private $selectionSpecified = false;

    /**
     * Flag indicates whether any of the expanded resource set is paged or not.
     *
     * @var bool
     */
    private $_hasPagedExpandedResult = false;

    /**
     * The base resource type of entities identified by the resource path uri,
     * this is usually the base resource type of the resource set to which
     * the entities belongs to, but it can happen that it's a derived type of
     * the resource set base type.
     *
     * @var ResourceType
     */
    private $_baseResourceType;

    /**
     * Constructs a new instance of 'RootProjectionNode' representing root
     * of 'Projection Tree'.
     *
     * @param ResourceSetWrapper  $resourceSetWrapper  ResourceSetWrapper of
     *                                                 the resource pointed
     *                                                 by the resource path
     * @param InternalOrderByInfo $internalOrderByInfo Details of ordering
     *                                                 to be applied to the
     *                                                 resource set pointed
     *                                                 by the resource path
     * @param int                 $skipCount           Number of resources to
     *                                                 be skipped from the
     *                                                 resource set pointed
     *                                                 by the resource path
     * @param int                 $takeCount           Number of resources to
     *                                                 be taken from the
     *                                                 resource set pointed
     *                                                 by the resource path
     * @param int                 $maxResultCount      The maximum limit
     *                                                 configured for the
     *                                                 resource set
     * @param ResourceType        $baseResourceType    Resource type of the
     *                                                 resource pointed
     *                                                 by the resource path
     */
    public function __construct(
        ResourceSetWrapper $resourceSetWrapper,
        $internalOrderByInfo,
        $skipCount,
        $takeCount,
        $maxResultCount,
        ResourceType $baseResourceType
    ) {
        $this->_baseResourceType = $baseResourceType;
        parent::__construct(
            null,
            null,
            $resourceSetWrapper,
            $internalOrderByInfo,
            $skipCount,
            $takeCount,
            $maxResultCount
        );
    }

    /**
     * Gets reference to the base resource type of entities identified by
     * the resource path uri this is usually the base resource type of the
     * resource set to which the entities belongs to but it can happen that
     * it's a derived type of the resource set base type.
     *
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_baseResourceType;
    }

    /**
     * Mark expansions are used in the query or not.
     *
     * @param bool $isExpansionSpecified True if expansion found, False else
     */
    public function setExpansionSpecified($isExpansionSpecified = true)
    {
        $this->expansionSpecified = $isExpansionSpecified;
    }

    /**
     * Check whether expansion were specified in the query.
     *
     * @return bool
     */
    public function isExpansionSpecified()
    {
        return $this->expansionSpecified;
    }

    /**
     * Mark selections are used in the query or not.
     *
     * @param bool $isSelectionSpecified True if selection found,
     *                                   False else
     */
    public function setSelectionSpecified($isSelectionSpecified = true)
    {
        $this->selectionSpecified = $isSelectionSpecified;
    }

    /**
     * Check whether selection were specified in the query.
     *
     * @return bool
     */
    public function isSelectionSpecified()
    {
        return $this->selectionSpecified;
    }

    /**
     * Mark paged expanded result will be there or not.
     *
     * @param bool $hasPagedExpandedResult True if found paging on expanded
     *                                     result, False else
     */
    public function setPagedExpandedResult($hasPagedExpandedResult = true)
    {
        $this->_hasPagedExpandedResult = $hasPagedExpandedResult;
    }

    /**
     * Check whether any of the expanded resource set is paged.
     *
     * @return bool
     */
    public function hasPagedExpandedResult()
    {
        return $this->_hasPagedExpandedResult;
    }
}
