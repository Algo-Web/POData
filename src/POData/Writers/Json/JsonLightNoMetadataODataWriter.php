<?php

namespace POData\Writers\Json;

use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataURLCollection;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataMediaLink;
use POData\Writers\Json\JsonWriter;
use POData\Writers\BaseODataWriter;
use POData\Common\Version;
use POData\Common\ODataConstants;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Common\InvalidOperationException;

/**
 * Class JsonLightNoMetadataODataWriter is a writer for the json format in OData V3 also known as JSON Light
 * @package POData\Writers\Json
 */
class JsonLightNoMetadataODataWriter extends JsonODataV1Writer
{
	public function __construct()
	{
		$this->_writer = new JsonWriter('', '"value" : ');
		$this->urlKey = ODataConstants::JSON_URL_STRING;
	}

	/**
	 * Write the given OData model in a specific response format
	 *
	 * @param  ODataURL|ODataURLCollection|ODataPropertyContent|ODataFeed|ODataEntry $model Object of requested content.
	 *
	 * @return JsonODataV1Writer
	 */
	public function write($model){

		//JSON Light doesn't output the wrapper for URLs
		if ($model instanceof ODataURL) {
			return $this->writeURL($model);
		}

		return parent::write($model);

	}

	/**
	 * NoMetdata means no metadata so this does nothing
	 *
	 * @param ODataEntry $entry Entry to write metadata for.
	 *
	 * @return JsonODataV1Writer
	 */
	protected function writeEntryMetadata(ODataEntry $entry){

	}

}