<?php

namespace POData\UriProcessor\QueryProcessor\ExpandProjectionParser;

use InvalidArgumentException;
use POData\Common\Messages;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;

/**
 * Class ExpandedProjectionNode.
 *
 * ExpandProjectionParser will create a 'Projection Tree' from the $expand
 * and/or $select query options, Each path segment in the $expand/$select
 * will be represented by a node in the projection tree, A path segment in
 * $expand option will be represented using this type and a path segment in
 * $select option (which is not appear in expand option) will be represented
 * using 'ProjectionNode' (base type of this type). The root of the projection
 * tree will be represented using the type 'RootProjectionNode' which is
 * derived from this type.
 */
class ExpandedProjectionNode extends ProjectionNode
{
    /**
     * An 'ExpandedProjectionNode' can represents either an expanded navigation
     * property or root of the 'Projection Tree', When the node represents
     * expanded navigation property this field holds reference to the resource
     * (entity) set pointed by the navigation property, when the node
     * represents 'Projection Tree' root, this fields holds reference to the
     * resource set that the uri resource path points to.
     *
     * @var ResourceSetWrapper
     */
    private $resourceSetWrapper;

    /**
     * The sort information associated with the expanded navigation property or
     * root of 'Projection Tree', when this node represents root of the
     * projection tree then this member will be set if $top, $skip is specified
     * in the request uri or server side paging is enabled for the resource
     * identified by the request uri, when this node represents an expanded
     * navigation property then this member will be set if server side paging
     * is enabled for the resource set corresponding to the navigation
     * property.
     *
     * @var InternalOrderByInfo
     */
    private $internalOrderByInfo;

    /**
     * Number of results to be skipped for this node, the value of this field
     * depends on the what this node actually represents,
     * (1) Node represents navigation property
     *     value will be always null
     * (2) Node represents root of the 'Projection Tree'
     *     value of the $skip query option, null if skip is absent
     * A null value for this filed means return all results.
     *
     * @var int
     */
    private $skipCount;

    /**
     * Maximum number of results to be returned for this node, the value of
     * this field depends on the what this node actually represents,
     * (1) Node represents navigation property
     *     The page size of the resource set pointed by the navigation
     *          property
     * (2) Node represents root of the 'Projection Tree'
     *     The minimum among the page size of the resource set that the
     *          uri resource path points to and the value of $top query option
     *          (if applied).
     * A null value for this filed means return all results.
     *
     * @var int
     */
    private $takeCount;

    /**
     * The maximum number of results allowed for this node, taken from
     * ServiceConfiguration::_maxResultsPerCollection null means no limit
     * will be applied and thus all results available should be returned.
     *
     * @var int
     */
    private $maxResultCount;

    /**
     * List of child nodes, array of ExpandedProjectionNode and/or
     * ProjectionNode.
     *
     * @var ProjectionNode[]
     */
    private $childNodes = [];

    /**
     * When we have seen a $select path including this expanded property then
     * this field will be set to true, this field is used to eliminate nodes
     * representing segments in $expand option which are not selected.
     *
     * e.g:
     * $expand=A/B, A/B/C, A/B/D, X/Y, M/N & $select=A/B
     *     Here we need to consider only A/B, A/B/C and A/B/D, we can eliminate
     *     the nodes X/Y and M/N which are not selected. This field will be set
     *     to true for the nodes A and B.
     *
     * @var bool
     */
    private $selectionFound = false;

    /**
     * This field set to true when we have seen the special token '*', means
     * select all immediate (child) properties of this node.
     *
     * e.g:
     * $expand=A/B, A/B/C & $select=A/*
     *     Here we need to return only set of A with immediate properties,
     *     expand request for B, B/C will be ignored
     * $expand=A/B, A/B/C & $select=*
     *   Here we need to return only set pointed by uri path segment with
     *   immediate properties, expand request be ignored
     * $expand=A/B, A/B/C & $select=A/*, A/B
     *   Here we need to return set of A with immediate properties and
     *   associated B's.
     *
     * @var bool
     */
    private $selectAllImmediateProperties = false;

    /**
     * Flag which indicate whether the entire expanded subtree of this node
     * should be selected or not.
     *
     * e.g:
     * $expand=A/B, A/B/C/D & $select=A/B
     *     Here need to return all immediate properties of B, associated
     *     C with immediate properties and associated D of C with immediate
     *     properties, so for B, C and D this field will be true.
     *
     * @var bool
     */
    private $selectSubtree = false;

    /**
     * Constructs a new instance of node representing expanded navigation property.
     *
     * @param string|null           $propertyName           The name of the property
     *                                                      to expand. If this node
     *                                                      represents an expanded
     *                                                      navigation property then
     *                                                      this is the name of the
     *                                                      navigation property. If this
     *                                                      node represents root of the
     *                                                      projection tree then this
     *                                                      will be null
     * @param ResourceSetWrapper    $resourceSetWrapper     The resource set to which
     *                                                      the expansion leads, see the
     *                                                      comment of _resourceSetWrapper
     *                                                      field
     * @param InternalOrderByInfo   $internalOrderByInfo    The sort information
     *                                                      associated with this node,
     *                                                      see the comments of
     *                                                      $_internalOrderByInfo field
     * @param int|null              $skipCount              The number of results to
     *                                                      skip, null means no
     *                                                      result to skip, see the
     *                                                      comments of _skipCount
     *                                                      field
     * @param int                   $takeCount              The maximum number of results
     *                                                      to return, null means return
     *                                                      all available result, see the
     *                                                      comments of _takeCount field
     * @param int                   $maxResultCount         The maximum number of
     *                                                      expected results,see comment
     *                                                      of _maxResultCount field
     * @param ResourceProperty|null $resourceProperty       The resource property for
     *                                                      the property to expand.
     *                                                      If this node represents an
     *                                                      expanded navigation property
     *                                                      then this is the resource
     *                                                      property of navigation
     *                                                      property, if this node
     *                                                      represents root of the
     *                                                      projection tree then
     *                                                      this will be null
     */
    public function __construct(
        $propertyName,
        ResourceSetWrapper $resourceSetWrapper,
        $internalOrderByInfo,
        $skipCount,
        $takeCount,
        $maxResultCount,
        ResourceProperty $resourceProperty = null
    ) {
        $this->resourceSetWrapper = $resourceSetWrapper;
        $this->internalOrderByInfo = $internalOrderByInfo;
        $this->skipCount = $skipCount;
        $this->takeCount = $takeCount;
        $this->maxResultCount = $maxResultCount;
        parent::__construct($propertyName, $resourceProperty);
    }

    /**
     * Resource set to which the expansion represented by this node leads to
     * (An expansion means a set of entities associated with an entity,
     * associated set will be sub set of an resource set) If this node
     * represents an expanded navigation property, this is the resource set
     * to which the expanded navigation property points to, If this node is
     * the root of projection tree, this is the resource set that the uri
     * resource path points to.
     *
     * @return ResourceSetWrapper
     */
    public function getResourceSetWrapper()
    {
        return $this->resourceSetWrapper;
    }

    /**
     * An expansion leads by this node results in a collection of entities,
     * this is the resource type of these entities, This is usually the
     * resource type of the 'ResourceSetWrapper' for this node, but it can
     * also be a derived type of ResourceSetWrapper::ResourceType, this can
     * happen if navigation property points to a resource set but uses a
     * derived type.
     *
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->resourceProperty->getResourceType();
    }

    /**
     * Gets array of child nodes.
     *
     * @return ProjectionNode[]|ExpandedProjectionNode[]
     */
    public function getChildNodes()
    {
        return $this->childNodes;
    }

    /**
     * Number of results to be skipped for this node, null means return all
     * results, when this node represents an expanded navigation property
     * then skip count will be null, If this node is the root of projection
     * tree, then skip count will be value of $skip query option.
     *
     * @return int
     */
    public function getSkipCount()
    {
        return $this->skipCount;
    }

    /**
     * Maximum number of results to be returned for this node, null means
     * return all results, when this node represents an expanded navigation
     * property then take count will be page size defined for the resource
     * set pointed by the navigation property, If this node is the root of
     * projection tree then take count will be the minimum among the page
     * size of the the resource set that the uri resource path points to and
     * the value of $top query option.
     *
     * @return int
     */
    public function getTakeCount()
    {
        return $this->takeCount;
    }

    /**
     * Gets the maximum number of expected result.
     *
     * @return int
     */
    public function getMaxResultCount()
    {
        return $this->maxResultCount;
    }

    /**
     * Gets the sort information associated with the expanded navigation
     * property or root of 'Projection Tree'.
     *
     * @return InternalOrderByInfo|null
     */
    public function getInternalOrderByInfo()
    {
        return $this->internalOrderByInfo;
    }

    /**
     * To set selection status of this node, When we have seen a $select
     * path segment that selects the expanded property represented by
     * this node then this function will be used to mark this node as selected.
     *
     * @param bool $isSelectionFound True if selection found in this node
     *                               False otherwise
     * @return void
     */
    public function setSelectionFound($isSelectionFound = true)
    {
        $this->selectionFound = $isSelectionFound;
    }

    /**
     * To check whether this node is selected or not.
     *
     * @return bool
     */
    public function isSelectionFound()
    {
        return $this->selectionFound;
    }

    /**
     * To set the flag indicating whether to include all immediate properties
     * of this node in the result or not, When we have seen a '*' in the
     * $select path segment, then this function will be used to set the flag
     * for immediate properties inclusion.
     *
     * @param bool $selectAllImmediateProperties True if all immediate
     *                                           properties to be included
     *                                           False otherwise
     * @return void
     */
    public function setSelectAllImmediateProperties(
        $selectAllImmediateProperties = true
    ) {
        $this->selectAllImmediateProperties = $selectAllImmediateProperties;
    }

    /**
     * To check whether immediate properties of the navigation property
     * represented by this node is to be included in the result or not.
     *
     * @return bool
     */
    public function canSelectAllImmediateProperties()
    {
        return $this->selectAllImmediateProperties;
    }

    /**
     * Whether all child properties of this node can be selected or not,
     * all child properties will be selected in 2 cases
     * (1) When flag for selection of all immediate properties is true
     *     $select=A/B/*
     *      Here 'immediate properties inclusion flag' will be true for B
     * (2) When flag for selection of this subtree is true
     *      $expand=A/B/D, A/B/C & $select = A/B
     *      Here 'subtree selection flag' will be true for B, C and D.
     *
     * @return bool
     */
    public function canSelectAllProperties()
    {
        return $this->selectSubtree || $this->selectAllImmediateProperties;
    }

    /**
     * Find a child node with given name, if no such child node then
     * return NULL.
     *
     * @param string $propertyName Name of the property to get the
     *                             corresponding node
     *
     * @return ProjectionNode|ExpandedProjectionNode|null
     */
    public function findNode($propertyName)
    {
        if (array_key_exists($propertyName, $this->childNodes)) {
            return $this->childNodes[$propertyName];
        }
        return null;
    }

    /**
     * To add a child node to the list of child nodes.
     *
     * @param ProjectionNode $node Node to add
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function addNode($node)
    {
        if (!($node instanceof ProjectionNode)
            && !($node instanceof self)
        ) {
            throw new \InvalidArgumentException(
                Messages::expandedProjectionNodeArgumentTypeShouldBeProjection()
            );
        }

        $this->childNodes[$node->getPropertyName()] = $node;
    }

    /**
     * Mark the entire subtree as selected, for example
     * $expand=A/B/C/D/E & $select = A/B Here we need to select the entire
     * subtree of B i.e result should include all immediate properties of B
     * and associated C's, D's associated with each C and E's associated each D.
     *
     * @return void
     */
    public function markSubtreeAsSelected()
    {
        $this->selectSubtree = true;
        $this->selectAllImmediateProperties = false;
        foreach ($this->childNodes as $node) {
            if ($node instanceof self) {
                $node->markSubtreeAsSelected();
            }
        }
    }

    /**
     * Remove all child 'ExpandedProjectionNode's of this node which are
     * not selected, Recursively invoke the same function for selected
     * node, so that all unnecessary nodes will be removed from the subtree.
     *
     * @return void
     */
    public function removeNonSelectedNodes()
    {
        //Possible Node status flags are:
        //for $expand=A/B/C/D, X/Y
        // | SF | SST |
        // | T  | F   |  For $select=A/B, this is status of A
        // | T  | T   |  For $select=A/B, this is status of B
        // | F  | T   |  For $select=A/B, this is status of C and D
        // | F  | F   |  For $select=A/B, this is status of X and Y

        foreach ($this->childNodes as $propertyName => $node) {
            if ($node instanceof self) {
                if (!$this->selectSubtree && !$node->selectionFound) {
                    unset($this->childNodes[$propertyName]);
                } else {
                    $node->removeNonSelectedNodes();
                }
            }
        }
    }

    /**
     * Remove explicitly included nodes which already included implicitly, For
     * an expand navigation property, all immediate properties will be
     * implicitly selected if that navigation property is the last segment of
     * expand path or if there is a '*' token present after the navigation
     * property, this function remove all explicitly included 'ProjectionNode's
     * which already included implicitly.
     *
     * @return void
     */
    public function removeNodesAlreadyIncludedImplicitly()
    {
        //$select=A/B, A/B/guid, A/B/Name
        //Here A/B cause to implicitly include all immediate properties of B
        //so remove explicitly included 'ProjectionNode' for guid and Name
        if ($this->selectSubtree) {
            foreach ($this->childNodes as $propertyName => $node) {
                if ($node instanceof self) {
                    $node->selectSubtree = true;
                    $node->removeNodesAlreadyIncludedImplicitly();
                } else {
                    unset($this->childNodes[$propertyName]);
                }
            }

            $this->selectAllImmediateProperties = false;

            return;
        }

        //$select=A/B/*, A/B/guid, A/B/Name
        //Here A/B/* cause to implicitly include all immediate properties of B
        //so remove explicitly included 'ProjectionNode' for guid and Name
        foreach ($this->childNodes as $propertyName => $node) {
            if ($node instanceof self) {
                $node->removeNodesAlreadyIncludedImplicitly();
            } elseif ($this->selectAllImmediateProperties) {
                unset($this->childNodes[$propertyName]);
            }
        }
    }

    /**
     * Sort the selected nodes such that order is same as the order in which
     * the properties are appear in the owning type.
     *
     * @return void
     */
    public function sortNodes()
    {
        if (count($this->childNodes) > 0) {
            foreach ($this->childNodes as $childNode) {
                if ($childNode instanceof self) {
                    $childNode->sortNodes();
                }
            }

            //We are applying sorting in bottom-up fashion, do it only we have
            // more than 1 child
            if (count($this->childNodes) > 1) {
                $existingNodes = $this->childNodes;
                $this->childNodes = [];
                foreach ($this->getResourceType()->getAllProperties() as $resourceProperty) {
                    $propertyName = $resourceProperty->getName();
                    if (array_key_exists($propertyName, $existingNodes)) {
                        $this->childNodes[$propertyName] = $existingNodes[$propertyName];
                    }
                }
            }
        }
    }
}
