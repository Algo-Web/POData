<?php

namespace POData\Writers;


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
	 * @param  ODataURL|ODataURLCollection|ODataPropertyContent|ODataFeed|ODataEntry $model Object of requested content.
	 *
	 * @return IODataWriter
	 */
	public function write($model)
	{
		if ($model instanceof ODataURL) {
			return $this->writeURL($model);
		}

		if ($model instanceof ODataURLCollection) {
			return $this->writeURLCollection($model);
		}

		if ($model instanceof ODataPropertyContent) {
			return $this->writeProperties($model, true);
		}

		if ($model instanceof ODataFeed) {
			return $this->writeFeed($model);
		}

		if ($model instanceof ODataEntry) {
			return $this->writeEntry($model);
		}

		return $this;
	}

	/**
	 * @param ODataURL $url the url to write
	 * @return BaseODataWriter
	 */
	abstract protected function writeURL(ODataURL $url);

	/**
	 * Write top level Feed/Collection
	 *
	 * @param ODataFeed $feed Object of ODataFeed
	 *
	 * @return BaseODataWriter
	 */
	abstract protected function writeFeed(ODataFeed $feed);

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
			$this->writeBeginLink($link);

			if ($link->isExpanded && !is_null($link->expandedResult)) {
				if ($link->isCollection) {
					$this->writeFeed($link->expandedResult);
				} else {
					$this->writeEntry($link->expandedResult);
				}
			}
			$this->writeEndLink($link);
		}

		return $this
			->preWriteProperties($entry)
			->writeProperties($entry->propertyContent)
			->postWriteProperties($entry)
			->endEntry($entry);
	}


	abstract protected function writeEndLink(ODataLink $link);

	/**
	 * @param ODataUrlCollection $urls ODataUrlCollection to Write.
	 *
	 * @return BaseODataWriter
	 */
	abstract protected  function writeUrlCollection(ODataURLCollection $urls);




    /**
     * Write end of entry
     *
     * @param ODataEntry $entry Ending the entry.
     * 
     * @return BaseODataWriter
     */
    abstract protected function endEntry(ODataEntry $entry);




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
     * @param ODataProperty $property Object of the property which need to end.
     * @param Boolean       $isTopLevel     Is property top level or not.
     * 
     * @return BaseODataWriter
     */
    abstract protected function endWriteProperty(ODataProperty $property, $isTopLevel);

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
     * @param ODataBagContent $bag
     *
     * @return BaseODataWriter
     */
    abstract protected function writeBagContent(ODataBagContent $bag);


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
     * Start writing an entry including it's metadata
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return BaseODataWriter
     */
    abstract protected function writeBeginEntry(ODataEntry $entry);


    /**
     * Start writing a link. This function perform the following sub-tasks:
     * (1). Using _startLink write starting of Atom link (Navigation link) 
     * [Atom, JSON]
     * (2). Using _writeLinkMetadata write link metadata [Atom, JSON]
     * Note: This method will not write the expanded result
     * 
     * @param ODataLink $link Link to write.
     *
     * @return BaseODataWriter
     */
    abstract protected function writeBeginLink(ODataLink $link);


	/**
	 * Write the given collection of properties.
	 * (properties of an entity or complex type)
	 *
	 * @param ODataPropertyContent $properties Collection of properties.
	 * @param bool $topLevel indicates if this property content is the top level response to be written
	 * @return BaseODataWriter
	 */
	protected function writeProperties(ODataPropertyContent $properties, $topLevel = false)
    {
        foreach ($properties->properties as $property) {
            $this->beginWriteProperty($property, $topLevel);

            if ($property->value == null) {
                $this->writeNullValue($property);
            } elseif ($property->value instanceof ODataPropertyContent) {
                $this
	                ->beginComplexProperty($property)
	                ->writeProperties($property->value, false)
	                ->endComplexProperty();
            } elseif ($property->value instanceof ODataBagContent) {
                $this->writeBagContent($property->value);
            } else {
                $this->writePrimitiveValue($property);
            }

            $this->endWriteProperty($property, $topLevel);
        }

	    return $this;
    }

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

}
