<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\CynicDeserialiser;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\WebOperationContext as OperationContextAdapter;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;
use UnitTests\POData\TestCase;

class ObjectDeserialiserOKTest extends TestCase
{
    public function testUnresolvableResourceSet()
    {
        $meta = m::mock(IMetadataProvider::class);
        $meta->shouldReceive('resolveResourceSet')->withAnyArgs()->andReturn(null)->once();

        $wrapper = m::mock(ProvidersWrapper::class);

        $foo = new CynicDeserialiser($meta, $wrapper);

        $payload                  = new ODataEntry();
        $payload->resourceSetName = 'resourceSet';

        $expected = 'Specified resource set could not be resolved';
        $actual   = null;

        try {
            $foo->processPayload($payload);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }
}
