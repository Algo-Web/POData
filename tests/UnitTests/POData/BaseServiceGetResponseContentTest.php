<?php


namespace UnitTests\POData\Common;


use POData\BaseService;
use POData\Common\Messages;
use POData\UriProcessor\RequestDescription;
use POData\IService;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessor;
use POData\Common\ODataConstants;
use POData\Common\MimeTypes;
use POData\OperationContext\ServiceHost;

use UnitTests\POData\BaseUnitTestCase;
use Phockito;


class BaseServiceGetResponseContentTest extends BaseUnitTestCase {

	/** @var  RequestDescription */
	protected $mockRequest;

	/** @var  UriProcessor */
	protected $mockUriProcessor;

	/** @var  IService */
	protected $mockService;

	/** @var  ServiceHost */
	protected $mockHost;

	public function setUp(){
		parent::setUp();

		Phockito::when($this->mockService->getHost())
			->return($this->mockHost);
	}

	public function testGetWhenTargetMetadataHeaderNullFormatNull()
	{
		$acceptsHeader = null;
		$format = null;

		Phockito::when($this->mockRequest->getTargetKind())
			->return(TargetKind::METADATA());

		Phockito::when($this->mockHost->getRequestAccept())
			->return($acceptsHeader);

		Phockito::when($this->mockHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FORMAT))
			->return($format);

		$actual = BaseService::getResponseContentType($this->mockRequest, $this->mockUriProcessor, $this->mockService);

		$this->assertEquals(MimeTypes::MIME_APPLICATION_XML, $actual);
	}

	public function testGetWhenTargetMetadataHeaderXmlFormatNull()
	{
		$acceptsHeader = MimeTypes::MIME_APPLICATION_XML;
		$format = null;

		Phockito::when($this->mockRequest->getTargetKind())
			->return(TargetKind::METADATA());

		Phockito::when($this->mockHost->getRequestAccept())
			->return($acceptsHeader);

		Phockito::when($this->mockHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FORMAT))
			->return($format);

		$actual = BaseService::getResponseContentType($this->mockRequest, $this->mockUriProcessor, $this->mockService);

		$this->assertEquals(MimeTypes::MIME_APPLICATION_XML, $actual);
	}

	public function testGetWhenTargetMetadataHeaderXmlFormatNull()
	{
		$acceptsHeader = MimeTypes::MIME_APPLICATION_XML;
		$format = null;

		Phockito::when($this->mockRequest->getTargetKind())
			->return(TargetKind::METADATA());

		Phockito::when($this->mockHost->getRequestAccept())
			->return($acceptsHeader);

		Phockito::when($this->mockHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FORMAT))
			->return($format);

		$actual = BaseService::getResponseContentType($this->mockRequest, $this->mockUriProcessor, $this->mockService);

		$this->assertEquals(MimeTypes::MIME_APPLICATION_XML, $actual);
	}

}