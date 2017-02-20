<?php

namespace UnitTests\POData\Common;

use Mockery as m;
use POData\BaseService;
use POData\Common\Url;
use POData\Configuration\ProtocolVersion;
use POData\Configuration\ServiceConfiguration;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\SimpleDataService;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\UriProcessor;
use POData\Writers\Atom\AtomODataWriter;
use POData\Writers\ODataWriterRegistry;
use UnitTests\POData\TestCase;

class BaseServiceTest extends TestCase
{
    /** @var RequestDescription */
    protected $mockRequest;

    /** @var UriProcessor */
    protected $mockUriProcessor;

    /** @var ODataWriterRegistry */
    protected $mockRegistry;

    /** @var IMetadataProvider */
    protected $mockMetaProvider;

    /** @var ServiceHost */
    protected $mockHost;

    public function setUp()
    {
        $this->mockHost = m::mock(ServiceHost::class)->makePartial();
        $this->mockMetaProvider = m::mock(IMetadataProvider::class)->makePartial();
        $this->mockRegistry = m::mock(ODataWriterRegistry::class)->makePartial();
    }

    public function testRegisterWritersV1()
    {
        /** @var BaseService $service */
        $service = m::mock(SimpleDataService::class)->makePartial();

        $this->mockRegistry->shouldReceive('register')->withAnyArgs()->times(2);

        //fake the service url
        $fakeUrl = 'http://host/service.svc/Collection';
        $this->mockHost->shouldReceive('getAbsoluteServiceUri')->andReturn(new Url($fakeUrl));

        $service->setHost($this->mockHost);

        //TODO: have to do this since the registry & config is actually only instantiated during a handleRequest
        //will change this once that request pipeline is cleaned up
        $service->shouldReceive('getODataWriterRegistry')->andReturn($this->mockRegistry);
        $fakeConfig = new ServiceConfiguration($this->mockMetaProvider);
        $fakeConfig->setMaxDataServiceVersion(ProtocolVersion::V1());
        $service->shouldReceive('getConfiguration')->andReturn($fakeConfig);

        $service->registerWriters();
    }

    public function testRegisterWritersV2()
    {
        /** @var BaseService $service */
        $service = m::mock(SimpleDataService::class)->makePartial();

        $service->setHost($this->mockHost);

        $this->mockRegistry->shouldReceive('register')->withAnyArgs()->passthru()->times(3);
        // TODO: Figure out how to set up overlapping expectations in Mockery
        /*
        $this->mockRegistry->shouldReceive('register')
            ->with(anInstanceOf('\POData\Writers\Atom\AtomODataWriter'))->passthru()->times(1);
        //since v2 derives from this,,it's 2 times
        $this->mockRegistry->shouldReceive('register')
            ->with(anInstanceOf('\POData\Writers\Json\JsonODataV1Writer'))->passthru()->times(2);
        $this->mockRegistry->shouldReceive('register')
            ->with(anInstanceOf('\POData\Writers\Json\JsonODataV2Writer'))->passthru()->times(1);*/

        //fake the service url
        $fakeUrl = 'http://host/service.svc/Collection';
        $this->mockHost->shouldReceive('getAbsoluteServiceUri')->andReturn(new Url($fakeUrl));

        //TODO: have to do this since the registry & config is actually only instantiated during a handleRequest
        //will change this once that request pipeline is cleaned up
        $service->shouldReceive('getODataWriterRegistry')->andReturn($this->mockRegistry);
        $fakeConfig = new ServiceConfiguration($this->mockMetaProvider);
        $fakeConfig->setMaxDataServiceVersion(ProtocolVersion::V2());
        $service->shouldReceive('getConfiguration')->andReturn($fakeConfig);

        $service->registerWriters();
    }

    public function testRegisterWritersV3()
    {
        /** @var BaseService $service */
        $service = m::mock(SimpleDataService::class)->makePartial();

        $service->setHost($this->mockHost);

        $this->mockRegistry->shouldReceive('register')->withAnyArgs()->passthru()->times(6);
        // TODO: Figure out how to set up overlapping expectations in Mockery
        /*
        $this->mockRegistry->shouldReceive('register')
            ->with(anInstanceOf(AtomODataWriter::class))->passthru()->times(1);
        //since v2 & light derives from this,,it's 1+1+3 times
        $this->mockRegistry->shouldReceive('register')
            ->with(anInstanceOf('\POData\Writers\Json\JsonODataV1Writer'))->passthru()->times(5);
        //since light derives from this it's 1+3 times
        $this->mockRegistry->shouldReceive('register')
            ->with(anInstanceOf('\POData\Writers\Json\JsonODataV2Writer'))->passthru()->times(4);
        $this->mockRegistry->shouldReceive('register')
            ->with(anInstanceOf('\POData\Writers\Json\JsonLightODataWriter'))->passthru()->times(3);*/

        //TODO: have to do this since the registry & config is actually only instantiated during a handleRequest
        //will change this once that request pipeline is cleaned up
        $service->shouldReceive('getODataWriterRegistry')->andReturn($this->mockRegistry);
        $fakeConfig = new ServiceConfiguration($this->mockMetaProvider);
        $fakeConfig->setMaxDataServiceVersion(ProtocolVersion::V3());
        $service->shouldReceive('getConfiguration')->andReturn($fakeConfig);

        //fake the service url
        $fakeUrl = 'http://host/service.svc/Collection';
        $this->mockHost->shouldReceive('getAbsoluteServiceUri')->andReturn(new Url($fakeUrl));

        $service->registerWriters();
    }
}
