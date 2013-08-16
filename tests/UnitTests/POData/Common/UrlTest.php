<?php

namespace UnitTests\POData\Common;

use POData\Common\Url;
use POData\Common\UrlFormatException;

class UrlTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {     
    }

    public function testUrl()
    {
        try {
            $urlStr = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders?\$filter=OrderID eq 123";
            $url = new Url($urlStr);
            $this->assertEquals($url->getScheme(), 'http');
            $this->assertEquals($url->getPort(), 80);
            $this->assertEquals($url->getHost(), 'localhost');
            $this->assertEquals($url->getPath(), "/NorthwindService.svc/Customers('ALFKI')/Orders");
            $this->assertEquals($url->getQuery(), '$filter=OrderID eq 123');
            $this->assertTrue($url->isAbsolute());
            $this->assertFalse($url->isRelative());
            
            //This is valid
            $urlStr = "http://localhost///NorthwindService.svc/Customers('ALFKI')/Orders//?\$filter=OrderID eq 123";
            $url = new Url($urlStr);
            $segments = $url->getSegments();
            $this->assertEquals(count($segments), 3);
            $this->assertEquals($segments[0], 'NorthwindService.svc');
            $this->assertEquals($segments[1], "Customers('ALFKI')");
            $this->assertEquals($segments[2], "Orders");
            
            //This is valid
            $urlStr = "http://localhost/NorthwindService.svc/@/./!/Customers('ALFKI')/Orders?\$filter=OrderID eq 123";
            new Url($urlStr);
            
            $exceptionThrown = false;
            try {                
                $urlStr = "http://localhost/NorthwindService.svc//Customers('ALFKI')/Orders?\$filter=OrderID eq 123";
                new Url($urlStr);
            } catch (UrlFormatException $exception) {                
                $exceptionThrown = true;                
            }

            if (!$exceptionThrown) {
                $this->fail('An expected UrlFormatException has not been raised');
            }

            $urlStr1 = "http://localhost/NorthwindService.svc";
            $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
            $url1 = new Url($urlStr1);
            $this->assertTrue($url1->isBaseOf(new Url($urlStr2)));


            $urlStr1 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders/Order_Details";
            $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
            $url1 = new Url($urlStr1);
            $this->assertFalse($url1->isBaseOf(new Url($urlStr2)));

            $urlStr1 = "http://localhost/NorthwindService";
            $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
            $url1 = new Url($urlStr1);
            $this->assertFalse($url1->isBaseOf(new Url($urlStr2)));

            $urlStr1 = "http://localhost/NorthwindService.svc";
            $urlStr2 = "https://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
            $url1 = new Url($urlStr1);
            $this->assertFalse($url1->isBaseOf(new Url($urlStr2)));

            $urlStr1 = "http://localhost:80/NorthwindService.svc";
            $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
            $url1 = new Url($urlStr1);
            $this->assertTrue($url1->isBaseOf(new Url($urlStr2)));

            $urlStr1 = "https://localhost:443/NorthwindService.svc";
            $urlStr2 = "https://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
            $url1 = new Url($urlStr1);
            $this->assertTrue($url1->isBaseOf(new Url($urlStr2)));

            $urlStr1 = "http://msn.com/NorthwindService.svc";
            $urlStr2 = "http://localhost/NorthwindService.svc/Customers('ALFKI')/Orders";
            $url1 = new Url($urlStr1);
            $this->assertFalse($url1->isBaseOf(new Url($urlStr2)));
            
        } catch (\Exception $exception) {
            $this->fail('An expected Exception has not been raised' . $exception->getMessage());
        }
    }

    protected function tearDown()
    {
    }
}