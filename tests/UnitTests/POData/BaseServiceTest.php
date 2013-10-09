<?php


namespace UnitTests\POData\Common;


use Doctrine\Common\Annotations\Annotation\Target;
use POData\BaseService;
use POData\Common\Url;
use POData\Configuration\ProtocolVersion;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\UriProcessor;
use POData\OperationContext\ServiceHost;
use POData\Configuration\ServiceConfiguration;
use POData\Common\Version;
use POData\Providers\Metadata\IMetadataProvider;

use POData\Writers\ODataWriterRegistry;
use UnitTests\POData\BaseUnitTestCase;
use Phockito;


class BaseServiceTest extends BaseUnitTestCase {

	/** @var  RequestDescription */
	protected $mockRequest;

	/** @var  UriProcessor */
	protected $mockUriProcessor;


	/** @var  ODataWriterRegistry */
	protected $mockRegistry;

	/** @var  IMetadataProvider */
	protected $mockMetaProvider;

	/** @var  ServiceHost */
	protected $mockHost;



	public function testRegisterWritersV1()
	{
		/** @var BaseService $service */
		$service = Phockito::spy('\POData\BaseService');

		$service->setHost($this->mockHost);

		//TODO: have to do this since the registry & config is actually only instantiated during a handleRequest
		//will change this once that request pipeline is cleaned up
		Phockito::when($service->getODataWriterRegistry())->return($this->mockRegistry);
		$fakeConfig = new ServiceConfiguration($this->mockMetaProvider);
		$fakeConfig->setMaxDataServiceVersion(ProtocolVersion::V1());
		Phockito::when($service->getConfiguration())->return($fakeConfig);

		//fake the service url
		$fakeUrl = "http://host/service.svc/Collection";
		Phockito::when($this->mockHost->getAbsoluteServiceUri())->return(new Url($fakeUrl));

		Phockito::verify($this->mockRegistry, 0)->register(anything()); //nothing should be registered at first

		$service->registerWriters();

		//only 2 writers for v1
		Phockito::verify($this->mockRegistry, 2)->register(anything());
		Phockito::verify($this->mockRegistry, 1)->register(anInstanceOf('\POData\Writers\Atom\AtomODataWriter'));
		Phockito::verify($this->mockRegistry, 1)->register(anInstanceOf('\POData\Writers\Json\JsonODataV1Writer'));


	}

	public function testRegisterWritersV2()
	{
		/** @var BaseService $service */
		$service = Phockito::spy('\POData\BaseService');

		$service->setHost($this->mockHost);

		//TODO: have to do this since the registry & config is actually only instantiated during a handleRequest
		//will change this once that request pipeline is cleaned up
		Phockito::when($service->getODataWriterRegistry())->return($this->mockRegistry);
		$fakeConfig = new ServiceConfiguration($this->mockMetaProvider);
		$fakeConfig->setMaxDataServiceVersion(ProtocolVersion::V2());
		Phockito::when($service->getConfiguration())->return($fakeConfig);

		//fake the service url
		$fakeUrl = "http://host/service.svc/Collection";
		Phockito::when($this->mockHost->getAbsoluteServiceUri())->return(new Url($fakeUrl));

		Phockito::verify($this->mockRegistry, 0)->register(anything()); //nothing should be registered at first

		$service->registerWriters();

		//only 2 writers for v1
		Phockito::verify($this->mockRegistry, 3)->register(anything());
		Phockito::verify($this->mockRegistry, 1)->register(anInstanceOf('\POData\Writers\Atom\AtomODataWriter'));
		Phockito::verify($this->mockRegistry, 2)->register(anInstanceOf('\POData\Writers\Json\JsonODataV1Writer')); //since v2 derives from this,,it's 2 times
		Phockito::verify($this->mockRegistry, 1)->register(anInstanceOf('\POData\Writers\Json\JsonODataV2Writer'));


	}

	public function testRegisterWritersV3()
	{
		/** @var BaseService $service */
		$service = Phockito::spy('\POData\BaseService');

		$service->setHost($this->mockHost);

		//TODO: have to do this since the registry & config is actually only instantiated during a handleRequest
		//will change this once that request pipeline is cleaned up
		Phockito::when($service->getODataWriterRegistry())->return($this->mockRegistry);
		$fakeConfig = new ServiceConfiguration($this->mockMetaProvider);
		$fakeConfig->setMaxDataServiceVersion(ProtocolVersion::V3());
		Phockito::when($service->getConfiguration())->return($fakeConfig);

		//fake the service url
		$fakeUrl = "http://host/service.svc/Collection";
		Phockito::when($this->mockHost->getAbsoluteServiceUri())->return(new Url($fakeUrl));

		Phockito::verify($this->mockRegistry, 0)->register(anything()); //nothing should be registered at first

		$service->registerWriters();

		//only 2 writers for v1
		Phockito::verify($this->mockRegistry, 6)->register(anything());
		Phockito::verify($this->mockRegistry, 1)->register(anInstanceOf('\POData\Writers\Atom\AtomODataWriter'));
		Phockito::verify($this->mockRegistry, 5)->register(anInstanceOf('\POData\Writers\Json\JsonODataV1Writer')); //since v2 & light derives from this,,it's 1+1+3 times
		Phockito::verify($this->mockRegistry, 4)->register(anInstanceOf('\POData\Writers\Json\JsonODataV2Writer')); //since light derives from this it's 1+3 times
		Phockito::verify($this->mockRegistry, 3)->register(anInstanceOf('\POData\Writers\Json\JsonLightODataWriter'));


	}



}