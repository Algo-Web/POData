<?php
/** 
 * Expression class specialized for a property access.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser_Expressions
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;
use ODataProducer\UriProcessor\QueryProcessor\FunctionDescription\FunctionDescription;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\Type\Navigation;
use ODataProducer\Providers\Metadata\Type\Boolean;
/**
 * Expression class for property access.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser_Expressions
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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
     * this expression
     * 
     * @var ResourceProperty
     */
    protected $resourceProperty;

    /**
     * Creates new instance of PropertyAccessExpression
     * 
     * @param PropertyAccessExpression $parent           The parent expression
     * @param ResourceProperty         $resourceProperty The ResourceProperty 
     */
    public function __construct($parent, $resourceProperty)
    {
        $this->parent = $parent;
        $this->child = null;
        $this->nodeType = ExpressionType::PROPERTYACCESS;
        $this->resourceProperty = $resourceProperty;
        //If the property is primitive type, then _type will be primitve types 
        //implementing IType
        if ($resourceProperty->getResourceType()->getResourceTypeKind() == ResourceTypeKind::PRIMITIVE) {
            $this->type = $resourceProperty->getResourceType()->getInstanceType();
        } else { 
            //This is a navigation i.e. Complex, ResourceReference or Collection
            $this->type = new Navigation($resourceProperty->getResourceType());
        }

        if (!is_null($parent)) {
            $parent->setChild($this);
        }
    }

    /**
     * To set the child if any
     * 
     * @param PropertyAccessExpression $child The child expression
     * 
     * @return void
     */
    public function setChild($child)
    {
        $this->child = $child;
    }

    /**
     * To get the parent. If this property is property of entity 
     * then return null, If this property is property of complex type 
     * then return PropertyAccessExpression for the parent complex type
     * 
     * @return PropertyAccessExpression
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * To get the child. Returns null if no child property
     * 
     * @return PropertyAccessExpression
     */
    public function getChild()
    {
        return $this->child;
    }
    
    /**
     * Get the resource type of the property hold by this expression
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->resourceProperty->getResourceType();
    }

    /**
     * Get the ResourceProperty describing the property hold by this expression
     * 
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->resourceProperty;
    }

    /**
     * Gets collection of navigation (resource set reference or resource set) 
     * properties used in this property access. 
     * 
     * @return array()/array(ResourceProperty) Returns empty array if no 
     *                                         navigation property is used else
     *                                         array of ResourceProperty.
     */
    public function getNavigationPropertiesInThePath()
    {
        $basePropertyExpression = $this;
        while (($basePropertyExpression != null) 
            && ($basePropertyExpression->parent != null)
        ) {
            $basePropertyExpression = $basePropertyExpression->parent;
        }

        $navigationPropertiesInThePath = array();
        while ($basePropertyExpression) {
            $resourceTypeKind 
                = $basePropertyExpression->getResourceType()->getResourceTypeKind();
            if ($resourceTypeKind == ResourceTypeKind::ENTITY) {
                $navigationPropertiesInThePath[] 
                    = $basePropertyExpression->resourceProperty;
            } else {
                break;
            }

            $basePropertyExpression = $basePropertyExpression->child;
        }

        return $navigationPropertiesInThePath;
    }

    /**
     * Function to create a nullable expression subtree for checking the
     * nullablilty of parent (and current poperty optionally) properties
     * 
     * @param boolean $includeMe Boolean flag indicating whether to include null
     *                           check for this property along with parents
     * 
     * @return AbstractExpression Instance of UnaryExpression, LogicalExpression
     *                            or Null
     * 
     */
    public function createNullableExpressionTree($includeMe)
    {
        $basePropertyExpression = $this;
        while (($basePropertyExpression != null) 
            && ($basePropertyExpression->parent != null)
        ) {
            $basePropertyExpression = $basePropertyExpression->parent;
        }

        //This property is direct child of ResourceSet, no need to check
        //nullability for direct ResourceSet properties 
        // ($c->CustomerID, $c->Order, $c->Address) unless $includeMe is true
        if ($basePropertyExpression == $this) {
            if ($includeMe) {
                return new UnaryExpression(
                    new FunctionCallExpression(
                        FunctionDescription::isNullCheckFunction(
                            $basePropertyExpression->getType()
                        ), 
                        array($basePropertyExpression)
                    ), 
                    ExpressionType::NOT_LOGICAL, 
                    new Boolean()
                );
            }
            
            return null;
        }

        //This property is a property of a complex type or resource reference
        //$c->Order->OrderID, $c->Address->LineNumber, 
        // $c->complex1->complex2->primitveVar
        //($c->Order != null),($c->Address != null),   
        // (($c->complex1 != null) && ($c->complex1->complex2 != null))
        $expression = new UnaryExpression(
            new FunctionCallExpression(
                FunctionDescription::isNullCheckFunction(
                    $basePropertyExpression->getType()
                ), 
                array($basePropertyExpression)
            ), 
            ExpressionType::NOT_LOGICAL, 
            new Boolean()
        );
        while (($basePropertyExpression->getChild() != null) 
                && ($basePropertyExpression->getChild()->getChild() != null)) {
            $basePropertyExpression = $basePropertyExpression->getChild();
            $expression2 = new UnaryExpression(
                new FunctionCallExpression(
                    FunctionDescription::isNullCheckFunction(
                        $basePropertyExpression->getType()
                    ), 
                    array($basePropertyExpression)
                ), 
                ExpressionType::NOT_LOGICAL,
                new Boolean()
            );
            $expression = new LogicalExpression(
                $expression, $expression2, 
                ExpressionType::AND_LOGICAL
            );
        }

        if ($includeMe) {
            $basePropertyExpression = $basePropertyExpression->getChild();
            $expression2 = new UnaryExpression(
                new FunctionCallExpression(
                    FunctionDescription::isNullCheckFunction(
                        $basePropertyExpression->getType()
                    ), 
                    array($basePropertyExpression)
                ), 
                ExpressionType::NOT_LOGICAL, 
                new Boolean()
            );
            $expression = new LogicalExpression(
                $expression, $expression2, 
                ExpressionType::AND_LOGICAL
            );
        }

        return $expression;
    }
    
    /**
     * (non-PHPdoc)
     * 
     * @see library/ODataProducer/QueryProcessor/Expressions/ODataProducer\QueryProcessor\Expressions.AbstractExpression::free()
     * 
     * @return void
     */
    public function free()
    {
        if (!is_null($this->parent)) {
            $this->parent->free();
            unset($this->parent);
        }
        
        if (!is_null($this->child)) {
            $this->child->free();
            unset($this->child);
        }
    }
}
?>