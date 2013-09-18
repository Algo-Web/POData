<?php

namespace UnitTests\POData\Writers;

use POData\Common\Messages;
use POData\Common\Version;
use POData\IService;
use POData\OperationContext\IServiceHost;
use POData\Common\Url;
use POData\Providers\MetadataQueryProviderWrapper;
use POData\ResponseFormat;
use POData\Writers\Atom\AtomODataWriter;
use POData\Writers\Json\JsonODataV1Writer;
use POData\Writers\Json\JsonODataV2Writer;
use POData\Writers\ODataWriterFactory;
use UnitTests\POData\BaseUnitTestCase;
use POData\UriProcessor\RequestDescription;
use Phockito;

class ODataWriterFactoryTest extends  BaseUnitTestCase {

	/**
	 * @var IService
	 */
	protected $mockService;

	/**
	 * @var RequestDescription
	 */
	protected $mockRequest;

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

	public function testGetWriterAtomVersion1()
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

		$fakeVersion = new Version(1,0);

		Phockito::when($this->mockRequest->getResponseDataServiceVersion())
			->return($fakeVersion);

		$factory = new ODataWriterFactory();

		$result = $factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::ATOM());
		$this->assertTrue($result instanceof AtomODataWriter);
	}



	public function testGetWriterAtomVersion2()
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

		$fakeVersion = new Version(2,0);

		Phockito::when($this->mockRequest->getResponseDataServiceVersion())
			->return($fakeVersion);

		$factory = new ODataWriterFactory();

		$result = $factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::ATOM());
		$this->assertTrue($result instanceof AtomODataWriter);
	}

	public function testGetWriterAtomVersion3()
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

		$fakeVersion = new Version(3,0);

		Phockito::when($this->mockRequest->getResponseDataServiceVersion())
			->return($fakeVersion);

		$factory = new ODataWriterFactory();

		$result = $factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::ATOM());
		$this->assertTrue($result instanceof AtomODataWriter);
	}


	public function testGetWriterPlainXMLVersion1()
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

		$fakeVersion = new Version(1,0);

		Phockito::when($this->mockRequest->getResponseDataServiceVersion())
			->return($fakeVersion);

		$factory = new ODataWriterFactory();

		$result = $factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::PLAIN_XML());
		$this->assertTrue($result instanceof AtomODataWriter);
	}



	public function testGetWriterPlainXMLVersion2()
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

		$fakeVersion = new Version(2,0);

		Phockito::when($this->mockRequest->getResponseDataServiceVersion())
			->return($fakeVersion);

		$factory = new ODataWriterFactory();

		$result = $factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::PLAIN_XML());
		$this->assertTrue($result instanceof AtomODataWriter);
	}

	public function testGetWriterPlainXMLVersion3()
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

		$fakeVersion = new Version(3,0);

		Phockito::when($this->mockRequest->getResponseDataServiceVersion())
			->return($fakeVersion);

		$factory = new ODataWriterFactory();

		$result = $factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::PLAIN_XML());
		$this->assertTrue($result instanceof AtomODataWriter);
	}


	public function testGetWriterJsonVersion1()
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

		$fakeVersion = new Version(1,0);

		Phockito::when($this->mockRequest->getResponseDataServiceVersion())
			->return($fakeVersion);

		$factory = new ODataWriterFactory();

		$result = $factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::JSON());
		$this->assertTrue($result instanceof JsonODataV1Writer);
	}



	public function testGetWriterJsonVersion2()
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

		$fakeVersion = new Version(2,0);

		Phockito::when($this->mockRequest->getResponseDataServiceVersion())
			->return($fakeVersion);

		$factory = new ODataWriterFactory();

		$result = $factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::JSON());
		$this->assertTrue($result instanceof JsonODataV2Writer);
	}

	public function testGetWriterJsonVersion3()
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

		$fakeVersion = new Version(3,0);

		Phockito::when($this->mockRequest->getResponseDataServiceVersion())
			->return($fakeVersion);

		$factory = new ODataWriterFactory();

		$result = $factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::JSON());
		$this->assertTrue($result instanceof JsonODataV2Writer);
	}


	public function testGetWriterUnknown()
	{
		$factory = new ODataWriterFactory();

		try {
			$factory->getWriter($this->mockService, $this->mockRequest, ResponseFormat::UNSUPPORTED());
			$this->fail("Expected exception not thrown");
		}
		catch(\Exception $ex){
			$this->assertEquals(Messages::badFormatForResource(ResponseFormat::UNSUPPORTED()), $ex->getMessage());
		}

	}
}