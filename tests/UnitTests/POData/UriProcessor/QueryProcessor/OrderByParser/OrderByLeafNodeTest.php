<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor\QueryProcessor\OrderByParser;

use Mockery as m;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByLeafNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
use UnitTests\POData\TestCase;

class OrderByLeafNodeTest extends TestCase
{
    public function testGetResourceType()
    {
        $propName = 'property';
        $rType    = m::mock(ResourceEntityType::class);
        $rProp    = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getResourceType')->andReturn($rType);
        $isAscending = true;

        $foo = new OrderByLeafNode($propName, $rProp, $isAscending);

        $this->assertTrue(null !== $foo->getResourceType());
        $this->assertTrue($foo->getResourceType() instanceof ResourceType);
    }

    public function testBuildComparisonFunctionFromEmptyArray()
    {
        $expected = 'There should be at least one ancestor for building the sort function';
        $actual   = null;

        $propName = 'property';
        $rType    = m::mock(ResourceEntityType::class);
        $rProp    = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getResourceType')->andReturn($rType);
        $isAscending = true;

        $foo = new OrderByLeafNode($propName, $rProp, $isAscending);

        try {
            $foo->buildComparisonFunction([]);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }
}
