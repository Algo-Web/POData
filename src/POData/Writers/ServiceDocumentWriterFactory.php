<?php


namespace POData\Writers;


use POData\Common\Messages;
use POData\IService;
use POData\ResponseFormat;
use POData\Writers\Atom\AtomServiceDocumentWriter;
use POData\Writers\IServiceDocumentWriter;
use POData\Writers\Json\JsonServiceDocumentWriter;


class ServiceDocumentWriterFactory {

	/**
	 * @param IService $service
	 * @param ResponseFormat $format
	 *
	 * @return IServiceDocumentWriter
	 *
	 * @throws \Exception when the given format is not supported
	 */
	public function getWriter(IService $service, ResponseFormat $format)
	{

		switch($format){
			case ResponseFormat::ATOM():
			case ResponseFormat::PLAIN_XML():
				$serviceBaseAbsoluteURI = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
				return new AtomServiceDocumentWriter($service->getMetadataQueryProviderWrapper(), $serviceBaseAbsoluteURI);

			case ResponseFormat::JSON():
				return new JsonServiceDocumentWriter($service->getMetadataQueryProviderWrapper());

			default:
				throw new \Exception( Messages::badFormatForServiceDocument($format) );

		}
	}
}