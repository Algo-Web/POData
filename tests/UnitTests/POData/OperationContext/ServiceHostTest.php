<?php

namespace UnitTests\POData\OperationContext\Web;

use POData\OperationContext\ServiceHost;
use POData\Common\ODataConstants;
use POData\Common\Version;

use UnitTests\POData\BaseUnitTestCase;
use Phockito;

class ServiceHostTest extends BaseUnitTestCase {

	//TOOD: should i use MimeTypes constants for these?
	//TODO: should i use the data generator instead of all these tests?
    public function testTranslateFormatToMimeVersion10FormatAtom()
    {
		$actual = ServiceHost::translateFormatToMime(new Version(1,0), ODataConstants::FORMAT_ATOM);

	    $expected = "application/atom+xml;q=1.0";

        $this->assertEquals($expected, $actual);
    }


    public function testTranslateFormatToMimeVersion10FormatJson()
    {
        $actual = ServiceHost::translateFormatToMime(new Version(1,0), ODataConstants::FORMAT_JSON);

        $expected = "application/json;q=1.0";

        $this->assertEquals($expected, $actual);
    }

	public function testTranslateFormatToMimeVersion10FormatXml()
	{
		$actual = ServiceHost::translateFormatToMime(new Version(1,0), ODataConstants::FORMAT_XML);

		$expected = "application/xml;q=1.0";

		$this->assertEquals($expected, $actual);
	}

	public function testTranslateFormatToMimeVersion10FormatRandom()
	{
		$format = uniqid("xxx");
		$actual = ServiceHost::translateFormatToMime(new Version(1,0), $format);

		$expected = "$format;q=1.0";

		$this->assertEquals($expected, $actual);
	}

    public function testTranslateFormatToMimeVersion20FormatAtom()
    {
        $actual = ServiceHost::translateFormatToMime(new Version(2,0), ODataConstants::FORMAT_ATOM);

        $expected = "application/atom+xml;q=1.0";

        $this->assertEquals($expected, $actual);
    }




    public function testTranslateFormatToMimeVersion20FormatJson()
    {
        $actual = ServiceHost::translateFormatToMime(new Version(2,0), ODataConstants::FORMAT_JSON);

        $expected = "application/json;q=1.0";

        $this->assertEquals($expected, $actual);
    }

	public function testTranslateFormatToMimeVersion20FormatXml()
	{
		$actual = ServiceHost::translateFormatToMime(new Version(2,0), ODataConstants::FORMAT_XML);

		$expected = "application/xml;q=1.0";

		$this->assertEquals($expected, $actual);
	}

	public function testTranslateFormatToMimeVersion20FormatRandom()
	{
		$format = uniqid("xxx");
		$actual = ServiceHost::translateFormatToMime(new Version(2,0), $format);

		$expected = "$format;q=1.0";

		$this->assertEquals($expected, $actual);
	}

    public function testTranslateFormatToMimeVersion30FormatAtom()
    {
        $actual = ServiceHost::translateFormatToMime(new Version(3,0), ODataConstants::FORMAT_ATOM);

        $expected = "application/atom+xml;q=1.0";

        $this->assertEquals($expected, $actual);
    }


    public function testTranslateFormatToMimeVersion30FormatJson()
    {
        $actual = ServiceHost::translateFormatToMime(new Version(3,0), ODataConstants::FORMAT_JSON);

        $expected = "application/json;odata=minimalmetadata;q=1.0";

        $this->assertEquals($expected, $actual);
    }

	public function testTranslateFormatToMimeVersion30FormatXml()
	{
		$actual = ServiceHost::translateFormatToMime(new Version(3,0), ODataConstants::FORMAT_XML);

		$expected = "application/xml;q=1.0";

		$this->assertEquals($expected, $actual);
	}

    public function testTranslateFormatToMimeVersion30FormatVerboseJson()
    {
        $actual = ServiceHost::translateFormatToMime(new Version(3,0), ODataConstants::FORMAT_VERBOSE_JSON);

        $expected = "application/json;odata=verbose;q=1.0";

        $this->assertEquals($expected, $actual);
    }

	public function testTranslateFormatToMimeVersion30FormatRandom()
	{
		$format = uniqid("xxx");
		$actual = ServiceHost::translateFormatToMime(new Version(3,0), $format);

		$expected = "$format;q=1.0";

		$this->assertEquals($expected, $actual);
	}


}