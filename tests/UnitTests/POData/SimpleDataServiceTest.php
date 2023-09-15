<?php

declare(strict_types=1);

namespace UnitTests\POData;

use Mockery as m;
use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\ObjectModel\IObjectSerialiser;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Query\IReadQueryProvider;
use POData\Providers\Stream\IStreamProvider2;
use POData\SimpleDataService;
use UnitTests\POData\Facets\NorthWind1\NorthWindQueryProvider;

class SimpleDataServiceTest extends TestCase
{
    public function testCreateWithSuppliedSerialiser()
    {
        $cereal = m::mock(IObjectSerialiser::class)->makePartial();
        $cereal->shouldReceive('setService')->withAnyArgs()->andReturnNull()->once();

        $meta    = m::mock(SimpleMetadataProvider::class);
        $db      = m::mock(IReadQueryProvider::class);
        $service = m::mock(ServiceHost::class);

        $foo = new SimpleDataService($db, $meta, $service, $cereal);
        $this->assertTrue($foo instanceof SimpleDataService);
    }

    public function testCreateWithImpliedSerialiser()
    {
        $cereal = m::mock(IObjectSerialiser::class);
        $cereal->shouldReceive('setService')->withAnyArgs()->andReturnNull()->once();

        $meta                       = m::mock(SimpleMetadataProvider::class);
        $service                    = m::mock(ServiceHost::class);
        $db                         = new \stdClass();
        $db->queryProviderClassName = NorthWindQueryProvider::class;
        $foo                        = new SimpleDataService($db, $meta, $service, $cereal);
        $this->assertTrue($foo instanceof SimpleDataService);
        $this->assertTrue($foo->getReadQueryProvider() instanceof NorthWindQueryProvider);
    }

    public function testCreateWithNullSerialiser()
    {
        $expected     = 'Invalid query provider supplied';
        $expectedCode = 500;
        $actual       = null;
        $actualCode   = null;

        $cereal = m::mock(IObjectSerialiser::class);

        $meta    = m::mock(SimpleMetadataProvider::class);
        $db      = null;
        $service = m::mock(ServiceHost::class);

        try {
            new SimpleDataService($db, $meta, $service, $cereal);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expectedCode, $actualCode);
        $this->assertEquals($expected, $actual);
    }

    public function testInitializeService()
    {
        $cereal = m::mock(IObjectSerialiser::class);
        $cereal->shouldReceive('setService')->withAnyArgs()->andReturnNull()->once();

        $meta    = m::mock(SimpleMetadataProvider::class);
        $db      = m::mock(IReadQueryProvider::class);
        $service = m::mock(ServiceHost::class);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('setEntitySetAccessRule')
            ->withArgs(['*', m::on(function (EntitySetRights $arg) {
                return EntitySetRights::ALL == $arg->getValue();
            })])->once();

        $foo = new SimpleDataService($db, $meta, $service, $cereal);
        $foo->initializeService($config);
        $this->assertTrue($foo->getStreamProviderX() instanceof IStreamProvider2);
    }
}
