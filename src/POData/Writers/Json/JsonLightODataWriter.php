<?php

declare(strict_types=1);

namespace POData\Writers\Json;

use Exception;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\Version;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;

/**
 * Class JsonLightODataWriter is a writer for the json format in OData V3 also known as JSON Light.
 */
class JsonLightODataWriter extends JsonODataV2Writer
{
    /**
     * @var JsonLightMetadataLevel
     */
    protected $metadataLevel;

    /**
     * The service base uri.
     *
     * @var string
     */
    protected $baseUri;

    /**
     * @param JsonLightMetadataLevel $metadataLevel
     * @param string                 $absoluteServiceUri
     *
     * @throws Exception
     */
    public function __construct(string $eol, bool $prettyPrint, JsonLightMetadataLevel $metadataLevel = null, $absoluteServiceUri = '')
    {
        if (strlen($absoluteServiceUri) == 0) {
            throw new Exception('absoluteServiceUri must not be empty or null');
        }
        $this->baseUri = $absoluteServiceUri;

        $this->writer        = new JsonWriter('', $eol, $prettyPrint);
        $this->urlKey        = ODataConstants::JSON_URL_STRING;
        $this->dataArrayName = ODataConstants::JSON_LIGHT_VALUE_NAME;
        $this->rowCountName  = ODataConstants::JSON_LIGHT_ROWCOUNT_STRING;
        $this->metadataLevel = $metadataLevel;
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
        if ($responseVersion != Version::v3()) {
            return false;
        }

        $parts = explode(';', $contentType);

        //It must be app/json and have the right odata= piece
        return in_array(MimeTypes::MIME_APPLICATION_JSON, $parts) && in_array($this->metadataLevel->getValue(), $parts);
    }

    /**
     * Write the given OData model in a specific response format.
     *
     * @param ODataURL|ODataURLCollection|ODataPropertyContent|ODataFeed|ODataEntry $model Object of requested content
     *
     * @throws Exception
     * @return JsonLightODataWriter
     */
    public function write($model)
    {
        $this->writer->startObjectScope();

        if ($model instanceof ODataURL) {
            $this->writeTopLevelMeta('url');
            $this->writeUrl($model);
        } elseif ($model instanceof ODataURLCollection) {
            $this->writeTopLevelMeta('urlCollection');
            $this->writeUrlCollection($model);
        } elseif ($model instanceof ODataPropertyContent) {
            $this->writeTopLevelMeta($model->properties[0]->typeName);
            $this->writeTopLevelProperty($model->properties[0]);
        } elseif ($model instanceof ODataFeed) {
            $effectiveTitle = $model->title instanceof ODataTitle ? $model->title->title : $model->title;
            $this->writeTopLevelMeta($effectiveTitle);
            $this->writeRowCount($model->rowCount);
            $this->writer
                ->writeName($this->dataArrayName)
                ->startArrayScope();
            $this->writeFeed($model);
            $this->writer->endScope();
        } elseif ($model instanceof ODataEntry) {
            $this->writeTopLevelMeta($model->resourceSetName . '/@Element');
            $this->writeEntry($model);
        }

        $this->writer->endScope();

        return $this;
    }

    /**
     * @param  string    $fragment
     * @throws Exception
     */
    protected function writeTopLevelMeta($fragment)
    {
        if ($this->metadataLevel == JsonLightMetadataLevel::NONE()) {
            return;
        }

        $this->writer
            ->writeName(ODataConstants::JSON_LIGHT_METADATA_STRING)
            ->writeValue($this->baseUri . '/' . ODataConstants::URI_METADATA_SEGMENT . '#' . $fragment);
    }

    /**
     * @param ODataProperty $property
     *
     * @throws Exception
     * @return JsonLightODataWriter
     */
    protected function writeTopLevelProperty(ODataProperty $property)
    {
        $this->writePropertyMeta($property);
        if ($property->value == null) {
            $this->writer->writeName(ODataConstants::JSON_LIGHT_VALUE_NAME);
            $this->writer->writeValue('null');
        } elseif ($property->value instanceof ODataPropertyContent) {
            //In the case of complex properties at the top level we don't write the name of the property,
            //just the sub properties.
            $this->writeComplexPropertyMeta($property)
                ->writeProperties($property->value);
        } elseif ($property->value instanceof ODataBagContent) {
            $this->writer->writeName(ODataConstants::JSON_LIGHT_VALUE_NAME);
            $this->writeBagContent($property->value);
        } else {
            $this->writer->writeName(ODataConstants::JSON_LIGHT_VALUE_NAME);
            $this->writer->writeValue($property->value, $property->typeName);
        }

        return $this;
    }

    /**
     * @param  ODataProperty           $property
     * @throws Exception
     * @return $this|JsonODataV2Writer
     */
    protected function writePropertyMeta(ODataProperty $property)
    {
        if ($this->metadataLevel != JsonLightMetadataLevel::FULL()) {
            //Only full meta level outputs this info
            return $this;
        }

        if (null === $property->value) {
            //it appears full metadata doesn't output types for nulls...
            return $this;
        }

        switch ($property->typeName) {
            //The type metadata is only included on certain types of properties
            //Note this also excludes Complex types

            case 'Edm.Decimal':
            case 'Edm.DateTime':
                $this->writer
                    ->writeName($property->name . ODataConstants::JSON_LIGHT_METADATA_PROPERTY_TYPE_SUFFIX_STRING)
                    ->writeValue($property->typeName);
        }

        return $this;
    }

    /**
     * @param  ODataProperty $property
     * @throws Exception
     * @return $this
     */
    protected function writeComplexPropertyMeta(ODataProperty $property)
    {
        if ($this->metadataLevel == JsonLightMetadataLevel::FULL()) {
            $this->writer
                ->writeName(ODataConstants::JSON_LIGHT_METADATA_TYPE_STRING)
                ->writeValue($property->typeName);
        }

        return $this;
    }

    /**
     * @param  ODataBagContent         $bag
     * @throws Exception
     * @return $this|JsonODataV1Writer
     */
    protected function writeBagContent(ODataBagContent $bag)
    {
        $this->writer->startArrayScope();

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

        $this->writer->endScope();
        return $this;
    }

    /**
     * @param ODataEntry $entry Entry to write metadata for
     *
     * @throws Exception
     * @return JsonLightODataWriter
     */
    protected function writeEntryMetadata(ODataEntry $entry)
    {
        if ($this->metadataLevel != JsonLightMetadataLevel::FULL()) {
            //Only full meta level outputs this info
            return $this;
        }

        $typeValue = $entry->type instanceof ODataCategory ? $entry->type->getTerm() : $entry->type;
        $linkValue = $entry->editLink instanceof ODataLink ? $entry->editLink->getUrl() : $entry->editLink;

        $this->writer
            ->writeName(ODataConstants::JSON_LIGHT_METADATA_TYPE_STRING)
            ->writeValue($typeValue)
            ->writeName(ODataConstants::JSON_LIGHT_METADATA_ID_STRING)
            ->writeValue($entry->id)
            ->writeName(ODataConstants::JSON_LIGHT_METADATA_ETAG_STRING)
            ->writeValue($entry->eTag)
            ->writeName(ODataConstants::JSON_LIGHT_METADATA_EDIT_LINK_STRING)
            ->writeValue($linkValue);

        return $this;
    }

    /**
     * @param ODataLink $link Link to write
     *
     * @throws Exception
     * @return JsonLightODataWriter
     */
    protected function writeLink(ODataLink $link)
    {
        if ($this->metadataLevel == JsonLightMetadataLevel::FULL()) {
            //Interestingly the fullmetadata outputs this metadata..even if the thing is expanded
            $this->writer
                ->writeName($link->getTitle() . ODataConstants::JSON_LIGHT_METADATA_LINK_NAVIGATION_SUFFIX_STRING)
                ->writeValue($link->getUrl());
        }

        if ($link->isExpanded) {
            $this->writer->writeName($link->getTitle());

            if (null === $link->expandedResult) {
                $this->writer->writeValue('null');
            } else {
                $this->writeExpandedLink($link);
            }
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
     * Writes the next page link.
     *
     * @param ODataLink|null $nextPageLinkUri Uri for next page link
     *
     * @return JsonLightODataWriter|null
     */
    protected function writeNextPageLink(ODataLink $nextPageLinkUri = null)
    {
        return null;
    }

    /**
     * Begin write complex property.
     *
     * @param ODataProperty $property property to write
     *
     * @throws Exception
     * @return JsonLightODataWriter
     */
    protected function writeComplexProperty(ODataProperty $property)
    {
        $this->writer->startObjectScope();

        $this->writeComplexPropertyMeta($property)
            ->writeProperties($property->value);

        $this->writer->endScope();

        return $this;
    }
}
