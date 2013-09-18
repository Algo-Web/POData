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
	 * Write the given OData model in a specific response format
	 *
	 *
	 * @param  ODataURL|ODataURLCollection|ODataPropertyContent|ODataFeed|ODataEntry $model Object of requested content.
	 *
	 * @return IODataWriter
	 */
	public function write($model)
	{
		if ($model instanceof ODataURL) {
			$this->writeURL($model);
		} else if ($model instanceof ODataURLCollection) {
			$this->writeURLCollection($model);
		} else if ($model instanceof ODataPropertyContent) {
			$this->writeProperty($model);
		} else if ($model instanceof ODataFeed) {
			$this->writeFeed($model);
		} else if ($model instanceof ODataEntry) {
			$this->writeEntry($model);
		}

		return $this;
	}

	/**
	 * Write top level link (url)
	 *
	 * @param ODataURL $oDataUrl Object of ODataUrl
	 *
	 * @return BaseODataWriter
	 */
	protected function writeURL(ODataURL $oDataUrl)
	{
		return $this
			->writeBeginUrl($oDataUrl)
			->writeEnd($oDataUrl);

	}

	/**
	 * Write top level link collection
	 *
	 * @param ODataURLCollection $oDataUrlCollection Object of ODataUrlCollection
	 *
	 * @return BaseODataWriter
	 */
	protected function writeURLCollection (ODataURLCollection $oDataUrlCollection)
	{
		return $this
			->writeBeginUrlCollection($oDataUrlCollection)
			->writeEnd($oDataUrlCollection);
	}

	/**
	 * Write top level Feed/Collection
	 *
	 * @param ODataFeed $feed Object of ODataFeed
	 *
	 * @return BaseODataWriter
	 */
	protected function writeFeed(ODataFeed $feed)
	{
		$this->writeBeginFeed($feed);
		foreach ($feed->entries as $entry) {
			$this->writeEntry($entry);
		}
		return $this->writeEnd($feed);
	}

	/**
	 * Write top level entry
	 *
	 * @param ODataEntry $entry Object of ODataEntry
	 *
	 * @return BaseODataWriter
	 */
	protected function writeEntry(ODataEntry $entry)
	{
		$this->writeBeginEntry($entry);
		foreach ($entry->links as $link) {
			$this->writeBeginLink($link, $link->isExpanded);

			if ($link->isExpanded && !is_null($link->expandedResult)) {
				if ($link->isCollection) {
					$this->writeFeed($link->expandedResult);
				} else {
					$this->writeEntry($link->expandedResult);
				}
			}
			$this->writeEndLink($link->isExpanded);
		}

		return $this
			->preWriteProperties($entry)
			->writeBeginProperties($entry->propertyContent)
			->postWriteProperties($entry)
			->writeEnd($entry);
	}

	/**
	 * Write top level Property
	 *
	 * @param ODataPropertyContent $propertyContent Object of ODataPropertyContent
	 *
	 * @return BaseODataWriter
	 */
	protected function writeProperty(ODataPropertyContent $propertyContent)
	{
		return $this->writeBeginProperties($propertyContent);
	}



    /**
     * Start writing a feed
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return BaseODataWriter
     */
    abstract protected function startFeed(ODataFeed $feed);

    /**
     * Write feed meta data
     *
     * @param ODataFeed $feed Feed whose metadata to be written
     * 
     * @return BaseODataWriter
     */
    abstract protected function writeFeedMetadata(ODataFeed $feed);

    /**
     * Write end of feed
     * 
     * @param ODataFeed $feed Ending the feed.
     * 
     * @return BaseODataWriter
     */
    abstract protected function endFeed(ODataFeed $feed);

    /**
     * Start writing a entry
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return BaseODataWriter
     */
    abstract protected function startEntry(ODataEntry $entry);

    /**
     * Write entry meta data
     *
     * @param ODataEntry $entry Entry whose metadata to be written
     * 
     * @return BaseODataWriter
     */
    abstract protected function writeEntryMetadata(ODataEntry $entry);

    /**
     * Write end of entry
     *
     * @param ODataEntry $entry Ending the entry.
     * 
     * @return BaseODataWriter
     */
    abstract protected function endEntry(ODataEntry $entry);

    /**
     * Start writing a link
     *
     * @param ODataLink $link Link to write
     * @param Boolean   $isExpanded is link expanded or not.
     * 
     * @return BaseODataWriter
     */
    abstract protected function startLink(ODataLink $link, $isExpanded);

    /**
     * Write link meta data
     *
     * @param ODataLink $link Link whose metadata to be written
     * @param Boolean   $isExpanded is link expanded or not.
     * 
     * @return BaseODataWriter
     */
    abstract protected function writeLinkMetadata(ODataLink $link, $isExpanded);

    /**
     * Write end of link
     *
     * @param boolean $isExpanded is link expanded or not.
     * 
     * @return BaseODataWriter
     */
    abstract protected function endLink($isExpanded);

    /**
     * Write the node which hold the entity properties as child
     * 
     * @param ODataEntry $entry ODataEntry object for PreWriteProperties.
     * 
     * @return BaseODataWriter
     */
    abstract protected function preWriteProperties(ODataEntry $entry);

    /**
     * Write a property
     *
     * @param ODataProperty $property Property to be written
     * @param Boolean       $isTopLevel     Is property top level or not.
     * 
     * @return BaseODataWriter
     */
    abstract protected function beginWriteProperty(ODataProperty $property, $isTopLevel);
        
    /**
     * Write end of a property
     * 
     * @param ODataPropertyContent $property Object of the property which need to end.
     * 
     * @return BaseODataWriter
     */
    abstract protected function endWriteProperty(ODataPropertyContent $property);

    /**
     * Write after last property
     * 
     * @param ODataEntry $entry ODataEntry object for PostWriteProperties.
     * 
     * @return BaseODataWriter
     */
    abstract protected function postWriteProperties(ODataEntry $entry);

    /**
     * Begin a complex property
     * 
     * @param ODataProperty $property whose value hold the complex property
     * 
     * @return BaseODataWriter
     */
    abstract protected function beginComplexProperty(ODataProperty $property);

    /**
     * End  complex property
     * 
     * @return BaseODataWriter
     */
    abstract protected function endComplexProperty();

    /**
     * Begin an item in a collection
     *  
     * @param ODataBagContent $bag
     *
     * 
     * @return BaseODataWriter
     */
    abstract protected function beginBagPropertyItem(ODataBagContent $bag);

    /**
     * End an item in a collection
     * 
     * @return BaseODataWriter
     */
    abstract protected function endBagPropertyItem();

    /**
     * begin write odata links
     * 
     * @param ODataURLCollection $urls Collection of OdataUrls.
     * 
     * @return BaseODataWriter
     */
    abstract protected function startUrlCollection(ODataURLCollection $urls);

    /**
     * begin write odata url
     * 
     * @param ODataURL $url object of ODataUrl
     * 
     * @return BaseODataWriter
     */
    abstract protected function startUrl(ODataURL $url);

    /**
     * Write end of OData url
     * 
     * @param ODataURL $url Object of ODataUrl.
     * 
     * @return BaseODataWriter
     */
    abstract protected function endUrl(ODataURL $url);

    /**
     * Write end of OData links
     * 
     * @param ODataURLCollection $urls object of ODataUrlCollection
     * 
     * @return BaseODataWriter
     */
    abstract protected function endUrlCollection(ODataURLCollection $urls);

    /**
     * Write null value
     * 
     * @param ODataProperty $property ODataProperty object to write null value
     * according to Property type.
     * 
     * @return BaseODataWriter
     */
    abstract protected function writeNullValue(ODataProperty $property);

    /**
     * Write basic (primitive) value
     *
     * @param ODataProperty $property object of property to write.
     * 
     * @return BaseODataWriter
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
     * @return IODataWriter
     */
    public function writeBeginFeed(ODataFeed $feed)
    {
        return $this
	        ->startFeed($feed)
            ->writeFeedMetadata($feed);
    }

    /**
     * Start writing an entry. This function perform the following sub-tasks:
     * (1). Using _startEntry write starting of a entry [Atom, JSON]
     * (2). Using _writeEntryMetadata write entry [Atom, JSON] metadata
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return IODataWriter
     */
    public function writeBeginEntry(ODataEntry $entry)
    {
        return $this
	        ->startEntry($entry)
	        ->writeEntryMetadata($entry);
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
     * @return IODataWriter
     */
    public function writeBeginLink(ODataLink $link, $isExpanded)
    {
        return $this
	        ->startLink($link, $isExpanded)
	        ->writeLinkMetadata($link, $isExpanded);
    }

    /**
     * Ending the Link according to how its opened. 
     * 
     * @param Boolean $isExpanded If link is expanded then end it accordingly.
     * 
     * @return IODataWriter
     */
    public function writeEndLink($isExpanded)
    {
        return $this->endLink($isExpanded);
    }
    /**
     * Write the given collection of properties. 
     * (properties of an entity or complex type)
     *
     * @param ODataPropertyContent $properties Collection of properties.
     * 
     * @return BaseODataWriter
     */
    public function writeBeginProperties(ODataPropertyContent $properties)
    {
        foreach ($properties->odataProperty as $property) {
            $this->beginWriteProperty($property, $properties->isTopLevel);

            if ($property->value == null) {
                $this->writeNullValue($property);
            } elseif ($property->value instanceof ODataPropertyContent) {
                $this
	                ->beginComplexProperty($property)
	                ->writeBeginProperties($property->value)
	                ->endComplexProperty();
            } elseif ($property->value instanceof ODataBagContent) {
                $this
	                ->beginBagPropertyItem($property->value)
	                ->endBagPropertyItem();
            } else {
                $this->writePrimitiveValue($property);
            }
            $this->endWriteProperty($properties);
        }

	    return $this;
    }

    /**
     * Start writing a top level url using _startUrl [Atom, JSON]
     * 
     * @param ODataURL $url Start writing Requested OdataUrl.
     * 
     * @return IODataWriter
     */
    public function writeBeginUrl(ODataURL $url)
    {
        return $this->startUrl($url);
    }

    /**
     * Start writing a top level url collection using _startCollection [Atom, JSON]
     * 
     * @param ODataURLCollection $urls Start Writing Collection of Url
     * 
     * @return IODataWriter
     */
    public function writeBeginUrlCollection(ODataURLCollection $urls)
    {
	    return $this->startUrlCollection($urls);
    }

    /**
     * End writing an ODataFeed/ODataEntry/ODataURL/ODataURLCollection/ODataProperty
     * Uses  endFeed, endEntry, endUrl, endUrlCollection and endWriteProperty
     * 
     * @param ODataFeed|ODataEntry|ODataURL|ODataURLCollection|ODataProperty $kind Object of top level request.
     * 
     * @return IODataWriter
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

	    return $this;
    }

}
