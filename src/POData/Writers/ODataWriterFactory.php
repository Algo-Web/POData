<?php

namespace POData\Writers;

use POData\IService;
use POData\ResponseFormat;
use POData\UriProcessor\RequestDescription;
use POData\Writers\Atom\AtomODataWriter;
use POData\Common\Messages;
use POData\Writers\Json\JsonODataV1Writer;
use POData\Writers\Json\JsonODataV2Writer;
use POData\Common\Version;
use POData\Writers\IODataWriter;

/**
 * Class ODataWriterFactory
 * @package POData\Writers\Common
 */
class ODataWriterFactory
{
	/**
	 * @param IService $service
	 * @param RequestDescription $request
	 * @param ResponseFormat $format
	 * @return IODataWriter
	 * @throws \Exception when the response format is invalid
	 */
	public function getWriter(IService $service, RequestDescription $request, ResponseFormat $format){
	    switch($format){
		    case ResponseFormat::ATOM():
		    case ResponseFormat::PLAIN_XML():
				//NOTE: the version of OData is irrelevant to the atom format
		        $serviceBaseAbsoluteURI = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
				return new AtomODataWriter($serviceBaseAbsoluteURI);

		    case ResponseFormat::JSON():
			    $isPostV1 = ($request->getResponseDataServiceVersion()->compare(new Version(1, 0)) == 1);
			    return $isPostV1 ? new JsonODataV2Writer() : new JsonODataV1Writer();

			default:
		        throw new \Exception( Messages::badFormatForResource($format) );

	    }

    }


}