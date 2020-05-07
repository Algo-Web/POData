<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\OrderByParser;

use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\Type\IType;
use ReflectionClass;
use ReflectionException;

/**
 * Class OrderBySubPathSegment.
 *
 * A type to represent sub path segment in an order by path segment.
 * Syntax of orderby clause is:
 *
 * OrderByClause         : OrderByPathSegment [, OrderByPathSegment]*
 * OrderByPathSegment    : OrderBySubPathSegment [/OrderBySubPathSegment]*[asc|desc]?
 * OrderBySubPathSegment : identifier
 */
class OrderBySubPathSegment
{
    /**
     * Resource property of the property corresponding to this sub path segment.
     *
     * @var ResourceProperty
     */
    private $resourceProperty;

    /**
     * Constructs a new instance of OrderBySubPathSegment.
     *
     * @param ResourceProperty $resourceProperty Resource property of the property corresponding to this sub path
     *                                           segment
     */
    public function __construct(ResourceProperty $resourceProperty)
    {
        $this->resourceProperty = $resourceProperty;
    }

    /**
     * Gets name of the sub path segment.
     *
     * @return string
     */
    public function getName()
    {
        return $this->resourceProperty->getName();
    }

    /**
     * Gets reference to the ResourceProperty instance corresponding to this sub path segment.
     *
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->resourceProperty;
    }

    /**
     * Gets instance type of the ResourceProperty instance corresponding to
     * this sub path segment If this sub path segment is last segment then
     * this function returns 'IType', otherwise 'ReflectionClass'.
     *
     * @throws ReflectionException
     * @return ReflectionClass|IType
     */
    public function getInstanceType()
    {
        return $this->resourceProperty->getInstanceType();
    }
}
