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
    const MAX_EXPAND_TREE_DEPTH = 20;

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
    private $hasPagedExpandedResult = false;

    /**
     * The base resource type of entities identified by the resource path uri,
     * this is usually the base resource type of the resource set to which
     * the entities belongs to, but it can happen that it's a derived type of
     * the resource set base type.
     *
     * @var ResourceType
     */
    private $baseResourceType;

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
        $this->baseResourceType = $baseResourceType;
        parent::__construct(
            null,
            $resourceSetWrapper,
            $internalOrderByInfo,
            $skipCount,
            $takeCount,
            $maxResultCount,
            null
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
        return $this->baseResourceType;
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
        $this->hasPagedExpandedResult = $hasPagedExpandedResult;
    }

    /**
     * Check whether any of the expanded resource set is paged.
     *
     * @return bool
     */
    public function hasPagedExpandedResult()
    {
        return $this->hasPagedExpandedResult;
    }

    /**
     * Get list of expanded properties to pass to specific query provider for eager loading.
     *
     * @return string[]
     */
    public function getEagerLoadList()
    {
        if (!$this->isExpansionSpecified()) {
            return [];
        }
        if (0 === count($this->getChildNodes())) {
            return [];
        }
        // need to use a stack to track chain of parent nodes back to root
        // each entry has three elements - zeroth being node itself, first being property name, second is
        // index in parent's children - when that overruns parent's childNodes array, we can pop the parent
        // node off the stack and move on to grandparent's next child.  When we're finished with a node, then and
        // only then generate its relation chain and stash it.  When we're done (stack empty), dedupe the chain stash
        // and return it.

        // set up tracking stack and scratchpad
        $trackStack = [];
        $trackStack[] = ['node' => $this, 'name' => null, 'index' => 0];
        $scratchpad = [];

        // now start the dance
        while (0 < count($trackStack)) {
            $stackDex = count($trackStack) - 1;
            assert(
                self::MAX_EXPAND_TREE_DEPTH > $stackDex,
                'Expansion stack too deep - should be less than '. self::MAX_EXPAND_TREE_DEPTH . 'elements'
            );
            $topNode = $trackStack[$stackDex];
            /** @var ExpandedProjectionNode $rawNode */
            $rawNode = $topNode['node'];
            $nodes = $rawNode->getChildNodes();
            // have we finished processing current level?
            // this treats a leaf node as simply another exhausted parent node with all of its zero children having
            // been processed
            $topDex = $topNode['index'];
            if ($topDex >= count($nodes)) {
                $eager = '';
                foreach ($trackStack as $stack) {
                    $eager .= $stack['name'] . '/';
                }
                $eager = trim($eager, '/');
                if (1 < strlen($eager)) {
                    $scratchpad[] = $eager;
                }
                array_pop($trackStack);
                assert(
                    count($trackStack) === $stackDex,
                    'Exhausted node must shrink tracking stack by exactly one element'
                );
                continue;
            }

            // dig up key
            $key = array_keys($nodes)[$topDex];
            // prep payload for this child
            $payload = ['node' => $nodes[$key], 'name' => $key, 'index' => 0];
            array_push($trackStack, $payload);
            // advance index pointer on parent
            $trackStack[$stackDex]['index']++;
            // $stackDex already decrements stack count by 1, so we have to bump it up by two to net out to a +1
            assert(
                count($trackStack) === $stackDex + 2,
                'Non-exhausted node must expand tracking stack by exactly one element'
            );
        }

        // dedupe scratchpad
        $scratchpad = array_unique($scratchpad);
        // deliberately shuffle scratchpad to falsify any ordering assumptions downstream
        shuffle($scratchpad);
        return $scratchpad;
    }
}
