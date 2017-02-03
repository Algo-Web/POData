<?php

namespace POData\UriProcessor\QueryProcessor\ExpandProjectionParser;

use POData\Providers\Metadata\ResourceProperty;

/**
 * Class ProjectionNode.
 *
 * ExpandProjectionParser will create a 'Projection Tree' from the $expand
 * and/or $select query options, Each path segement in the $expand/$select
 * will be represented by a node in the proejction tree, A path segment in
 * $expand option (which is not appear in expand option) will be represented
 * using a type derived from this type 'ExpandedProjectionNode' and a path
 * segment in $select option will be represented using 'ProjectionNode'.
 * The root of the projection tree will be represented using the type
 * 'RootProjectionNode' which is derived from the type 'ExpandedProjectionNode'
 *
 *               'ProjectionNode'
 *                       |
 *                       |
 *            'ExpandedProjectionNode'
 *                       |
 *                       |
 *              'RootProjectionNode'
 *
 * Note: In the context of library we use the term 'Projection' to represent
 * both expansion and selection.
 */
class ProjectionNode
{
    /**
     * The name of the property to be projected. When this node represents a
     * select path segment then this member holds the name of the property to
     * select, when this node represents an expand path segment then this
     * member holds the name of the property (a navigation property) to expand,
     * if this node represents root of the projection tree, this field will be
     * null.
     *
     * @var string
     */
    protected $propertyName;

    /**
     * The resource type of the property to be projected. if this node
     * represents root of the projection tree, this field will be null.
     *
     * @var ResourceProperty
     */
    protected $resourceProperty;

    /**
     * Constructs a new instance of ProjectionNode.
     *
     * @param string           $propertyName     Name of the property to
     *                                           be projected
     * @param ResourceProperty $resourceProperty The resource type of the
     *                                           property to be projected
     */
    public function __construct($propertyName, $resourceProperty)
    {
        $this->propertyName = $propertyName;
        $this->resourceProperty = $resourceProperty;
    }

    /**
     * Gets name of the property to be projected, if this is root node then
     * name will be null.
     *
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Gets reference to the resource property instance for the property to be
     * projected, if this is root node then name will be null.
     *
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->resourceProperty;
    }
}
