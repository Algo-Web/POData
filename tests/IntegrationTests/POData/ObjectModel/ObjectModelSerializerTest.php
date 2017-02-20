<?php

ob_start();
/*
 * Note:
 * 1. This test case requires the service 'NorthWind' to be
 *    accessed using http://localhost:8086/NorthWind.svc
 * 2. Do not remove the  ob_start statement above.
 *
 */
use POData\Common\ODataConstants;
use POData\HttpProcessUtility;

class TestObjectModelSerializer extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        ob_start();
    }

    public function tearDown()
    {
        ob_end_clean();
    }

    /**
     * Tests ObjectModelSerializer::WriteTopLevelElements'.
     */
    public function testWriteTopLevelElements1()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = '/NorthWind.svc/Customers';
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
        //    = '$top=1';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<feed',
            $my_str
        );

        $this->assertStringEndsWith(
            '</feed>'."\n",
            $my_str
        );
    }

    /**
     * Tests ObjectModelSerializer::WriteTopLevelElements'.
     */
    public function testWriteTopLevelElements2()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = '/NorthWind.svc/Customers';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING]
            = '$top=2&$skip=3';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<feed',
            $my_str
        );

        $this->assertStringEndsWith(
            '</feed>'."\n",
            $my_str
        );
    }

    /**
     * Tests ObjectModelSerializer::WriteTopLevelElements'.
     */
    public function testWriteTopLevelElements3()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = '/NorthWind.svc/Orders(10643)/Customer/Orders';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<feed',
            $my_str
        );

        $this->assertStringEndsWith(
            '</feed>'."\n",
            $my_str
        );
    }

    /**
     * Test ObjectModelSerializer::WriteTopLevelElement'.
     */
    public function testWriteTopLevelElement1()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = "/NorthWind.svc/Customers('ALFKI')";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
        //    = '$top=1';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();
        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<entry',
            $my_str
        );

        $this->assertStringEndsWith(
            '</entry>'."\n",
            $my_str
        );
    }

    /**
     * Test ObjectModelSerializer::WriteTopLevelElement'.
     */
    public function testWriteTopLevelElement2()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = '/NorthWind.svc/Orders(10643)/Customer';
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
        //    = '$top=1';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<entry',
            $my_str
        );

        $this->assertStringEndsWith(
            '</entry>'."\n",
            $my_str
        );
    }

    /**
     * Test ObjectModelSerializer::writeUrlElements'.
     */
    public function testWriteTopLevelUrlElements()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = "/NorthWind.svc/Customers('ALFKI')/\$links/Orders";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
        //    = '$top=1';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<links',
            $my_str
        );

        $this->assertStringEndsWith(
            '</links>'."\n",
            $my_str
        );
    }

    /**
     * Test ObjectModelSerializer::writeUrlElements'.
     */
    public function testWriteTopLevelUrlElement()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = '/NorthWind.svc/Orders(10643)/$links/Customer';
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
        //    = '$top=1';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<uri',
            $my_str
        );

        $this->assertStringEndsWith(
            '</uri>'."\n",
            $my_str
        );
    }

    /**
     * Test ObjectModelSerializer::writeTopLevelComplexObject'.
     */
    public function testWriteTopLevelComplexObject()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = "/NorthWind.svc/Customers('ALFKI')/Address";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
        //    = '$top=1';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<d:Address m:type="NorthWind.Address"',
            $my_str
        );

        $this->assertStringEndsWith(
            '</d:Address>'."\n",
            $my_str
        );
    }

    /**
     * Test ObjectModelSerializer::writeTopLevelPrimitive'.
     */
    public function testWriteTopLevelPrimitive()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = "/NorthWind.svc/Customers('ALFKI')/Address/Country";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
        //    = '$top=1';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<d:Country m:type="Edm.String"',
            $my_str
        );

        $this->assertStringEndsWith(
            '</d:Country>'."\n",
            $my_str
        );
    }

    /**
     * Test ObjectModelSerializer::writeTopLevelBagObject'.
     */
    public function TestWriteTopLevelBagObject()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = "/NorthWind.svc/Customers('ALFKI')/OtherAddresses";
        //$_SERVER[ODataConstants::HTTPREQUEST_HEADER_QUERY_STRING]
        //    = '$top=1';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<d:OtherAddresses m:type="Collection(NorthWind.Address)"',
            $my_str
        );

        $this->assertStringEndsWith(
            '</d:OtherAddresses>'."\n",
            $my_str
        );
    }

    /**
     * Test with $select and $expansion.
     */
    public function TestSelectionAndExpansion()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = "/NorthWind.svc/Customers('ALFKI')";
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING]
            = '$expand=Orders,$select=CustomerID,Orders/OrderID';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<entry',
            $my_str
        );

        $this->assertStringEndsWith(
            '</entry>'."\n",
            $my_str
        );
    }

    /**
     * Tests orderby.
     */
    public function testOrderBy()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)]
            = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI]
            = '/NorthWind.svc/Customers';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING]
            = '$orderby=Address/Country';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<feed',
            $my_str
        );

        $this->assertStringEndsWith(
            '</feed>'."\n",
            $my_str
        );
    }

    /**
     * Tests inlinecount.
     */
    public function testInlineCountAllPages()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING]
            = '$inlinecount=allpages';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<feed',
            $my_str
        );

        $this->assertStringEndsWith(
            '</feed>'."\n",
            $my_str
        );
    }

    /**
     * Tests inlinecount.
     */
    public function testInlineCountNone()
    {
        $_SERVER[ODataConstants::HTTPREQUEST_METHOD]
            = ODataConstants::HTTP_METHOD_GET;
        $_SERVER[ODataConstants::HTTPREQUEST_PROTOCOL]
            = ODataConstants::HTTPREQUEST_PROTOCOL_HTTP;
        $_SERVER[HttpProcessUtility::headerToServerKey(ODataConstants::HTTPREQUEST_HEADER_HOST)] = 'localhost:8086';
        $_SERVER[ODataConstants::HTTPREQUEST_URI] = '/NorthWind.svc/Customers';
        $_SERVER[ODataConstants::HTTPREQUEST_QUERY_STRING] = '$inlinecount=none';

        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        $my_str = ob_get_contents();
        ob_end_clean();

        $this->assertStringStartsWith(
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".'<feed',
            $my_str
        );

        $this->assertStringEndsWith(
            '</feed>'."\n",
            $my_str
        );
    }
}
