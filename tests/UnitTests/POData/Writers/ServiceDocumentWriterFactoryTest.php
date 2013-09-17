<?php

namespace UnitTests\POData\Writers;

use POData\Common\Messages;
use POData\IService;
use POData\OperationContext\IServiceHost;
use POData\Common\Url;
use POData\Providers\MetadataQueryProviderWrapper;
use POData\ResponseFormat;
use POData\Writers\Atom\AtomServiceDocumentWriter;
use POData\Writers\Json\JsonServiceDocumentWriter;
use POData\Writers\ServiceDocumentWriterFactory;
use UnitTests\POData\BaseUnitTestCase;
use Phockito;

class ServiceDocumentWriterFactoryTest extends  BaseUnitTestCase {

	/**
	 * @var IService
	 */
	protected $mockService;


	/**
	 * @var IServiceHost
	 */
	protected $mockServiceHost;


	/**
	 * @var URL
	 */
	protected $mockServiceURI;

	/**
	 * @var MetadataQueryProviderWrapper
	 */
	protected $mockProvider;

	public function testGetWriterAtom()
	{
		Phockito::when($this->mockService->getMetadataQueryProviderWrapper())
			->return($this->mockProvider);

		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($this->mockServiceURI);

		$fakeUrl = "http://some/place/some/where";

		Phockito::when($this->mockServiceURI->getUrlAsString())
			->return($fakeUrl);

		$factory = new ServiceDocumentWriterFactory();

		$result = $factory->getWriter($this->mockService, ResponseFormat::ATOM());

		$this->assertTrue($result instanceof AtomServiceDocumentWriter);

	}

	public function testGetWriterPlanXML()
	{
		Phockito::when($this->mockService->getMetadataQueryProviderWrapper())
			->return($this->mockProvider);

		Phockito::when($this->mockService->getHost())
			->return($this->mockServiceHost);

		Phockito::when($this->mockServiceHost->getAbsoluteServiceUri())
			->return($this->mockServiceURI);

		$fakeUrl = "http://some/place/some/where";

		Phockito::when($this->mockServiceURI->getUrlAsString())
			->return($fakeUrl);

		$factory = new ServiceDocumentWriterFactory();

		$result = $factory->getWriter($this->mockService, ResponseFormat::PLAIN_XML());

		$this->assertTrue($result instanceof AtomServiceDocumentWriter);

	}


	public function testGetWriterJSON()
	{
		Phockito::when($this->mockService->getMetadataQueryProviderWrapper())
			->return($this->mockProvider);


		$factory = new ServiceDocumentWriterFactory();


		$result = $factory->getWriter($this->mockService, ResponseFormat::JSON());


		$this->assertTrue($result instanceof JsonServiceDocumentWriter);
	}


	public function testGetWriterUnknown()
	{
		$factory = new ServiceDocumentWriterFactory();

		try {
			$factory->getWriter($this->mockService, ResponseFormat::UNSUPPORTED());
			$this->fail("Expected exception not thrown");
		}
		catch(\Exception $ex){
			$this->assertEquals(Messages::badFormatForServiceDocument(ResponseFormat::UNSUPPORTED()), $ex->getMessage());
		}



	}
}