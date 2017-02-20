<?php

namespace UnitTests\POData\UriProcessor;

use POData\UriProcessor\XML2Array;
use UnitTests\POData\TestCase;

class XML2ArrayTest extends TestCase
{
    public function provideInvalidObjects()
    {
        return [
            [new \stdClass()],
        ];
    }

    public function provideInvalidTypes()
    {
        return [
            [1.1],
            [true],
        ];
    }

    public function provideEmptyTypes()
    {
        return [
            [0],
            [null],
            [false],
        ];
    }

    public function provideInvalidXML()
    {
        return [
            ['<?xml version="1.0" encoding="UTF-8"?><root>'],
            ['<?xml version="1.0" encoding="UTF-8"?><root><head>'],
        ];
    }

    /**
     * @param object $invalidObject
     *
     * @throws \Exception
     * @expectedException \Exception
     * @expectedExceptionMessage [XML2Array] The input XML object should be of type: DOMDocument.
     * @dataProvider provideInvalidObjects
     */
    public function testXMLFileToArrayRejectsInvalidObjects($invalidObject)
    {
        XML2Array::createArray($invalidObject);
    }

    /**
     * @param mixed $invalidType
     *
     * @throws \Exception
     * @expectedException \Exception
     * @expectedExceptionMessage [XML2Array] Invalid input
     * @dataProvider provideInvalidTypes
     */
    public function testXMLFileToArrayRejectsInvalidTypes($invalidType)
    {
        XML2Array::createArray($invalidType);
    }

    /**
     * @param mixed $invalidType
     *
     * @dataProvider provideEmptyTypes
     */
    public function testXMLFileToArrayRejectsEmptyTypes($emptyType)
    {
        $this->assertEquals(0, count(XML2Array::createArray($emptyType)));
    }

    /**
     * @param string $invalidXML
     *
     * @throws \Exception
     * @expectedException \Exception
     * @expectedExceptionMessage [XML2Array] Error parsing the XML string.
     * @dataProvider provideInvalidXML
     */
    public function testXMLFileToArrayRejectsInvalidXML($invalidXML)
    {
        XML2Array::createArray($invalidXML);
    }
}
