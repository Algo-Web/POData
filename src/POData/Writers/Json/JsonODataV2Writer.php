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
 * Class JsonODataV2Writer is a writer for the json format in OData V2 AKA JSON Verbose
 * @package POData\Writers\Json
 */
class JsonODataV2Writer extends JsonODataV1Writer
{

    /** 
     * begin write OData links
     * 
     * @param ODataURLCollection $urls url collection to write
     * 
     * @return JsonODataV2Writer
     */
    public function writeUrlCollection(ODataURLCollection $urls)
    {
        // {
        $this->_writer
	        ->startObjectScope()

            // Json Format V2:
            // "results":
            ->writeDataArrayName()

            // [
            ->startArrayScope();

        foreach ($urls->oDataUrls as $url) {
            $this->_writer
	            ->startObjectScope()
                ->writeName(ODataConstants::JSON_URI_STRING)
	            ->writeValue($url->oDataUrl)
	            ->endScope();
        }


	    // ]
	    $this->_writer->endScope();

	    $this->writeRowCount($urls->count);
	    $this->writeNextPageLink($urls->nextPageLink);
	    // }, End object scope for V2
	    $this->_writer->endScope();


	    return $this;
    }
  
    /**
     * Start writing a feed
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return JsonODataV2Writer
     */
    protected function writeBeginFeed(ODataFeed $feed)
    {

        // {
        $this->_writer
	        ->startObjectScope()
            // Json Format V2:
            // "results":
            ->writeDataArrayName()
	         // [
	        ->startArrayScope();

	    return $this;
    }
  

    /**
     * End writing feed
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return JsonODataV2Writer
     */
    protected function endFeed(ODataFeed $feed)
    {
        // ]
        $this->_writer->endScope();
    
        if ($feed->isTopLevel) {
            $this->writeRowCount($feed->rowCount);
        }
        $this->writeNextPageLink($feed->nextPageLink);

        // }, End object scope for V2
        $this->_writer->endScope();


	    return $this;
    }
  
    /**
     * Start writing a entry
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return JsonODataV2Writer
     */
    protected function writeBeginEntry(ODataEntry $entry)
    {
        if ($entry->isTopLevel) {
            // {
            $this->_writer->startObjectScope();

            // Json Format V2:
            // "results":
            $this->_writer->writeDataArrayName();
        }

        // {
        $this->_writer->startObjectScope();

	    return $this->writeEntryMetadata($entry);
    }
  

  
    /**
     * Write end of entry.
     *
     * @param ODataEntry $entry entry to end
     * 
     * @return JsonODataV2Writer
     */
    protected function endEntry(ODataEntry $entry)
    {
        // }
        $this->_writer->endScope();

        if ($entry->isTopLevel) {
			// }, End object scope for V2
            $this->_writer->endScope();
        }

	    return $this;
    }
  

    /**
     * Begin write property.
     *
     * @param ODataProperty $property property to write.
     * @param Boolean       $isTopLevel     is top level or not.
     * 
     * @return JsonODataV2Writer
     */
    protected function beginWriteProperty(ODataProperty $property, $isTopLevel)
    {
        if ($isTopLevel) {
            // {
            $this->_writer->startObjectScope();
            // Json Format V2:
            // "results":
            $this->_writer->writeDataArrayName();

            // {
            $this->_writer->startObjectScope();
        }

        $this->_writer->writeName($property->name);

	    return $this;
    }
  

    /**
     * End write property.
     *
     * @param ODataPropertyContent $property kind of operation to end
     * 
     * @return JsonODataV2Writer
     */
    protected function endWriteProperty(ODataPropertyContent $property)
    {   
        if ($property->isTopLevel) {
            // }
            $this->_writer->endScope();
            // }
            $this->_writer->endScope();

        }

	    return $this;
    }


	/**
	 * Writes the row count.
	 *
	 * @param int $count Row count value.
	 *
	 * @return JsonODataV2Writer
	 */
	protected function writeRowCount($count)
	{
		if ($count != null) {
			$this->_writer->writeName(ODataConstants::JSON_ROWCOUNT_STRING);
			$this->_writer->writeValue($count);
		}

		return $this;
	}


	/**
	 * Writes the next page link.
	 *
	 * @param ODataLink $nextPageLinkUri Uri for next page link.
	 *
	 * @return JsonODataV2Writer
	 */
	protected function writeNextPageLink(ODataLink $nextPageLinkUri = null)
	{
		// "__next" : uri
		if ($nextPageLinkUri != null) {
			$this->_writer
				->writeName(ODataConstants::JSON_NEXT_STRING)
				->writeValue($nextPageLinkUri->url);
		}

		return $this;
	}

}