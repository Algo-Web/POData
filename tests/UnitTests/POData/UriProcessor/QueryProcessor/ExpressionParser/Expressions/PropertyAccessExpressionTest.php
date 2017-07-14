<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use UnitTests\POData\TestCase;
use Mockery as m;

class PropertyAccessExpressionTest extends TestCase
{
    public function testFree()
    {
        $parent = m::mock(PropertyAccessExpression::class);
        $parent->shouldReceive('free')->andReturnNull()->once();
        $parent->shouldReceive('setChild')->andReturnNull()->once();
        $child = m::mock(PropertyAccessExpression::class);
        $child->shouldReceive('free')->andReturnNull()->once();

        $iType = m::mock(IType::class);
        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE);
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $prop = m::mock(ResourceProperty::class);
        $prop->shouldReceive('getResourceType')->andReturn($rType);

        $foo = new PropertyAccessExpression($parent, $prop);
        $foo->setChild($child);
        $this->assertNotNull($foo->getParent());
        $this->assertNotNull($foo->getChild());
        $foo->free();
        $this->assertNull($foo->getParent());
        $this->assertNull($foo->getChild());
    }
}
