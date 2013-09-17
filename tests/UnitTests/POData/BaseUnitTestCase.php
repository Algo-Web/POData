<?php

namespace UnitTests\POData;

use UnitTests\POData\PhpDocParser;

/**
 * Class BaseUnitTestCase generates Phockito Mocks for any base test case members
 */
class BaseUnitTestCase extends \PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->generateTestMocks();
	}

	/**
	 * Reflects on the current instance for any members prefixed with the name mock
	 * For each found it reflects on the php doc comment for the type and then generates a mock instance
	 * and sets the member to that mock instance.
	 */
	public function generateTestMocks()
	{
		$parser = new PhpDocParser();

		$class = new \ReflectionClass($this);

		//Find every member that begins with "mock"
		foreach ($class->getProperties() as $property) {

			if (strpos($property->name, 'mock') === 0) {
				if($property->name == "mockObjects"){
					//This is inherited from PHPUnit_Framework_TestCase and we can't mock it
					continue;
				}

				$classType = $parser->getPropertyType($class, $property);

				//Create the mock and assign it to the member
				$mock = \Phockito::mock($classType);
				$property->setAccessible(true);
				$property->setValue( $this, $mock );

			}
		}

	}



}