<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\OrderByParser;

use InvalidArgumentException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;

/**
 * Class OrderByNode.
 *
 * Type to represent non-leaf node of 'OrderBy Tree' (the root node and
 * intermediate nodes[complex/navigation]).
 */
class OrderByNode extends OrderByBaseNode
{
    /**
     * The resource set wrapper associated with this node, this will
     * be null if this node represents a complex sub path segment.
     *
     * @var ResourceSetWrapper
     */
    private $resourceSetWrapper;

    /**
     * list of child nodes.
     *
     * @var OrderByBaseNode[]
     */
    private $childNodes = [];

    /**
     * Construct a new instance of OrderByNode.
     *
     * @param string|null             $propertyName       Name of the property corresponds to the sub path
     *                                                    segment represented by this node, this parameter
     *                                                    will be null if this node is root
     * @param ResourceProperty|null   $resourceProperty   Resource property corresponds to the sub path
     *                                                    segment represented by this node, this parameter
     *                                                    will be null if  this node is root
     * @param ResourceSetWrapper|null $resourceSetWrapper The resource set wrapper associated with the sub path
     *                                                    segment represented by this node, this will be null
     *                                                    if this node represents a complex sub path segment
     */
    public function __construct(
        ?string $propertyName,
        ?ResourceProperty $resourceProperty,
        ?ResourceSetWrapper $resourceSetWrapper
    ) {
        // This must be the parameter state at this point, we won't check
        // these as this is an internal class
        //if ($resourceProperty != NULL)
        //{
        //    Node represents navigation or complex
        //    if ($resourceProperty::Kind == Complex)
        //        assert($resourceSetWrapper == null);
        //    else if ($resourceProperty::Kind == ResourceReference)
        //        assert($resourceSetWrapper !== null);
        //} else {
        //    Node represents root
        //    assert($resourceSetWrapper != null)
        //}
        parent::__construct($propertyName, $resourceProperty);
        $this->resourceSetWrapper = $resourceSetWrapper;
    }

    /**
     * (non-PHPdoc).
     *
     * @return void
     * @see library/POData/QueryProcessorOrderByParser.OrderByBaseNode::free()
     */
    public function free(): void
    {
        foreach ($this->childNodes as $childNode) {
            $childNode->free();
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @return ResourceType
     * @see library/POData/QueryProcessorOrderByParser.OrderByBaseNode::getResourceType()
     */
    public function getResourceType(): ResourceType
    {
        return $this->resourceProperty->getResourceType();
    }

    /**
     * To get reference to the resource set wrapper, this will be null
     * if this node represents a complex sub path segment.
     *
     * @return ResourceSetWrapper|null
     */
    public function getResourceSetWrapper(): ?ResourceSetWrapper
    {
        return $this->resourceSetWrapper;
    }

    /**
     * Find a child node with given name, if no such child node then return NULL.
     *
     * @param string $propertyName Name of the property to get the corresponding node
     *
     * @return OrderByBaseNode|null
     */
    public function findNode(string $propertyName): ?OrderByBaseNode
    {
        if (array_key_exists($propertyName, $this->childNodes)) {
            return $this->childNodes[$propertyName];
        }
        return null;
    }

    /**
     * To add a child node to the list of child nodes.
     *
     * @param OrderByBaseNode $node The child node
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function addNode(OrderByBaseNode $node): void
    {
        assert($node instanceof OrderByNode || $node instanceof OrderByLeafNode);

        $this->childNodes[$node->getPropertyName()] = $node;
    }
}
