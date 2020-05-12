<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\Navigation;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use ReflectionException;

/**
 * Class PropertyAccessExpression.
 */
class PropertyAccessExpression extends AbstractExpression
{
    /**
     * @var PropertyAccessExpression
     */
    protected $parent;

    /**
     * @var PropertyAccessExpression
     */
    protected $child;

    /**
     * Resource property instance describes the property represented by
     * this expression.
     *
     * @var ResourceProperty
     */
    protected $resourceProperty;

    /**
     * Creates new instance of PropertyAccessExpression.
     *
     * @param  ResourceProperty              $resourceProperty The ResourceProperty
     * @param  PropertyAccessExpression|null $parent           The parent expression
     * @throws ReflectionException
     */
    public function __construct(ResourceProperty $resourceProperty, PropertyAccessExpression $parent = null)
    {
        $this->parent           = $parent;
        $this->child            = null;
        $this->nodeType         = ExpressionType::PROPERTYACCESS();
        $this->resourceProperty = $resourceProperty;
        //If the property is primitive type, then _type will be primitive types implementing IType
        if ($resourceProperty->getResourceType()->getResourceTypeKind() == ResourceTypeKind::PRIMITIVE()) {
            $rawType = $resourceProperty->getResourceType()->getInstanceType();
            assert($rawType instanceof IType, 'Primitive type instance type not an IType');
            $this->type = $rawType;
        } else {
            //This is a navigation i.e. Complex, ResourceReference or Collection
            $this->type = new Navigation($resourceProperty->getResourceType());
        }

        if (null !== $parent) {
            $parent->setChild($this);
        }
    }

    /**
     * To get the parent. If this property is property of entity
     * then return null, If this property is property of complex type
     * then return PropertyAccessExpression for the parent complex type.
     *
     * @return PropertyAccessExpression|null
     */
    public function getParent(): ?PropertyAccessExpression
    {
        return isset($this->parent) ? $this->parent : null;
    }

    /**
     * Get the ResourceProperty describing the property hold by this expression.
     *
     * @return ResourceProperty
     */
    public function getResourceProperty(): ResourceProperty
    {
        return $this->resourceProperty;
    }

    /**
     * Gets collection of navigation (resource set reference or resource set)
     * properties used in this property access.
     *
     * @return ResourceProperty[] Returns empty array if no navigation property is used else array of ResourceProperty
     */
    public function getNavigationPropertiesInThePath(): array
    {
        $basePropertyExpression = $this;
        while (($basePropertyExpression != null)
            && ($basePropertyExpression->parent != null)
        ) {
            $basePropertyExpression = $basePropertyExpression->parent;
        }

        $navigationPropertiesInThePath = [];
        while ($basePropertyExpression) {
            $resourceTypeKind = $basePropertyExpression->getResourceType()->getResourceTypeKind();
            if ($resourceTypeKind == ResourceTypeKind::ENTITY()) {
                $navigationPropertiesInThePath[] = $basePropertyExpression->resourceProperty;
            } else {
                break;
            }

            $basePropertyExpression = $basePropertyExpression->child;
        }

        return $navigationPropertiesInThePath;
    }

    /**
     * Get the resource type of the property hold by this expression.
     *
     * @return ResourceType
     */
    public function getResourceType(): ResourceType
    {
        return $this->resourceProperty->getResourceType();
    }

    /**
     * Function to create a nullable expression subtree for checking the
     * nullability of parent (and current property optionally) properties.
     *
     * @param bool $includeMe Boolean flag indicating whether to include null check for this property along with parents
     *
     * @return UnaryExpression|LogicalExpression|null Instance of UnaryExpression, LogicalExpression or Null
     */
    public function createNullableExpressionTree(bool $includeMe): ?AbstractExpression
    {
        $basePropertyExpression = $this;
        while (($basePropertyExpression != null)
            && ($basePropertyExpression->parent != null)
        ) {
            $basePropertyExpression = $basePropertyExpression->parent;
        }

        //This property is direct child of ResourceSet, no need to check nullability for direct ResourceSet properties
        // ($c->CustomerID, $c->Order, $c->Address) unless $includeMe is true
        if ($basePropertyExpression == $this) {
            if ($includeMe) {
                return new UnaryExpression(
                    new FunctionCallExpression(
                        FunctionDescription::isNullCheckFunction(
                            $basePropertyExpression->getType()
                        ),
                        [$basePropertyExpression]
                    ),
                    ExpressionType::NOT_LOGICAL(),
                    new Boolean()
                );
            }

            return null;
        }

        //This property is a property of a complex type or resource reference, whether directly or otherwise
        //$c->Order->OrderID, $c->Address->LineNumber,
        // $c->complex1->complex2->primitiveVar
        // Intermediate values must of course be non-null - otherwise PHP gets rather shirty
        $expression = new UnaryExpression(
            new FunctionCallExpression(
                FunctionDescription::isNullCheckFunction($basePropertyExpression->getType()),
                [$basePropertyExpression]
            ),
            ExpressionType::NOT_LOGICAL(),
            new Boolean()
        );
        while (($basePropertyExpression->getChild() != null)
            && ($basePropertyExpression->getChild()->getChild() != null)) {
            $basePropertyExpression = $basePropertyExpression->getChild();
            $expression2            = new UnaryExpression(
                new FunctionCallExpression(
                    FunctionDescription::isNullCheckFunction($basePropertyExpression->getType()),
                    [$basePropertyExpression]
                ),
                ExpressionType::NOT_LOGICAL(),
                new Boolean()
            );
            $expression = new LogicalExpression(
                $expression,
                $expression2,
                ExpressionType::AND_LOGICAL()
            );
        }

        if ($includeMe) {
            $basePropertyExpression = $basePropertyExpression->getChild();
            $expression2            = new UnaryExpression(
                new FunctionCallExpression(
                    FunctionDescription::isNullCheckFunction($basePropertyExpression->getType()),
                    [$basePropertyExpression]
                ),
                ExpressionType::NOT_LOGICAL(),
                new Boolean()
            );
            $expression = new LogicalExpression(
                $expression,
                $expression2,
                ExpressionType::AND_LOGICAL()
            );
        }

        return $expression;
    }

    /**
     * To get the child. Returns null if no child property.
     *
     * @return PropertyAccessExpression|null
     */
    public function getChild(): ?PropertyAccessExpression
    {
        return isset($this->child) ? $this->child : null;
    }

    /**
     * To set the child if any.
     *
     * @param PropertyAccessExpression $child The child expression
     */
    public function setChild(PropertyAccessExpression $child): void
    {
        $this->child = $child;
    }

    /**
     * (non-PHPdoc).
     *
     * @see library/POData/QueryProcessor/Expressions/Expressions.AbstractExpression::free()
     */
    public function free(): void
    {
        if (null !== $this->parent) {
            $this->parent->free();
            unset($this->parent);
        }

        if (null !== $this->child) {
            $this->child->free();
            unset($this->child);
        }
    }
}
