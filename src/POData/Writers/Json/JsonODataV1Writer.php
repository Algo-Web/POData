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
use POData\Writers\Json\JsonWriter;
use POData\Writers\IODataWriter;
use POData\Common\Version;
use POData\Common\ODataConstants;
use POData\Common\MimeTypes;
use POData\Common\ODataException;
use POData\Providers\ProvidersWrapper;

/**
 * Class JsonODataV1Writer is a writer for the json format in OData V1
 * @package POData\Writers\Json
 */
class JsonODataV1Writer implements IODataWriter
{
    /**
     * Json output writer.
     *
     */
    protected $_writer;


    protected $urlKey = ODataConstants::JSON_URI_STRING;

    /**
     * Constructs and initializes the Json output writer.
     * 
     */
    public function __construct()
    {
        $this->_writer = new JsonWriter('');
    }

    /**
     * Determines if the given writer is capable of writing the response or not
     * @param Version $responseVersion the OData version of the response
     * @param string $contentType the Content Type of the response
     * @return boolean true if the writer can handle the response, false otherwise
     */
    public function canHandle(Version $responseVersion, $contentType)
    {
        if($responseVersion != Version::v1()){
            return false;
        }

        $parts = explode(";", $contentType);

        return in_array(MimeTypes::MIME_APPLICATION_JSON, $parts);
    }

    /**
     * Write the given OData model in a specific response format
     *
     * @param  ODataURL|ODataURLCollection|ODataPropertyContent|ODataFeed|ODataEntry $model Object of requested content.
     *
     * @return JsonODataV1Writer
     */
    public function write($model){
        // { "d" :
        $this->_writer
            ->startObjectScope()
            ->writeName("d");


        if ($model instanceof ODataURL) {
            $this->_writer->startObjectScope();
            $this->writeURL($model);
        } elseif ($model instanceof ODataURLCollection) {
            $this->_writer->startArrayScope();
            $this->writeURLCollection($model);
        } elseif ($model instanceof ODataPropertyContent) {
            $this->_writer->startObjectScope();
            $this->writeProperties($model);
        } elseif ($model instanceof ODataFeed) {
            $this->_writer->startArrayScope();
            $this->writeFeed($model);
        }elseif ($model instanceof ODataEntry) {
            $this->_writer->startObjectScope();
            $this->writeEntry($model);
        }


        $this->_writer->endScope();
        $this->_writer->endScope();

        return $this;
    }


    /**
     * @param ODataURL $url the url to write
     *
     * @return JsonODataV1Writer
     */
    public function writeUrl(ODataURL $url)
    {
        $this->_writer
            ->writeName($this->urlKey)
            ->writeValue($url->url);

        return $this;
    }

    /**
     * begin write OData links
     *
     * @param ODataURLCollection $urls url collection to write
     *
     * @return JsonODataV1Writer
     */
    public function writeUrlCollection(ODataURLCollection $urls)
    {
        foreach ($urls->urls as $url) {
            $this->_writer->startObjectScope();
            $this->writeUrl($url);
            $this->_writer->endScope();
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
    protected function writeFeed(ODataFeed $feed)
    {

        foreach ($feed->entries as $entry) {
            $this->_writer->startObjectScope();
            $this->writeEntry($entry);
            $this->_writer->endScope();
        }


        return $this;
    }




    /**
     * @param ODataEntry $entry Entry to write
     *
     * @return JsonODataV1Writer
     */
    protected function writeEntry(ODataEntry $entry)
    {

        $this->writeEntryMetadata($entry);
        foreach ($entry->links as $link) {
            $this->writeLink($link);
        }

        $this->writeProperties($entry->propertyContent);


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
                    ->writeName($this->urlKey)
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
     * @param ODataLink $link Link to write.
     *
     * @return JsonODataV1Writer
     */
    protected function writeLink(ODataLink $link)
    {

        // "<linkname>" :
        $this->_writer->writeName($link->title);

        if ($link->isExpanded) {
            if(is_null($link->expandedResult)){
                $this->_writer->writeValue("null");
            }
            else{
                $this->writeExpandedLink($link);
            }
        } else {
            $this->_writer
                ->startObjectScope()
                ->writeName(ODataConstants::JSON_DEFERRED_STRING)
                ->startObjectScope()
                ->writeName($this->urlKey)
                ->writeValue($link->url)
                ->endScope()
                ->endScope()
            ;
        }

        return $this;
    }

    protected function writeExpandedLink(ODataLink $link)
    {
        if ($link->isCollection) {
            $this->_writer->startArrayScope();
            $this->writeFeed($link->expandedResult);
        } else {
            $this->_writer->startObjectScope();
            $this->writeEntry($link->expandedResult);
        }

        $this->_writer->endScope();
    }


    /**
     * Write the given collection of properties.
     * (properties of an entity or complex type)
     *
     * @param ODataPropertyContent $properties Collection of properties.
     *
     * @return JsonODataV1Writer
     */
    protected function writeProperties(ODataPropertyContent $properties)
    {
        foreach ($properties->properties as $property) {

            $this->writePropertyMeta($property);
            $this->_writer->writeName($property->name);

            if ($property->value == null) {
                $this->_writer->writeValue("null");
            } elseif ($property->value instanceof ODataPropertyContent) {
                $this->writeComplexProperty($property);
            } elseif ($property->value instanceof ODataBagContent) {
                $this->writeBagContent($property->value);
            } else {
                $this->_writer->writeValue($property->value, $property->typeName);
            }

        }

        return $this;
    }


    protected function writePropertyMeta(ODataProperty $property)
    {
        return $this; //does nothing in v1 or v2, json light outputs stuff
    }

    /**
     * Begin write complex property.
     *
     * @param ODataProperty $property property to write.
     *
     * @return JsonODataV1Writer
     */
    protected function writeComplexProperty(ODataProperty $property)
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

        $this->writeProperties($property->value);

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
    protected function writeBagContent(ODataBagContent $bag)
    {

        $this->_writer
            ->startObjectScope() // {
            ->writeName(ODataConstants::JSON_METADATA_STRING) //__metadata : { Type : "typename" }
            ->startObjectScope()

            ->writeName(ODataConstants::JSON_TYPE_STRING)
            ->writeValue($bag->type)
            ->endScope()  // }
            ->writeName(ODataConstants::JSON_RESULT_NAME) // "__results":
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
            ->endScope()  // ]
            ->endScope(); // }
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
     * Get the Json final output.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->_writer->getJsonOutput();
    }


    /**
     * @param ProvidersWrapper $providers
     * @return IODataWriter
     */
    public function writeServiceDocument(ProvidersWrapper $providers) {
        $writer = $this->_writer;
        $writer
            ->startObjectScope() // {
            ->writeName("d") //  "d" :
            ->startObjectScope() // {
            ->writeName(ODataConstants::ENTITY_SET) // "EntitySets"
            ->startArrayScope() // [
        ;

        foreach ($providers->getResourceSets() as $resourceSetWrapper) {
            $writer->writeValue($resourceSetWrapper->getName());
        }

        $writer
            ->endScope() // ]
            ->endScope() // }
            ->endScope() // }
        ;

        return $this;

    }
}