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
 * Class JsonODataWriter
 * @package POData\Writers\Json
 */
class JsonODataWriter extends BaseODataWriter
{
    /**
     * Json output writer.
     *
     */
    private $_writer;
  
    /**
     * Odata version.
     *      
     */
    private $_isPostV1;
  
    /**
     * Constructs and initializes the Json output writer.
     * 
     * @param String  $absoluteServiceUri Absolute url
     * @param Boolean $isPostV1           OData version above to v1 or not
     * 
     * @return Void
     */
    public function __construct($absoluteServiceUri, $isPostV1)
    {
        $this->_writer = new JsonWriter('');
        $this->_isPostV1 = $isPostV1;
    }
  
    /**
     * Enter the top level scope.
     *
     * @return void
     */
    protected function enterTopLevelScope()
    {
        // { "d" :
        $this->_writer->startObjectScope();
        $this->_writer->writeDataWrapper();
    }
  
    /**
     * Leave the top level scope.
     * 
     * @return void
     */
    protected function leaveTopLevelScope()
    {
        // }
        $this->_writer->endScope();
    }
  
    /**
     * @param ODataURL $url OData url to write
     * 
     * @return void
     */
    protected function startUrl(ODataURL $url)
    {
        $this->enterTopLevelScope();
        $this->_writer->startObjectScope();
      
        $this->_writer->writeName(ODataConstants::JSON_URI_STRING);
        $this->_writer->writeValue($url->oDataUrl);
      
        $this->_writer->endScope();
        $this->_writer->endScope();
    }
    
    /** 
     * begin write OData links
     * 
     * @param ODataURLCollection $urls url collection to write
     * 
     * @return void
     */
    protected function startUrlCollection(ODataURLCollection $urls)
    {
        $this->enterTopLevelScope();
        if ($this->_isPostV1) {
            // {
            $this->_writer->startObjectScope();
      
            // Json Format V2:
            // "__results":
            $this->_writer->writeDataArrayName();
        }
        // [
        $this->_writer->startArrayScope();
        foreach ($urls->oDataUrls as $url) {
            $this->_writer
	            ->startObjectScope()
                ->writeName(ODataConstants::JSON_URI_STRING)
	            ->writeValue($url->oDataUrl)
	            ->endScope();
        }
    }
  
    /**
     * Start writing a feed
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return void 
     */
    protected function startFeed(ODataFeed $feed)
    {
        if ($feed->isTopLevel) {
            $this->enterTopLevelScope();
        }
    
        if ($this->_isPostV1) {
            // {
            $this->_writer->startObjectScope();
            // Json Format V2:
            // "__results":
            $this->_writer->writeDataArrayName();
        }

        // [
        $this->_writer->startArrayScope();
    }
  
    /**
     * Write feed metadata
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return void 
     */
    protected function writeFeedMetadata(ODataFeed $feed)
    {    
    }
  
    /**
     * End writing feed
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return void 
     */
    protected function endFeed(ODataFeed $feed)
    {
        // ]
        $this->_writer->endScope();
    
        if ($this->_isPostV1) {
            if ($feed->isTopLevel) {
                $this->writeRowCount($feed->rowCount);
            }
            $this->writeNextPageLink($feed->nextPageLink);

            // }, End object scope for V2
            $this->_writer->endScope();
        }
        if ($feed->isTopLevel) {
            $this->leaveTopLevelScope();
        }
    }
  
    /**
     * Start writing a entry
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return void 
     */
    protected function startEntry(ODataEntry $entry)
    {
        if ($entry->isTopLevel) {
            $this->enterTopLevelScope();

            if ($this->_isPostV1) {
                // {
                $this->_writer->startObjectScope();

                // Json Format V2:
                // "__results":
                $this->_writer->writeDataArrayName();
            }
        }

        // {
        $this->_writer->startObjectScope();  
    }
  
    /**
     * Write metadata information for the entry.
     *
     * @param ODataEntry $entry Entry to write metadata for.
     * 
     * @return void 
     */
    protected function writeEntryMetadata(ODataEntry $entry)
    {
        // __metadata : { uri: "Uri", type: "Type" [Media Link Properties] }
        if ($entry->id != null || $entry->type != null || $entry->eTag != null) {
            // "__metadata"
            $this->_writer
	            ->writeName(ODataConstants::JSON_METADATA_STRING)
                ->startObjectScope();

            // Write uri value only for entity types
            if ($entry->id != null) {
                $this->_writer
	                ->writeName(ODataConstants::JSON_URI_STRING)
                    ->writeValue($entry->id);
            }
        
            // Write the etag property, if the entry has etag properties.
            if ($entry->eTag != null) {
                $this->_writer
	                ->writeName(ODataConstants::JSON_ETAG_STRING)
                    ->writeValue($entry->eTag);
            }

            // Write the type property, if the entry has type properties.
            if ($entry->type != null) {
                $this->_writer
	                ->writeName(ODataConstants::JSON_TYPE_STRING)
	                ->writeValue($entry->type);
            }
        }
      
        // Media links.
        if ($entry->isMediaLinkEntry) {
            if ($entry->mediaLink != null) {
                $this->_writer
	                ->writeName(ODataConstants::JSON_EDITMEDIA_STRING)
	                ->writeValue($entry->mediaLink->editLink)

	                ->writeName(ODataConstants::JSON_MEDIASRC_STRING)
	                ->writeValue($entry->mediaLink->srcLink)

	                ->writeName(ODataConstants::JSON_CONTENTTYPE_STRING)
	                ->writeValue($entry->mediaLink->contentType);

                if ($entry->mediaLink->eTag != null) {
                    $this->_writer
	                    ->writeName(ODataConstants::JSON_MEDIAETAG_STRING)
	                    ->writeValue($entry->mediaLink->eTag);
                }
          
                $this->_writer->endScope();
            }

            // writing named resource streams
            foreach ($entry->mediaLinks as $mediaLink) {
                $this->_writer
	                ->writeName($mediaLink->name)
	                ->startObjectScope()

	                ->writeName(ODataConstants::JSON_MEDIASRC_STRING)
	                ->writeValue($mediaLink->srcLink)

	                ->writeName(ODataConstants::JSON_CONTENTTYPE_STRING)
	                ->writeValue($mediaLink->contentType);

                if ($mediaLink->eTag != null) {
                    $this->_writer
	                    ->writeName(ODataConstants::JSON_MEDIAETAG_STRING)
	                    ->writeValue($mediaLink->eTag);
                }

                $this->_writer->endScope();
            }
        } else { 
            $this->_writer->endScope();
        }
    }
  
    /**
     * Write end of entry.
     *
     * @param ODataEntry $entry entry to end
     * 
     * @return void  
     */
    protected function endEntry(ODataEntry $entry)
    {
        // }
        $this->_writer->endScope();

        if ($entry->isTopLevel) {
            if ($this->_isPostV1) {
                // }, End object scope for V2
                $this->_writer->endScope();
            }

            $this->leaveTopLevelScope();
        }
    }
  
    /**
     * Start writing a link.
     *
     * @param ODataLink $link Link to write
     * @param Boolean   $isExpanded expanded or not
     * 
     * @return void  
     */
    protected function startLink(ODataLink $link, $isExpanded)
    {
        // "<linkname>" :
        $this->_writer->writeName($link->title);
    }
  
    /**
     * Start writing a link metadata.
     *
     * @param ODataLink $link Link to write
     * @param Boolean   $isExpanded expanded or not
     * 
     * @return void  
     */
    protected function writeLinkMetadata(ODataLink $link, $isExpanded)
    {
        if (!$link->expandedResult) {
            $this->_writer
	            ->startObjectScope()
	            ->writeName(ODataConstants::JSON_DEFERRED_STRING)
	            ->startObjectScope()
	            ->writeName(ODataConstants::JSON_URI_STRING)
	            ->writeValue($link->url)
	            ->endScope()
            ;
        }
    }
  
    /**
     * Write end of link.
     *
     * @param Boolean $isExpanded expanded or not
     * 
     * @return void 
     */
    protected function endLink($isExpanded)
    {
        if (!$isExpanded) {
            // }
            $this->_writer->endScope();
        }
    }
  
    /**
     * Writes the row count.
     *
     * @param int $count Row count value.
     * 
     * @return void
     */
    protected function writeRowCount($count)
    {
        if ($count != null) {
            $this->_writer->writeName(ODataConstants::JSON_ROWCOUNT_STRING);
            $this->_writer->writeValue($count);
        }
    }
  
    /**
     * Writes the next page link.
     *
     * @param string $nextPageLinkUri Uri for next page link.
     * 
     * @return void
     */
    protected function writeNextPageLink($nextPageLinkUri)
    {
        // "__next" : uri 
        if ($nextPageLinkUri != null) {
            $this->_writer->writeName(ODataConstants::JSON_NEXT_STRING);
            $this->_writer->writeValue($nextPageLinkUri->url);
        }
    }
  
    /**
     * Pre Write Properties. For this writer this means do nothing
     *
     * @param ODataEntry $entry OData entry to write.
     * 
     * @return void 
     */
    public function preWriteProperties(ODataEntry $entry)
    {
    }
  
    /**
     * Begin write property.
     *
     * @param ODataProperty $property property to write.
     * @param Boolean       $isTopLevel     is top level or not.
     * 
     * @return void 
     */
    protected function beginWriteProperty(ODataProperty $property, $isTopLevel)
    {
        if ($isTopLevel) {
            $this->enterTopLevelScope();
            if ($this->_isPostV1) {
                // {
                $this->_writer->startObjectScope();
                // Json Format V2:
                // "__results":
                $this->_writer->writeDataArrayName();
            }

            // {
            $this->_writer->startObjectScope();
        }

        $this->_writer->writeName($property->name);
    }
  
    /**
     * Begin write complex property.
     *
     * @param ODataProperty $property property to write.
     * 
     * @return void 
     */
    protected function beginComplexProperty(ODataProperty $property)
    {
        // {
        $this->_writer->startObjectScope();

        // __metadata : { Type : "typename" }
        $this->_writer->writeName(ODataConstants::JSON_METADATA_STRING);
        $this->_writer->startObjectScope();

        $this->_writer->writeName(ODataConstants::JSON_TYPE_STRING);
        $this->_writer->writeValue($property->typeName);

        $this->_writer->endScope();
    }
  
    /**
     * End write complex property.
     *
     * @return void
     */
    protected function endComplexProperty()
    {
        // }
        $this->_writer->endScope();
    }
  
    /**
     * Begin an item in a collection
     *  
     * @param ODataBagContent $bag bag property to write
     * 
     * @return void 
     */
    protected function beginBagPropertyItem(ODataBagContent $bag)
    {

        $this->_writer
	        ->startObjectScope() // {
	        ->writeName(ODataConstants::JSON_METADATA_STRING) //__metadata : { Type : "typename" }
            ->startObjectScope()

            ->writeName(ODataConstants::JSON_TYPE_STRING)
            ->writeValue($bag->type)
            ->endScope()  // }
	        ->writeDataArrayName() // "__results":
	    	->startArrayScope(); // [

        foreach ($bag->propertyContents as $content) {
            if ($content instanceof ODataPropertyContent) {
                $this->_writer->startObjectScope();
                $this->writeBeginProperties($content);
                $this->_writer->endScope();
            } else {
                // retrieving the collection datatype in order 
                //to write in json specific format, with in chords or not
                preg_match('#\((.*?)\)#', $bag->type, $type);
                $this->_writer->writeValue($content, $type[1]);
            }
        }

        // ]
        $this->_writer->endScope();
    }
    
    /**
     * End an item in a collection
     * 
     * @return void 
     */
    protected function endBagPropertyItem()
    {
        // }
        $this->_writer->endScope();
    }
    
    /**
     * Write end of OData url
     * 
     * @param ODataURL $url OData url to end
     * 
     * @return void 
     */
    protected function endUrl(ODataURL $url)
    {
      
    }
    
    /**
     * Write end of OData links
     * 
     * @param ODataURLCollection $urls odata url collection to end
     * 
     * @return void 
     */
    protected function endUrlCollection(ODataURLCollection $urls)
    {
        // ]
        $this->_writer->endScope();

        if ($this->_isPostV1) {
            $this->writeRowCount($urls->count);
            $this->writeNextPageLink($urls->nextPageLink);
            // }, End object scope for V2
            $this->_writer->endScope();
        }

        $this->leaveTopLevelScope();
    }
     
    /**
     * End write property.
     *
     * @param ODataPropertyContent $property kind of operation to end
     * 
     * @return void 
     */
    protected function endWriteProperty(ODataPropertyContent $property)
    {   
        if ($property->isTopLevel) {
            // }
            $this->_writer->endScope();
            if ($this->_isPostV1) {
                // }
                $this->_writer->endScope();
            }

            $this->leaveTopLevelScope();
        } 
    }
  
    /**
     * post write properties
     *
     * @param ODataEntry $entry OData entry
     *
     * @return void 
     */
    public function postWriteProperties(ODataEntry $entry)
    {
    }
  
    /**
     * write null value.
     *
     * @param ODataProperty $property odata property
     *
     * @return void 
     */
    protected function writeNullValue(ODataProperty $property)
    {
        $this->_writer->writeValue("null");
    }
  
    /**
     * serialize exception.
     * 
     * @param ODataException $exception              Exception to serialize
     * @param Boolean        $serializeInnerException if set to true
     * 
     * serialize the inner exception if $exception is an ODataException.
     * 
     * @return string
     */
    public static function serializeException(ODataException $exception, $serializeInnerException)
    {
        $writer = new JsonWriter('');
        // Wrapper for error.
        $writer
	        ->startObjectScope()
            ->writeName(ODataConstants::JSON_ERROR) // "error"
            ->startObjectScope();

        // "code"
        if ($exception->getCode() != null) {
            $writer
	            ->writeName(ODataConstants::JSON_ERROR_CODE)
	            ->writeValue($exception->getCode());
        }

        // "message"
        $writer
	        ->writeName(ODataConstants::JSON_ERROR_MESSAGE)
	        ->startObjectScope()
	        ->writeName(ODataConstants::XML_LANG_ATTRIBUTE_NAME) // "lang"
	        ->writeValue('en-US')
            ->writeName(ODataConstants::JSON_ERROR_VALUE)
	        ->writeValue($exception->getMessage())

	        ->endScope()
	        ->endScope()
	        ->endScope();

        return $writer->getJsonOutput();
    }
  
    /**
     * attempts to convert the specified primitive value to a serializable string.
     *
     * @param ODataProperty $property value to convert.
     * 
     * @return void 
     */
    protected function writePrimitiveValue(ODataProperty $property)
    {
        $this->_writer->writeValue($property->value, $property->typeName);
    }
  
    /**
     * Get the Json final output.
     *
     * @return string
     */
    protected function getOutput()
    {
        return $this->_writer->getJsonOutput();
    }
}