<?php

namespace UnitTests\POData\Common;

use POData\Common\Url;
use POData\Common\UrlFormatException;
use UnitTests\POData\TestCase;

class UrlTest extends TestCase
{
    public function testAbsoluteUrl()
    {
        $urlStr = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders?\$filter=OrderID eq 123";
        $url = new Url($urlStr);
        $this->assertEquals('http', $url->getScheme());
        $this->assertEquals(80, $url->getPort());
        $this->assertEquals('localhost', $url->getHost());
        $this->assertEquals("/NorthwindService.svc/Customers('ALFKI')/Orders", $url->getPath());
        $this->assertEquals('$filter=OrderID eq 123', $url->getQuery());
        $this->assertTrue($url->isAbsolute());
        $this->assertFalse($url->isRelative());
    }

    public function testGetSegmentsAbsoluteUrlWithRedundantSlashing()
    {
        //This is valid
        $urlStr = "http://localhost///NorthwindService.svc/Customers('ALFKI')/Orders//?\$filter=OrderID eq 123";
        $url = new Url($urlStr);

        $actual = $url->getSegments();

        $expected = [
            'NorthwindService.svc',
            "Customers('ALFKI')",
            'Orders',
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testAbsoluteUrlWithSpecialCharacters()
    {
        //TODO: i thought the @ made it so everything before is a username...

        //This is valid
        $urlStr = "http://localhost/NorthwindService.svc/@/./!/Customers('ALFKI')/Orders?\$filter=OrderID eq 123";
        $url = new Url($urlStr);
        $actual = $url->getSegments();

        $expected = [
            'NorthwindService.svc',
            '@',
            '.',
            '!',
            "Customers('ALFKI')",
            'Orders',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testAbsoluteURLWithDoubleSlashesAfterService()
    {
        $urlStr = "http://localhost/NorthwindService.svc//Customers('ALFKI')/Orders?\$filter=OrderID eq 123";
        try {
            new Url($urlStr);
            $this->fail('An expected UrlFormatException has not been raised');
        } catch (UrlFormatException $exception) {
            $this->assertEquals("Bad Request - The url '$urlStr' is malformed.", $exception->getMessage());
        }
    }

    public function testNotAURL()
    {
        $urlStr = "doubt i'm a url";
        try {
            new Url($urlStr);
            $this->fail('An expected UrlFormatException has not been raised');
        } catch (UrlFormatException $exception) {
            $this->assertEquals("Bad Request - The url '$urlStr' is malformed.", $exception->getMessage());
        }
    }

    public function testABadlyFormedURL()
    {
        $urlStr = 'http:///example.com';     //this one gets passed the relative regex check, but not parse_url
        try {
            new Url($urlStr, false);
            $this->fail('An expected UrlFormatException has not been raised');
        } catch (UrlFormatException $exception) {
            $this->assertEquals("Bad Request - The url '$urlStr' is malformed.", $exception->getMessage());
        }
    }

    public function testIsBaseOf()
    {
        $urlStr1 = 'http://localhost/NorthwindService.svc';
        $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
        $url1 = new Url($urlStr1);
        $this->assertTrue($url1->isBaseOf(new Url($urlStr2)));

        $urlStr1 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders/Order_Details";
        $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
        $url1 = new Url($urlStr1);
        $this->assertFalse($url1->isBaseOf(new Url($urlStr2)));

        $urlStr1 = 'http://localhost/NorthwindService';
        $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
        $url1 = new Url($urlStr1);
        $this->assertFalse($url1->isBaseOf(new Url($urlStr2)));

        $urlStr1 = 'http://localhost/NorthwindService.svc';
        $urlStr2 = "https://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
        $url1 = new Url($urlStr1);
        $this->assertFalse($url1->isBaseOf(new Url($urlStr2)));

        $urlStr1 = 'http://localhost:80/NorthwindService.svc';
        $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
        $url1 = new Url($urlStr1);
        $this->assertTrue($url1->isBaseOf(new Url($urlStr2)));

        $urlStr1 = 'https://localhost:443/NorthwindService.svc';
        $urlStr2 = "https://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
        $url1 = new Url($urlStr1);
        $this->assertTrue($url1->isBaseOf(new Url($urlStr2)));

        $urlStr1 = 'http://msn.com/NorthwindService.svc';
        $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
        $url1 = new Url($urlStr1);
        $this->assertFalse($url1->isBaseOf(new Url($urlStr2)));
    }
}
