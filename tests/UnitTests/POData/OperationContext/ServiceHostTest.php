<?php

namespace UnitTests\POData\OperationContext\Web;

use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\IncomingRequest;
use UnitTests\POData\BaseUnitTestCase;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\IHTTPRequest;
use POData\Common\ODataConstants;


use Phockito;

class ServiceHostTest extends BaseUnitTestCase {


	/** @var  IOperationContext */
	protected $mockOperationContext;

	/** @var  IHTTPRequest */
	protected $mockHTTPRequest;

    public function testProcessFormatOptionWithFormatOfJSON()
    {
		Phockito::when($this->mockOperationContext->incomingRequest())
			->return($this->mockHTTPRequest);

	    $fakeURL = "http://host/service.svc/Collection";
	    Phockito::when($this->mockHTTPRequest->getRawUrl())
		    ->return($fakeURL);

	    $fakeQueryParameters = array(
			array(
				'$format' => 'json'
			)
	    );
	    Phockito::when($this->mockHTTPRequest->getQueryParameters())
		    ->return($fakeQueryParameters);

	    $host = new ServiceHost($this->mockOperationContext);

	    $host->processFormatOption();

	    //because $format was specified, the setRequestAccept should have been called to override the header info
	    Phockito::verify($this->mockHTTPRequest, 1)->setRequestAccept(ODataConstants::MIME_APPLICATION_JSON . ';q=1.0');
    }


	public function testProcessFormatOptionWithFormatOfAtom()
	{
		Phockito::when($this->mockOperationContext->incomingRequest())
			->return($this->mockHTTPRequest);

		$fakeURL = "http://host/service.svc/Collection";
		Phockito::when($this->mockHTTPRequest->getRawUrl())
			->return($fakeURL);

		$fakeQueryParameters = array(
			array(
				'$format' => 'atom'
			)
		);
		Phockito::when($this->mockHTTPRequest->getQueryParameters())
			->return($fakeQueryParameters);

		$host = new ServiceHost($this->mockOperationContext);

		$host->processFormatOption();

		//because $format was specified, the setRequestAccept should have been called to override the header info
		Phockito::verify($this->mockHTTPRequest, 1)->setRequestAccept(ODataConstants::MIME_APPLICATION_ATOM . ';q=1.0');
	}

}