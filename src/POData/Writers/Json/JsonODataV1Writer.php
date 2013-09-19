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
 * Class JsonODataV1Writer is a writer for the json format in OData V1
 * @package POData\Writers\Json
 */
class JsonODataV1Writer extends BaseODataWriter
{
    /**
     * Json output writer.
     *
     */
    protected $_writer;
  
    /**
     * Constructs and initializes the Json output writer.
     * 
     */
    public function __construct()
    {
        $this->_writer = new JsonWriter('');
    }
  
    /**
     * Enter the top level scope.
     *
     * @return JsonODataV1Writer
     */
    protected function enterTopLevelScope()
    {
        // { "d" :
        $this->_writer
	        ->startObjectScope()
            ->writeDataWrapper();

	    return $this;
    }
  
    /**
     * Leave the top level scope.
     * 
     * @return JsonODataV1Writer
     */
    protected function leaveTopLevelScope()
    {
        // }
        $this->_writer->endScope();
	    return $this;
    }
  
    /**
     * @param ODataURL $url OData url to write
     * 
     * @return JsonODataV1Writer
     */
    protected function startUrl(ODataURL $url)
    {
        $this->enterTopLevelScope();
        $this->_writer
	        ->startObjectScope()
            ->writeName(ODataConstants::JSON_URI_STRING)
	        ->writeValue($url->oDataUrl)
	        ->endScope()
	        ->endScope();

	    return $this;
    }
    
    /** 
     * begin write OData links
     * 
     * @param ODataURLCollection $urls url collection to write
     * 
     * @return JsonODataV1Writer
     */
    protected function startUrlCollection(ODataURLCollection $urls)
    {
        $this->enterTopLevelScope();
        // [
        $this->_writer->startArrayScope();
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
     * @return JsonODataV1Writer
     */
    protected function startFeed(ODataFeed $feed)
    {
        if ($feed->isTopLevel) {
            $this->enterTopLevelScope();
        }
    

        // [
        $this->_writer->startArrayScope();

	    return $this;
    }
  
    /**
     * Write feed metadata
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return JsonODataV1Writer
     */
    protected function writeFeedMetadata(ODataFeed $feed)
    {
	    return $this;
    }
  
    /**
     * End writing feed
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return JsonODataV1Writer
     */
    protected function endFeed(ODataFeed $feed)
    {
        // ]
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
     * @return JsonODataV1Writer
     */
    protected function startEntry(ODataEntry $entry)
    {
        if ($entry->isTopLevel) {
            $this->enterTopLevelScope();

        }

        // {
        $this->_writer->startObjectScope();

	    return $this;
    }
  
    /**
     * Write metadata information for the entry.
     *
     * @param ODataEntry $entry Entry to write metadata for.
     * 
     * @return JsonODataV1Writer
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

	    return $this;
    }
  
    /**
     * Write end of entry.
     *
     * @param ODataEntry $entry entry to end
     * 
     * @return JsonODataV1Writer
     */
    protected function endEntry(ODataEntry $entry)
    {
        // }
        $this->_writer->endScope();

        if ($entry->isTopLevel) {

            $this->leaveTopLevelScope();
        }

	    return $this;
    }
  
    /**
     * Start writing a link.
     *
     * @param ODataLink $link Link to write
     * @param Boolean   $isExpanded expanded or not
     * 
     * @return JsonODataV1Writer
     */
    protected function startLink(ODataLink $link, $isExpanded)
    {
        // "<linkname>" :
        $this->_writer->writeName($link->title);

	    return $this;
    }
  
    /**
     * Start writing a link metadata.
     *
     * @param ODataLink $link Link to write
     * @param Boolean   $isExpanded expanded or not
     * 
     * @return JsonODataV1Writer
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

	    return $this;
    }
  
    /**
     * Write end of link.
     *
     * @param Boolean $isExpanded expanded or not
     * 
     * @return JsonODataV1Writer
     */
    protected function endLink($isExpanded)
    {
        if (!$isExpanded) {
            // }
            $this->_writer->endScope();
        }

	    return $this;
    }
  

    /**
     * Pre Write Properties. For this writer this means do nothing
     *
     * @param ODataEntry $entry OData entry to write.
     *
     * @return JsonODataV1Writer
     */
    public function preWriteProperties(ODataEntry $entry)
    {
	    return $this;
    }

    /**
     * Begin write property.
     *
     * @param ODataProperty $property property to write.
     * @param Boolean       $isTopLevel     is top level or not.
     *
     * @return JsonODataV1Writer
     */
    protected function beginWriteProperty(ODataProperty $property, $isTopLevel)
    {
        if ($isTopLevel) {
            $this->enterTopLevelScope();
            // {
            $this->_writer->startObjectScope();
        }

        $this->_writer->writeName($property->name);

	    return $this;
    }
  
    /**
     * Begin write complex property.
     *
     * @param ODataProperty $property property to write.
     * 
     * @return JsonODataV1Writer
     */
    protected function beginComplexProperty(ODataProperty $property)
    {

        $this->_writer
	        // {
	        ->startObjectScope()

	        // __metadata : { Type : "typename" }
	        ->writeName(ODataConstants::JSON_METADATA_STRING)
	        ->startObjectScope()
	        ->writeName(ODataConstants::JSON_TYPE_STRING)
	        ->writeValue($property->typeName)
	        ->endScope();

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
     * Begin an item in a collection
     *  
     * @param ODataBagContent $bag bag property to write
     * 
     * @return JsonODataV1Writer
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
	    return $this;
    }
    
    /**
     * End an item in a collection
     *
     * @return JsonODataV1Writer
     */
    protected function endBagPropertyItem()
    {
        // }
        $this->_writer->endScope();
	    return $this;
    }
    
    /**
     * Write end of OData url
     * 
     * @param ODataURL $url OData url to end
     * 
     * @return JsonODataV1Writer
     */
    protected function endUrl(ODataURL $url)
    {
	    return $this;
    }
    
    /**
     * Write end of OData links
     * 
     * @param ODataURLCollection $urls odata url collection to end
     * 
     * @return JsonODataV1Writer
     */
    protected function endUrlCollection(ODataURLCollection $urls)
    {
        // ]
        $this->_writer->endScope();

        return $this->leaveTopLevelScope();
    }
     
    /**
     * End write property.
     *
     * @param ODataPropertyContent $property kind of operation to end
     * 
     * @return JsonODataV1Writer
     */
    protected function endWriteProperty(ODataPropertyContent $property)
    {   
        if ($property->isTopLevel) {
            // }
            $this->_writer->endScope();

            $this->leaveTopLevelScope();
        }

	    return $this;
    }
  
    /**
     * post write properties
     *
     * @param ODataEntry $entry OData entry
     *
     * @return JsonODataV1Writer
     */
    public function postWriteProperties(ODataEntry $entry)
    {
	    return $this;
    }
  
    /**
     * write null value.
     *
     * @param ODataProperty $property odata property
     *
     * @return JsonODataV1Writer
     */
    protected function writeNullValue(ODataProperty $property)
    {
        $this->_writer->writeValue("null");
	    return $this;
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
     * @return JsonODataV1Writer
     */
    protected function writePrimitiveValue(ODataProperty $property)
    {
        $this->_writer->writeValue($property->value, $property->typeName);
	    return $this;
    }
  
    /**
     * Get the Json final output.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->_writer->getJsonOutput();
    }
}