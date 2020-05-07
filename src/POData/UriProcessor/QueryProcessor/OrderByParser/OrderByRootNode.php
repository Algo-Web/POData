<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\OrderByParser;

use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;

/**
 * Class OrderByRootNode.
 *
 * A type to represent root node of 'OrderBy Tree', the root node includes
 * details of resource set pointed by the request resource path uri.
 */
class OrderByRootNode extends OrderByNode
{
    /**
     * The resource type resource set pointed by the request resource
     * path uri.
     *
     * @var ResourceType
     */
    private $baseResourceType;

    /**
     * Constructs a new instance of 'OrderByRootNode' representing
     * root of 'OrderBy Tree'.
     *
     * @param ResourceSetWrapper $resourceSetWrapper The resource set pointed by
     *                                               the request resource path uri
     * @param ResourceType       $baseResourceType   The resource type resource set
     *                                               pointed to by the request resource path uri
     */
    public function __construct(
        ResourceSetWrapper $resourceSetWrapper,
        ResourceType $baseResourceType
    ) {
        parent::__construct(null, null, $resourceSetWrapper);
        $this->baseResourceType = $baseResourceType;
    }

    /**
     * (non-PHPdoc).
     *
     * @return ResourceType
     * @see library/POData/QueryProcessorOrderByParser.OrderByNode::getResourceType()
     */
    public function getResourceType()
    {
        return $this->baseResourceType;
    }
}
