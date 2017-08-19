<?php

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\OperationContext\Web\Illuminate\IncomingIlluminateRequest;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use UnitTests\POData\TestCase;

class RequestDescriptionMockeryTest extends TestCase
{
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

        $expectedArray = unserialize('O:29:"POData\ObjectModel\ODataEntry":16:{s:2:"id";N;s:8:"selfLink";N;s:5:"title";N;s:8:"editLink";N;s:4:"type";N;s:15:"propertyContent";O:39:"POData\ObjectModel\ODataPropertyContent":1:{s:10:"properties";a:5:{s:6:"Street";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:6:"Street";s:8:"typeName";s:10:"Edm.String";s:19:"attributeExtensions";N;s:5:"value";s:20:" 15 Woop Woop Drive ";}s:6:"Suburb";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:6:"Suburb";s:8:"typeName";s:10:"Edm.String";s:19:"attributeExtensions";N;s:5:"value";s:20:" Downtown Woop Woop ";}s:5:"State";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:5:"State";s:8:"typeName";s:10:"Edm.String";s:19:"attributeExtensions";N;s:5:"value";s:3:"NSW";}s:8:"Postcode";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:8:"Postcode";s:8:"typeName";s:10:"Edm.String";s:19:"attributeExtensions";N;s:5:"value";s:4:"2998";}s:7:"Country";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:7:"Country";s:8:"typeName";s:10:"Edm.String";s:19:"attributeExtensions";N;s:5:"value";s:0:"";}}}s:10:"mediaLinks";a:0:{}s:9:"mediaLink";N;s:5:"links";a:0:{}s:4:"eTag";N;s:16:"isMediaLinkEntry";b:0;s:15:"resourceSetName";N;s:7:"updated";N;s:7:"baseURI";N;s:11:"atomContent";N;s:10:"atomAuthor";N;}');



        $url = m::mock(Url::class);
        $version = Version::v3();

        $segment = m::mock(SegmentDescriptor::class);
        $segArray = [$segment];

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn([$raw])->atLeast(1);

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request);

        $data = $desc->getData();
        $this->assertEquals($expectedArray, $data);
    }

    public function testProcessDataFromClientEndpointRequest()
    {
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

        $expectedArray = unserialize('O:29:"POData\ObjectModel\ODataEntry":16:{s:2:"id";s:0:"";s:8:"selfLink";N;s:5:"title";O:29:"POData\ObjectModel\ODataTitle":2:{s:5:"title";s:0:"";s:4:"type";N;}s:8:"editLink";N;s:4:"type";O:32:"POData\ObjectModel\ODataCategory":2:{s:4:"term";s:23:"Data.CompanyConfigModel";s:6:"scheme";s:60:"http://schemas.microsoft.com/ado/2007/08/dataservices/scheme";}s:15:"propertyContent";O:39:"POData\ObjectModel\ODataPropertyContent":1:{s:10:"properties";a:6:{s:10:"company_id";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:10:"company_id";s:8:"typeName";N;s:19:"attributeExtensions";N;s:5:"value";s:6:"111111";}s:9:"configKey";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:9:"configKey";s:8:"typeName";N;s:19:"attributeExtensions";N;s:5:"value";s:20:"CompanyMainDashboard";}s:10:"created_at";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:10:"created_at";s:8:"typeName";s:12:"Edm.DateTime";s:19:"attributeExtensions";N;s:5:"value";s:19:"0001-01-01T00:00:00";}s:2:"id";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:2:"id";s:8:"typeName";s:9:"Edm.Int32";s:19:"attributeExtensions";N;s:5:"value";s:1:"0";}s:10:"updated_at";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:10:"updated_at";s:8:"typeName";s:12:"Edm.DateTime";s:19:"attributeExtensions";N;s:5:"value";s:19:"0001-01-01T00:00:00";}s:5:"value";O:32:"POData\ObjectModel\ODataProperty":4:{s:4:"name";s:5:"value";s:8:"typeName";N;s:19:"attributeExtensions";N;s:5:"value";s:530:"<Dashboard CurrencyCulture="en-AU">
  <Title Text="Dashboard" />
  <DataSources>
    <ObjectDataSource ComponentName="dashboardObjectDataSource1">
      <Name>Foo Bar</Name>
      <DataSource Type="System.Data.Services.Client.DataServiceQuery`1+DataServiceOrderedQuery[[FooBar.FooBarRemoteWCF.Address, FooBar, Version=1.0.0.0, Culture=neutral, PublicKeyToken=null]], Microsoft.Data.Services.Client, Version=5.6.4.0, Culture=neutral, PublicKeyToken=31bf3856ad364e35" />
    </ObjectDataSource>
  </DataSources>
</Dashboard>";}}}s:10:"mediaLinks";a:0:{}s:9:"mediaLink";N;s:5:"links";a:0:{}s:4:"eTag";N;s:16:"isMediaLinkEntry";b:0;s:15:"resourceSetName";N;s:7:"updated";s:20:"2017-06-15T04:44:40Z";s:7:"baseURI";N;s:11:"atomContent";N;s:10:"atomAuthor";N;}');

        $url = m::mock(Url::class);
        $version = Version::v3();

        $segment = m::mock(SegmentDescriptor::class);
        $segArray = [$segment];

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn($raw)->atLeast(1);

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request);

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

        $url = m::mock(Url::class);
        $version = Version::v3();

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn(null)->atLeast(1);

        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request);

        $expected = 'Delta';
        $info = $desc->getResourceStreamInfo();
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

        $url = m::mock(Url::class);
        $version = Version::v3();

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn(null)->atLeast(1);

        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request);

        $this->assertTrue($desc->needExecution());
        $desc->setExecuted();
        $this->assertFalse($desc->needExecution());
    }

    public function testRequestVersionWithTwoDots()
    {
        $expected = 'The header DataServiceVersion has malformed version value 0.1.1';
        $actual = null;

        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url = m::mock(Url::class);
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
        $this->assertEquals($expected, $actual);
    }

    public function testRequestVersionNonNumeric()
    {
        $expected = 'The header DataServiceVersion has malformed version value slash.dot';
        $actual = null;

        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url = m::mock(Url::class);
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
        $this->assertEquals($expected, $actual);
    }

    public function testRequestVersionStartsWithDot()
    {
        $expected = 'The header DataServiceVersion has malformed version value .1';
        $actual = null;

        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url = m::mock(Url::class);
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
        $this->assertEquals($expected, $actual);
    }

    public function testRequestVersionNumericButUnsupported()
    {
        $expected = 'The version value 0.1 in the header DataServiceVersion is not supported, available'
                    .' versions are 1.0, 2.0, 3.0';
        $actual = null;

        $rStream = new ResourceStreamInfo('Delta');

        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['identifier'])->andReturn($rStream);

        $segment = m::mock(SegmentDescriptor::class);
        $segment->shouldReceive('getTargetResourceType')->andReturn($rType);
        $segment->shouldReceive('getIdentifier')->andReturn('identifier');
        $segment->shouldReceive('getTargetKind')->andReturn(TargetKind::MEDIA_RESOURCE());
        $segArray = [$segment];

        $url = m::mock(Url::class);
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
        $this->assertEquals($expected, $actual);
    }
}
