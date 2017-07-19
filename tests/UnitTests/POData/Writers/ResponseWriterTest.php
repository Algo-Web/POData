<?php

namespace UnitTests\POData\Writers;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\Version;
use POData\IService;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\OutgoingResponse;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\Writers\IODataWriter;
use POData\Writers\ODataWriterRegistry;
use POData\Writers\ResponseWriter;
use UnitTests\POData\TestCase;

class ResponseWriterTest extends TestCase
{
    public function testWriteMetadata()
    {
        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('getMetadataXML')->andReturn('MetadataXML')->once();

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::METADATA());

        $response = m::mock(OutgoingResponse::class)->makePartial();
        $response->shouldReceive('setStream')->withArgs(['MetadataXML'])->andReturnNull()->once();

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext->outgoingResponse')->andReturn($response);

        $service = m::mock(IService::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host)->atLeast(1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);

        ResponseWriter::write($service, $request, null, null);
    }

    public function testWriteServiceDocument()
    {
        $writer = m::mock(IODataWriter::class);
        $writer->shouldReceive('writeServiceDocument->getOutput')->andReturn('ServiceDocument');

        $wrapper = m::mock(ProvidersWrapper::class);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::SERVICE_DIRECTORY());

        $response = m::mock(OutgoingResponse::class)->makePartial();
        $response->shouldReceive('setStream')->withArgs(['ServiceDocument'])->andReturnNull()->once();

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext->outgoingResponse')->andReturn($response);

        $service = m::mock(IService::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host)->atLeast(1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getODataWriterRegistry->getWriter')->andReturn($writer);

        ResponseWriter::write($service, $request, null, null);
    }

    public function testWriteServiceDocumentNoWriter()
    {
        $expected = 'No writer can handle the request.';
        $actual = null;

        $writer = null;

        $wrapper = m::mock(ProvidersWrapper::class);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::SERVICE_DIRECTORY());

        $response = m::mock(OutgoingResponse::class)->makePartial();
        $response->shouldReceive('setStream')->withArgs(['ServiceDocument'])->andReturnNull()->never();

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext->outgoingResponse')->andReturn($response);

        $service = m::mock(IService::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host)->atLeast(1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getODataWriterRegistry->getWriter')->andReturn($writer);

        try {
            ResponseWriter::write($service, $request, null, null);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testWriteMediaResource()
    {
        $streamWrapper = m::mock(StreamProviderWrapper::class);
        $streamWrapper->shouldReceive('getStreamETag')->andReturn('eTag')->once();
        $streamWrapper->shouldReceive('getReadStream')->withArgs([null, null])->andReturn('MediaResource');

        $wrapper = m::mock(ProvidersWrapper::class);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $request->shouldReceive('getTargetResult')->andReturnNull()->once();
        $request->shouldReceive('getResourceStreamInfo')->andReturnNull()->once();

        $response = m::mock(OutgoingResponse::class)->makePartial();
        $response->shouldReceive('setStream')->withArgs(['MediaResource'])->andReturnNull()->once();

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext->outgoingResponse')->andReturn($response);

        $service = m::mock(IService::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host)->atLeast(1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getStreamProviderWrapper')->andReturn($streamWrapper);

        ResponseWriter::write($service, $request, null, null);
    }

    public function testWriteOctetStream()
    {
        $streamWrapper = m::mock(StreamProviderWrapper::class);
        $streamWrapper->shouldReceive('getReadStream')->withArgs([null, null])->andReturn('MediaResource');

        $wrapper = m::mock(ProvidersWrapper::class);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::PRIMITIVE());
        $request->shouldReceive('getTargetResult')->andReturn('Primitive')->once();

        $response = m::mock(OutgoingResponse::class)->makePartial();
        $response->shouldReceive('setStream')->withArgs(['Primitive'])->andReturnNull()->once();

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext->outgoingResponse')->andReturn($response);

        $service = m::mock(IService::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host)->atLeast(1);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getStreamProviderWrapper')->andReturn($streamWrapper);

        ResponseWriter::write($service, $request, null, MimeTypes::MIME_APPLICATION_OCTETSTREAM);
    }
}
