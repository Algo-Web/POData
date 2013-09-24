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

use POData\Writers\Json\JsonLightMetadataLevel;


/**
 * Class JsonLightODataWriter is a writer for the json format in OData V3 also known as JSON Light
 * @package POData\Writers\Json
 */
class JsonLightODataWriter extends JsonODataV2Writer
{

	/**
	 * @var JsonLightMetadataLevel
	 */
	protected $metadataLevel;


	/**
	 *
	 * The service base uri
	 * @var string
	 */
	protected $baseUri;


	public function __construct(JsonLightMetadataLevel $metadataLevel, $absoluteServiceUri)
	{
		if(strlen($absoluteServiceUri) == 0)
		{
			throw new \Exception("absoluteServiceUri must not be empty or null");
		}
		$this->baseUri = $absoluteServiceUri;

		$this->_writer = new JsonWriter('');
		$this->urlKey = ODataConstants::JSON_URL_STRING;
		$this->dataArrayName = ODataConstants::JSON_LIGHT_VALUE_NAME;
		$this->metadataLevel = $metadataLevel;
	}


	protected function enterTopLevelScope($model)
	{
		if ($model instanceof ODataURL) {
			$this->_writer->startObjectScope();
		} else if ($model instanceof ODataURLCollection) {
			$this->_writer->startObjectScope();
		} elseif ($model instanceof ODataPropertyContent) {

		} elseif ($model instanceof ODataFeed) {
			$this->_writer->startObjectScope();
		} elseif ($model instanceof ODataEntry) {
			$this->_writer->startObjectScope();
		}

		return $this;
	}


	protected function leaveTopLevelScope()
	{
		$this->_writer->endScope();
		return $this;
	}

	/**
	 * @param ODataURL $url the url to write
	 *
	 * @return JsonLightODataWriter
	 */
	public function writeUrl(ODataURL $url)
	{
		switch($this->metadataLevel){

			case JsonLightMetadataLevel::NONE():
				break;


			case JsonLightMetadataLevel::MINIMAL():
				$this->_writer
					->writeName(ODataConstants::JSON_LIGHT_METADATA_STRING)
					->writeValue($url->oDataUrl);

				break;
		}

		return parent::writeUrl($url);
	}


	/**
	 *
	 * @param ODataEntry $entry Entry to write metadata for.
	 *
	 * @return JsonLightODataWriter
	 */
	protected function writeEntryMetadata(ODataEntry $entry){

		switch($this->metadataLevel){

			case JsonLightMetadataLevel::NONE():
				//No meta data means no meta data
				break;

		}

		return $this;
	}


	/**
	 * @param ODataLink $link Link to write.
	 *
	 * @return JsonLightODataWriter
	 */
	protected function writeBeginLink(ODataLink $link)
	{
		switch($this->metadataLevel){

			case JsonLightMetadataLevel::NONE():
				//No meta data means no meta data
				break;

		}

		/*
		// "<linkname>" :
		$this->_writer
			->writeName($link->title);

		if (!$link->expandedResult) {
			$this->_writer
				->startObjectScope()
				->writeName(ODataConstants::JSON_DEFERRED_STRING)
				->startObjectScope()
				->writeName($this->urlKey)
				->writeValue($link->url)
				->endScope()
			;
		}
        */
		return $this;
	}

	/**
	 * Write end of link.
	 *
	 * @param ODataLink $link the link to end
	 *
	 * @return JsonLightODataWriter
	 */
	public function writeEndLink(ODataLink $link)
	{
		switch($this->metadataLevel){

			case JsonLightMetadataLevel::NONE():
				//No meta data means no meta data
				break;

		}

		/*
		if (!$link->isExpanded) {
			// }
			$this->_writer->endScope();
		}
        */
		return $this;
	}


	/**
	 * Writes the row count for when $inlinecount is specified as allpages.
	 *
	 * @param int $count Row count value.
	 *
	 * @return JsonLightODataWriter
	 */
	protected function writeRowCount($count)
	{
		if ($count != null) {
			$this->_writer
				->writeName(ODataConstants::JSON_LIGHT_ROWCOUNT_STRING)
				->writeValue($count);
		}

		return $this;
	}


	/**
	 * Writes the next page link.
	 *
	 * @param ODataLink $nextPageLinkUri Uri for next page link.
	 *
	 * @return JsonLightODataWriter
	 */
	protected function writeNextPageLink(ODataLink $nextPageLinkUri = null)
	{
		switch($this->metadataLevel){

			case JsonLightMetadataLevel::NONE():
				//No meta data means no meta data
				break;

		}

		/*
		// "__next" : uri
		if ($nextPageLinkUri != null) {
			$this->_writer
				->writeName(ODataConstants::JSON_NEXT_STRING)
				->writeValue($nextPageLinkUri->url);
		}

		return $this;
		*/
	}


	/**
	 * Begin write complex property.
	 *
	 * @param ODataProperty $property property to write.
	 *
	 * @return JsonLightODataWriter
	 */
	protected function beginComplexProperty(ODataProperty $property)
	{
		// {
		$this->_writer->startObjectScope();


		/*
			// __metadata : { Type : "typename" }
			->writeName(ODataConstants::JSON_METADATA_STRING)
			->startObjectScope()
			->writeName(ODataConstants::JSON_TYPE_STRING)
			->writeValue($property->typeName)
			->endScope()
		*/


		switch($this->metadataLevel){

			case JsonLightMetadataLevel::NONE():
				//No meta data means no meta data
				break;

		}

		return $this;
	}


	/**
	 * End write complex property.
	 *
	 * @return JsonODataV1Writer
	 */
	protected function endComplexProperty()
	{
		// }
		$this->_writer->endScope();
		return $this;
	}

	/**
	 * Begin write property.
	 *
	 * @param ODataProperty $property property to write.
	 * @param Boolean       $isTopLevel     is top level or not.
	 *
	 * @return JsonLightODataWriter
	 */
	protected function beginWriteProperty(ODataProperty $property, $isTopLevel)
	{

		//JSON light doesn't output the property name
		//Complex looks like {  subProp1: X, subProp2 : Y}
		//Primitive looks like { value : X };
		if($isTopLevel){
			if($property->value instanceof ODataPropertyContent){
				return $this;
			}

			$this->_writer
				->startObjectScope()
				->writeName(ODataConstants::JSON_LIGHT_VALUE_NAME);

			return $this;

		}

		return parent::beginWriteProperty($property, $isTopLevel);
	}





	/**
	 * Begin an item in a collection
	 *
	 * @param ODataBagContent $bag bag property to write
	 *
	 * @return JsonLightODataWriter
	 */
	protected function writeBagContent(ODataBagContent $bag)
	{

		$this->_writer
			->startArrayScope(); // [

		foreach ($bag->propertyContents as $content) {
			if ($content instanceof ODataPropertyContent) {
				$this->_writer->startObjectScope();
				$this->writeProperties($content);
				$this->_writer->endScope();
			} else {
				// retrieving the collection datatype in order
				//to write in json specific format, with in chords or not
				preg_match('#\((.*?)\)#', $bag->type, $type);
				$this->_writer->writeValue($content, $type[1]);
			}
		}


		$this->_writer
			->endScope();  // ]
		return $this;
	}
}