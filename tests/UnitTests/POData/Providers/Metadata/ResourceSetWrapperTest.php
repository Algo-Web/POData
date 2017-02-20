<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\ProvidersWrapper;
use UnitTests\POData\TestCase;

class ResourceSetWrapperTest extends TestCase
{
    public function testHasNamedStreamsYes()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasNamedStream')->andReturnNull()->once();

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getResourceType')->andReturn($type);
        $config = m::mock(ServiceConfiguration::class);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(200);
        $config->shouldReceive('getEntitySetAccessRule')->andReturnNull();

        $derived = m::mock(ResourceType::class);
        $derived->shouldReceive('hasNamedStream')->andReturn(true)->once();

        $wrap = m::mock(ProvidersWrapper::class);
        $wrap->shouldReceive('getDerivedTypes')->withAnyArgs()->andReturn([$derived]);

        $foo = new ResourceSetWrapper($set, $config);
        $this->assertTrue($foo->hasNamedStreams($wrap));
    }

    public function testGetResourceSetRights()
    {
        $set = m::mock(ResourceSet::class);
        $config = m::mock(ServiceConfiguration::class);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(200);
        $config->shouldReceive('getEntitySetAccessRule')->andReturnNull();

        $foo = new ResourceSetWrapper($set, $config);
        $this->assertNull($foo->getResourceSetRights());
    }

    public function testHasBagPropertyYes()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasBagProperty')->andReturnNull()->once();

        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getResourceType')->andReturn($type);
        $config = m::mock(ServiceConfiguration::class);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(200);
        $config->shouldReceive('getEntitySetAccessRule')->andReturnNull();

        $derived = m::mock(ResourceType::class);
        $derived->shouldReceive('hasBagProperty')->andReturn(true)->once();

        $wrap = m::mock(ProvidersWrapper::class);
        $wrap->shouldReceive('getDerivedTypes')->withAnyArgs()->andReturn([$derived]);

        $foo = new ResourceSetWrapper($set, $config);
        $this->assertTrue($foo->hasBagProperty($wrap));
    }

    public function testCheckResourceSetRightsAndThrowException()
    {
        $set = m::mock(ResourceSet::class);
        $config = m::mock(ServiceConfiguration::class);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(200);
        $config->shouldReceive('getEntitySetAccessRule')->andReturn(EntitySetRights::NONE);

        $foo = new ResourceSetWrapper($set, $config);

        $expected = 'Forbidden.';
        $actual = null;

        try {
            $foo->checkResourceSetRights(EntitySetRights::ALL);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
