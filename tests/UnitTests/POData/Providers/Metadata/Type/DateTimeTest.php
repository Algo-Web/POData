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

class DateTimeTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return IType
	 */
	public function getAsIType()
	{
		return new DateTime();
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

		$this->assertEquals("Edm.DateTime", $actual);

	}

	public function testGetTypeCode()
	{
		$type = $this->getAsIType();

		$actual = $type->getTypeCode();

		$this->assertEquals(TypeCode::DATETIME, $actual);

	}

	public function testCompatibleWith()
	{
		$type = $this->getAsIType();

		$this->assertFalse( $type->isCompatibleWith(new Binary()) );
		$this->assertFalse( $type->isCompatibleWith(new Boolean()) );
		$this->assertFalse( $type->isCompatibleWith(new Byte()) );
		$this->assertFalse( $type->isCompatibleWith(new Char()) );
		$this->assertTrue( $type->isCompatibleWith(new DateTime()) );
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
		$this->markTestSkipped("Too lazy see #67");
	}


	public function testValidateFailure()
	{

		$this->markTestSkipped("Too lazy see #67");

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

		$expected = "datetime'afaefasevaswee'";
		$this->assertEquals($expected, $actual);
	}



	/**************
	 *
	 *  Begin Type Specific Tests
	 *
	 */
}