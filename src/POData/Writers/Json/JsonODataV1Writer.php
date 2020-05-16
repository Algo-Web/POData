<?php

declare(strict_types=1);

namespace POData\Writers\Json;

use Exception;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Version;
use POData\Configuration\ServiceConfiguration;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\Providers\ProvidersWrapper;
use POData\Writers\IODataWriter;

/**
 * Class JsonODataV1Writer is a writer for the json format in OData V1.
 */
class JsonODataV1Writer implements IODataWriter
{
    /**
     * Json output writer.
     */
    protected $writer;

    protected $urlKey = ODataConstants::JSON_URI_STRING;

    /**
     * Constructs and initializes the Json output writer.
     */
    public function __construct(string $eol, bool $prettyPrint)
    {
        $this->writer = new JsonWriter('', $eol, $prettyPrint);
    }

    /**
     * serialize exception.
     *
     * @param ODataException $exception Exception to serialize
     *
     * serialize the inner exception if $exception is an ODataException
     *
     * @throws Exception
     * @return string
     */
    public static function serializeException(ODataException $exception, ServiceConfiguration $config)
    {
        $writer = new JsonWriter('', $config->getLineEndings(), $config->getPrettyOutput());
        // Wrapper for error.
        $writer
            ->startObjectScope()
            ->writeName(ODataConstants::JSON_ERROR)// "error"
            ->startObjectScope();

        // "code"
        if (null !== $exception->getCode()) {
            $writer
                ->writeName(ODataConstants::JSON_ERROR_CODE)
                ->writeValue($exception->getCode());
        }

        // "message"
        $writer
            ->writeName(ODataConstants::JSON_ERROR_MESSAGE)
            ->startObjectScope()
            ->writeName(ODataConstants::XML_LANG_ATTRIBUTE_NAME)// "lang"
            ->writeValue('en-US')
            ->writeName(ODataConstants::JSON_ERROR_VALUE)
            ->writeValue($exception->getMessage())
            ->endScope()
            ->endScope()
            ->endScope();

        return $writer->getJsonOutput();
    }

    /**
     * Determines if the given writer is capable of writing the response or not.
     *
     * @param Version $responseVersion the OData version of the response
     * @param string  $contentType     the Content Type of the response
     *
     * @return bool true if the writer can handle the response, false otherwise
     */
    public function canHandle(Version $responseVersion, $contentType)
    {
        if ($responseVersion != Version::v1()) {
            return false;
        }

        $parts = explode(';', $contentType);

        return in_array(MimeTypes::MIME_APPLICATION_JSON, $parts);
    }

    /**
     * Write the given OData model in a specific response format.
     *
     * @param ODataURL|ODataURLCollection|ODataPropertyContent|ODataFeed|ODataEntry $model Object of requested content
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    public function write($model)
    {
        // { "d" :
        $this->writer
            ->startObjectScope()
            ->writeName('d');

        if ($model instanceof ODataURL) {
            $this->writer->startObjectScope();
            $this->writeUrl($model);
        } elseif ($model instanceof ODataURLCollection) {
            $this->writer->startArrayScope();
            $this->writeUrlCollection($model);
        } elseif ($model instanceof ODataPropertyContent) {
            $this->writer->startObjectScope();
            $this->writeProperties($model);
        } elseif ($model instanceof ODataFeed) {
            $this->writer->startArrayScope();
            $this->writeFeed($model);
        } elseif ($model instanceof ODataEntry) {
            $this->writer->startObjectScope();
            $this->writeEntry($model);
        }

        $this->writer->endScope();
        $this->writer->endScope();

        return $this;
    }

    /**
     * @param ODataURL $url the url to write
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    public function writeUrl(ODataURL $url)
    {
        $this->writer
            ->writeName($this->urlKey)
            ->writeValue($url->url);

        return $this;
    }

    /**
     * begin write OData links.
     *
     * @param ODataURLCollection $urls url collection to write
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    public function writeUrlCollection(ODataURLCollection $urls)
    {
        foreach ($urls->urls as $url) {
            $this->writer->startObjectScope();
            $this->writeUrl($url);
            $this->writer->endScope();
        }

        return $this;
    }

    /**
     * Write the given collection of properties.
     * (properties of an entity or complex type).
     *
     * @param ODataPropertyContent $properties Collection of properties
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    protected function writeProperties(ODataPropertyContent $properties = null)
    {
        if (null !== $properties) {
            foreach ($properties->properties as $property) {
                $this->writePropertyMeta($property);
                $this->writer->writeName($property->name);

                if ($property->value == null) {
                    $this->writer->writeValue('null');
                } elseif ($property->value instanceof ODataPropertyContent) {
                    $this->writeComplexProperty($property);
                } elseif ($property->value instanceof ODataBagContent) {
                    $this->writeBagContent($property->value);
                } else {
                    $this->writer->writeValue($property->value, $property->typeName);
                }
            }
        }

        return $this;
    }

    /**
     * @param  ODataProperty $property
     * @return $this
     */
    protected function writePropertyMeta(ODataProperty $property)
    {
        return $this; //does nothing in v1 or v2, json light outputs stuff
    }

    /**
     * Begin write complex property.
     *
     * @param ODataProperty $property property to write
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    protected function writeComplexProperty(ODataProperty $property)
    {
        $this->writer
            // {
            ->startObjectScope()
            // __metadata : { Type : "typename" }
            ->writeName(ODataConstants::JSON_METADATA_STRING)
            ->startObjectScope()
            ->writeName(ODataConstants::JSON_TYPE_STRING)
            ->writeValue($property->typeName)
            ->endScope();

        $this->writeProperties($property->value);

        $this->writer->endScope();

        return $this;
    }

    /**
     * Begin an item in a collection.
     *
     * @param ODataBagContent $bag bag property to write
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    protected function writeBagContent(ODataBagContent $bag)
    {
        $this->writer
            ->startObjectScope()// {
            ->writeName(ODataConstants::JSON_METADATA_STRING)//__metadata : { Type : "typename" }
            ->startObjectScope()
            ->writeName(ODataConstants::JSON_TYPE_STRING)
            ->writeValue($bag->getType())
            ->endScope()// }
            ->writeName(ODataConstants::JSON_RESULT_NAME)// "__results":
            ->startArrayScope(); // [

        foreach ($bag->getPropertyContents() as $content) {
            if ($content instanceof ODataPropertyContent) {
                $this->writer->startObjectScope();
                $this->writeProperties($content);
                $this->writer->endScope();
            } else {
                // retrieving the collection datatype in order
                //to write in json specific format, with in chords or not
                preg_match('#\((.*?)\)#', $bag->getType(), $type);
                $this->writer->writeValue($content, $type[1]);
            }
        }

        $this->writer
            ->endScope()// ]
            ->endScope(); // }
        return $this;
    }

    /**
     * Start writing a feed.
     *
     * @param ODataFeed $feed Feed to write
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    protected function writeFeed(ODataFeed $feed)
    {
        foreach ($feed->entries as $entry) {
            $this->writer->startObjectScope();
            $this->writeEntry($entry);
            $this->writer->endScope();
        }

        return $this;
    }

    /**
     * @param ODataEntry $entry Entry to write
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    protected function writeEntry(ODataEntry $entry)
    {
        $this->writeEntryMetadata($entry);
        foreach ($entry->getLinks() as $link) {
            $this->writeLink($link);
        }

        $this->writeProperties($entry->propertyContent);

        return $this;
    }

    /**
     * Write metadata information for the entry.
     *
     * @param ODataEntry $entry Entry to write metadata for
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    protected function writeEntryMetadata(ODataEntry $entry)
    {
        // "__metadata"
        $this->writer
            ->writeName(ODataConstants::JSON_METADATA_STRING)
            ->startObjectScope();
        // __metadata : { uri: "Uri", type: "Type" [Media Link Properties] }
        if ($entry->id != null || $entry->type != null || $entry->eTag != null) {
            // Write uri value only for entity types
            if ($entry->id != null) {
                $this->writer
                    ->writeName($this->urlKey)
                    ->writeValue($entry->id);
            }

            // Write the etag property, if the entry has etag properties.
            if ($entry->eTag != null) {
                $this->writer
                    ->writeName(ODataConstants::JSON_ETAG_STRING)
                    ->writeValue($entry->eTag);
            }

            // Write the type property, if the entry has type properties.
            if ($entry->type != null) {
                $value = $entry->type instanceof ODataCategory ? $entry->type->getTerm() : $entry->type;
                $this->writer
                    ->writeName(ODataConstants::JSON_TYPE_STRING)
                    ->writeValue($value);
            }
        }

        // Media links.
        if ($entry->isMediaLinkEntry) {
            if ($entry->mediaLink != null) {
                $this->writer
                    ->writeName(ODataConstants::JSON_EDITMEDIA_STRING)
                    ->writeValue($entry->mediaLink->editLink)
                    ->writeName(ODataConstants::JSON_MEDIASRC_STRING)
                    ->writeValue($entry->mediaLink->srcLink)
                    ->writeName(ODataConstants::JSON_CONTENTTYPE_STRING)
                    ->writeValue($entry->mediaLink->contentType);

                if ($entry->mediaLink->eTag != null) {
                    $this->writer
                        ->writeName(ODataConstants::JSON_MEDIAETAG_STRING)
                        ->writeValue($entry->mediaLink->eTag);
                }

                $this->writer->endScope();
            }

            // writing named resource streams
            foreach ($entry->mediaLinks as $mediaLink) {
                $this->writer
                    ->writeName($mediaLink->name)
                    ->startObjectScope()
                    ->writeName(ODataConstants::JSON_MEDIASRC_STRING)
                    ->writeValue($mediaLink->srcLink)
                    ->writeName(ODataConstants::JSON_CONTENTTYPE_STRING)
                    ->writeValue($mediaLink->contentType);

                if ($mediaLink->eTag != null) {
                    $this->writer
                        ->writeName(ODataConstants::JSON_MEDIAETAG_STRING)
                        ->writeValue($mediaLink->eTag);
                }

                $this->writer->endScope();
            }
        } else {
            $this->writer->endScope();
        }

        return $this;
    }

    /**
     * @param ODataLink $link Link to write
     *
     * @throws Exception
     * @return JsonODataV1Writer
     */
    protected function writeLink(ODataLink $link)
    {

        // "<linkname>" :
        $this->writer->writeName($link->getTitle());

        if ($link->isExpanded) {
            if (null === $link->expandedResult) {
                $this->writer->writeValue('null');
            } else {
                $this->writeExpandedLink($link);
            }
        } else {
            $this->writer
                ->startObjectScope()
                ->writeName(ODataConstants::JSON_DEFERRED_STRING)
                ->startObjectScope()
                ->writeName($this->urlKey)
                ->writeValue($link->url)
                ->endScope()
                ->endScope();
        }

        return $this;
    }

    /**
     * @param  ODataLink $link
     * @throws Exception
     */
    protected function writeExpandedLink(ODataLink $link)
    {
        if ($link->isCollection) {
            $this->writer->startArrayScope();
            $this->writeFeed(/* @scrutinizer ignore-type */ $link->expandedResult);
        } else {
            $this->writer->startObjectScope();
            $this->writeEntry(/* @scrutinizer ignore-type */ $link->expandedResult);
        }

        $this->writer->endScope();
    }

    /**
     * Get the Json final output.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->writer->getJsonOutput();
    }

    /**
     * @param ProvidersWrapper $providers
     *
     * @throws Exception
     * @return IODataWriter
     */
    public function writeServiceDocument(ProvidersWrapper $providers)
    {
        $writer = $this->writer;
        $writer
            ->startObjectScope()// {
            ->writeName('d')//  "d" :
            ->startObjectScope()// {
            ->writeName(ODataConstants::ENTITY_SET)// "EntitySets"
            ->startArrayScope() // [
        ;

        foreach ($providers->getResourceSets() as $resourceSetWrapper) {
            $writer->writeValue($resourceSetWrapper->getName());
        }
        foreach ($providers->getSingletons() as $singleton) {
            $writer->writeValue($singleton->getName());
        }

        $writer
            ->endScope()// ]
            ->endScope()// }
            ->endScope() // }
        ;

        return $this;
    }
}
