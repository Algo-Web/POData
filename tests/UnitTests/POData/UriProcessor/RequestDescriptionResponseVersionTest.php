<?php

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\Configuration\ServiceConfiguration;
use POData\IService;
use POData\OperationContext\ServiceHost;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use Symfony\Component\EventDispatcher\Tests\Service;
use UnitTests\POData\TestCase;

//OData has some interesting version negotiations
//from http://www.odata.org/documentation/odata-v2-documentation/overview/#ProtocolVersioning

// Roughly
// If the cline specified a RequestVersion use that, otherwise use the Max Version supported by service
// If the client specifies a MaxRequestVersion use that, otherwise use the RequestVersion
// When responding use the minimum allowed

// That last part can be a bit tricky, it comes into play when the RequestVersion is specified below the Max Service
// Version - because during testing you rarely specify the versions, it can seem weird, but it's a backward
// compatibility thing

// Finally when the service is OData v3 capable...things switch
// see http://www.odata.org/documentation/odata-v3-documentation/odata-core/#5_Versioning
// in V3, the default is to return everything as v3 unless the max doesn't allow it

class RequestDescriptionResponseVersionTest extends TestCase
{
    /** @var IService */
    protected $mockService;

    /** @var ServiceHost */
    protected $mockServiceHost;

    /**
     * @var IServiceConfiguration
     */
    protected $mockServiceConfiguration;

    public function setUp()
    {
        parent::setUp();

        $this->mockServiceConfiguration = m::mock(ServiceConfiguration::class)->makePartial();
        $this->mockServiceHost = m::mock(ServiceHost::class)->makePartial();
        $this->mockService = m::mock(IService::class)->makePartial();

        //setup the general object graph
        $this->mockService->shouldReceive('getHost')->andReturn($this->mockServiceHost);
        $this->mockService->shouldReceive('getConfiguration')->andReturn($this->mockServiceConfiguration);
    }

    public function testGetResponseVersionConfigMaxVersion10RequestVersionNullRequestMaxVersionNull()
    {
        //Here's the key stuff
        $requestVersion = null;
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v1();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $fakeConfigMaxVersion->toString(),
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion10RequestVersion10RequestMaxVersionNull()
    {
        //Here's the key stuff
        $requestVersion = '1.0';
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v1();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals($fakeConfigMaxVersion, $request->getResponseVersion());

        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $fakeConfigMaxVersion->toString(),
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion10RequestVersion10RequestMaxVersion10()
    {
        //Here's the key stuff
        $requestVersion = '1.0';
        $requestMaxVersion = '1.0';
        $fakeConfigMaxVersion = Version::v1();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals($fakeConfigMaxVersion, $request->getResponseVersion());

        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $fakeConfigMaxVersion->toString(),
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion10RequestVersion10RequestMaxVersion20()
    {
        //Here's the key stuff
        $requestVersion = '1.0';
        $requestMaxVersion = '2.0';
        $fakeConfigMaxVersion = Version::v1();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals($fakeConfigMaxVersion, $request->getResponseVersion());

        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionIsBiggerThanProtocolVersion(
                    '2.0',
                    $fakeConfigMaxVersion->toString()
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion10RequestVersion20RequestMaxVersionNull()
    {
        //Here's the key stuff
        $requestVersion = '2.0';
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v1();

        //Note: in this case , even though the max version of the service (1) is less than the request version (2)
        //the request and response are valid until the response or the request require version 2.0
        //at least that's how it seems to be working...need to look into this deeper

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals($fakeConfigMaxVersion, $request->getResponseVersion());

        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionIsBiggerThanProtocolVersion(
                    '2.0',
                    $fakeConfigMaxVersion->toString()
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersionNullRequestMaxVersionNull()
    {
        $requestVersion = null;
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        $this->assertEquals(
            Version::v2(),
            $request->getResponseVersion(),
            'Response version should be upped from the raise'
        );

        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $fakeConfigMaxVersion->toString(),
                    '3.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion10RequestMaxVersionNull()
    {
        $requestVersion = '1.0';
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        //because there is no max version specified, it is set to the request version, which is 1, so moving to 2 is
        // not allowed
        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestVersion,
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion10RequestMaxVersion10()
    {
        $requestVersion = '1.0';
        $requestMaxVersion = '1.0';
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        //because there is no max version specified, it is set to the request version, which is 1, so moving to 2 is
        // not allowed
        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestVersion,
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion10RequestMaxVersion20()
    {
        $requestVersion = '1.0';
        $requestMaxVersion = '2.0';
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //because there the max version is specified as 2.0 this raise is allowed
        $this->assertEquals(
            Version::v2(),
            $request->getResponseVersion(),
            'Response version should be upped from the raise'
        );

        //moving to 3.0 should fail however
        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '3.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion10RequestMaxVersion30()
    {
        $requestVersion = '1.0';
        $requestMaxVersion = '3.0';
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //because there the max version is specified as 3.0 this raise is allowed
        $this->assertEquals(
            Version::v2(),
            $request->getResponseVersion(),
            'Response version should be upped from the raise'
        );

        //moving to 3.0 should fail however because the service protocol limit is 2.0
        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionIsBiggerThanProtocolVersion(
                    '3.0',
                    $fakeConfigMaxVersion->toString()
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion20RequestMaxVersionNull()
    {
        $requestVersion = '2.0';
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //because there is no max version specified, it is set to the request version, which is 2, so moving to 2
        // is allowed
        $this->assertEquals(
            Version::v2(),
            $request->getResponseVersion(),
            'Response version should be upped from the raise'
        );

        //however moving to 3.0 is not

        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestVersion,
                    '3.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion20RequestMaxVersion10()
    {
        $requestVersion = '2.0';
        $requestMaxVersion = '1.0';
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        //moving to 2.0 breaks the max of 1.0...but this is weird as something is supposed to verify
        //that the max isn't less than the request version (i think)
        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion20RequestMaxVersion20()
    {
        $requestVersion = '2.0';
        $requestMaxVersion = '2.0';
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //because the max version specified is 2.0 we should be able to make this raise
        $this->assertEquals(
            Version::v2(),
            $request->getResponseVersion(),
            'Response version should be upped from the raise'
        );

        //moving to 3.0 should fail however
        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '3.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion20RequestMaxVersion30()
    {
        $requestVersion = '2.0';
        $requestMaxVersion = '3.0';
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //because the max version specified is 3.0 we should be able to make this raise
        $this->assertEquals(
            Version::v2(),
            $request->getResponseVersion(),
            'Response version should be upped from the raise'
        );

        //moving to 3.0 should fail however as the protocol version for the service is less than this
        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionIsBiggerThanProtocolVersion(
                    '3.0',
                    $fakeConfigMaxVersion->toString()
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion30RequestMaxVersionNull()
    {
        $requestVersion = '3.0';
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //because there is no max version specified, it is set to the request version, which is 2, so moving to 2
        // is allowed
        $this->assertEquals(
            Version::v2(),
            $request->getResponseVersion(),
            'Response version should be upped from the raise'
        );

        //however moving to 3.0 is not

        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionIsBiggerThanProtocolVersion(
                    '3.0',
                    $fakeConfigMaxVersion->toString()
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion30RequestMaxVersion10()
    {
        //Note: i think something should be complaining about this
        $requestVersion = '3.0';
        $requestMaxVersion = '1.0';
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        //trying to move to 2 will exceed the max

        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion30RequestMaxVersion20()
    {
        $requestVersion = '3.0';
        $requestMaxVersion = '2.0';
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //because there max is 2.0, this is allowed
        $this->assertEquals(
            Version::v2(),
            $request->getResponseVersion(),
            'Response version should be upped from the raise'
        );

        //however moving to 3.0 is not

        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '3.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion20RequestVersion30RequestMaxVersion30()
    {
        $requestVersion = '3.0';
        $requestMaxVersion = '3.0';
        $fakeConfigMaxVersion = Version::v2();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //because max is 3 this is allowed
        $this->assertEquals(
            Version::v2(),
            $request->getResponseVersion(),
            'Response version should be upped from the raise'
        );

        //however moving to 3.0 is not because the service protocol is only 2

        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionIsBiggerThanProtocolVersion(
                    '3.0',
                    $fakeConfigMaxVersion->toString()
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    //Now the fun begins..if the service version is 3.0
    //if max is not specified max is 3.0
    //it should respond to everything as the max is specified
    //also it should be bound by the min..but we don't support that today and i don't see how it matters
    //at least till we get to 4.0

    public function testGetResponseVersionConfigMaxVersion30RequestVersionNullRequestMaxVersionNull()
    {
        $requestVersion = null;
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //max is 3, because it's not specified so it's set to the max service level so this is allowed
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(3, 0);
        //ditto
        $this->assertEquals(Version::v3(), $request->getResponseVersion());
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersionNullRequestMaxVersion10()
    {
        $requestVersion = null;
        $requestMaxVersion = '1.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        //moving to 2 is greater than max, so error should be thrown
        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersionNullRequestMaxVersion20()
    {
        $requestVersion = null;
        $requestMaxVersion = '2.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v2(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0); //max is already 2, so this is allowed
        $this->assertEquals(Version::v2(), $request->getResponseVersion());

        //moving to 3 is greater than max, so error should be thrown
        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '3.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersionNullRequestMaxVersion30()
    {
        $requestVersion = null;
        $requestMaxVersion = '3.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0); //max is already 3, so this is allowed
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(3, 0); //max is already 3 ditto
        $this->assertEquals(Version::v3(), $request->getResponseVersion());
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion10RequestMaxVersionNull()
    {
        $requestVersion = '1.0';
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //max is 3, because it's not specified so it's set to the max service level so this is allowed
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(3, 0);
        //ditto
        $this->assertEquals(Version::v3(), $request->getResponseVersion());
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion10RequestMaxVersion10()
    {
        $requestVersion = '1.0';
        $requestMaxVersion = '1.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        //moving to 2 is greater than max, so error should be thrown
        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion10RequestMaxVersion20()
    {
        $requestVersion = '1.0';
        $requestMaxVersion = '2.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v2(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0); //max is already 2, so this is allowed
        $this->assertEquals(Version::v2(), $request->getResponseVersion());

        //moving to 3 is greater than max, so error should be thrown
        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '3.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion10RequestMaxVersion30()
    {
        $requestVersion = '1.0';
        $requestMaxVersion = '3.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0); //max is already 3, so this is allowed
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(3, 0); //max is already 3 ditto
        $this->assertEquals(Version::v3(), $request->getResponseVersion());
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion20RequestMaxVersionNull()
    {
        $requestVersion = '2.0';
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //max is 3, because it's not specified so it's set to the max service level so this is allowed
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(3, 0);
        //ditto
        $this->assertEquals(Version::v3(), $request->getResponseVersion());
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion20RequestMaxVersion10()
    {
        $requestVersion = '2.0';
        $requestMaxVersion = '1.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        //moving to 2 is greater than max, so error should be thrown
        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion20RequestMaxVersion20()
    {
        $requestVersion = '2.0';
        $requestMaxVersion = '2.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v2(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0); //max is already 2, so this is allowed
        $this->assertEquals(Version::v2(), $request->getResponseVersion());

        //moving to 3 is greater than max, so error should be thrown
        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '3.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion20RequestMaxVersion30()
    {
        $requestVersion = '2.0';
        $requestMaxVersion = '3.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0); //max is already 3, so this is allowed
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(3, 0); //max is already 3 ditto
        $this->assertEquals(Version::v3(), $request->getResponseVersion());
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion30RequestMaxVersionNull()
    {
        $requestVersion = '3.0';
        $requestMaxVersion = null;
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0);
        //max is 3, because it's not specified so it's set to the max service level so this is allowed
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(3, 0);
        //ditto
        $this->assertEquals(Version::v3(), $request->getResponseVersion());
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion30RequestMaxVersion10()
    {
        $requestVersion = '3.0';
        $requestMaxVersion = '1.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v1(), $request->getResponseVersion());

        //moving to 2 is greater than max, so error should be thrown
        try {
            $request->raiseResponseVersion(2, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '2.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion30RequestMaxVersion20()
    {
        $requestVersion = '3.0';
        $requestMaxVersion = '2.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v2(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0); //max is already 2, so this is allowed
        $this->assertEquals(Version::v2(), $request->getResponseVersion());

        //moving to 3 is greater than max, so error should be thrown
        try {
            $request->raiseResponseVersion(3, 0);
            $this->fail('Expected exception not thrown');
        } catch (ODataException $ex) {
            $this->assertEquals(
                Messages::requestVersionTooLow(
                    $requestMaxVersion,
                    '3.0'
                ),
                $ex->getMessage()
            );
            $this->assertEquals(400, $ex->getStatusCode());
        }
    }

    public function testGetResponseVersionConfigMaxVersion30RequestVersion30RequestMaxVersion30()
    {
        $requestVersion = '3.0';
        $requestMaxVersion = '3.0';
        $fakeConfigMaxVersion = Version::v3();

        $this->mockServiceConfiguration->shouldReceive('getMaxDataServiceVersion')->andReturn($fakeConfigMaxVersion);

        $fakeURL = new Url('http://host/service.svc/Collection');
        $fakeSegments = [
            new SegmentDescriptor(),
        ];

        $request = new RequestDescription(
            $fakeSegments,
            $fakeURL,
            $fakeConfigMaxVersion,
            $requestVersion,
            $requestMaxVersion
        );

        //This respects the max version
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(2, 0); //max is already 3, so this is allowed
        $this->assertEquals(Version::v3(), $request->getResponseVersion());

        $request->raiseResponseVersion(3, 0); //max is already 3 ditto
        $this->assertEquals(Version::v3(), $request->getResponseVersion());
    }
}
