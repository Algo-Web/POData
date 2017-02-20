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

class Null1Test extends TestCase
{
    /**
     * @return IType
     */
    public function getAsIType()
    {
        return new Null1();
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

        //TODO: is there no EDM equivalent to NULL Type?
        $this->assertEquals('System.NULL', $actual);
    }

    public function testGetTypeCode()
    {
        $type = $this->getAsIType();

        $actual = $type->getTypeCode();

        $this->assertEquals(TypeCode::NULL1, $actual);
    }

    public function testCompatibleWith()
    {
        $this->setExpectedException('POData\Common\NotImplementedException');

        $type = $this->getAsIType();

        $this->assertFalse($type->isCompatibleWith(new Binary()));
        $this->assertFalse($type->isCompatibleWith(new Boolean()));
        $this->assertFalse($type->isCompatibleWith(new Byte()));
        $this->assertFalse($type->isCompatibleWith(new Char()));
        $this->assertFalse($type->isCompatibleWith(new DateTime()));
        $this->assertFalse($type->isCompatibleWith(new Decimal()));
        $this->assertFalse($type->isCompatibleWith(new Double()));
        $this->assertFalse($type->isCompatibleWith(new Guid()));
        $this->assertFalse($type->isCompatibleWith(new Int16()));
        $this->assertFalse($type->isCompatibleWith(new Int32()));
        $this->assertFalse($type->isCompatibleWith(new Int64()));
        $this->assertTrue($type->isCompatibleWith(new Null1()));
        $this->assertFalse($type->isCompatibleWith(new SByte()));
        $this->assertFalse($type->isCompatibleWith(new Single()));
        $this->assertFalse($type->isCompatibleWith(new StringType()));
        $this->assertFalse($type->isCompatibleWith(new VoidType()));
    }

    public function testValidateSuccess()
    {
        $type = $this->getAsIType();

        $in = 'null';
        $out = null;
        $this->assertTrue($type->validate($in, $out));

        $this->assertSame('null', $out);
    }

    public function testValidateFailure()
    {
        $type = $this->getAsIType();

        $in = 'NULL';
        $out = null;
        $this->assertFalse($type->validate($in, $out));

        $in = '';
        $out = null;
        $this->assertFalse($type->validate($in, $out));

        $in = 'aefasefsf';
        $out = null;
        $this->assertFalse($type->validate($in, $out));
    }

    public function testConvert()
    {
        $type = $this->getAsIType();

        $value = 'afaefasevaswee';
        $actual = $type->convert($value);

        $expected = 'afaefasevaswee';
        $this->assertSame($expected, $actual);

        $value = 'null';
        $actual = $type->convert($value);

        $expected = null;
        $this->assertSame($expected, $actual);

        $value = 'NULL';
        $actual = $type->convert($value);

        $expected = 'NULL';
        $this->assertSame($expected, $actual);
    }

    public function testConvertToOData()
    {
        $this->setExpectedException('POData\Common\NotImplementedException');
        $type = $this->getAsIType();

        $value = 'afaefasevaswee';
        $actual = $type->convertToOData($value);
    }

    public function testGetName()
    {
        $type = $this->getAsIType();

        $actual = $type->getName();

        $this->assertEquals('System.NULL', $actual);
    }

    /**************
     *
     *  Begin Type Specific Tests
     *
     */
}
