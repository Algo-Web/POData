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
 * Class JsonODataV2Writer is a writer for the json format in OData V2
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
    protected function startUrlCollection(ODataURLCollection $urls)
    {
        $this->enterTopLevelScope();
        // {
        $this->_writer
	        ->startObjectScope()

            // Json Format V2:
            // "__results":
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

	    return $this;
    }
  
    /**
     * Start writing a feed
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return JsonODataV2Writer
     */
    protected function startFeed(ODataFeed $feed)
    {
        if ($feed->isTopLevel) {
            $this->enterTopLevelScope();
        }
    
        // {
        $this->_writer
	        ->startObjectScope()
            // Json Format V2:
            // "__results":
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

        if ($feed->isTopLevel) {
            $this->leaveTopLevelScope();
        }

	    return $this;
    }
  
    /**
     * Start writing a entry
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return JsonODataV2Writer
     */
    protected function startEntry(ODataEntry $entry)
    {
        if ($entry->isTopLevel) {
            $this->enterTopLevelScope();

            // {
            $this->_writer->startObjectScope();

            // Json Format V2:
            // "__results":
            $this->_writer->writeDataArrayName();
        }

        // {
        $this->_writer->startObjectScope();

	    return $this;
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
            $this->leaveTopLevelScope();
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
            $this->enterTopLevelScope();
            // {
            $this->_writer->startObjectScope();
            // Json Format V2:
            // "__results":
            $this->_writer->writeDataArrayName();

            // {
            $this->_writer->startObjectScope();
        }

        $this->_writer->writeName($property->name);

	    return $this;
    }
  

    /**
     * Write end of OData links
     * 
     * @param ODataURLCollection $urls odata url collection to end
     * 
     * @return JsonODataV2Writer
     */
    protected function endUrlCollection(ODataURLCollection $urls)
    {
        // ]
        $this->_writer->endScope();

        $this->writeRowCount($urls->count);
        $this->writeNextPageLink($urls->nextPageLink);
        // }, End object scope for V2
        $this->_writer->endScope();

        return $this->leaveTopLevelScope();
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

            $this->leaveTopLevelScope();
        }

	    return $this;
    }

}