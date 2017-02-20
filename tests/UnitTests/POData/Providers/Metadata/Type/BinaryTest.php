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

class BinaryTest extends TestCase
{
    /**
     * @return IType
     */
    public function getAsIType()
    {
        return new Binary();
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

        $this->assertEquals('Edm.Binary', $actual);
    }

    public function testGetTypeCode()
    {
        $type = $this->getAsIType();

        $actual = $type->getTypeCode();

        $this->assertEquals(TypeCode::BINARY, $actual);
    }

    public function testCompatibleWith()
    {
        $type = $this->getAsIType();

        $this->assertTrue($type->isCompatibleWith(new Binary()));
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
        $this->assertFalse($type->isCompatibleWith(new Null1()));
        $this->assertFalse($type->isCompatibleWith(new SByte()));
        $this->assertFalse($type->isCompatibleWith(new Single()));
        $this->assertFalse($type->isCompatibleWith(new StringType()));
        $this->assertFalse($type->isCompatibleWith(new VoidType()));
    }

    public function testValidateSuccess()
    {
        $type = $this->getAsIType();

        $expected = [327680];
        $out = '';

        $this->assertTrue($type->validate('x\'ab\'', $out));
        $this->assertEquals($expected, $out);
    }

    public function testValidateFailureBadStart()
    {
        $type = $this->getAsIType();

        $out = '';

        $this->assertFalse($type->validate('a', $out));
    }

    public function testValidateFailureBadBinaryStart()
    {
        $type = $this->getAsIType();

        $out = '';

        $this->assertFalse($type->validate('binary\'c', $out));
    }

    public function testValidateFailureBadCapitalXStart()
    {
        $type = $this->getAsIType();

        $out = '';

        $this->assertFalse($type->validate('X\'c', $out));
    }

    public function testValidateFailureBadXStart()
    {
        $type = $this->getAsIType();

        $out = '';

        $this->assertFalse($type->validate('x\'c', $out));
    }

    public function testValidateFailureOddLengthAfterPrefix()
    {
        $type = $this->getAsIType();

        $out = '';

        $this->assertFalse($type->validate('x\'abc\'', $out));
    }

    public function testConvert()
    {
        $type = $this->getAsIType();

        $value = 'afaefasevaswee';
        $actual = $type->convert($value);

        $expected = 'afaefasevaswee';
        $this->assertEquals($expected, $actual);
    }

    public function testConvertToOData()
    {
        $type = $this->getAsIType();

        $value = 'afaefasevaswee';
        $actual = $type->convertToOData($value);

        $expected = "binary'6166616566617365766173776565'";
        $this->assertEquals($expected, $actual);
    }

    /**************
     *
     *  Begin Type Specific Tests
     *
     */

    public function testValidateWithoutPrefix()
    {
        $out = null;
        $this->assertFalse(Binary::validateWithoutPrefix('', $out), 'empty string should be false');
        $this->assertNull($out);

        $rand = rand(1, 11);
        if ($rand % 2 == 0) {
            $rand++;
        } //make it odd

        $input = str_repeat(uniqid(), $rand);
        $this->assertFalse(Binary::validateWithoutPrefix($input, $out), 'odd numbered length string should be false');
        $this->assertNull($out);

        $input = '1234567890abcdefABCDEF';
        $this->assertTrue(Binary::validateWithoutPrefix($input, $out), 'These characters should work');
        //Expect values for each individual byte
        $expected = [
            hexdec('1') << 4 + hexdec('2'),
            hexdec('3') << 4 + hexdec('4'),
            hexdec('5') << 4 + hexdec('6'),
            hexdec('7') << 4 + hexdec('8'),
            hexdec('9') << 4 + hexdec('0'),
            hexdec('a') << 4 + hexdec('b'),
            hexdec('c') << 4 + hexdec('d'),
            hexdec('e') << 4 + hexdec('f'),
            hexdec('A') << 4 + hexdec('B'),
            hexdec('C') << 4 + hexdec('D'),
            hexdec('E') << 4 + hexdec('F'),

        ];
        $this->assertEquals($expected, $out);

        $input = '1234567890abcdefABCDXX';
        $this->assertFalse(
            Binary::validateWithoutPrefix($input, $out),
            'Invalid character X anywhere in string should fail'
        );
        //Expect values for each individual byte
        $this->assertNull($out);
    }

    public function testStaticBinaryEqualAtLeastOneNull()
    {
        $this->assertFalse(Binary::binaryEqual(null, null));
        $this->assertFalse(Binary::binaryEqual('ab', null));
        $this->assertFalse(Binary::binaryEqual(null, 'cd'));
    }

    public function testStaticBinaryEqualNoneNull()
    {
        $this->assertFalse(Binary::binaryEqual('true', 'false'));
        $this->assertTrue(Binary::binaryEqual('false', 'false'));
    }

    public function testGetName()
    {
        $type = $this->getAsIType();

        $actual = $type->getName();

        $this->assertEquals('Edm.Binary', $actual);
    }
}
