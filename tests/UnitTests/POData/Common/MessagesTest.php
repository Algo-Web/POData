<?php


namespace UnitTests\POData\Common;


use POData\Common\Messages;
use POData\ResponseFormat;
use UnitTests\POData\BaseUnitTestCase;

class MessagesTest extends BaseUnitTestCase{


	public function testBadFormatForServiceDocument()
	{

		$this->assertEquals("The requested format of Atom is not supported for service documents", Messages::badFormatForServiceDocument(ResponseFormat::ATOM()));
	}
}