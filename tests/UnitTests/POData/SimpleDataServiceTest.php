<?php

namespace UnitTests\POData;

use Mockery as m;
use POData\IService;
use POData\ObjectModel\IObjectSerialiser;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Query\IQueryProvider;
use POData\SimpleDataService;

class SimpleDataServiceTest extends TestCase
{
    public function testCreateWithSuppliedSerialiser()
    {
        $cereal = m::mock(IObjectSerialiser::class);
        $cereal->shouldReceive('setService')->withAnyArgs()->andReturnNull()->once();

        $meta = m::mock(SimpleMetadataProvider::class);
        $db = m::mock(IQueryProvider::class);
        $service = m::mock(ServiceHost::class);

        $foo = new SimpleDataService($db, $meta, $service, $cereal);
        $this->assertTrue($foo instanceof SimpleDataService);
    }
}
