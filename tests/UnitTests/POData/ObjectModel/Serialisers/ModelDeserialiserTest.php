<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\ModelDeserialiser;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\IncomingRequest;
use POData\OperationContext\Web\WebOperationContext as OperationContextAdapter;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Query\IQueryProvider;
use UnitTests\POData\Facets\NorthWind1\Address4;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\Employee2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;

class ModelDeserialiserTest extends SerialiserTestBase
{
    public function testDeserialiseWithUnsetType()
    {
        $resource = m::mock(ResourceEntityType::class);
        $entry    = new ODataEntry();

        $expected = 'ODataEntry payload type not set';
        $actual   = null;

        $cereal = new ModelDeserialiser();

        try {
            $cereal->bulkDeserialise($resource, $entry);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testDeserialiseWithTypeMismatch()
    {
        $resource = m::mock(ResourceEntityType::class);
        $resource->shouldReceive('getName')->andReturn('RockTheBlock');

        $entry       = new ODataEntry();
        $entry->type = new ODataCategory('fnord');

        $expected = 'Payload resource type does not match supplied resource type.';
        $actual   = null;

        $cereal = new ModelDeserialiser();

        try {
            $cereal->bulkDeserialise($resource, $entry);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testDeserialiseActualModel()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        $objectResult     = new ODataEntry(
            'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')',
            new ODataLink('self', 'Customer', null, 'Customers(CustomerID=\'1\',CustomerGuid'
                . '=guid\'123e4567-e89b-12d3-a456-426655440000\')'),
            new ODataTitle('Customer'),
            new ODataLink('edit', 'Customer', null, 'Customers(CustomerID=\'1\',CustomerGuid'
                . '=guid\'123e4567-e89b-12d3-a456-426655440000\')'),
            new ODataCategory('Customer'),
            new ODataPropertyContent(
                [
                    'CustomerID' => new ODataProperty('CustomerID', 'Edm.String', '1'),
                    'CustomerGuid' => new ODataProperty('CustomerGuid', 'Edm.Guid', '123e4567-e89b-12d3-a456-426655440000'),
                    'CustomerName' => new ODataProperty('CustomerName', 'Edm.String', ' MakeItPhunkee '),
                    'country' => new ODataProperty('country', 'Edm.String', ' Oop North '),
                    'Rating' => new ODataProperty('Rating', 'Edm.Int32', null),
                    'Photo' => new ODataProperty('Photo', 'Edm.Binary', null),
                    'Address' => new ODataProperty('Address', 'Address', null)
                ]
            ),
            [],
            null,
            [],
            null,
            null,
            'Customers',
            '2017-01-01T00:00:00+00:00',
            null
        );

        $type = $meta->resolveResourceType('Customer');

        $cereal = new ModelDeserialiser();
        $cereal->reset();

        $expected = ['CustomerName' => 'MakeItPhunkee', 'country' => 'Oop North', 'Rating' => null, 'Photo' => null,
            'Address' => null];

        $actual = $cereal->bulkDeserialise($type, $objectResult);
        $this->assertEquals($expected, $actual);
    }

    public function testDeserialiseBooleanType()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $type = new ResourcePrimitiveType(new Boolean());

        $prop = m::mock(ResourceProperty::class)->makePartial();
        $prop->shouldReceive('getName')->andReturn('gotFnord');
        $prop->shouldReceive('getResourceType')->andReturn($type);

        $resource = m::mock(ResourceEntityType::class);
        $resource->shouldReceive('getName')->andReturn('RockTheBlock');
        $resource->shouldReceive('getKeyProperties')->andReturn([]);
        $resource->shouldReceive('getAllProperties')->andReturn(['gotFnord' => $prop]);

        $odataProp           = new ODataProperty('gotFnord', 'Edm.Boolean', 'true');

        $content = new ODataPropertyContent(['gotFnord' => $odataProp]);

        $entry       = new ODataEntry();
        $entry->type = new ODataCategory('RockTheBlock');
        $entry->setPropertyContent($content);

        $cereal = new ModelDeserialiser();

        $expected = ['gotFnord' => true];

        $actual = $cereal->bulkDeserialise($resource, $entry);
        $this->assertEquals($expected, $actual);
    }

    public function testDeserialiseDateTimeTypeWithTimezoneAndPickles()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $type = new ResourcePrimitiveType(new DateTime());

        $prop = m::mock(ResourceProperty::class)->makePartial();
        $prop->shouldReceive('getName')->andReturn('startFnord');
        $prop->shouldReceive('getResourceType')->andReturn($type);

        $resource = m::mock(ResourceEntityType::class);
        $resource->shouldReceive('getName')->andReturn('RockTheBlock');
        $resource->shouldReceive('getKeyProperties')->andReturn([]);
        $resource->shouldReceive('getAllProperties')->andReturn(['startFnord' => $prop]);

        $odataProp           = new ODataProperty('startFnord', 'Edm.DateTime', '2017-12-18T18:22:11.3779297-08:00');

        $content = new ODataPropertyContent(['startFnord' => $odataProp]);

        $entry       = new ODataEntry();
        $entry->type = new ODataCategory('RockTheBlock');
        $entry->setPropertyContent($content);

        $cereal = new ModelDeserialiser();

        $date = Carbon::create(2017, 12, 18, 18, 22, 11, '-08:00');

        $expected = ['startFnord' => $date];

        $actual = $cereal->bulkDeserialise($resource, $entry);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param $request
     * @return array
     */
    private function setUpDataServiceDeps($request)
    {
        $op   = new OperationContextAdapter($request);
        $host = new ServiceHost($op);

        $meta  = NorthWindMetadata::Create();
        $query = m::mock(IQueryProvider::class);

        return [$host, $meta, $query];
    }
}
