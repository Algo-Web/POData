<?php
namespace UnitTests\POData\Writers;

use JMS\Serializer\SerializerBuilder;


class TestJMSAsWireSerializer extends \PHPUnit_Framework_TestCase
{


    public function testWireSerializerExpanded(){
        $serialize = $this->initSerialiser();
        //dd(ObjectModelsForTests::NorthWindCustomersExpandOrders());
        //die($serialize->serialize(ObjectModelsForTests::NorthWindCustomersExpandOrders(), 'xml'));
        $this->assertXmlStringEqualsXmlString(ObjectModelsForTests::$NorthWindCustomersExpandOrdersXML,$serialize->serialize(ObjectModelsForTests::NorthWindCustomersExpandOrders(), 'xml'));
    }

    public function testWireSerializerNoneExpanded(){
        $serialize = $this->initSerialiser();
        //dd(ObjectModelsForTests::NorthWindCustomers());
        //die($serialize->serialize(ObjectModelsForTests::NorthWindCustomers(), 'xml'));
        $this->assertXmlStringEqualsXmlString(ObjectModelsForTests::$NorthWindCustomersXML,$serialize->serialize(ObjectModelsForTests::NorthWindCustomers(), 'xml'));
    }

    private function initSerialiser()
    {

        $ymlDir = dirname(__DIR__,4) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .'POData' .
            DIRECTORY_SEPARATOR .'Writers' . DIRECTORY_SEPARATOR .'YML';
        return
            SerializerBuilder::create()
                ->addMetadataDir($ymlDir)
                ->build();
    }
}
