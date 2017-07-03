<?php

namespace UnitTests\POData;

use Mockery as m;
use phpDocumentor\Reflection\Types\Resource;
use POData\Common\HttpStatus;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\ObjectModel\IObjectSerialiser;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Stream\IStreamProvider2;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\SimpleDataService;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessor;
use POData\Writers\ODataWriterRegistry;
use UnitTests\POData\ObjectModel\reusableEntityClass2;
use UnitTests\POData\ObjectModel\reusableEntityClass3;

class BaseServiceNewTest extends TestCase
{
    public function testRattleStubMethods()
    {
        $db = m::mock(IQueryProvider::class);
        $host = m::mock(ServiceHost::class)->makePartial();
        $cereal = $this->spinUpMockSerialiser();
        $wrap = m::mock(StreamProviderWrapper::class)->makePartial();

        $foo = new BaseServiceDummy($db, $host, $cereal, $wrap, null);

        $foo->handleRequest2();
        $foo->delegateRequestProcessing();
        $foo->serializeResultForResponseBody();
        $foo->handlePOSTOperation();
        $foo->handlePUTOperation();
        $foo->handleDELETEOperation();

        $cereal = $foo->getObjectSerialiser();
        $this->assertTrue($cereal instanceof IObjectSerialiser);
        $rebar = $foo->getStreamProviderWrapper();
        $this->assertTrue($rebar instanceof StreamProviderWrapper);
    }

    public function testGetResultWithNullMetadataProviderThrowException()
    {
        $db = m::mock(IQueryProvider::class);
        $host = m::mock(ServiceHost::class)->makePartial();
        $cereal = $this->spinUpMockSerialiser();
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
        $cereal = $this->spinUpMockSerialiser();
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

        $service = m::mock(BaseServiceDummy::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'Unsupported media type requested.';
        $actual = null;

        try {
            $service->getResponseContentType($request, $proc);
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

        $service = m::mock(BaseServiceDummy::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'assert(): is_null($projectedProperty) failed';
        $actual = null;

        try {
            $result = $service->getResponseContentType($request, $proc);
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

        $service = m::mock(BaseServiceDummy::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'assert(): !$type instanceof IType failed';
        $actual = null;

        try {
            $result = $service->getResponseContentType($request, $proc);
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

        $service = m::mock(BaseServiceDummy::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'application/octet-stream';
        $result = $service->getResponseContentType($request, $proc);

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

        $service = m::mock(BaseServiceDummy::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host);

        $proc = m::mock(UriProcessor::class);

        $expected = 'The URI \'https://www.example.org/odata.svc\' is not valid. The segment before \'$value\' '
                    .'must be a Media Link Entry or a primitive property.';
        $actual = null;

        try {
            $service->getResponseContentType($request, $proc);
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
        $host->shouldReceive('getResponseContentType')->andReturn('application/xml')->times(2);
        $host->shouldReceive('getResponseETag')->andReturn('electric-rave')->times(2);

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

        $context = m::mock(IOperationContext::class)->makePartial();

        $provWrap = m::mock(StreamProviderWrapper::class)->makePartial();
        //$provWrap->shouldReceive('getStreamContentType2')->andReturnNull()->once();

        $stream = m::mock(IStreamProvider2::class);
        $stream->shouldReceive('getStreamContentType2')->andReturn('application/xml')->once();

        $service = m::mock(BaseServiceDummy::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getStreamProviderWrapper')->andReturn($provWrap);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getStreamProviderX')->andReturn($stream);
        $provWrap->setService($service);

        $proc = m::mock(UriProcessor::class);
        $proc->shouldReceive('execute')->andReturnNull()->once();

        $result = $service->getResponseContentType($request, $proc);
        $this->assertEquals('application/xml', $result);
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

        $context = m::mock(IOperationContext::class)->makePartial();

        $service = m::mock(BaseServiceDummy::class)->makePartial();
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getStreamProviderWrapper->getStreamContentType')
            ->andReturn(MimeTypes::MIME_TEXTXML)->once();
        $service->shouldReceive('getOperationContext')->andReturn($context);

        $proc = m::mock(UriProcessor::class);
        $proc->shouldReceive('execute')->andReturnNull()->once();

        $result = $service->getResponseContentType($request, $proc);
        $this->assertEquals('text/xml', $result);
    }

    public function testGetEtagForEntryNoProperties()
    {
        $host = m::mock(ServiceHost::class);
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

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
        $cereal = $this->spinUpMockSerialiser();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2('foo', 'bar');

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
        $cereal = $this->spinUpMockSerialiser();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2('foo', 'bar');

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
        $cereal = $this->spinUpMockSerialiser();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2('foo', 'bar');

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
        $cereal = $this->spinUpMockSerialiser();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2('foo', 'bar');

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
        $cereal = $this->spinUpMockSerialiser();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $needtoSerialise = false;
        $object = new reusableEntityClass2('foo', 'bar');

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

    public function testSerializeResultETagNotSpecifiedThrowException()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);

        $cereal = $this->spinUpMockSerialiser();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(false);

        $uriProc = m::mock(UriProcessor::class);

        $expected = 'If-Match or If-None-Match HTTP headers cannot be specified since the'
                    .' URI \'https://www.example.org/odata.svc\' refers to a collection of resources or has'
                    .' a $count or $link segment or has a $expand as one of the query parameters.';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultNullContentTypeWhenNotMediaResourceThrowException()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->withAnyArgs()->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);

        $cereal = $this->spinUpMockSerialiser();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $bar = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);
        $foo = m::mock(BaseServiceDummy::class)->makePartial();
        $foo->shouldReceive('getConfiguration')->andReturn($config);
        $foo->shouldReceive('getResponseContentType')->andReturn(null);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::BAG());

        $uriProc = m::mock(UriProcessor::class);

        $expected = 'Unsupported media type requested.';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithRequestNotExecuted()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withArgs([HttpStatus::CODE_OK])->andReturnNull()->once();
        $host->shouldReceive('setResponseContentType')->withArgs(['application/xml;charset=utf-8'])
            ->andReturnNull()->once();
        $host->shouldReceive('setResponseVersion')->withArgs(['3.0;'])->andReturnNull()->once();
        $host->shouldReceive('setResponseCacheControl')
            ->withArgs([ODataConstants::HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE])->andReturnNull()->once();
        $host->shouldReceive('getOperationContext->outgoingResponse->setStream')
            ->withArgs(['ScatmanJohn'])->andReturnNull()->once();

        $cereal = $this->spinUpMockSerialiser();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::METADATA(), TargetKind::PRIMITIVE_VALUE());
        $request->shouldReceive('needExecution')->andReturn(false)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn('ScatmanJohn');

        $uriProc = m::mock(UriProcessor::class);

        $foo->serializeResult($request, $uriProc);
    }

    public function testSerializeResultWithDeleteRequestExecuted()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::DELETE())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withArgs([HttpStatus::CODE_OK])->andReturnNull()->once();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::METADATA(), TargetKind::PRIMITIVE_VALUE());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn('ScatmanJohn');

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $foo->serializeResult($request, $uriProc);
    }

    public function testSerializeResultWithNonDeleteRequestAndTripAssertion()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::METADATA(), TargetKind::PRIMITIVE_VALUE());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn('ScatmanJohn');
        $request->shouldReceive('getTargetResourceType')->andReturnNull()->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'assert(): Target resource type cannot be null failed';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithNonDeleteRequestAndNonSingleResultTripAssertion()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $queryResult = new QueryResult();
        $queryResult->results = 'ScatmanJohn';

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::METADATA(), TargetKind::PRIMITIVE_VALUE());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(false)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'assert(): !is_array($entryObjects->results) failed';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithNonDeleteRequestAndNonSingleResultIsLinkTripAssertion()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();
        $cereal->shouldReceive('writeUrlElements')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $queryResult = new QueryResult();
        $queryResult->results = ['ScatmanJohn'];

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::METADATA(), TargetKind::PRIMITIVE_VALUE());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(true);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(false)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'assert(): !$odataModelInstance instanceof ODataURLCollection failed';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithNonDeleteRequestAndNonSingleResultIsNotLinkTripAssertion()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();
        $cereal->shouldReceive('writeTopLevelElements')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $queryResult = new QueryResult();
        $queryResult->results = ['ScatmanJohn'];

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::METADATA(), TargetKind::PRIMITIVE_VALUE());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(false)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'assert(): !$odataModelInstance instanceof ODataFeed failed';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithNonDeleteRequestAndIsLinkSingleResultNotFoundTripAssertion()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);

        $property = m::mock(ResourceProperty::class);

        $queryResult = new QueryResult();
        $queryResult->results = null;

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(null);
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(true);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(true)->once();
        $request->shouldReceive('getIdentifier')->andReturn('FNORD')->once();
        $request->shouldReceive('getProjectedProperty')->andReturn($property)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'Resource not found for the segment \'FNORD\'.';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithNonDeleteRequestAndIsLinkSingleResultIsFoundNoWriterThrowException()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();
        $cereal->shouldReceive('writeUrlElement')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $rego = m::mock(ODataWriterRegistry::class);
        $rego->shouldReceive('getWriter')->withAnyArgs()->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);
        $foo->setODataWriterRegistry($rego);

        $property = m::mock(ResourceProperty::class);

        $queryResult = new QueryResult();
        $queryResult->results = 'ad astra per fnordua';

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(null);
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(true);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(true)->once();
        $request->shouldReceive('getProjectedProperty')->andReturn($property)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'No writer can handle the request.';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithNonDeleteRequestAndIsNotLinkSingleResultNullTypeTripAssertion()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $rego = m::mock(ODataWriterRegistry::class);

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);
        $foo->setODataWriterRegistry($rego);

        $property = m::mock(ResourceProperty::class);

        $queryResult = new QueryResult();
        $queryResult->results = 'ad astra per fnordua';

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::METADATA(), null);
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(true)->once();
        $request->shouldReceive('setExecuted')->andReturnNull()->never();
        $request->shouldReceive('getProjectedProperty')->andReturn($property)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'assert(): Unexpected resource target kind failed';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerialiseResultWithPutRequestAndPrimitiveTypeWithNullResourcePropertyAndNoWriter()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();
        $cereal->shouldReceive('writeTopLevelComplexObject')->andReturnNull()->never();
        $cereal->shouldReceive('writeTopLevelPrimitive')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $rego = m::mock(ODataWriterRegistry::class);
        $rego->shouldReceive('getWriter')->withAnyArgs()->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);
        $foo->setODataWriterRegistry($rego);

        $queryResult = new QueryResult();
        $queryResult->results = 'ad astra per fnordua';

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::PRIMITIVE());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(true)->once();
        $request->shouldReceive('getProjectedProperty')->andReturn(null)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'No writer can handle the request.';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerialiseResultWithPutRequestAndBagTypeWithNullResourcePropertyTripAssertion()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();
        $cereal->shouldReceive('writeTopLevelComplexObject')->andReturnNull()->never();
        $cereal->shouldReceive('writeTopLevelPrimitive')->andReturnNull()->never();
        $cereal->shouldReceive('writeTopLevelBagObject')->andReturnNull()->never();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $rego = m::mock(ODataWriterRegistry::class);
        $rego->shouldReceive('getWriter')->withAnyArgs()->andReturnNull()->never();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);
        $foo->setODataWriterRegistry($rego);

        $queryResult = new QueryResult();
        $queryResult->results = 'ad astra per fnordua';

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::BAG());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(true)->once();
        $request->shouldReceive('getProjectedProperty')->andReturn(null)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'assert(): Projected request property cannot be null failed';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }


    public function testSerializeResultWithPutRequestAndIsLinkComplexObjectNoWriterThrowException()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();
        $cereal->shouldReceive('writeTopLevelComplexObject')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $rego = m::mock(ODataWriterRegistry::class);
        $rego->shouldReceive('getWriter')->withAnyArgs()->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);
        $foo->setODataWriterRegistry($rego);

        $queryResult = new QueryResult();
        $queryResult->results = 'ad astra per fnordua';

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::COMPLEX_OBJECT());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(true)->once();
        $request->shouldReceive('getProjectedProperty->getName')->andReturn('name')->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'No writer can handle the request.';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithPutRequestAndIsNotLinkBagNoWriterThrowException()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();
        $cereal->shouldReceive('writeTopLevelBagObject')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $rego = m::mock(ODataWriterRegistry::class);
        $rego->shouldReceive('getWriter')->withAnyArgs()->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);
        $foo->setODataWriterRegistry($rego);

        $queryResult = new QueryResult();
        $queryResult->results = 'ad astra per fnordua';

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::BAG());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(true)->once();
        $request->shouldReceive('getProjectedProperty->getName')->andReturn('name')->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'No writer can handle the request.';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithPutRequestAndIsNotLinkPrimitiveNoWriterThrowException()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $prop = m::mock(ResourceProperty::class);

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();
        $cereal->shouldReceive('writeTopLevelPrimitive')->andReturnNull()->once();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $rego = m::mock(ODataWriterRegistry::class);
        $rego->shouldReceive('getWriter')->withAnyArgs()->andReturnNull()->once();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);
        $foo->setODataWriterRegistry($rego);

        $queryResult = new QueryResult();
        $queryResult->results = 'ad astra per fnordua';

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::PRIMITIVE());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(true)->once();
        $request->shouldReceive('getProjectedProperty')->andReturn($prop)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'No writer can handle the request.';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSerializeResultWithPutRequestAndIsNotLinkResourceIfAndIfNoneSetThrowException()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $prop = m::mock(ResourceProperty::class);

        $type = m::mock(ResourceType::class);

        $req = m::mock(IHTTPRequest::class);
        $req->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::PUT())->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getValidateETagHeader')->andReturn(true);

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('outgoingResponse->setStream')->withAnyArgs()->andReturnNull()->never();
        $context->shouldReceive('incomingRequest')->andReturn($req);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('a');
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('b');
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($url);
        $host->shouldReceive('getRequestAccept')->andReturn(null);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        $host->shouldReceive('setResponseStatusCode')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseContentType')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseVersion')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('setResponseCacheControl')->withAnyArgs()->andReturnNull()->never();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $cereal = $this->spinUpMockSerialiser();
        $cereal->shouldReceive('setRequest')->andReturnNull()->once();
        $cereal->shouldReceive('writeTopLevelElement')->andReturnNull()->never();

        $stream = m::mock(StreamProviderWrapper::class);
        $stream->shouldReceive('setService')->andReturnNull()->once();

        $rego = m::mock(ODataWriterRegistry::class);
        $rego->shouldReceive('getWriter')->withAnyArgs()->andReturnNull()->never();

        $foo = new BaseServiceDummy(null, $host, $cereal, $stream, null, $config);
        $foo->setODataWriterRegistry($rego);

        $queryResult = new QueryResult();
        $queryResult->results = 'ad astra per fnordua';

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('isETagHeaderAllowed')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());
        $request->shouldReceive('needExecution')->andReturn(true)->once();
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3());
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetResult')->andReturn($queryResult);
        $request->shouldReceive('getTargetResourceType')->andReturn($type)->once();
        $request->shouldReceive('isSingleResult')->andReturn(true)->once();
        $request->shouldReceive('getProjectedProperty')->andReturn($prop)->once();

        $uriProc = m::mock(UriProcessor::class);
        $uriProc->shouldReceive('execute')->andReturnNull()->once();

        $expected = 'Both If-Match and If-None-Match HTTP headers cannot be specified at the same time.'
                    .' Please specify either one of the headers or none of them.';
        $actual = null;

        try {
            $foo->serializeResult($request, $uriProc);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResponseTypeForMetadata()
    {
        $db = m::mock(IQueryProvider::class);
        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getRequestAccept')->andReturn('application/xml, application/atomsvc+xml');
        $host->shouldReceive('getQueryStringItem')->andReturn(null)->once();
        $cereal = $this->spinUpMockSerialiser();
        $wrap = m::mock(StreamProviderWrapper::class)->makePartial();

        $foo = new BaseServiceDummy($db, $host, $cereal, $wrap, null);

        $uriProc = m::mock(UriProcessor::class)->makePartial();
        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3())->once();
        $request->shouldReceive('isLinkUri')->andReturn(false)->once();
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::METADATA());

        $expected = 'application/xml';
        $actual = $foo->getResponseContentType($request, $uriProc);
        $this->assertEquals($expected, $actual);
    }

    public function testGetResponseTypeForServiceDirectory()
    {
        $db = m::mock(IQueryProvider::class);
        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getRequestAccept')->andReturn('application/xml, application/atomsvc+xml');
        $host->shouldReceive('getQueryStringItem')->andReturn(null)->once();
        $cereal = $this->spinUpMockSerialiser();
        $wrap = m::mock(StreamProviderWrapper::class)->makePartial();

        $foo = new BaseServiceDummy($db, $host, $cereal, $wrap, null);

        $uriProc = m::mock(UriProcessor::class)->makePartial();
        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getResponseVersion')->andReturn(Version::v3())->once();
        $request->shouldReceive('isLinkUri')->andReturn(false)->once();
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::SERVICE_DIRECTORY());

        $expected = 'application/atomsvc+xml';
        $actual = $foo->getResponseContentType($request, $uriProc);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return m\MockInterface
     */
    private function spinUpMockSerialiser()
    {
        $cereal = m::mock(IObjectSerialiser::class);
        $cereal->shouldReceive('setService')->withAnyArgs()->andReturnNull();
        return $cereal;
    }
}
