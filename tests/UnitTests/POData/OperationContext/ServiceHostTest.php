<?php

namespace UnitTests\POData\OperationContext\Web;

use Illuminate\Http\Request;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Version;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext;
use POData\OperationContext\Web\Illuminate\IncomingIlluminateRequest;
use UnitTests\POData\TestCase;
use Mockery as m;

class ServiceHostTest extends TestCase
{
    //TOOD: should i use MimeTypes constants for these?
    //TODO: should i use the data generator instead of all these tests?
    public function testTranslateFormatToMimeVersion10FormatAtom()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v1(), ODataConstants::FORMAT_ATOM);

        $expected = 'application/atom+xml;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion10FormatJson()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v1(), ODataConstants::FORMAT_JSON);

        $expected = 'application/json;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion10FormatXml()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v1(), ODataConstants::FORMAT_XML);

        $expected = 'application/xml;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion10FormatRandom()
    {
        $format = uniqid('xxx');
        $actual = ServiceHost::translateFormatToMime(Version::v1(), $format);

        $expected = "$format;q=1.0";

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion20FormatAtom()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v2(), ODataConstants::FORMAT_ATOM);

        $expected = 'application/atom+xml;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion20FormatJson()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v2(), ODataConstants::FORMAT_JSON);

        $expected = 'application/json;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion20FormatXml()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v2(), ODataConstants::FORMAT_XML);

        $expected = 'application/xml;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion20FormatRandom()
    {
        $format = uniqid('xxx');
        $actual = ServiceHost::translateFormatToMime(Version::v2(), $format);

        $expected = "$format;q=1.0";

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion30FormatAtom()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v3(), ODataConstants::FORMAT_ATOM);

        $expected = 'application/atom+xml;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion30FormatJson()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v3(), ODataConstants::FORMAT_JSON);

        $expected = 'application/json;odata=minimalmetadata;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion30FormatXml()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v3(), ODataConstants::FORMAT_XML);

        $expected = 'application/xml;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion30FormatVerboseJson()
    {
        $actual = ServiceHost::translateFormatToMime(Version::v3(), ODataConstants::FORMAT_VERBOSE_JSON);

        $expected = 'application/json;odata=verbose;q=1.0';

        $this->assertEquals($expected, $actual);
    }

    public function testTranslateFormatToMimeVersion30FormatRandom()
    {
        $format = uniqid('xxx');
        $actual = ServiceHost::translateFormatToMime(Version::v3(), $format);

        $expected = "$format;q=1.0";

        $this->assertEquals($expected, $actual);
    }

    public function testValidateQueryParametersStartWithDollarButNotOData()
    {
        $expected = 'The query parameter \'$impostorkey\' begins with a system-reserved'
                    .' \'$\' character but is not recognized.';
        $actual = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$impostorKey' => 'value']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        try {
            $host->validateQueryParameters();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testValidateQueryParametersEmptyODataValue()
    {
        $expected = 'Query parameter \'$skip\' is specified, but it should be specified with value.';
        $actual = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        try {
            $host->validateQueryParameters();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
