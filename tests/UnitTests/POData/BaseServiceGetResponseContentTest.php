<?php


namespace UnitTests\POData\Common;


use POData\BaseService;
use POData\Common\Messages;
use POData\UriProcessor\RequestDescription;

use UnitTests\POData\BaseUnitTestCase;
use Phockito;


class BaseServiceGetResponseContentTest extends BaseUnitTestCase {

	/** @var  RequestDescription */
	protected $mockRequestDescription()

	public function testGetWhen()
	{
		BaseService::getResponseContentType($this->mockRequestDescription)
	}

}