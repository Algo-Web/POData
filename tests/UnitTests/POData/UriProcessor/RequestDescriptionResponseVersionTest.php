<?php

namespace UnitTests\POData\UriProcessor;


use POData\IService;
use POData\OperationContext\ServiceHost;
use POData\Configuration\ServiceConfiguration;
use POData\Common\Messages;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\Providers\Metadata\Type\DateTime;
use POData\Common\Url;
use POData\Common\Version;
use POData\Common\ODataException;


use Phockito;
use UnitTests\POData\BaseUnitTestCase;

//OData has some interesting version negotiations
//from http://www.odata.org/documentation/odata-v2-documentation/overview/#ProtocolVersioning
//
// Roughly
// If the cline specified a RequestVersion use that, otherwise use the Max Version supported by service
// If the client specifies a MaxRequestVersion use that, otherwise use the RequestVersion
// When responding use the minimum allowed
//
// That last part can be a bit tricky, it comes into play when the RequestVersion is specified below the Max Service Version
// Because during testing you rarely specify the versions, it can seem weird, but it's a backward compatibility thing
//
// Finally when the service is OData v3 capable...things switch
// see http://www.odata.org/documentation/odata-v3-documentation/odata-core/#5_Versioning
// in V3, the default is to return everything as v3 unless the max doesn't allow it


class RequestDescriptionResponseVersionTest extends BaseUnitTestCase
{

	/** @var  IService */
	protected $mockService;


	/** @var  ServiceHost */
	protected $mockServiceHost;

	/**
	 * @var ServiceConfiguration
	 */
	protected $mockServiceConfiguration;


	public function setUp()
	{
		parent::setUp();

		//setup the general object graph
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);


		Phockito::when($this->mockService->getConfiguration())
			->return($this->mockServiceConfiguration);


	}
    public function testGetResponseVersionConfigMaxVersion10RequestVersionNullRequestMaxVersionNull()
    {
	    //Here's the key stuff
	    $requestVersion = null;
	    $requestMaxVersion = null;
	    $fakeConfigMaxVersion = new Version(1,0);

	    Phockito::when($this->mockServiceConfiguration->getMaxDataServiceVersion())
		    ->return($fakeConfigMaxVersion);

	    $fakeURL = new Url("http://host/service.svc/Collection");
	    $fakeSegments = array(
			new SegmentDescriptor(),
	    );

	    $request = new RequestDescription($fakeSegments, $fakeURL, $fakeConfigMaxVersion, $requestVersion, $requestMaxVersion);


	    $this->assertEquals($fakeConfigMaxVersion, $request->getResponseVersion(), "Should default to max data service version of the config");


	    try{
		    $request->raiseResponseVersion(2, 0);
		    $this->fail("Expected exception not thrown");
	    } catch(ODataException $ex) {
		    $this->assertEquals(
			    Messages::requestVersionTooLow(
				    $fakeConfigMaxVersion->toString(),
			        "2.0"
		        ),
			    $ex->getMessage()
		    );
		    $this->assertEquals(400, $ex->getStatusCode());
	    }


    }


	public function testGetResponseVersionConfigMaxVersion20NoClientMaxVersion()
	{
		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);


		Phockito::when($this->mockService->getConfiguration())
			->return($this->mockServiceConfiguration);

		$fakeConfigMaxVersion = new Version(2,0);
		Phockito::when($this->mockServiceConfiguration->getMaxDataServiceVersion())
			->return($fakeConfigMaxVersion);

		$fakeURL = new Url("http://host/service.svc/Collection");
		$fakeSegments = array(
			new SegmentDescriptor(),
		);

		$request = new RequestDescription($fakeSegments, $fakeURL, $fakeConfigMaxVersion, null, null);


		$this->assertEquals(new Version(1, 0), $request->getResponseVersion(), "Should default to max data service version of the config since max was not in header");

		$request->raiseResponseVersion(2, 0);
		$this->assertEquals(new Version(2, 0), $request->getResponseVersion(), "Response version should be upped from the raise");

		try{
			$request->raiseResponseVersion(3, 0);
			$this->fail("Expected exception not thrown");
		} catch(ODataException $ex) {
			$this->assertEquals(
				Messages::requestVersionTooLow(
					$fakeConfigMaxVersion->toString(),
					"3.0"
				),
				$ex->getMessage()
			);
			$this->assertEquals(400, $ex->getStatusCode());
		}


	}




}