<?php


namespace UnitTests\POData\Common;


use Doctrine\Common\Annotations\Annotation\Target;
use POData\BaseService;
use POData\Common\Messages;
use POData\UriProcessor\RequestDescription;
use POData\IService;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessor;
use POData\Common\ODataConstants;
use POData\Common\MimeTypes;
use POData\OperationContext\ServiceHost;
use POData\Common\Version;

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



	/**
	 * @dataProvider provider
	 */
	public function testGetResponseContentType($id, TargetKind $target, Version $version, $acceptsHeader, $format, $expectedValue)
	{

		Phockito::when($this->mockRequest->getTargetKind())
			->return($target);

		Phockito::when($this->mockHost->getRequestAccept())
			->return($acceptsHeader);

		Phockito::when($this->mockHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FORMAT))
			->return($format);

		Phockito::when($this->mockRequest->getResponseVersion())
			->return($version);

		$actual = BaseService::getResponseContentType($this->mockRequest, $this->mockUriProcessor, $this->mockService);

		//accepts doesn't match any possibles actual for that format..so it should return null
		$this->assertEquals($expectedValue, $actual, $id);
	}

	public function provider()
	{
		$v1 = new Version(1,0);
		$v2 = new Version(2,0);
		$v3 = new Version(3,0);
		return array(
			//    Target                  Ver   header                              $format                         expected
			array(101, TargetKind::METADATA(), $v1,  null,                               null,                           MimeTypes::MIME_APPLICATION_XML),
			array(102, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_XML,    null,                           MimeTypes::MIME_APPLICATION_XML),
			array(103, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,   null,                           null), //invalid format
			//Format overrides header
			array(104, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,   ODataConstants::FORMAT_XML,     MimeTypes::MIME_APPLICATION_XML),
			array(105, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,   ODataConstants::FORMAT_XML,     MimeTypes::MIME_APPLICATION_XML),
			array(106, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,   ODataConstants::FORMAT_XML,     MimeTypes::MIME_APPLICATION_XML),

			//       Target                           Ver   header                                     $format                          expected
			array(201, TargetKind::SERVICE_DIRECTORY(), $v1,  null,                                    null,                             MimeTypes::MIME_APPLICATION_XML),
			array(202, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_ATOMSERVICE, null,                             MimeTypes::MIME_APPLICATION_ATOMSERVICE),
			array(203, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON,        null,                             MimeTypes::MIME_APPLICATION_JSON),
			array(204, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,        null,                             null),
			array(205, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,        MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON),
			array(206, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,        MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON),
			array(207, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,        MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON),

			//TODO: this is more complicated, so we'll test them separetely

			//       Target                           Ver   header                                     $format                          expected
			//array(300, TargetKind::PRIMITIVE_VALUE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,        MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON),

			//          Target                    Ver   header                                $format                          expected
			array(400, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_XML,     null,                            MimeTypes::MIME_APPLICATION_XML),
			array(401, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_TEXTXML,             null,                            MimeTypes::MIME_TEXTXML),
			array(402, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON,    null,                            MimeTypes::MIME_APPLICATION_JSON),
			//TODO: kinda surprising this isn't supported..is it it supported in v3?
			array(403, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    null,                            null),
			array(404, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(405, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(406, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(407, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(408, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(409, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),


			//          Target                       Ver   header                                $format                          expected
			array(500, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_XML,     null,                            MimeTypes::MIME_APPLICATION_XML),
			array(501, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_TEXTXML,             null,                            MimeTypes::MIME_TEXTXML),
			array(502, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON,    null,                            MimeTypes::MIME_APPLICATION_JSON),
			//TODO: kinda surprising this isn't supported..is it it supported in v3?
			array(503, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    null,                            null),
			array(504, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(505, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(506, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(507, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(508, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(509, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),


			//          Target            Ver   header                                $format                          expected
			array(600, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_XML,     null,                            MimeTypes::MIME_APPLICATION_XML),
			array(601, TargetKind::BAG(), $v1,  MimeTypes::MIME_TEXTXML,             null,                            MimeTypes::MIME_TEXTXML),
			array(602, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON,    null,                            MimeTypes::MIME_APPLICATION_JSON),
			//TODO: kinda surprising this isn't supported..is it it supported in v3?
			array(603, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    null,                            null),
			array(604, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(605, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(606, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(607, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(608, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(609, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),


			//          Target             Ver   header                                $format                          expected
			array(600, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_XML,     null,                            MimeTypes::MIME_APPLICATION_XML),
			array(601, TargetKind::LINK(), $v1,  MimeTypes::MIME_TEXTXML,             null,                            MimeTypes::MIME_TEXTXML),
			array(602, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON,    null,                            MimeTypes::MIME_APPLICATION_JSON),
			//TODO: kinda surprising this isn't supported..is it it supported in v3?
			array(603, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    null,                            null),
			array(604, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(605, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(606, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(607, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(608, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
			array(609, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),


			//          Target                 Ver   header                              $format                          expected
			array(700, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,   null,                            MimeTypes::MIME_APPLICATION_ATOM),
			array(702, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON,   null,                            MimeTypes::MIME_APPLICATION_JSON),

			array(703, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_XML,    null,                            null),
			array(704, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_XML,    ODataConstants::FORMAT_ATOM,     MimeTypes::MIME_APPLICATION_ATOM),
			array(705, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_XML,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(706, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_XML,    ODataConstants::FORMAT_ATOM,     MimeTypes::MIME_APPLICATION_ATOM),
			array(707, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_XML,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
			array(708, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_XML,    ODataConstants::FORMAT_ATOM,     MimeTypes::MIME_APPLICATION_ATOM),
			array(709, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_XML,    ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),


			//Note: we don't test media resources because they execute stuff and it's more complicated
		);
	}


}