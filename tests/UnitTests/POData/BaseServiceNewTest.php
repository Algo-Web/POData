<?php

namespace UnitTests\POData;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\IService;
use POData\ObjectModel\IObjectSerialiser;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessor;
use UnitTests\POData\TestCase;

class BaseServiceNewTest extends TestCase
{
    public function testGetResultWithNullMetadataProviderThrowException()
    {
        $db = m::mock(IQueryProvider::class);
        $host = m::mock(ServiceHost::class)->makePartial();
        $cereal = m::mock(IObjectSerialiser::class);
        $wrap = m::mock(StreamProviderWrapper::class)->makePartial();

        $foo = new BaseServiceDummy($db, $host, $cereal, $wrap, null);

        $expected = 'For custom providers, GetService should not return null for both IMetadataProvider'
                    .' and IQueryProvider types.';
        $actual = null;

        try {
            $result = $foo->handleRequest();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResultWithBadMetadataProviderTypeThrowException()
    {
        $foo = m::mock(BaseServiceDummy::class)->makePartial();
        $foo->shouldReceive('getMetadataProvider')->andReturn('foobar');

        $expected = 'IService.getMetdataProvider returns invalid object.';
        $actual = null;

        try {
            $result = $foo->handleRequest();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResultWithNullQueryProviderThrowException()
    {
        $db = null;
        $host = m::mock(ServiceHost::class)->makePartial();
        $cereal = m::mock(IObjectSerialiser::class);
        $wrap = m::mock(StreamProviderWrapper::class)->makePartial();
        $meta = m::mock(IMetadataProvider::class);

        $foo = new BaseServiceDummy($db, $host, $cereal, $wrap, $meta);

        $expected = 'For custom providers, GetService should not return null for both IMetadataProvider'
                    .' and IQueryProvider types.';
        $actual = null;

        try {
            $result = $foo->handleRequest();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResultWithBadQueryProviderTypeThrowException()
    {
        $meta = m::mock(IMetadataProvider::class);

        $foo = m::mock(BaseServiceDummy::class)->makePartial();
        $foo->shouldReceive('getMetadataProvider')->andReturn($meta);
        $foo->shouldReceive('getQueryProvider')->andReturn('foobar');

        $expected = 'IService.getQueryProvider returns invalid object.';
        $actual = null;

        try {
            $result = $foo->handleRequest();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResponseContentTypeBadTypeThrowException()
    {
        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestAccept')->andReturn(null);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetKind')->andReturn(null);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'Unsupported media type requested.';
        $actual = null;

        try {
            BaseServiceDummy::getResponseContentType($request, $proc, $service);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResponseContentTypePrimitiveValueNullPropertyThrowException()
    {
        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestAccept')->andReturn(null);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::PRIMITIVE_VALUE());
        $request->shouldReceive('getIdentifier')->andReturn('entity');
        $request->shouldReceive('getProjectedProperty')->andReturn(null);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'assert(): is_null($projectedProperty) failed';
        $actual = null;

        try {
            BaseServiceDummy::getResponseContentType($request, $proc, $service);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResponseContentTypePrimitiveValueNullInstanceTypeThrowException()
    {
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getInstanceType')->andReturn(null);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestAccept')->andReturn(null);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::PRIMITIVE_VALUE());
        $request->shouldReceive('getIdentifier')->andReturn('entity');
        $request->shouldReceive('getProjectedProperty')->andReturn($property);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'assert(): !$type instanceof IType failed';
        $actual = null;

        try {
            BaseServiceDummy::getResponseContentType($request, $proc, $service);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResponseContentTypePrimitiveValueBinaryInstanceType()
    {
        $type = new Binary();

        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getInstanceType')->andReturn($type);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestAccept')->andReturn(null);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::PRIMITIVE_VALUE());
        $request->shouldReceive('getIdentifier')->andReturn('entity');
        $request->shouldReceive('getProjectedProperty')->andReturn($property);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'application/octet-stream';
        $result = BaseServiceDummy::getResponseContentType($request, $proc, $service);

        $this->assertEquals($expected, $result);
    }

    public function testGetResponseContentTypeMediaResourceBinaryInstanceType()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = new Binary();

        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getInstanceType')->andReturn($type);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $request->shouldReceive('getIdentifier')->andReturn('entity');
        $request->shouldReceive('getProjectedProperty')->andReturn($property);
        $request->shouldReceive('isNamedStream')->andReturn(false);
        $request->shouldReceive('getTargetResourceType->isMediaLinkEntry')->andReturn(false);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'The URI \'https://www.example.org/odata.svc\' is not valid. The segment before \'$value\' '
                    .'must be a Media Link Entry or a primitive property.';
        $actual = null;

        try {
            BaseServiceDummy::getResponseContentType($request, $proc, $service);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetContentTypeIsMediaResourceNullContentType()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = new Binary();

        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getInstanceType')->andReturn($type);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $request->shouldReceive('getIdentifier')->andReturn('entity');
        $request->shouldReceive('getProjectedProperty')->andReturn($property);
        $request->shouldReceive('isNamedStream')->andReturn(true)->once();
        $request->shouldReceive('getTargetResourceType->isMediaLinkEntry')->andReturn(false)->never();
        $request->shouldReceive('setExecuted')->andReturnNull()->once();
        $request->shouldReceive('getTargetResult')->andReturnNull()->once();
        $request->shouldReceive('getResourceStreamInfo')->andReturnNull()->once();

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getStreamProviderWrapper->getStreamContentType')->andReturnNull()->once();

        $proc = m::mock(UriProcessor::class);
        $proc->shouldReceive('execute')->andReturnNull()->once();

        $result = BaseServiceDummy::getResponseContentType($request, $proc, $service);
        $this->assertNull($result);
    }

    public function testGetContentTypeIsMediaResourceNonNullContentType()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = new Binary();

        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getInstanceType')->andReturn($type);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $request->shouldReceive('getIdentifier')->andReturn('entity');
        $request->shouldReceive('getProjectedProperty')->andReturn($property);
        $request->shouldReceive('isNamedStream')->andReturn(true)->once();
        $request->shouldReceive('getTargetResourceType->isMediaLinkEntry')->andReturn(false)->never();
        $request->shouldReceive('setExecuted')->andReturnNull()->once();
        $request->shouldReceive('getTargetResult')->andReturnNull()->once();
        $request->shouldReceive('getResourceStreamInfo')->andReturnNull()->once();

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getStreamProviderWrapper->getStreamContentType')
            ->andReturn(MimeTypes::MIME_TEXTXML)->once();

        $proc = m::mock(UriProcessor::class);
        $proc->shouldReceive('execute')->andReturnNull()->once();

        $result = BaseServiceDummy::getResponseContentType($request, $proc, $service);
        $this->assertEquals('text/xml', $result);
    }
}