<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor\QueryProcessor\OrderByParser;

use Mockery as m;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\QueryResult;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderBySubPathSegment;
use UnitTests\POData\TestCase;

class InternalOrderByInfoTest extends TestCase
{
    public function testBuildSkipTokenValueWithNoSubPathSegments()
    {
        $expected = '';
        $actual   = null;

        $segment = m::mock(OrderByPathSegment::class)->makePartial();
        $segment->shouldReceive('getSubPathSegments')->andReturn([]);

        $foo = m::mock(InternalOrderByInfo::class)->makePartial();
        $foo->shouldReceive('getOrderByPathSegments')->andReturn([$segment]);

        $actual = $foo->buildSkipTokenValue(new \DateTime());
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testBuildSkipTokenValueFromQueryResultWithOneSubPathSegmentPointingToNull()
    {
        $bar                 = new \stdClass();
        $bar->bar            = 'null';
        $bar->type           = 'R';
        $lastObject          = new QueryResult();
        $lastObject->results = $bar;

        $expected = 'null';
        $actual   = null;

        $rType = m::mock(ResourceEntityType::class)->makePartial();
        $rType->shouldReceive('getPropertyValue')->with(m::any(), 'bar')->andReturnNull();

        $subseg1 = m::mock(OrderBySubPathSegment::class);
        $subseg1->shouldReceive('getName')->andReturn('bar');
        $subseg2 = m::mock(OrderBySubPathSegment::class);
        $subseg2->shouldReceive('getName')->andReturn('type');

        $segment = m::mock(OrderByPathSegment::class)->makePartial();
        $segment->shouldReceive('getSubPathSegments')->andReturn([$subseg1, $subseg2]);

        $foo = m::mock(InternalOrderByInfo::class)->makePartial();
        $foo->shouldReceive('getOrderByPathSegments')->andReturn([$segment]);
        $foo->shouldReceive('getResourceType')->andReturn($rType);

        $actual = $foo->buildSkipTokenValue($bar);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testBuildSkipTokenValueFromQueryResultWithOneSubPathSegmentThrowReflectionException()
    {
        $bar                 = new \stdClass();
        $bar->bar            = 'null';
        $bar->type           = 'R';
        $lastObject          = new QueryResult();
        $lastObject->results = $bar;

        $expected = 'internalSkipTokenInfo failed to access or initialize the property bar';
        $actual   = null;

        $rType = m::mock(ResourceEntityType::class)->makePartial();
        $rType->shouldReceive('getPropertyValue')->withAnyArgs()->andThrow(new \ReflectionException());

        $subseg1 = m::mock(OrderBySubPathSegment::class);
        $subseg1->shouldReceive('getName')->andReturn('bar');
        $subseg2 = m::mock(OrderBySubPathSegment::class);
        $subseg2->shouldReceive('getName')->andReturn('type');

        $segment = m::mock(OrderByPathSegment::class)->makePartial();
        $segment->shouldReceive('getSubPathSegments')->andReturn([$subseg1, $subseg2]);

        $foo = m::mock(InternalOrderByInfo::class)->makePartial();
        $foo->shouldReceive('getOrderByPathSegments')->andReturn([$segment]);
        $foo->shouldReceive('getResourceType')->andReturn($rType);

        try {
            $foo->buildSkipTokenValue($lastObject);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testBuildSkipTokenValueFromQueryResultWithTwoSubPathSegments()
    {
        $bar                 = new \stdClass();
        $bar->bar            = 'rebar';
        $bar->type           = 'R';
        $lastObject          = new QueryResult();
        $lastObject->results = $bar;

        $iType = new StringType();

        $expected = '\'R\'';
        $actual   = null;

        $rType = m::mock(ResourceEntityType::class)->makePartial();
        $rType->shouldReceive('getPropertyValue')->with(m::any(), 'bar')->andReturn('rebar');
        $rType->shouldReceive('getPropertyValue')->with(m::any(), 'type')->andReturn('R');

        $subseg1 = m::mock(OrderBySubPathSegment::class);
        $subseg1->shouldReceive('getName')->andReturn('bar');
        $subseg2 = m::mock(OrderBySubPathSegment::class);
        $subseg2->shouldReceive('getName')->andReturn('type');
        $subseg2->shouldReceive('getInstanceType')->andReturn($iType);

        $segment = m::mock(OrderByPathSegment::class)->makePartial();
        $segment->shouldReceive('getSubPathSegments')->andReturn([$subseg1, $subseg2]);

        $foo = m::mock(InternalOrderByInfo::class)->makePartial();
        $foo->shouldReceive('getOrderByPathSegments')->andReturn([$segment]);
        $foo->shouldReceive('getResourceType')->andReturn($rType);

        $actual = $foo->buildSkipTokenValue($lastObject);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }
}
