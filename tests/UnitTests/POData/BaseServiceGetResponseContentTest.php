<?php

namespace UnitTests\POData\Common;

use Doctrine\Common\Annotations\Annotation\Target;
use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\Version;
use POData\IService;
use POData\OperationContext\ServiceHost;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\UriProcessor;
use UnitTests\POData\BaseServiceDummy;
use UnitTests\POData\TestCase;

class BaseServiceGetResponseContentTest extends TestCase
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

        $this->mockUriProcessor = m::mock(UriProcessor::class)->makePartial();
        $this->mockHost = m::mock(ServiceHost::class)->makePartial();
        $this->mockService = m::mock(BaseServiceDummy::class)->makePartial();
        $this->mockService->shouldReceive('getHost')->andReturn($this->mockHost);

        $this->mockRequest = m::mock(RequestDescription::class)->makePartial();
    }

    /**
     * @dataProvider provider
     */
    public function testGetResponseContentType(
        $id,
        TargetKind $target,
        Version $version,
        $acceptsHeader,
        $format,
        $expectedValue
    ) {
        $this->mockRequest->shouldReceive('getTargetKind')->andReturn($target);
        $this->mockRequest->shouldReceive('getResponseVersion')->andReturn($version);

        $this->mockHost->shouldReceive('getRequestAccept')->andReturn($acceptsHeader);
        $this->mockHost->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_FORMAT])->andReturn($format);

        $actual = $this->mockService->getResponseContentType(
            $this->mockRequest,
            $this->mockUriProcessor,
            $this->mockService
        );

        //accepts doesn't match any possibles actual for that format..so it should return null
        $this->assertEquals($expectedValue, $actual, $id);
    }

    public function provider()
    {
        $v1 = Version::v1();
        $v2 = Version::v2();
        $v3 = Version::v3();

        return [
            //    Target                       Ver   header                                         $format                         expected
            [101, TargetKind::METADATA(), $v1,  null,                                          null,                           MimeTypes::MIME_APPLICATION_XML],
            [102, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                           MimeTypes::MIME_APPLICATION_XML],
            [103, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                           null], //invalid format
            //Format overrides header
            [104, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,     MimeTypes::MIME_APPLICATION_XML],
            [105, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,     MimeTypes::MIME_APPLICATION_XML],
            [106, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,     MimeTypes::MIME_APPLICATION_XML],

            [108, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                           null],
            [109, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                           null],
            [110, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                           null],

            [111, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                           null],
            [112, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                           null],
            [113, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                           null],

            [114, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                           null],
            [115, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                           null],
            [116, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                           null],

            [124, TargetKind::METADATA(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                           null],
            [125, TargetKind::METADATA(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                           null],
            [126, TargetKind::METADATA(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                           null],

            //         Target                           Ver   header                                         $format                           expected
            [201, TargetKind::SERVICE_DIRECTORY(), $v1,  null,                                          null,                             MimeTypes::MIME_APPLICATION_ATOMSERVICE],
            [202, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_ATOMSERVICE,       null,                             MimeTypes::MIME_APPLICATION_ATOMSERVICE],
            [203, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                             MimeTypes::MIME_APPLICATION_JSON],
            [204, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                             null],
            [205, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON],
            [206, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON],
            //Note this is a special case, when format is application/json, and we're v3, it's switched to minimal meta
            [207, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [208, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [209, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [210, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],

            [211, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [212, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [213, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],

            [214, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [215, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [216, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [224, TargetKind::SERVICE_DIRECTORY(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [225, TargetKind::SERVICE_DIRECTORY(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [226, TargetKind::SERVICE_DIRECTORY(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],

            //TODO: this is more complicated, so we'll test them separately

            //       Target                           Ver   header                                     $format                          expected
            //array(300, TargetKind::PRIMITIVE_VALUE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,        MimeTypes::MIME_APPLICATION_JSON, MimeTypes::MIME_APPLICATION_JSON),

            //          Target                    Ver   header                                       $format                          expected
            [400, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            MimeTypes::MIME_APPLICATION_XML],
            [401, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_TEXTXML,                       null,                            MimeTypes::MIME_TEXTXML],
            [402, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON],
            //TODO: kinda surprising this isn't supported..is it it supported in v3?
            [403, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            null],
            [404, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            [405, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [406, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            [407, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [408, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            //special case, v3 application/json is switched to minimal meta
            [409, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [418, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [419, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [420, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],

            [421, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [422, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [423, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],

            [424, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [425, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [426, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [434, TargetKind::PRIMITIVE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [435, TargetKind::PRIMITIVE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [436, TargetKind::PRIMITIVE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],

            //          Target                       Ver   header                                         $format                          expected
            [500, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            MimeTypes::MIME_APPLICATION_XML],
            [501, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_TEXTXML,                       null,                            MimeTypes::MIME_TEXTXML],
            [502, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON],
            //TODO: kinda surprising this isn't supported..is it it supported in v3?
            [503, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            null],
            [504, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            [505, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [506, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            [507, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [508, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            //special case, v3 application/json is switched to minimal meta
            [509, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [518, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [519, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [520, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],

            [521, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [522, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [523, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],

            [524, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [525, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [526, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [534, TargetKind::COMPLEX_OBJECT(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [535, TargetKind::COMPLEX_OBJECT(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [536, TargetKind::COMPLEX_OBJECT(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],

            //          Target            Ver   header                                         $format                          expected
            [600, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            MimeTypes::MIME_APPLICATION_XML],
            [601, TargetKind::BAG(), $v1,  MimeTypes::MIME_TEXTXML,                       null,                            MimeTypes::MIME_TEXTXML],
            [602, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON],
            //TODO: kinda surprising this isn't supported..is it it supported in v3?
            [603, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            null],
            [604, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            [605, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [606, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            [607, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [608, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            //special case, v3 application/json is switched to minimal meta
            [609, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [618, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [619, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [620, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],

            [621, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [622, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [623, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],

            [624, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [625, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [626, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [634, TargetKind::BAG(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [635, TargetKind::BAG(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [636, TargetKind::BAG(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],

            //          Target             Ver   header                                         $format                          expected
            [700, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            MimeTypes::MIME_APPLICATION_XML],
            [701, TargetKind::LINK(), $v1,  MimeTypes::MIME_TEXTXML,                       null,                            MimeTypes::MIME_TEXTXML],
            [702, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON],
            //TODO: kinda surprising this isn't supported..is it it supported in v3?
            [703, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            null],
            [704, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            [705, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [706, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            [707, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [708, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_XML,      MimeTypes::MIME_APPLICATION_XML],
            //special case, v3 application/json is switched to minimal meta
            [709, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_ATOM,              ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [718, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [719, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [720, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],

            [721, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [722, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [723, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],

            [724, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [725, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [726, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [734, TargetKind::LINK(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [735, TargetKind::LINK(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [736, TargetKind::LINK(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],

            //          Target                 Ver   header                                         $format                          expected
            [800, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_ATOM,              null,                            MimeTypes::MIME_APPLICATION_ATOM],
            [802, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON,              null,                            MimeTypes::MIME_APPLICATION_JSON],

            [803, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_XML,               null,                            null],
            [804, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_ATOM,     MimeTypes::MIME_APPLICATION_ATOM],
            [805, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [806, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_ATOM,     MimeTypes::MIME_APPLICATION_ATOM],
            [807, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON],
            [808, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_ATOM,     MimeTypes::MIME_APPLICATION_ATOM],
            //special case, v3 application/json is switched to minimal meta
            [809, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_XML,               ODataConstants::FORMAT_JSON,     MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [818, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [819, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],
            [820, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_FULL_META,    null,                             MimeTypes::MIME_APPLICATION_JSON_FULL_META],

            [821, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [822, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],
            [823, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_NO_META,      null,                             MimeTypes::MIME_APPLICATION_JSON_NO_META],

            [824, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [825, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],
            [826, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, null,                             MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META],

            [834, TargetKind::RESOURCE(), $v1,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [835, TargetKind::RESOURCE(), $v2,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],
            [836, TargetKind::RESOURCE(), $v3,  MimeTypes::MIME_APPLICATION_JSON_VERBOSE,      null,                             MimeTypes::MIME_APPLICATION_JSON_VERBOSE],

            //Note: we don't test media resources because they execute stuff and it's more complicated
        ];
    }
}
