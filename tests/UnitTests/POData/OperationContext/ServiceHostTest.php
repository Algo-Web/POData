<?php

declare(strict_types=1);

namespace UnitTests\POData\OperationContext\Web;

use Illuminate\Http\Request;
use Mockery as m;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Version;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext;
use POData\OperationContext\Web\Illuminate\IncomingIlluminateRequest;
use TypeError;
use UnitTests\POData\TestCase;

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
                    . ' \'$\' character but is not recognized.';
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
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testValidateQueryParametersEmptyODataValue()
    {
        $expected = 'Query parameter \'$skip\' is specified, but it should be specified with value.';
        $actual   = null;

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
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testResponseETagRoundTrip()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        $expected = 'etag';
        $host->setResponseETag('etag');
        $actual = $host->getResponseETag();
        $this->assertEquals($expected, $actual);
    }

    public function testSetBadResponseCodeTooBig()
    {
        $expected = 'Invalid status code: 600';
        $actual   = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        try {
            $host->setResponseStatusCode(600);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSetBadResponseCodeTooSmall()
    {
        $expected = 'Invalid status code: 99';
        $actual   = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        try {
            $host->setResponseStatusCode(99);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testAddHeaderAndGetHeader()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        $host->addResponseHeader('STOP!', 'Hammer time!');
        $result = $host->getResponseHeaders();
        $this->assertTrue(isset($result['STOP!']));
        $this->assertEquals('Hammer time!', $result['STOP!']);
    }

    public function testSetResponseStatusDescription()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);
        $host->setResponseStatusDescription('OK');
        $result = $host->getResponseHeaders();
        $this->assertTrue(isset($result['StatusDesc']));
        $this->assertEquals('OK', $result['StatusDesc']);
    }

    public function testSetResponseStream()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);
        $stream  = 'stream';

        $host = new ServiceHost($context, $request);
        $host->setResponseStream($stream);
        $actual = $context->outgoingResponse()->getStream();
        $this->assertEquals($stream, $actual);
    }

    public function testSetResponseLocation()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context  = new IlluminateOperationContext($request);
        $location = 'location';

        $host = new ServiceHost($context, $request);
        $host->setResponseLocation($location);
        $result = $host->getResponseHeaders();
        $this->assertTrue(isset($result['Location']));
        $this->assertEquals('location', $result['Location']);
    }

    public function testGetAbsoluteRequestUriWhenMalformed()
    {
        $expected = 'Bad Request - The url \'BORK BORK BORK!\' is malformed.';
        $actual   = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('BORK BORK BORK!');

        $context = new IlluminateOperationContext($request);

        try {
            $host = new ServiceHost($context, $request);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSetBadResponseLength()
    {
        $expected = 'ContentLength: abc is invalid';
        $actual   = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        try {
            $host->setResponseContentLength('abc');
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSetGoodResponseLength()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);
        $host->setResponseContentLength('42');
        $result = $host->getResponseHeaders();
        $this->assertTrue(isset($result['Content-Length']));
        $this->assertEquals(42, $result['Content-Length']);
    }

    public function testGetRequestContentType()
    {
        $expected = 'foo';

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');
        $request->shouldReceive('header')->withArgs([ODataConstants::HTTP_CONTENTTYPE])->andReturn('foo');

        $context = new IlluminateOperationContext($request);

        $host   = new ServiceHost($context, $request);
        $actual = $host->getRequestContentType();
        $this->assertEquals($expected, $actual);
    }

    public function testGetRequestAcceptCharSet()
    {
        $expected = 'foo';

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc');
        $request->shouldReceive('header')->withArgs([ODataConstants::HTTPREQUEST_HEADER_ACCEPT_CHARSET])
            ->andReturn('foo');

        $context = new IlluminateOperationContext($request);

        $host   = new ServiceHost($context, $request);
        $actual = $host->getRequestAcceptCharSet();
        $this->assertEquals($expected, $actual);
    }

    public function testGetAbsoluteRequestUriAsString()
    {
        $expected = 'http://localhost/odata.svc/$metadata';

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn(['$top' => 'value', '$skip' => '']);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/$metadata');

        $context = new IlluminateOperationContext($request);

        $host   = new ServiceHost($context, $request);
        $actual = $host->getAbsoluteRequestUriAsString();
        $this->assertEquals($expected, $actual);
    }

    public function testGetAbsoluteRequestUriMalformed()
    {
        $malformedUrl = 'foobar';
        $expected     = 'Bad Request - The url \'foobar\' is malformed.';

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext->incomingRequest->getRawUrl')->andReturn($malformedUrl)->once();

        try {
            $host->getAbsoluteRequestUri();
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetAbsoluteServiceUriAsString()
    {
        $expected = 'http://localhost/odata.svc';

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([['$top' => 'value'], ['$skip' => '']]);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/$metadata');

        $context = new IlluminateOperationContext($request);

        $host   = new ServiceHost($context, $request);
        $actual = $host->getAbsoluteServiceUriAsString();
        $this->assertEquals($expected, $actual);
    }

    public function testDoubledQueryParameters()
    {
        $expected = 'Query parameter \'$top\' is specified, but it should be specified exactly once.';
        $actual   = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([['$top' => 'value'], ['$top' => '']]);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/$metadata');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        try {
            $host->validateQueryParameters();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testEmptyLabelWithSystemReservedValueQueryParameters()
    {
        $expected = 'The query parameter \'$value\' begins with a system-reserved \'$\' character'
                    . ' but is not recognized.';
        $actual = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([['' => '$value']]);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/$metadata');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        try {
            $host->validateQueryParameters();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testEmptyLabelWithOdataValueQueryParameters()
    {
        $expected = 'Query parameter \'$orderby\' is specified, but it should be specified with value.';
        $actual   = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([['' => ODataConstants::HTTPQUERY_STRING_ORDERBY]]);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/$metadata');

        $context = new IlluminateOperationContext($request);

        $host = new ServiceHost($context, $request);

        try {
            $host->validateQueryParameters();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSetServiceUriWithMalformedUri()
    {
        $expected = 'Malformed base service uri in the configuration file (should end with .svc, there should'
                    . ' not be query or fragment in the base service uri)';
        $actual = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([['' => ODataConstants::HTTPQUERY_STRING_ORDERBY]]);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/$metadata');

        $context = new IlluminateOperationContext($request);

        $host = m::mock(ServiceHost::class)->makePartial();

        try {
            $host->setServiceUri('BORK BORK BORK!');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSetServiceUriWithMissingServiceLink()
    {
        $expected = 'Malformed base service uri in the configuration file (should end with .svc, there should'
                    . ' not be query or fragment in the base service uri)';
        $actual = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([['' => ODataConstants::HTTPQUERY_STRING_ORDERBY]]);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/$metadata');

        $context = new IlluminateOperationContext($request);

        $host = m::mock(ServiceHost::class)->makePartial();

        try {
            $host->setServiceUri('http://localhost/odata');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSetServiceWithInvalidRelativeUrl()
    {
        $expected = 'The request uri http://localhost/odata.svc/$metadata is not valid as it is not based'
                    . ' on the configured relative uri /public/odata.svc';
        $actual = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([[ODataConstants::HTTPQUERY_STRING_ORDERBY => '']]);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/$metadata');

        $context = new IlluminateOperationContext($request);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        try {
            $host->setServiceUri('/public/odata.svc');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSetServiceWithMismatchedRelativeUrl()
    {
        $expected = 'The request uri http://localhost/private/odata.svc is not valid as it is not based' .
                    ' on the configured relative uri /public/odata.svc';
        $actual = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([[ODataConstants::HTTPQUERY_STRING_ORDERBY => '']]);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/private/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        try {
            $host->setServiceUri('/public/odata.svc');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSetServiceWithMatchedRelativeUrlAndNonstandardHttpPort()
    {
        $expected = 'http://localhost:81/private/odata.svc';
        $actual   = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([[ODataConstants::HTTPQUERY_STRING_ORDERBY => '']]);
        $request->shouldReceive('fullUrl')->andReturn('http://localhost:81/private/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $host->setServiceUri('/private/odata.svc');
        $actual = $host->getAbsoluteServiceUriAsString();

        $this->assertEquals($expected, $actual);
    }

    public function testSetServiceWithMatchedRelativeUrlAndNonstandardHttpsPort()
    {
        $expected = 'https://localhost:445/private/odata.svc';
        $actual   = null;

        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('all')->andReturn([[ODataConstants::HTTPQUERY_STRING_ORDERBY => '']]);
        $request->shouldReceive('fullUrl')->andReturn('https://localhost:445/private/odata.svc');

        $context = new IlluminateOperationContext($request);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getOperationContext')->andReturn($context);

        $host->setServiceUri('/private/odata.svc');
        $actual = $host->getAbsoluteServiceUriAsString();

        $this->assertEquals($expected, $actual);
    }
}
