<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\ObjectModel\ODataEntry;
use POData\OperationContext\Web\IncomingRequest;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use POData\Providers\Metadata\Type\TypeCode;
use POData\Readers\Atom\AtomODataReader;
use POData\Readers\ODataReaderRegistry;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use UnitTests\POData\TestCase;

class RequestDescriptionMockeryTest extends TestCase
{
    /**
     * @throws ODataException
     */
    public function testProcessDataStandaloneRequest()
    {
        $raw = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<entry xml:base="http://localhost/foobar/odata.svc" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
    <content type="application/xml">
        <m:properties>
            <d:Street m:type="Edm.String"> 15 Woop Woop Drive </d:Street>
            <d:Suburb m:type="Edm.String"> Downtown Woop Woop </d:Suburb>
            <d:State m:type="Edm.String">NSW</d:State>
            <d:Postcode m:type="Edm.String">2998</d:Postcode>
            <d:Country m:type="Edm.String" m:null="true"/>
        </m:properties>
    </content>
</entry>';

        /** @var ODataEntry $expectedArray */
        $expectedArray = unserialize(base64_decode('TzoyOToiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhRW50cnkiOjE1OntzOjg6ImVkaXRMaW5rIjtOO3M6NDoidHlwZSI7TjtzOjE1OiJwcm9wZXJ0eUNvbnRlbnQiO086Mzk6IlBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5Q29udGVudCI6MTp7czo1MToiAFBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5Q29udGVudABwcm9wZXJ0aWVzIjthOjU6e3M6NjoiU3RyZWV0IjtPOjMyOiJQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eSI6NDp7czozODoiAFBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5AG5hbWUiO3M6NjoiU3RyZWV0IjtzOjQyOiIAUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkAdHlwZU5hbWUiO3M6MTA6IkVkbS5TdHJpbmciO3M6Mzk6IgBQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eQB2YWx1ZSI7czoyMDoiIDE1IFdvb3AgV29vcCBEcml2ZSAiO3M6MTk6ImF0dHJpYnV0ZUV4dGVuc2lvbnMiO2E6MDp7fX1zOjY6IlN1YnVyYiI7TzozMjoiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkiOjQ6e3M6Mzg6IgBQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eQBuYW1lIjtzOjY6IlN1YnVyYiI7czo0MjoiAFBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5AHR5cGVOYW1lIjtzOjEwOiJFZG0uU3RyaW5nIjtzOjM5OiIAUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkAdmFsdWUiO3M6MjA6IiBEb3dudG93biBXb29wIFdvb3AgIjtzOjE5OiJhdHRyaWJ1dGVFeHRlbnNpb25zIjthOjA6e319czo1OiJTdGF0ZSI7TzozMjoiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkiOjQ6e3M6Mzg6IgBQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eQBuYW1lIjtzOjU6IlN0YXRlIjtzOjQyOiIAUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkAdHlwZU5hbWUiO3M6MTA6IkVkbS5TdHJpbmciO3M6Mzk6IgBQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eQB2YWx1ZSI7czozOiJOU1ciO3M6MTk6ImF0dHJpYnV0ZUV4dGVuc2lvbnMiO2E6MDp7fX1zOjg6IlBvc3Rjb2RlIjtPOjMyOiJQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eSI6NDp7czozODoiAFBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5AG5hbWUiO3M6ODoiUG9zdGNvZGUiO3M6NDI6IgBQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eQB0eXBlTmFtZSI7czoxMDoiRWRtLlN0cmluZyI7czozOToiAFBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5AHZhbHVlIjtzOjQ6IjI5OTgiO3M6MTk6ImF0dHJpYnV0ZUV4dGVuc2lvbnMiO2E6MDp7fX1zOjc6IkNvdW50cnkiO086MzI6IlBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5Ijo0OntzOjM4OiIAUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkAbmFtZSI7czo3OiJDb3VudHJ5IjtzOjQyOiIAUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkAdHlwZU5hbWUiO3M6MTA6IkVkbS5TdHJpbmciO3M6Mzk6IgBQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eQB2YWx1ZSI7czowOiIiO3M6MTk6ImF0dHJpYnV0ZUV4dGVuc2lvbnMiO2E6MDp7fX19fXM6MTA6Im1lZGlhTGlua3MiO2E6MDp7fXM6OToibWVkaWFMaW5rIjtOO3M6NToibGlua3MiO2E6MDp7fXM6NDoiZVRhZyI7TjtzOjE2OiJpc01lZGlhTGlua0VudHJ5IjtiOjA7czoxNToicmVzb3VyY2VTZXROYW1lIjtOO3M6MjoiaWQiO047czo1OiJ0aXRsZSI7TjtzOjQ3OiIAUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhQ29udGFpbmVyQmFzZQBzZWxmTGluayI7TjtzOjc6InVwZGF0ZWQiO047czo3OiJiYXNlVVJJIjtOO3M6ODoic2VsZkxpbmsiO047fQ=='));

        $url = m::mock(Url::class);
        $url->shouldReceive('getUrlAsString')->andReturn('http://localhost/foobar/odata.svc/Addresses');
        $version = Version::v3();

        $segment  = m::mock(SegmentDescriptor::class);
        $segArray = [$segment];

        /** @var m\Mock|IncomingRequest $request */
        $request = m::mock(IncomingRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn($raw)->atLeast(1);

        $type           = MimeTypes::MIME_APPLICATION_ATOM;
        $readerRegistry = new ODataReaderRegistry();
        $readerRegistry->register(new AtomODataReader());
        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request, $readerRegistry);

        $data              = $desc->getData();

        $this->assertEquals($expectedArray, $data);
    }

    public function testGetResourceStreamInfo()
    {
        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url     = m::mock(Url::class);
        $version = Version::v3();

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $request = m::mock(IncomingRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn(null)->atLeast(1);
        $readerRegistry = new ODataReaderRegistry();
        $readerRegistry->register(new AtomODataReader());
        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request, $readerRegistry);

        $expected = 'Delta';
        $info     = $desc->getResourceStreamInfo();
        $this->assertTrue(isset($info));
        $actual = $info->getName();
        $this->assertEquals($expected, $actual);
    }

    public function testSetExecutionAttribute()
    {
        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url     = m::mock(Url::class);
        $version = Version::v3();

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $request = m::mock(IncomingRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn(null)->atLeast(1);
        $readerRegistry = new ODataReaderRegistry();
        $readerRegistry->register(new AtomODataReader());
        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request, $readerRegistry);

        $this->assertTrue($desc->needExecution());
        $desc->setExecuted();
        $this->assertFalse($desc->needExecution());
    }

    public function testRequestVersionWithTwoDots()
    {
        $expected = 'The header DataServiceVersion has malformed version value 0.1.1';
        $actual   = null;

        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url     = m::mock(Url::class);
        $version = Version::v3();

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $request = m::mock(IncomingRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn(null)->atLeast(1);

        $requestVersion = '0.1.1';

        try {
            $desc = new RequestDescription($segArray, $url, $version, $requestVersion, null, $type, $request);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testRequestVersionNonNumeric()
    {
        $expected = 'The header DataServiceVersion has malformed version value slash.dot';
        $actual   = null;

        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url     = m::mock(Url::class);
        $version = Version::v3();

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $request = m::mock(IncomingRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn(null)->atLeast(1);

        $requestVersion = 'slash.dot';

        try {
            $desc = new RequestDescription($segArray, $url, $version, $requestVersion, null, $type, $request);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testRequestVersionStartsWithDot()
    {
        $expected = 'The header DataServiceVersion has malformed version value .1';
        $actual   = null;

        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url     = m::mock(Url::class);
        $version = Version::v3();

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $request = m::mock(IncomingRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn(null)->atLeast(1);

        $requestVersion = '.1';

        try {
            $desc = new RequestDescription($segArray, $url, $version, $requestVersion, null, $type, $request);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testRequestVersionNumericButUnsupported()
    {
        $expected = 'The version value 0.1 in the header DataServiceVersion is not supported, available'
                    . ' versions are 1.0, 2.0, 3.0';
        $actual = null;

        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url     = m::mock(Url::class);
        $version = Version::v3();

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $request = m::mock(IncomingRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn(null)->atLeast(1);

        $requestVersion = '0.1';

        try {
            $desc = new RequestDescription($segArray, $url, $version, $requestVersion, null, $type, $request);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }
}
