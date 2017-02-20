<?php

namespace UnitTests\POData\Providers\Expression;

use Mockery as m;
use POData\Providers\Expression\PHPExpressionProvider;
use POData\Providers\Metadata\ResourceType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use UnitTests\POData\TestCase;

class PHPExpressionProviderErrorCheckTest extends TestCase
{
    public function testonPropertyAccessExpressionNullExpression()
    {
        $foo = new PHPExpressionProvider('abc');

        $expected = 'onPropertyAccessExpression - expression null';
        $actual = null;

        try {
            $result = $foo->onPropertyAccessExpression(null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testonPropertyAccessExpressionBadTypeExpression()
    {
        $foo = new PHPExpressionProvider('abc');

        $expected = 'onPropertyAccessExpression - expression is incorrect type';
        $actual = null;

        try {
            $result = $foo->onPropertyAccessExpression($foo);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testonPropertyAccessExpressionNullResource()
    {
        $property = m::mock(PropertyAccessExpression::class)->makePartial();
        $foo = new PHPExpressionProvider('abc');

        $expected = 'onPropertyAccessExpression - resourceType null';
        $actual = null;

        try {
            $result = $foo->onPropertyAccessExpression($property);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testonPropertyAccessExpressionOnTheResourceWithNoName()
    {
        $res = m::mock(ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn(null);

        $property = m::mock(PropertyAccessExpression::class)->makePartial();
        $foo = new PHPExpressionProvider('abc');
        $foo->setResourceType($res);

        $expected = 'onPropertyAccessExpression - resourceType has no name';
        $actual = null;

        try {
            $result = $foo->onPropertyAccessExpression($property);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testonPropertyAccessExpressionOnExpressionWithNoResourceProperty()
    {
        $res = m::mock(ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn('Desert');

        $property = m::mock(PropertyAccessExpression::class)->makePartial();
        $property->shouldReceive('getResourceProperty')->andReturn(null);

        $foo = new PHPExpressionProvider('abc');
        $foo->setResourceType($res);

        $expected = 'onPropertyAccessExpression - expression has no resource property';
        $actual = null;

        try {
            $result = $foo->onPropertyAccessExpression($property);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
