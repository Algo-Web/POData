<?php

namespace UnitTests\POData\Providers\Metadata\Type;

use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\Byte;
use POData\Providers\Metadata\Type\Char;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Decimal;
use POData\Providers\Metadata\Type\Double;
use POData\Providers\Metadata\Type\Guid;
use POData\Providers\Metadata\Type\Int16;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\Int64;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\Null1;
use POData\Providers\Metadata\Type\SByte;
use POData\Providers\Metadata\Type\Single;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Metadata\Type\TypeCode;
use POData\Providers\Metadata\Type\VoidType;
use UnitTests\POData\TestCase;

class GuidTest extends TestCase
{
    /**
     * @return IType
     */
    public function getAsIType()
    {
        return new Guid();
    }

    public function testConstructorAndDefaultValues()
    {
        $type = $this->getAsIType();

        $actual = get_object_vars($type);

        $expected = [

        ];

        $this->assertEquals($expected, $actual);
    }

    public function testGetFullTypeName()
    {
        $type = $this->getAsIType();

        $actual = $type->getFullTypeName();

        $this->assertEquals('Edm.Guid', $actual);
    }

    public function testGetTypeCode()
    {
        $type = $this->getAsIType();

        $actual = $type->getTypeCode();

        $this->assertEquals(TypeCode::GUID, $actual);
    }

    public function testCompatibleWith()
    {
        $type = $this->getAsIType();

        $this->assertFalse($type->isCompatibleWith(new Binary()));
        $this->assertFalse($type->isCompatibleWith(new Boolean()));
        $this->assertFalse($type->isCompatibleWith(new Byte()));
        $this->assertFalse($type->isCompatibleWith(new Char()));
        $this->assertFalse($type->isCompatibleWith(new DateTime()));
        $this->assertFalse($type->isCompatibleWith(new Decimal()));
        $this->assertFalse($type->isCompatibleWith(new Double()));
        $this->assertTrue($type->isCompatibleWith(new Guid()));
        $this->assertFalse($type->isCompatibleWith(new Int16()));
        $this->assertFalse($type->isCompatibleWith(new Int32()));
        $this->assertFalse($type->isCompatibleWith(new Int64()));
        $this->assertFalse($type->isCompatibleWith(new Null1()));
        $this->assertFalse($type->isCompatibleWith(new SByte()));
        $this->assertFalse($type->isCompatibleWith(new Single()));
        $this->assertFalse($type->isCompatibleWith(new StringType()));
        $this->assertFalse($type->isCompatibleWith(new VoidType()));
    }

    public function testValidateSuccess()
    {
        $this->markTestSkipped('Too lazy for now see #62');

        $type = $this->getAsIType();

        $in = '';
        $out = null;
        $this->assertTrue($type->validate($in, $out));

        $this->assertSame('', $out);
    }

    public function testValidateFailureTooShort()
    {
        $type = $this->getAsIType();

        $in = '';
        $out = null;
        $this->assertFalse($type->validate($in, $out));
        $this->assertEquals(null, $out);
    }

    public function testValidateFailureNoLeadingGuidTag()
    {
        $type = $this->getAsIType();

        $in = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKL';
        $out = null;
        $this->assertFalse($type->validate($in, $out));
        $this->assertEquals(null, $out);
    }

    public function testValidateFailureNoTrailingQuote()
    {
        $type = $this->getAsIType();

        $in = 'guid\'fghijklmnopqrstuvwxyzABCDEFGHIJKL';
        $out = null;
        $this->assertFalse($type->validate($in, $out));
        $this->assertEquals(null, $out);
    }

    public function testValidateFailureBadData()
    {
        $type = $this->getAsIType();

        $in = 'guid\'fghijklmnopqrstuvwxyzABCDEFGHIJK\'';
        $out = null;
        $this->assertFalse($type->validate($in, $out));
        $this->assertEquals(null, $out);
    }

    public function testConvertShortString()
    {
        $type = $this->getAsIType();

        $expected = 'a';
        $data = 'a';
        $result = $type->convert($data);
        $this->assertEquals($expected, $result);
    }

    public function testConvertToOData()
    {
        $type = $this->getAsIType();

        $value = '{34234234}-{2342423}';
        $actual = $type->convertToOData($value);

        $expected = "guid'%7B34234234%7D-%7B2342423%7D'";
        $this->assertEquals($expected, $actual);
    }

    public function testGetName()
    {
        $type = $this->getAsIType();

        $actual = $type->getName();

        $this->assertEquals('Edm.Guid', $actual);
    }

    /**************
     *
     *  Begin Type Specific Tests
     *
     */

    public function testValidateWithoutPrefixWithoutQuotes()
    {
        $value = '00000000000000000000000000000000';
        $this->assertTrue(Guid::validateWithoutPrefix($value));
    }
}
