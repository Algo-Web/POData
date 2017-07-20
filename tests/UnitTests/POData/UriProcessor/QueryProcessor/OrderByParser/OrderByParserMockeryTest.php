<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\OrderByParser;

use Mockery as m;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
use UnitTests\POData\TestCase;

class OrderByParserMockeryTest extends TestCase
{
    public function testBuildOrderByTreeWithNullProperty()
    {
        $expected = 'Error in the \'orderby\' clause. Type Edm.None does not have a property named \'Customers\'.';
        $actual = null;

        $wrapper = m::mock(ResourceSetWrapper::class);
        $wrapper->shouldReceive('getName')->andReturn('resourceSet');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('resolveProperty')->andReturn(null)->once();
        $type->shouldReceive('getFullName')->andReturn('Edm.None')->once();
        $type->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $provider = m::mock(ProvidersWrapper::class);
        $orderBy = 'Customers asc';

        try {
            OrderByParser::parseOrderByClause($wrapper, $type, $orderBy, $provider);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBuildOrderByTreeWithPrimitiveAsIntermediateSegment()
    {
        $expected = 'The primitive property \'rProp\' cannot be used as intermediate segment, it should'
                    .' be last segment';
        $actual = null;

        $wrapper = m::mock(ResourceSetWrapper::class);
        $wrapper->shouldReceive('getName')->andReturn('resourceSet');

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(false);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $rProp->shouldReceive('getName')->andReturn('rProp');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('resolveProperty')->andReturn($rProp)->once();
        $type->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $provider = m::mock(ProvidersWrapper::class);
        $orderBy = 'Customers/Id asc, Orders desc, Id asc';

        try {
            OrderByParser::parseOrderByClause($wrapper, $type, $orderBy, $provider);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBuildOrderByTreeWithUnexpectedPropertyType()
    {
        $expected = 'Property type unexpected';
        $actual = null;

        $wrapper = m::mock(ResourceSetWrapper::class);
        $wrapper->shouldReceive('getName')->andReturn('resourceSet');

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $rProp->shouldReceive('getName')->andReturn('rProp');
        $rProp->shouldReceive('getKind')->andReturn(null);

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('resolveProperty')->andReturn($rProp)->once();
        $type->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $provider = m::mock(ProvidersWrapper::class);
        $orderBy = 'Customers/Id asc, Orders desc, Id asc';

        try {
            OrderByParser::parseOrderByClause($wrapper, $type, $orderBy, $provider);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBuildOrderByTreeWithReflectionExceptionOnResourceRefPropertyInit()
    {
        $expected = 'OrderBy parser failed to access or initialize the property rProp of type';
        $actual = null;

        $wrapper = m::mock(ResourceSetWrapper::class);
        $wrapper->shouldReceive('getName')->andReturn('resourceSet');

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true, false);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $rProp->shouldReceive('getName')->andReturn('rProp');
        $rProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE);

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('resolveProperty')->andReturn($rProp)->once();
        $type->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $type->shouldReceive('setPropertyValue')->andThrow(new \ReflectionException());
        $type->shouldReceive('getName')->andReturn('type');
        $provider = m::mock(ProvidersWrapper::class);
        $orderBy = 'Customers asc, Orders desc, Id asc';

        try {
            OrderByParser::parseOrderByClause($wrapper, $type, $orderBy, $provider);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBuildOrderByTreeWithReflectionExceptionOnComplexTypePropertyInit()
    {
        $expected = 'OrderBy parser failed to access or initialize the property prop of rType';
        $actual = null;

        $wrapper = m::mock(ResourceSetWrapper::class);
        $wrapper->shouldReceive('getName')->andReturn('resourceSet');

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true, false);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $rProp->shouldReceive('getName')->andReturn('prop');
        $rProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE);

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('resolveProperty')->andReturn($rProp)->once();
        $type->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $type->shouldReceive('setPropertyValue')->andThrow(new \ReflectionException());
        $type->shouldReceive('getName')->andReturn('rType');
        $provider = m::mock(ProvidersWrapper::class);
        $orderBy = 'Customers asc, Orders desc, Id asc';

        try {
            OrderByParser::parseOrderByClause($wrapper, $type, $orderBy, $provider);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
