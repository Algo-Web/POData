<?php

namespace POData\Writers;

use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\String;
use POData\Common\ODataException;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataURLCollection;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataMediaLink;

/**
 * Class BaseODataWriter
 * @package POData\Writers\Common
 */
abstract class BaseODataWriter implements IODataWriter
{
    /**
     * 
     * The service base uri
     * @var uri
     */
    protected $baseUri;

    /**
     * True if the server used version greater than 1 to generate the 
     * object model instance, False otherwise. 
     * 
     * @var boolean
     */
    protected $isPostV1;

    /**
     * Construct a new instance of BaseODataWriter.
     * 
     * @param string  $absoluteServiceUri the absolute uri of the Service.
     * @param boolean $isPostV1           True if the server used version 
     *                                    greater than 1 to generate the 
     *                                    object model instance, False otherwise.
     */
    public function __construct($absoluteServiceUri, $isPostV1) 
    {
        $this->baseUri = $absoluteServiceUri;
        $this->isPostV1 = $isPostV1;
    }

    /**
     * Start writing a feed
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return void
     */
    abstract protected function startFeed(ODataFeed $feed);

    /**
     * Write feed meta data
     *
     * @param ODataFeed $feed Feed whose metadata to be written
     * 
     * @return void
     */
    abstract protected function writeFeedMetadata(ODataFeed $feed);

    /**
     * Write end of feed
     * 
     * @param ODataFeed $feed Ending the feed.
     * 
     * @return void
     */
    abstract protected function endFeed(ODataFeed $feed);

    /**
     * Start writing a entry
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return void
     */
    abstract protected function startEntry(ODataEntry $entry);

    /**
     * Write entry meta data
     *
     * @param ODataEntry $entry Entry whose metadata to be written
     * 
     * @return void
     */
    abstract protected function writeEntryMetadata(ODataEntry $entry);

    /**
     * Write end of entry
     *
     * @param ODataEntry $entry Ending the entry.
     * 
     * @return void
     */
    abstract protected function endEntry(ODataEntry $entry);

    /**
     * Start writing a link
     *
     * @param ODataLink $link Link to write
     * @param Boolean   $isExpanded is link expanded or not.
     * 
     * @return void
     */
    abstract protected function startLink(ODataLink $link, $isExpanded);

    /**
     * Write link meta data
     *
     * @param ODataLink $link Link whose metadata to be written
     * @param Boolean   $isExpanded is link expanded or not.
     * 
     * @return void
     */
    abstract protected function writeLinkMetadata(ODataLink $link, $isExpanded);

    /**
     * Write end of link
     *
     * @param boolean $isExpanded is link expanded or not.
     * 
     * @return void
     */
    abstract protected function endLink($isExpanded);

    /**
     * Write the node which hold the entity properties as child
     * 
     * @param ODataEntry $entry ODataEntry object for PreWriteProperties.
     * 
     * @return void
     */
    abstract protected function preWriteProperties(ODataEntry $entry);

    /**
     * Write a property
     *
     * @param ODataProperty $property Property to be written
     * @param Boolean       $isTopLevel     Is property top level or not.
     * 
     * @return void
     */
    abstract protected function beginWriteProperty(ODataProperty $property, $isTopLevel);
        
    /**
     * Write end of a property
     * 
     * @param ODataPropertyContent $property Object of the property which need to end.
     * 
     * @return void
     */
    abstract protected function endWriteProperty(ODataPropertyContent $property);

    /**
     * Write after last property
     * 
     * @param ODataEntry $entry ODataEntry object for PostWriteProperties.
     * 
     * @return void
     */
    abstract protected function postWriteProperties(ODataEntry $entry);

    /**
     * Begin a complex property
     * 
     * @param ODataProperty $property whose value hold the complex property
     * 
     * @return void
     */
    abstract protected function beginComplexProperty(ODataProperty $property);

    /**
     * End  complex property
     * 
     * @return void
     */
    abstract protected function endComplexProperty();

    /**
     * Begin an item in a collection
     *  
     * @param ODataBagContent $bag
     *
     * 
     * @return void
     */
    abstract protected function beginBagPropertyItem(ODataBagContent $bag);

    /**
     * End an item in a collection
     * 
     * @return void
     */
    abstract protected function endBagPropertyItem();

    /**
     * begin write odata links
     * 
     * @param ODataURLCollection $urls Collection of OdataUrls.
     * 
     * @return void
     */
    abstract protected function startUrlCollection(ODataURLCollection $urls);

    /**
     * begin write odata url
     * 
     * @param ODataURL $url object of ODataUrl
     * 
     * @return void
     */
    abstract protected function startUrl(ODataURL $url);

    /**
     * Write end of OData url
     * 
     * @param ODataURL $url Object of ODataUrl.
     * 
     * @return void
     */
    abstract protected function endUrl(ODataURL $url);

    /**
     * Write end of OData links
     * 
     * @param ODataURLCollection $urls object of ODataUrlCollection
     * 
     * @return void
     */
    abstract protected function endUrlCollection(ODataURLCollection $urls);

    /**
     * Write null value
     * 
     * @param ODataProperty $property ODataProperty object to write null value
     * according to Property type.
     * 
     * @return void
     */
    abstract protected function writeNullValue(ODataProperty $property);

    /**
     * Write basic (primitive) value
     *
     * @param object $property object of property to write.
     * 
     * @return void
     */
    abstract protected function writePrimitiveValue(ODataProperty $property);

    /**
     * Serialize the exception
     *
     * @param ODataException $exception              Exception to serialize
     * @param boolean        $serializeInnerException if set to true,
     * serialize the inner exception if $exception is an ODataException.
     * 
     * @return void
     */
    public static function serializeException(ODataException $exception, $serializeInnerException)
    {
    }

    /**
     * Start writing a feed. This function perform the following sub-tasks:
     * (1). Using _startFeed write start of a feed [Atom]/or collection [JSON]
     * (2). Using _writeFeedMetadata out feed [Atom]/or collection [JSON] metadata
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return void
     */
    public function writeBeginFeed(ODataFeed $feed)
    {
        $this->startFeed($feed);
        $this->writeFeedMetadata($feed);
    }

    /**
     * Start writing an entry. This function perform the following sub-tasks:
     * (1). Using _startEntry write starting of a entry [Atom, JSON]
     * (2). Using _writeEntryMetadata write entry [Atom, JSON] metadata
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return void
     */
    public function writeBeginEntry(ODataEntry $entry)
    {
        $this->startEntry($entry);
        $this->writeEntryMetadata($entry);
    }

    /**
     * Start writing a link. This function perform the following sub-tasks:
     * (1). Using _startLink write starting of Atom link (Navigation link) 
     * [Atom, JSON]
     * (2). Using _writeLinkMetadata write link metadata [Atom, JSON]
     * Note: This method will not write the expanded result
     * 
     * @param ODataLink $link Link to write.
     * @param Boolean   $isExpanded Is link expanded or not.
     * 
     * @return void
     */
    public function writeBeginLink(ODataLink $link, $isExpanded)
    {
        $this->startLink($link, $isExpanded);
        $this->writeLinkMetadata($link, $isExpanded);
    }

    /**
     * Ending the Link according to how its opened. 
     * 
     * @param Boolean $isExpanded If link is expanded then end it accordingly.
     * 
     * @return void
     */
    public function writeEndLink($isExpanded)
    {
        $this->endLink($isExpanded);
    }
    /**
     * Write the given collection of properties. 
     * (properties of an entity or complex type)
     *
     * @param ODataPropertyContent $properties Collection of properties.
     * 
     * @return void
     */
    public function writeBeginProperties(ODataPropertyContent $properties)
    {
        foreach ($properties->odataProperty as $property) {
            $this->beginWriteProperty($property, $properties->isTopLevel);

            if ($property->value == null) {
                $this->writeNullValue($property);
            } elseif ($property->value instanceof ODataPropertyContent) {
                $this->beginComplexProperty($property);
                $this->writeBeginProperties($property->value);
                $this->endComplexProperty();
            } elseif ($property->value instanceof ODataBagContent) {
                $this->beginBagPropertyItem($property->value);
                $this->endBagPropertyItem();
            } else {
                $this->writePrimitiveValue($property);
            }
            $this->endWriteProperty($properties);
        }
    }

    /**
     * Start writing a top level url using _startUrl [Atom, JSON]
     * 
     * @param ODataURL $url Start writing Requested OdataUrl.
     * 
     * @return void
     */
    public function writeBeginUrl(ODataURL $url)
    {
        $this->startUrl($url);
    }

    /**
     * Start writing a top level url collection using _startCollection [Atom, JSON]
     * 
     * @param ODataURLCollection $urls Start Writing Collection of Url
     * 
     * @return void
     */
    public function writeBeginUrlCollection(ODataURLCollection $urls)
    {
        $this->startUrlCollection($urls);
    }

    /**
     * End writing an ODataFeed/ODataEntry/ODataURL/ODataURLCollection/ODataProperty
     * Uses  endFeed, endEntry, endUrl, endUrlCollection and endWriteProperty
     * 
     * @param ODataFeed|ODataEntry|ODataURL|ODataURLCollection|ODataProperty $kind Object of top level request.
     * 
     * @return void
     */
    public function writeEnd($kind)
    {
        if ($kind instanceof ODataURL) {
            $this->endUrl($kind);
        } elseif ($kind instanceof ODataURLCollection) {
            $this->endUrlCollection($kind);
        } elseif ($kind instanceof ODataEntry) {
            $this->endEntry($kind);
        } elseif ($kind instanceof ODataFeed) {
            $this->endFeed($kind);
        } elseif ($kind instanceof ODataPropertyContent) {
            $this->endWriteProperty($kind);
        }
    }


	//TODO: can we combine these down to one method?
	/**
	 * Get the results as string
	 *
	 * @return string
	 */
	protected abstract function getOutput();

    /**
     * Get the result as string using _getResult [Atom, JSON]
     * 
     * @return String Output in the format of Atom or JSON
     */
    public function getResult()
    {
        return $this->getOutput();
    }
}
