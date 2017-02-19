<?php

namespace UnitTests\POData;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\ObjectModel\IObjectSerialiser;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\SimpleDataService;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessor;
use UnitTests\POData\ObjectModel\reusableEntityClass1;
use UnitTests\POData\ObjectModel\reusableEntityClass2;
use UnitTests\POData\ObjectModel\reusableEntityClass3;
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

    public function testGetEtagForEntryNoProperties()
    {
        $host = m::mock(ServiceHost::class);
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getETagProperties')->andReturn([]);
        $object = null;

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null);

        $result = $foo->getETagForEntry($object, $type);
        $this->assertNull($result);
    }

    public function testGetEtagForEntrySinglePropertyBadInstanceTypeThrowException()
    {
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getInstanceType')->andReturnNull()->once();

        $host = m::mock(ServiceHost::class);
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getETagProperties')->andReturn([$property]);
        $object = null;

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null);

        $expected = 'assert(): !$type instanceof IType failed';
        $actual = null;

        try {
            $foo->getETagForEntry($object, $type);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetEtagForEntrySinglePropertyBadPropertyNameThrowException()
    {
        $instanceType = m::mock(IType::class);

        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getInstanceType')->andReturn($instanceType)->once();
        $property->shouldReceive('getName')->andReturn('name');

        $host = m::mock(ServiceHost::class);
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getETagProperties')->andReturn([$property]);
        $type->shouldReceive('getName')->andReturn('type');
        $object = null;

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null);

        $expected = 'Data Service failed to access or initialize the property name of type.';
        $actual = null;

        try {
            $foo->getETagForEntry($object, $type);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetEtagForEntryObjectWithMagicGetter()
    {
        $instanceType = new StringType();

        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getInstanceType')->andReturn($instanceType)->twice();
        $property->shouldReceive('getName')->andReturn('name', 'type');

        $host = m::mock(ServiceHost::class);
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getETagProperties')->andReturn([$property, $property]);
        $type->shouldReceive('getName')->andReturn('type');
        $object = new reusableEntityClass2('hammer', 'time!');

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null);
        $result = $foo->getETagForEntry($object, $type);
        $this->assertEquals("'hammer','time!'", $result);
    }

    public function testGetEtagForEntryObjectWithoutMagicGetter()
    {
        $instanceType = new StringType();

        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getInstanceType')->andReturn($instanceType)->twice();
        $property->shouldReceive('getName')->andReturn('name', 'type');

        $host = m::mock(ServiceHost::class);
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getETagProperties')->andReturn([$property, $property]);
        $type->shouldReceive('getName')->andReturn('type');
        $object = new reusableEntityClass3('hammer', 'time!');

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null);
        $result = $foo->getETagForEntry($object, $type);
        $this->assertEquals("'hammer','time!'", $result);
    }

    public function testCompareETagNonExistentResourceThrowException()
    {
        $type = m::mock(ResourceType::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();
        $type = m::mock(ResourceType::class);

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null);

        $expected = 'The resource targeted by the request does not exists, eTag header is not allowed'
                    .' for non-existing resource.';
        $actual = null;
        $needtoSerialise = false;
        $object = null;

        try {
            $foo->compareETag($object, $type, $needtoSerialise);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCompareETagNonExistentResourceReturnNull()
    {
        $type = m::mock(ResourceType::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn(null);
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn(null);
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();
        $type = m::mock(ResourceType::class);

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null);

        $needtoSerialise = false;
        $object = null;

        $result = $foo->compareETag($object, $type, $needtoSerialise);
        $this->assertNull($result);
    }

    public function testCompareETagPropertyHeaderMismatchThrowException()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasETagProperties')->andReturn(false);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $expected = 'If-Match or If-None-Match headers cannot be specified if the target type does not'
                    .' have etag properties defined.';
        $actual = null;
        $needtoSerialise = false;
        $object = 'abc';

        try {
            $foo->compareETag($object, $type, $needtoSerialise);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCompareETagPropertyHeaderMismatchReturnNull()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasETagProperties')->andReturn(false);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn(null);
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn(null);
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = 'abc';

        $result = $foo->compareETag($object, $type, $needtoSerialise);
        $this->assertNull($result);
        $this->assertTrue($needtoSerialise);
    }

    public function testCompareETagPropertyNotValidatingReturnNull()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasETagProperties')->andReturn(false);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(false);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn(null);
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn(null);
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = 'abc';

        $result = $foo->compareETag($object, $type, $needtoSerialise);
        $this->assertNull($result);
        $this->assertTrue($needtoSerialise);
    }

    public function testCompareETagPropertyValidateEtagHeadersNoRequestMatching()
    {
        $itype = new StringType();
        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getInstanceType')->andReturn($itype);
        $resProp->shouldReceive('getName')->andReturn('type');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasETagProperties')->andReturn(true);
        $type->shouldReceive('getETagProperties')->andReturn([$resProp]);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn(null);
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn(null);
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2("foo", "bar");

        $expected = 'W/"\'bar\'"';

        $result = $foo->compareETag($object, $type, $needtoSerialise);
        $this->assertEquals($expected, $result);
        $this->assertTrue($needtoSerialise);
    }

    public function testCompareETagPropertyValidateEtagHeadersIfNoneMatchAll()
    {
        $itype = new StringType();
        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getInstanceType')->andReturn($itype);
        $resProp->shouldReceive('getName')->andReturn('type');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasETagProperties')->andReturn(true);
        $type->shouldReceive('getETagProperties')->andReturn([$resProp]);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn(null);
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('*');
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2("foo", "bar");

        $expected = 'W/"\'bar\'"';

        $result = $foo->compareETag($object, $type, $needtoSerialise);
        $this->assertEquals($expected, $result);
        $this->assertFalse($needtoSerialise);
    }

    public function testCompareETagPropertyValidateEtagHeadersIfNoneMatchSome()
    {
        $itype = new StringType();
        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getInstanceType')->andReturn($itype);
        $resProp->shouldReceive('getName')->andReturn('type');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasETagProperties')->andReturn(true);
        $type->shouldReceive('getETagProperties')->andReturn([$resProp]);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn(null);
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('abc');
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2("foo", "bar");

        $expected = 'W/"\'bar\'"';

        $result = $foo->compareETag($object, $type, $needtoSerialise);
        $this->assertEquals($expected, $result);
        $this->assertTrue($needtoSerialise);
    }

    public function testCompareETagPropertyValidateEtagHeadersIfNoneMatchEtag()
    {
        $itype = new StringType();
        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getInstanceType')->andReturn($itype);
        $resProp->shouldReceive('getName')->andReturn('type');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasETagProperties')->andReturn(true);
        $type->shouldReceive('getETagProperties')->andReturn([$resProp]);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn(null);
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('W/"\'bar\'"');
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2("foo", "bar");

        $expected = 'W/"\'bar\'"';

        $result = $foo->compareETag($object, $type, $needtoSerialise);
        $this->assertEquals($expected, $result);
        $this->assertFalse($needtoSerialise);
    }

    public function testCompareETagPropertyValidateEtagHeadersPreconditionFailure()
    {
        $itype = new StringType();
        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getInstanceType')->andReturn($itype);
        $resProp->shouldReceive('getName')->andReturn('type');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('hasETagProperties')->andReturn(true);
        $type->shouldReceive('getETagProperties')->andReturn([$resProp]);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('abc');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('W/"\'bar\'"');
        $cereal = m::mock(IObjectSerialiser::class);

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2("foo", "bar");

        $expected = 'W/"\'bar\'"';

        $expected = 'The etag value in the request header does not match with the current etag value of the object.';
        $actual = null;

        try {
            $foo->compareETag($object, $type, $needtoSerialise);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetStreamProvider()
    {
        $foo = m::mock(SimpleDataService::class)->makePartial();

        $result = $foo->getStreamProvider();
        $this->assertTrue($result instanceof StreamProviderWrapper);
    }
}