<?php

namespace UnitTests\POData\OperationContext\Web;

use POData\OperationContext\ServiceHost;

use POData\Common\Version;

use UnitTests\POData\BaseUnitTestCase;
use Phockito;

class ServiceHostTest extends BaseUnitTestCase {


    public function testGetMimeTypeFromFormatVersion10FormatAtom()
    {
		$actual = ServiceHost::getMimeTypeFromFormat(new Version(1,0), "atom");

	    $expected = "application/atom+xml;q=1.0";

        $this->assertEquals($expected, $actual);
    }


    public function testGetMimeTypeFromFormatVersion10FormatJson()
    {
        $actual = ServiceHost::getMimeTypeFromFormat(new Version(1,0), "json");

        $expected = "application/json;q=1.0";

        $this->assertEquals($expected, $actual);
    }

    public function testGetMimeTypeFromFormatVersion20FormatAtom()
    {
        $actual = ServiceHost::getMimeTypeFromFormat(new Version(2,0), "atom");

        $expected = "application/atom+xml;q=1.0";

        $this->assertEquals($expected, $actual);
    }


    public function testGetMimeTypeFromFormatVersion20FormatJson()
    {
        $actual = ServiceHost::getMimeTypeFromFormat(new Version(2,0), "json");

        $expected = "application/json;q=1.0";

        $this->assertEquals($expected, $actual);
    }

    public function testGetMimeTypeFromFormatVersion30FormatAtom()
    {
        $actual = ServiceHost::getMimeTypeFromFormat(new Version(3,0), "atom");

        $expected = "application/atom+xml;q=1.0";

        $this->assertEquals($expected, $actual);
    }


    public function testGetMimeTypeFromFormatVersion30FormatJson()
    {
        $actual = ServiceHost::getMimeTypeFromFormat(new Version(3,0), "json");

        $expected = "application/json;odata=minimalmetadata;q=1.0";

        $this->assertEquals($expected, $actual);
    }

    public function testGetMimeTypeFromFormatVersion30FormatVerboseJson()
    {
        $actual = ServiceHost::getMimeTypeFromFormat(new Version(3,0), "verbosejson");

        $expected = "application/json;odata=verbose;q=1.0";

        $this->assertEquals($expected, $actual);
    }


}