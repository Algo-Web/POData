<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\OperationContext\Web\Illuminate\IncomingIlluminateRequest;
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
     * @throws \Doctrine\Common\Annotations\AnnotationException
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

        $expectedArray = unserialize(base64_decode('TzoyOToiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhRW50cnkiOjE2OntzOjI6ImlkIjtOO3M6ODoic2VsZkxpbmsiO047czo1OiJ0aXRsZSI7TjtzOjg6ImVkaXRMaW5rIjtOO3M6NDoidHlwZSI7TjtzOjE1OiJwcm9wZXJ0eUNvbnRlbnQiO086Mzk6IlBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5Q29udGVudCI6MTp7czoxMDoicHJvcGVydGllcyI7YTo1OntzOjY6IlN0cmVldCI7TzozMjoiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkiOjQ6e3M6NDoibmFtZSI7czo2OiJTdHJlZXQiO3M6ODoidHlwZU5hbWUiO3M6MTA6IkVkbS5TdHJpbmciO3M6MTk6ImF0dHJpYnV0ZUV4dGVuc2lvbnMiO047czo1OiJ2YWx1ZSI7czoyMDoiIDE1IFdvb3AgV29vcCBEcml2ZSAiO31zOjY6IlN1YnVyYiI7TzozMjoiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkiOjQ6e3M6NDoibmFtZSI7czo2OiJTdWJ1cmIiO3M6ODoidHlwZU5hbWUiO3M6MTA6IkVkbS5TdHJpbmciO3M6MTk6ImF0dHJpYnV0ZUV4dGVuc2lvbnMiO047czo1OiJ2YWx1ZSI7czoyMDoiIERvd250b3duIFdvb3AgV29vcCAiO31zOjU6IlN0YXRlIjtPOjMyOiJQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eSI6NDp7czo0OiJuYW1lIjtzOjU6IlN0YXRlIjtzOjg6InR5cGVOYW1lIjtzOjEwOiJFZG0uU3RyaW5nIjtzOjE5OiJhdHRyaWJ1dGVFeHRlbnNpb25zIjtOO3M6NToidmFsdWUiO3M6MzoiTlNXIjt9czo4OiJQb3N0Y29kZSI7TzozMjoiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkiOjQ6e3M6NDoibmFtZSI7czo4OiJQb3N0Y29kZSI7czo4OiJ0eXBlTmFtZSI7czoxMDoiRWRtLlN0cmluZyI7czoxOToiYXR0cmlidXRlRXh0ZW5zaW9ucyI7TjtzOjU6InZhbHVlIjtzOjQ6IjI5OTgiO31zOjc6IkNvdW50cnkiO086MzI6IlBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5Ijo0OntzOjQ6Im5hbWUiO3M6NzoiQ291bnRyeSI7czo4OiJ0eXBlTmFtZSI7czoxMDoiRWRtLlN0cmluZyI7czoxOToiYXR0cmlidXRlRXh0ZW5zaW9ucyI7TjtzOjU6InZhbHVlIjtzOjA6IiI7fX19czoxMDoibWVkaWFMaW5rcyI7YTowOnt9czo5OiJtZWRpYUxpbmsiO047czo1OiJsaW5rcyI7YTowOnt9czo0OiJlVGFnIjtOO3M6MTY6ImlzTWVkaWFMaW5rRW50cnkiO2I6MDtzOjE1OiJyZXNvdXJjZVNldE5hbWUiO047czo3OiJ1cGRhdGVkIjtOO3M6NzoiYmFzZVVSSSI7TjtzOjExOiJhdG9tQ29udGVudCI7TjtzOjEwOiJhdG9tQXV0aG9yIjtOO30'));

        $url = m::mock(Url::class);
        $url->shouldReceive('getUrlAsString')->andReturn('http://localhost/foobar/odata.svc/Addresses');
        $version = Version::v3();

        $segment  = m::mock(SegmentDescriptor::class);
        $segArray = [$segment];

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn([$raw])->atLeast(1);

        $type           = MimeTypes::MIME_APPLICATION_ATOM;
        $readerRegistry = new ODataReaderRegistry();
        $readerRegistry->register(new AtomODataReader());
        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request, $readerRegistry);

        $data = $desc->getData();
        $this->assertEquals($expectedArray, $data);
    }

    public function testProcessDataFromClientEndpointRequest()
    {
        $bar      = new SimpleMetadataProvider('Data', 'Data');
        $refClass = m::mock(\ReflectionClass::class);
        $refClass->shouldReceive('hasMethod')->withArgs(['__get'])->andReturn(true);
        $refClass->shouldReceive('isInstance')->andReturn(true);
        $resourceType = $bar->addEntityType($refClass, 'CompanyConfigModel');
        $bar->addKeyProperty($resourceType, 'id', EdmPrimitiveType::INT32());

        $raw = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
<category term="Data.CompanyConfigModel" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><id /><title />
<updated>2017-06-15T04:44:40Z</updated><author><name /></author>
<content type="application/xml">
<m:properties><d:company_id>111111</d:company_id><d:configKey>CompanyMainDashboard</d:configKey><d:created_at m:type="Edm.DateTime">0001-01-01T00:00:00</d:created_at><d:id m:type="Edm.Int32">0</d:id><d:updated_at m:type="Edm.DateTime">0001-01-01T00:00:00</d:updated_at>
<d:value>&lt;Dashboard CurrencyCulture="en-AU"&gt;&#xD;
  &lt;Title Text="Dashboard" /&gt;&#xD;
  &lt;DataSources&gt;&#xD;
    &lt;ObjectDataSource ComponentName="dashboardObjectDataSource1"&gt;&#xD;
      &lt;Name&gt;Foo Bar&lt;/Name&gt;&#xD;
      &lt;DataSource Type="System.Data.Services.Client.DataServiceQuery`1+DataServiceOrderedQuery[[FooBar.FooBarRemoteWCF.Address, FooBar, Version=1.0.0.0, Culture=neutral, PublicKeyToken=null]], Microsoft.Data.Services.Client, Version=5.6.4.0, Culture=neutral, PublicKeyToken=31bf3856ad364e35" /&gt;&#xD;
    &lt;/ObjectDataSource&gt;&#xD;
  &lt;/DataSources&gt;&#xD;
&lt;/Dashboard&gt;</d:value></m:properties></content></entry>
';

        $expectedArray                  = unserialize(base64_decode('TzoyOToiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhRW50cnkiOjE2OntzOjI6ImlkIjtzOjA6IiI7czo4OiJzZWxmTGluayI7TjtzOjU6InRpdGxlIjtPOjI5OiJQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFUaXRsZSI6Mjp7czo1OiJ0aXRsZSI7czowOiIiO3M6NDoidHlwZSI7Tjt9czo4OiJlZGl0TGluayI7TjtzOjQ6InR5cGUiO086MzI6IlBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YUNhdGVnb3J5IjoyOntzOjQ6InRlcm0iO3M6MjM6IkRhdGEuQ29tcGFueUNvbmZpZ01vZGVsIjtzOjY6InNjaGVtZSI7czo2MDoiaHR0cDovL3NjaGVtYXMubWljcm9zb2Z0LmNvbS9hZG8vMjAwNy8wOC9kYXRhc2VydmljZXMvc2NoZW1lIjt9czoxNToicHJvcGVydHlDb250ZW50IjtPOjM5OiJQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eUNvbnRlbnQiOjE6e3M6MTA6InByb3BlcnRpZXMiO2E6Njp7czoxMDoiY29tcGFueV9pZCI7TzozMjoiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkiOjQ6e3M6NDoibmFtZSI7czoxMDoiY29tcGFueV9pZCI7czo4OiJ0eXBlTmFtZSI7TjtzOjE5OiJhdHRyaWJ1dGVFeHRlbnNpb25zIjtOO3M6NToidmFsdWUiO3M6NjoiMTExMTExIjt9czo5OiJjb25maWdLZXkiO086MzI6IlBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5Ijo0OntzOjQ6Im5hbWUiO3M6OToiY29uZmlnS2V5IjtzOjg6InR5cGVOYW1lIjtOO3M6MTk6ImF0dHJpYnV0ZUV4dGVuc2lvbnMiO047czo1OiJ2YWx1ZSI7czoyMDoiQ29tcGFueU1haW5EYXNoYm9hcmQiO31zOjEwOiJjcmVhdGVkX2F0IjtPOjMyOiJQT0RhdGFcT2JqZWN0TW9kZWxcT0RhdGFQcm9wZXJ0eSI6NDp7czo0OiJuYW1lIjtzOjEwOiJjcmVhdGVkX2F0IjtzOjg6InR5cGVOYW1lIjtzOjEyOiJFZG0uRGF0ZVRpbWUiO3M6MTk6ImF0dHJpYnV0ZUV4dGVuc2lvbnMiO047czo1OiJ2YWx1ZSI7czoxOToiMDAwMS0wMS0wMVQwMDowMDowMCI7fXM6MjoiaWQiO086MzI6IlBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5Ijo0OntzOjQ6Im5hbWUiO3M6MjoiaWQiO3M6ODoidHlwZU5hbWUiO3M6OToiRWRtLkludDMyIjtzOjE5OiJhdHRyaWJ1dGVFeHRlbnNpb25zIjtOO3M6NToidmFsdWUiO3M6MToiMCI7fXM6MTA6InVwZGF0ZWRfYXQiO086MzI6IlBPRGF0YVxPYmplY3RNb2RlbFxPRGF0YVByb3BlcnR5Ijo0OntzOjQ6Im5hbWUiO3M6MTA6InVwZGF0ZWRfYXQiO3M6ODoidHlwZU5hbWUiO3M6MTI6IkVkbS5EYXRlVGltZSI7czoxOToiYXR0cmlidXRlRXh0ZW5zaW9ucyI7TjtzOjU6InZhbHVlIjtzOjE5OiIwMDAxLTAxLTAxVDAwOjAwOjAwIjt9czo1OiJ2YWx1ZSI7TzozMjoiUE9EYXRhXE9iamVjdE1vZGVsXE9EYXRhUHJvcGVydHkiOjQ6e3M6NDoibmFtZSI7czo1OiJ2YWx1ZSI7czo4OiJ0eXBlTmFtZSI7TjtzOjE5OiJhdHRyaWJ1dGVFeHRlbnNpb25zIjtOO3M6NToidmFsdWUiO3M6NTMwOiI8RGFzaGJvYXJkIEN1cnJlbmN5Q3VsdHVyZT0iZW4tQVUiPg0KICA8VGl0bGUgVGV4dD0iRGFzaGJvYXJkIiAvPg0KICA8RGF0YVNvdXJjZXM+DQogICAgPE9iamVjdERhdGFTb3VyY2UgQ29tcG9uZW50TmFtZT0iZGFzaGJvYXJkT2JqZWN0RGF0YVNvdXJjZTEiPg0KICAgICAgPE5hbWU+Rm9vIEJhcjwvTmFtZT4NCiAgICAgIDxEYXRhU291cmNlIFR5cGU9IlN5c3RlbS5EYXRhLlNlcnZpY2VzLkNsaWVudC5EYXRhU2VydmljZVF1ZXJ5YDErRGF0YVNlcnZpY2VPcmRlcmVkUXVlcnlbW0Zvb0Jhci5Gb29CYXJSZW1vdGVXQ0YuQWRkcmVzcywgRm9vQmFyLCBWZXJzaW9uPTEuMC4wLjAsIEN1bHR1cmU9bmV1dHJhbCwgUHVibGljS2V5VG9rZW49bnVsbF1dLCBNaWNyb3NvZnQuRGF0YS5TZXJ2aWNlcy5DbGllbnQsIFZlcnNpb249NS42LjQuMCwgQ3VsdHVyZT1uZXV0cmFsLCBQdWJsaWNLZXlUb2tlbj0zMWJmMzg1NmFkMzY0ZTM1IiAvPg0KICAgIDwvT2JqZWN0RGF0YVNvdXJjZT4NCiAgPC9EYXRhU291cmNlcz4NCjwvRGFzaGJvYXJkPiI7fX19czoxMDoibWVkaWFMaW5rcyI7YTowOnt9czo5OiJtZWRpYUxpbmsiO047czo1OiJsaW5rcyI7YTowOnt9czo0OiJlVGFnIjtOO3M6MTY6ImlzTWVkaWFMaW5rRW50cnkiO2I6MDtzOjE1OiJyZXNvdXJjZVNldE5hbWUiO047czo3OiJ1cGRhdGVkIjtzOjIwOiIyMDE3LTA2LTE1VDA0OjQ0OjQwWiI7czo3OiJiYXNlVVJJIjtOO3M6MTE6ImF0b21Db250ZW50IjtOO3M6MTA6ImF0b21BdXRob3IiO047fQ=='));
        $expectedArray->resourceSetName = 'CompanyConfigModels';

        $url = m::mock(Url::class);
        $url->shouldReceive('getUrlAsString')->andReturn('http://localhost/foobar/odata.svc/CompanyConfigModels');
        $version = Version::v3();

        $segment  = m::mock(SegmentDescriptor::class);
        $segArray = [$segment];

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn($raw)->atLeast(1);

        $type           = MimeTypes::MIME_APPLICATION_ATOM;
        $readerRegistry = new ODataReaderRegistry();
        $readerRegistry->register(new AtomODataReader());
        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request, $readerRegistry);

        $data = $desc->getData();
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

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
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

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
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

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
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

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
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

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
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

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
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
