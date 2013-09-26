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
use POData\Providers\Metadata\Type\Navigation;
use POData\Providers\Metadata\Type\Null1;
use POData\Providers\Metadata\Type\SByte;
use POData\Providers\Metadata\Type\Single;
use POData\Providers\Metadata\Type\String;
use POData\Providers\Metadata\Type\TypeCode;
use POData\Providers\Metadata\Type\Void;

class BinaryTest extends \PHPUnit_Framework_TestCase {

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

		$expected = array(

		);

		$this->assertEquals($expected, $actual);

	}


	public function testGetFullTypeName()
	{
		$type = $this->getAsIType();

		$actual = $type->getFullTypeName();

		$this->assertEquals("Edm.Binary", $actual);

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

		$this->assertTrue( $type->isCompatibleWith(new Binary()) );
		$this->assertFalse( $type->isCompatibleWith(new Boolean()) );
		$this->assertFalse( $type->isCompatibleWith(new Byte()) );
		$this->assertFalse( $type->isCompatibleWith(new Char()) );
		$this->assertFalse( $type->isCompatibleWith(new DateTime()) );
		$this->assertFalse( $type->isCompatibleWith(new Decimal()) );
		$this->assertFalse( $type->isCompatibleWith(new Double()) );
		$this->assertFalse( $type->isCompatibleWith(new Guid()) );
		$this->assertFalse( $type->isCompatibleWith(new Int16()) );
		$this->assertFalse( $type->isCompatibleWith(new Int32()) );
		$this->assertFalse( $type->isCompatibleWith(new Int64()) );
		$this->assertFalse( $type->isCompatibleWith(new Null1()) );
		$this->assertFalse( $type->isCompatibleWith(new SByte()) );
		$this->assertFalse( $type->isCompatibleWith(new Single()) );
		$this->assertFalse( $type->isCompatibleWith(new String()) );
		$this->assertFalse( $type->isCompatibleWith(new Void()) );



	}

	public function testValidateSuccess()
	{
		$this->markTestSkipped("Too lazy see #65");
	}


	public function testValidateFailure()
	{
		$this->markTestSkipped("Too lazy see #65");
	}


	public function testConvert()
	{

		$type = $this->getAsIType();

		$value = "afaefasevaswee";
		$actual = $type->convert($value);

		$expected = "afaefasevaswee";
		$this->assertEquals($expected, $actual);
	}

	public function testConvertToOData()
	{

		$type = $this->getAsIType();

		$value = "afaefasevaswee";
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
		$this->assertFalse(Binary::validateWithoutPrefix("", $out), "empty string should be false");
		$this->assertNull($out);

		$rand = rand(1,11);
		if($rand % 2 == 0) $rand++; //make it odd

		$input = str_repeat(uniqid(), $rand);
		$this->assertFalse(Binary::validateWithoutPrefix($input, $out), "odd numbered length string should be false");
		$this->assertNull($out);


		$input = '1234567890abcdefABCDEF';
		$this->assertTrue(Binary::validateWithoutPrefix($input, $out), "These characters should work");
		//Expect values for each individual byte
		$expected = array(
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

		);
		$this->assertEquals($expected, $out);

		$input = '1234567890abcdefABCDXX';
		$this->assertFalse(Binary::validateWithoutPrefix($input, $out), "Invalid character X anywhere in string should fail");
		//Expect values for each individual byte
		$this->assertNull($out);
	}
}