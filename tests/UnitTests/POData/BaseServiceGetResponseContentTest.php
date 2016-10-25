<?php

namespace UnitTests\POData\Common;

use Doctrine\Common\Annotations\Annotation\Target;
use POData\BaseService;
use POData\UriProcessor\RequestDescription;
use POData\IService;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessor;
use POData\Common\ODataConstants;
use POData\Common\MimeTypes;
use POData\OperationContext\ServiceHost;
use POData\Common\Version;
use PhockitoUnit\PhockitoUnitTestCase;
use Phockito\Phockito;

class BaseServiceGetResponseContentTest extends PhockitoUnitTestCase
{
    /** @var RequestDescription */
    protected $mockRequest;

    /** @var UriProcessor */
    protected $mockUriProcessor;

    /** @var IService */
    protected $mockService;

    /** @var ServiceHost */
    protected $mockHost;

    public function setUp()
    {
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
        $v1 = Version::v1();
        $v2 = Version::v2();
        $v3 = Version::v3();

        return array(
            //    Target                       Ver   header                                         $format                         expected
            array(101, TargetKind::METADATA(), $v1,  null,                                          null,                           MimeTypes::MIME_APPLICATION_XML),
            array(102, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                           MimeTypes::MIME_APPLICATION_XML),
            array(103, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                           null), //invalid format
            //Format overrides header
            array(104, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,     MimeTypes::MIME_APPLICATION_XML),
            array(105, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,     MimeTypes::MIME_APPLICATION_XML),
            array(106, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,     MimeTypes::MIME_APPLICATION_XML),

            array(108, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                           null),
            array(109, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                           null),
            array(110, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                           null),

            array(111, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                           null),
            array(112, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                           null),
            array(113, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                           null),

            array(114, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                           null),
            array(115, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                           null),
            array(116, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                           null),

            array(124, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                           null),
            array(125, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                           null),
            array(126, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                           null),

            //         Target                           Ver   header                                         $format                           expected
            array(201, TargetKind::SERVICE_DIRECTORY(), $v1,  null,                                          null,                             MimeTypes::MIME_APPLICATION_XML),
            array(202, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_ATOMSERVICE,       null,                             MimeTypes::MIME_APPLICATION_ATOMSERVICE),
            array(203, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                             MimeTypes::MIME_APPLICATION_JSON),
            array(204, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                             null),
            array(205, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON),
            array(206, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON),
            //Note this is a special case, when format is application/json, and we're v3, it's switched to minimal meta
            array(207, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(208, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(209, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(210, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),

            array(211, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(212, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(213, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),

            array(214, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(215, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(216, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(224, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(225, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(226, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),

            //TODO: this is more complicated, so we'll test them separately

            //       Target                           Ver   header                                     $format                          expected
            //array(300, TargetKind::PRIMITIVE_VALUE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,        MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON),

            //          Target                    Ver   header                                       $format                          expected
            array(400, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            MimeTypes::MIME_APPLICATION_XML),
            array(401, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_TEXTXML,                       null,                            MimeTypes::MIME_TEXTXML),
            array(402, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON),
            //TODO: kinda surprising this isn't supported..is it it supported in v3?
            array(403, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            null),
            array(404, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            array(405, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(406, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            array(407, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(408, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            //special case, v3 application/json is switched to minimal meta
            array(409, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(418, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(419, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(420, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),

            array(421, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(422, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(423, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),

            array(424, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(425, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(426, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(434, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(435, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(436, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),

            //          Target                       Ver   header                                         $format                          expected
            array(500, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            MimeTypes::MIME_APPLICATION_XML),
            array(501, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_TEXTXML,                       null,                            MimeTypes::MIME_TEXTXML),
            array(502, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON),
            //TODO: kinda surprising this isn't supported..is it it supported in v3?
            array(503, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            null),
            array(504, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            array(505, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(506, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            array(507, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(508, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            //special case, v3 application/json is switched to minimal meta
            array(509, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(518, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(519, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(520, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),

            array(521, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(522, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(523, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),

            array(524, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(525, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(526, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(534, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(535, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(536, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),

            //          Target            Ver   header                                         $format                          expected
            array(600, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            MimeTypes::MIME_APPLICATION_XML),
            array(601, TargetKind::BAG(), $v1,  MimeTypes::MIME_TEXTXML,                       null,                            MimeTypes::MIME_TEXTXML),
            array(602, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON),
            //TODO: kinda surprising this isn't supported..is it it supported in v3?
            array(603, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            null),
            array(604, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            array(605, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(606, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            array(607, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(608, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            //special case, v3 application/json is switched to minimal meta
            array(609, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(618, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(619, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(620, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),

            array(621, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(622, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(623, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),

            array(624, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(625, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(626, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(634, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(635, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(636, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),

            //          Target             Ver   header                                         $format                          expected
            array(700, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            MimeTypes::MIME_APPLICATION_XML),
            array(701, TargetKind::LINK(), $v1,  MimeTypes::MIME_TEXTXML,                       null,                            MimeTypes::MIME_TEXTXML),
            array(702, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON),
            //TODO: kinda surprising this isn't supported..is it it supported in v3?
            array(703, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            null),
            array(704, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            array(705, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(706, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            array(707, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(708, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML),
            //special case, v3 application/json is switched to minimal meta
            array(709, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(718, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(719, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(720, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),

            array(721, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(722, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(723, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),

            array(724, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(725, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(726, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(734, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(735, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(736, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),

            //          Target                 Ver   header                                         $format                          expected
            array(800, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            MimeTypes::MIME_APPLICATION_ATOM),
            array(802, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON),

            array(803, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            null),
            array(804, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_ATOM,     MimeTypes::MIME_APPLICATION_ATOM),
            array(805, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(806, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_ATOM,     MimeTypes::MIME_APPLICATION_ATOM),
            array(807, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON),
            array(808, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_ATOM,     MimeTypes::MIME_APPLICATION_ATOM),
            //special case, v3 application/json is switched to minimal meta
            array(809, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(818, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(819, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),
            array(820, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META),

            array(821, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(822, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),
            array(823, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META),

            array(824, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(825, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),
            array(826, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META),

            array(834, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(835, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),
            array(836, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE),

            //Note: we don't test media resources because they execute stuff and it's more complicated
        );
    }
}
