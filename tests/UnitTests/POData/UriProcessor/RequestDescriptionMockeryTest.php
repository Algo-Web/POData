<?php

namespace UnitTests\POData\UriProcessor;

use POData\Common\MimeTypes;
use POData\Common\Url;
use POData\Common\Version;
use POData\OperationContext\Web\Illuminate\IncomingIlluminateRequest;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use UnitTests\POData\TestCase;
use Mockery as m;

class RequestDescriptionMockeryTest extends TestCase
{
    public function testProcessDataStandaloneRequest()
    {
        $raw = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
<entry xml:base=\"http://localhost/foobar/odata.svc\" xmlns:d=\"http://schemas.microsoft.com/ado/2007/08/dataservices\" xmlns:m=\"http://schemas.microsoft.com/ado/2007/08/dataservices/metadata\" xmlns=\"http://www.w3.org/2005/Atom\">
    <content type=\"application/xml\">
        <m:properties>
            <d:Street m:type=\"Edm.String\"> 15 Woop Woop Drive </d:Street>
            <d:Suburb m:type=\"Edm.String\"> Downtown Woop Woop </d:Suburb>
            <d:State m:type=\"Edm.String\">NSW</d:State>
            <d:Postcode m:type=\"Edm.String\">2998</d:Postcode>
            <d:Country m:type=\"Edm.String\" m:null=\"true\"/>
        </m:properties>
    </content>
</entry>";

        $expectedArray = [
            'Street' => '15 Woop Woop Drive',
            'Suburb' => 'Downtown Woop Woop',
            'State' => 'NSW',
            'Postcode' => 2998,
            'Country' => ''
        ];

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
        $raw = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<entry xmlns=\"http://www.w3.org/2005/Atom\" xmlns:d=\"http://schemas.microsoft.com/ado/2007/08/dataservices\" xmlns:m=\"http://schemas.microsoft.com/ado/2007/08/dataservices/metadata\">
<category term=\"Data.CompanyConfigModel\" scheme=\"http://schemas.microsoft.com/ado/2007/08/dataservices/scheme\" /><id /><title />
<updated>2017-06-15T04:44:40Z</updated><author><name /></author>
<content type=\"application/xml\">
<m:properties><d:company_id>111111</d:company_id><d:configKey>CompanyMainDashboard</d:configKey><d:created_at m:type=\"Edm.DateTime\">0001-01-01T00:00:00</d:created_at><d:id m:type=\"Edm.Int32\">0</d:id><d:updated_at m:type=\"Edm.DateTime\">0001-01-01T00:00:00</d:updated_at>
<d:value>&lt;Dashboard CurrencyCulture=\"en-AU\"&gt;&#xD;
  &lt;Title Text=\"Dashboard\" /&gt;&#xD;
  &lt;DataSources&gt;&#xD;
    &lt;ObjectDataSource ComponentName=\"dashboardObjectDataSource1\"&gt;&#xD;
      &lt;Name&gt;Foo Bar&lt;/Name&gt;&#xD;
      &lt;DataSource Type=\"System.Data.Services.Client.DataServiceQuery`1+DataServiceOrderedQuery[[FooBar.FooBarRemoteWCF.Address, FooBar, Version=1.0.0.0, Culture=neutral, PublicKeyToken=null]], Microsoft.Data.Services.Client, Version=5.6.4.0, Culture=neutral, PublicKeyToken=31bf3856ad364e35\" /&gt;&#xD;
    &lt;/ObjectDataSource&gt;&#xD;
  &lt;/DataSources&gt;&#xD;
&lt;/Dashboard&gt;</d:value></m:properties></content></entry>
";

        $expectedArray = [
            'company_id' => '111111',
            'configKey' => 'CompanyMainDashboard',
            'created_at' => '0001-01-01T00:00:00',
            'id' => '',
            'updated_at' => '0001-01-01T00:00:00',
        ];

        $url = m::mock(Url::class);
        $version = Version::v3();

        $segment = m::mock(SegmentDescriptor::class);
        $segArray = [$segment];

        $request = m::mock(IncomingIlluminateRequest::class)->makePartial();
        $request->shouldReceive('getAllInput')->andReturn($raw)->atLeast(1);

        $type = MimeTypes::MIME_APPLICATION_ATOM;

        $desc = new RequestDescription($segArray, $url, $version, null, null, $type, $request);

        $data = $desc->getData();
        unset($data['value']);
        $this->assertEquals($expectedArray, $data);
    }
}
