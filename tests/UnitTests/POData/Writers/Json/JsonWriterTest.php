<?php

namespace UnitTests\POData\Writers\Json;

use POData\Providers\Metadata\Type\Guid;
use POData\Writers\Json\JsonWriter;
use UnitTests\POData\TestCase;

class JsonWriterTest extends TestCase
{
    public function testWriteValueNoType()
    {
        $writer = new JsonWriter('');

        $result = $writer->writeValue('car');
        $this->assertSame($result, $writer);

        $this->assertEquals('"car"', $writer->getJsonOutput());
    }

    public function testWriteValueUnknownType()
    {
        $writer = new JsonWriter('');

        $result = $writer->writeValue('car', 'afdaseefae');
        $this->assertSame($result, $writer);
        $this->assertEquals('"car"', $writer->getJsonOutput());

        //TODO: WTF is going on here?

        /*
        // Escape ( " \ / \n \r \t \b \f) characters with a backslash.
        $search  = array('\\', "\n", "\t", "\r", "\b", "\f", '"');
        $replace = array('\\\\', '\\n', '\\t', '\\r', '\\b', '\\f', '\"');
        $processedString  = str_replace($search, $replace, $string);
        // Escape some ASCII characters(0x08, 0x0c)
        $processedString = str_replace(array(chr(0x08), chr(0x0C)), array('\b', '\f'), $processedString);
        return $processedString;
        */
        //$string = "\\  \n  \t  \r  \b  \f " . chr(0x08) . "  " . chr(0x0C);
        //$writer = new JsonWriter("");
        //$writer->writeValue($string, "afdaseefae");
        //$this->assertEquals('"car"', $writer->getJsonOutput());
    }

    public function testWriteValueEdmBoolean()
    {
        $writer = new JsonWriter('');

        //TODO: should this really work this way?
        $result = $writer->writeValue('car', 'Edm.Boolean');
        $this->assertSame($result, $writer);
        $this->assertEquals('car', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(false, 'Edm.Boolean');
        $this->assertSame($result, $writer);
        $this->assertEquals('', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(true, 'Edm.Boolean');
        $this->assertSame($result, $writer);
        $this->assertEquals('1', $writer->getJsonOutput());
    }

    public function testWriteValueEdmInt16()
    {
        $writer = new JsonWriter('');

        //TODO: should this really work this way?
        $result = $writer->writeValue('car', 'Edm.Int16');
        $this->assertSame($result, $writer);
        $this->assertEquals('car', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(0, 'Edm.Int16');
        $this->assertSame($result, $writer);
        $this->assertEquals('0', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(100, 'Edm.Int16');
        $this->assertSame($result, $writer);
        $this->assertEquals('100', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(-100, 'Edm.Int16');
        $this->assertSame($result, $writer);
        $this->assertEquals('-100', $writer->getJsonOutput());
    }

    public function testWriteValueEdmInt32()
    {
        $writer = new JsonWriter('');

        //TODO: should this really work this way?
        $result = $writer->writeValue('car', 'Edm.Int32');
        $this->assertSame($result, $writer);
        $this->assertEquals('car', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(0, 'Edm.Int32');
        $this->assertSame($result, $writer);
        $this->assertEquals('0', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(100, 'Edm.Int32');
        $this->assertSame($result, $writer);
        $this->assertEquals('100', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(-100, 'Edm.Int32');
        $this->assertSame($result, $writer);
        $this->assertEquals('-100', $writer->getJsonOutput());
    }

    public function testWriteValueEdmInt64()
    {

        //apparently 64 bit means put it in quotes?

        $writer = new JsonWriter('');

        //TODO: should this really work this way?
        $result = $writer->writeValue('car', 'Edm.Int64');
        $this->assertSame($result, $writer);
        $this->assertEquals('"car"', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(0, 'Edm.Int64');
        $this->assertSame($result, $writer);
        $this->assertEquals('"0"', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(100, 'Edm.Int64');
        $this->assertSame($result, $writer);
        $this->assertEquals('"100"', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(-100, 'Edm.Int64');
        $this->assertSame($result, $writer);
        $this->assertEquals('"-100"', $writer->getJsonOutput());
    }

    public function testWriteValueEdmSingle()
    {

        //TODO: the fact that this is null is surprising
        $writer = new JsonWriter('');
        $result = $writer->writeValue(log(0), 'Edm.Single');
        $this->assertSame($result, $writer);
        $this->assertEquals('null', $writer->getJsonOutput(), 'is_infinite comes out as null');

        //TODO: the fact that this is null is surprising
        $writer = new JsonWriter('');
        $result = $writer->writeValue(acos(1.01), 'Edm.Single');
        $this->assertSame($result, $writer);
        $this->assertEquals('null', $writer->getJsonOutput(), 'nan comes out as null');

        $writer = new JsonWriter('');
        $result = $writer->writeValue(0.345, 'Edm.Single');
        $this->assertSame($result, $writer);
        $this->assertEquals(0.345, $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(100, 'Edm.Single');
        $this->assertSame($result, $writer);
        $this->assertEquals(100, $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(-100, 'Edm.Single');
        $this->assertSame($result, $writer);
        $this->assertEquals(-100, $writer->getJsonOutput());
    }

    public function testWriteValueEdmDouble()
    {

        //TODO: the fact that this is null is surprising
        $writer = new JsonWriter('');
        $result = $writer->writeValue(log(0), 'Edm.Double');
        $this->assertSame($result, $writer);
        $this->assertEquals('null', $writer->getJsonOutput(), 'is_infinite comes out as null');

        //TODO: the fact that this is null is surprising
        $writer = new JsonWriter('');
        $result = $writer->writeValue(acos(1.01), 'Edm.Double');
        $this->assertSame($result, $writer);
        $this->assertEquals('null', $writer->getJsonOutput(), 'nan comes out as null');

        $writer = new JsonWriter('');
        $result = $writer->writeValue(0.345, 'Edm.Double');
        $this->assertSame($result, $writer);
        $this->assertEquals(0.345, $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(100, 'Edm.Double');
        $this->assertSame($result, $writer);
        $this->assertEquals(100, $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(-100, 'Edm.Double');
        $this->assertSame($result, $writer);
        $this->assertEquals(-100, $writer->getJsonOutput());
    }

    public function testWriteValueEdmGuid()
    {
        $writer = new JsonWriter('');
        $g = uniqid();
        $result = $writer->writeValue($g, 'Edm.Guid');
        $this->assertSame($result, $writer);
        $this->assertEquals('"'.$g.'"', $writer->getJsonOutput());
    }

    public function testWriteValueEdmDecimal()
    {
        $writer = new JsonWriter('');
        $result = $writer->writeValue(0.345, 'Edm.Decimal');
        $this->assertSame($result, $writer);
        $this->assertEquals('"0.345"', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(100.00155, 'Edm.Decimal');
        $this->assertSame($result, $writer);
        $this->assertEquals('"100.00155"', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue(-100.00155, 'Edm.Decimal');
        $this->assertSame($result, $writer);
        $this->assertEquals('"-100.00155"', $writer->getJsonOutput());
    }

    public function testWriteValueEdmDateTime()
    {
        //TODO: add tests for other timezones
        $writer = new JsonWriter('');
        $result = $writer->writeValue('Mar 3, 2012 11:14:32 AM', 'Edm.DateTime');
        $this->assertSame($result, $writer);
        $this->assertEquals('"/Date(1330773272000)/"', $writer->getJsonOutput());
    }

    public function testWriteValueEdmByte()
    {

        //TODO: need to ensure we're doing this all right #59
        $writer = new JsonWriter('');
        $result = $writer->writeValue('01010101', 'Edm.Byte');
        $this->assertSame($result, $writer);
        $this->assertEquals('01010101', $writer->getJsonOutput());
    }

    public function testWriteValueEdmSByte()
    {

        //TODO: need to ensure we're doing this all right #59
        $writer = new JsonWriter('');
        $result = $writer->writeValue('01010101', 'Edm.SByte');
        $this->assertSame($result, $writer);
        $this->assertEquals('01010101', $writer->getJsonOutput());
    }

    public function testWriteValueEdmBinary()
    {

        //TODO: need to ensure we're doing this all right #59
        $writer = new JsonWriter('');
        $result = $writer->writeValue('01010101', 'Edm.Binary');
        $this->assertSame($result, $writer);
        $this->assertEquals('"01010101"', $writer->getJsonOutput());
    }

    public function testWriteValueEdmString()
    {

        //TODO: need to ensure we're doing this all right #59
        $writer = new JsonWriter('');
        $result = $writer->writeValue(null, 'Edm.String');
        $this->assertSame($result, $writer);
        $this->assertEquals('null', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $result = $writer->writeValue('null', 'Edm.String');
        $this->assertSame($result, $writer);
        $this->assertEquals('"null"', $writer->getJsonOutput());

        $writer = new JsonWriter('');
        $val = 'http://yahoo.com/some/path';
        $result = $writer->writeValue($val, 'Edm.String');
        $this->assertSame($result, $writer);
        $this->assertEquals('"http://yahoo.com/some/path"', $writer->getJsonOutput());
    }

    public function testStartArrayScope()
    {
        $writer = new JsonWriter('');
        $result = $writer->startArrayScope();
        $this->assertSame($result, $writer);
        $writer->writeValue('1', 'Edm.String');
        $writer->writeValue(2, 'Edm.Int16');

        $expected = "[\n    \"1\",2";
        $this->assertEquals($expected, $writer->getJsonOutput());

        $result = $writer->endScope();
        $this->assertSame($result, $writer);

        $expected = "[\n    \"1\",2\n]";
        $this->assertEquals($expected, $writer->getJsonOutput());
    }

    public function testStartObjectScope()
    {
        $writer = new JsonWriter('');
        $result = $writer->startObjectScope();
        $this->assertSame($result, $writer);
        $writer->writeName('1');
        $writer->writeValue(2, 'Edm.Int16');

        $expected = "{\n    \"1\":2";
        $this->assertEquals($expected, $writer->getJsonOutput());

        $result = $writer->endScope();
        $this->assertSame($result, $writer);

        $expected = "{\n    \"1\":2\n}";
        $this->assertEquals($expected, $writer->getJsonOutput());
    }

    public function testComplexObject()
    {
        $writer = new JsonWriter('');
        $writer->startObjectScope();
        $writer->writeName('1');
        $writer->writeValue(2, 'Edm.Int16');

        $writer->writeName('child');
        $writer->startObjectScope();

        $writer->writeName('array');
        $writer->startArrayScope();

        $writer->writeValue(100.00155, 'Edm.Decimal');
        $writer->writeValue('Mar 3, 2012 11:14:32 AM', 'Edm.DateTime');

        $writer->endScope();
        $writer->endScope();
        $writer->endScope();

        $expected = "{\n    \"1\":2,\"child\":{\n        \"array\":[\n            \"100.00155\",\"/Date(1330773272000)/\"\n        ]\n    }\n}";
        $this->assertEquals($expected, $writer->getJsonOutput());
    }
}
