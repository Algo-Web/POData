<?php
/**
 * Contains Base class for OData Writers which implements IODataWriter.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer\Writers\Common;
use ODataProducer\Providers\Metadata\Type\Boolean;
use ODataProducer\Providers\Metadata\Type\String;
use ODataProducer\Common\ODataException;
use ODataProducer\ObjectModel\ODataFeed;
use ODataProducer\ObjectModel\ODataEntry;
use ODataProducer\ObjectModel\ODataURLCollection;
use ODataProducer\ObjectModel\ODataURL;
use ODataProducer\ObjectModel\ODataLink;
use ODataProducer\ObjectModel\ODataPropertyContent;
use ODataProducer\ObjectModel\ODataBagContent;
use ODataProducer\ObjectModel\ODataProperty;
use ODataProducer\ObjectModel\ODataMediaLink;
/** 
 * Base class for OData writers.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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
     * @param ODataFeed &$odataFeed Feed to write
     * 
     * @return nothing
     */
    abstract protected function startFeed(ODataFeed &$odataFeed);

    /**
     * Write feed meta data
     *
     * @param ODataFeed &$odataFeed Feed whose metadata to be written
     * 
     * @return nothing
     */
    abstract protected function writeFeedMetadata(ODataFeed &$odataFeed);

    /**
     * Write end of feed
     * 
     * @param ODataFeed &$odataFeed Ending the feed.
     * 
     * @return nothing
     */
    abstract protected function endFeed(ODataFeed &$odataFeed);

    /**
     * Start writing a entry
     *
     * @param ODataEntry &$odataEntry Entry to write
     * 
     * @return nothing
     */
    abstract protected function startEntry(ODataEntry &$odataEntry);

    /**
     * Write entry meta data
     *
     * @param ODataEntry &$odataEntry Entry whose metadata to be written
     * 
     * @return nothing
     */
    abstract protected function writeEntryMetadata(ODataEntry &$odataEntry);

    /**
     * Write end of entry
     *
     * @param ODataEntry &$odataEntry Ending the entry.
     * 
     * @return nothing
     */
    abstract protected function endEntry(ODataEntry &$odataEntry);

    /**
     * Start writing a link
     *
     * @param ODataLink &$odatalink Link to write
     * @param Boolean   $isExpanded is link expanded or not.
     * 
     * @return nothing
     */
    abstract protected function startLink(ODataLink &$odatalink, $isExpanded);

    /**
     * Write link meta data
     *
     * @param ODataLink &$odatalink Link whose metadata to be written
     * @param Boolean   $isExpanded is link expanded or not.
     * 
     * @return nothing
     */
    abstract protected function writeLinkMetadata(ODataLink &$odatalink, $isExpanded);

    /**
     * Write end of link
     *
     * @param boolean $isExpanded is link expanded or not.
     * 
     * @return nothing
     */
    abstract protected function endLink($isExpanded);

    /**
     * Write the node which hold the entity properties as child
     * 
     * @param ODataEntry &$odataEntry ODataEntry object for PreWriteProperties. 
     * 
     * @return nothing
     */
    abstract protected function preWriteProperties(ODataEntry &$odataEntry);

    /**
     * Write a property
     *
     * @param ODataProperty &$odataProperty Property to be written
     * @param Boolean       $isTopLevel     Is property top level or not.
     * 
     * @return nothing
     */
    abstract protected function beginWriteProperty(
        ODataProperty &$odataProperty, $isTopLevel
    );
        
    /**
     * Write end of a property
     * 
     * @param Object $kind Object of the property which need to end.
     * 
     * @return nothing
     */
    abstract protected function endWriteProperty($kind);

    /**
     * Write after last property
     * 
     * @param ODataEntry &$odataEntry ODataEntry object for PostWriteProperties.
     * 
     * @return nothing
     */
    abstract protected function postWriteProperties(ODataEntry &$odataEntry);

    /**
     * Begin a complex property
     * 
     * @param ODataProperty &$odataProperty whose value hold the complex property
     * 
     * @return nothing
     */
    abstract protected function beginComplexProperty(
        ODataProperty &$odataProperty
    );

    /**
     * End  complex property
     * 
     * @return nothing
     */
    abstract protected function endComplexProperty();

    /**
     * Begin an item in a collection
     *  
     * @param ODataProperty &$odataBagProperty ODataProperty object to write 
     * Bag Property.
     * 
     * @return nothing
     */
    abstract protected function beginBagPropertyItem(
        ODataProperty &$odataBagProperty
    );

    /**
     * End an item in a collection
     * 
     * @return nothing
     */
    abstract protected function endBagPropertyItem();

    /**
     * begin write odata links
     * 
     * @param ODataURLCollection &$odataUrlCollection Collection of OdataUrls.
     * 
     * @return nothing
     */
    abstract protected function startUrlCollection(
        ODataURLCollection &$odataUrlCollection
    );

    /**
     * begin write odata url
     * 
     * @param ODataURL &$odataUrl object of ODataUrl
     * 
     * @return nothing
     */
    abstract protected function startUrl(ODataURL &$odataUrl);

    /**
     * Write end of odata url
     * 
     * @param ODataURL &$odataUrl Object of ODataUrl.
     * 
     * @return nothing
     */
    abstract protected function endUrl(ODataURL &$odataUrl);

    /**
     * Write end of odata links
     * 
     * @param ODataURLCollection &$odataUrlCollection object of ODataUrlCollection
     * 
     * @return nothing
     */
    abstract protected function endUrlCollection(ODataURLCollection &$odataUrlCollection);

    /**
     * Write null value
     * 
     * @param ODataProperty &$odataProperty ODataProperty object to write null value
     * according to Property type.
     * 
     * @return nothing
     */
    abstract protected function writeNullValue(ODataProperty &$odataProperty);

    /**
     * Write basic (primitive) value
     *
     * @param object &$odataProperty object of property to write.
     * 
     * @return nothing
     */
    abstract protected function writePrimitiveValue(ODataProperty &$odataProperty);

    /**
     * Serialize the exception
     *
     * @param ODataException &$exception              Exception to serialize
     * @param boolean        $serializeInnerException if set to true,
     * serialize the inner exception if $exception is an ODataException.
     * 
     * @return nothing
     */
    public static function serializeException(ODataException &$exception, $serializeInnerException)
    {
    }

    /**
     * Start writing a feed. This function perform the following sub-tasks:
     * (1). Using _startFeed write start of a feed [Atom]/or collection [JSON]
     * (2). Using _writeFeedMetadata out feed [Atom]/or collection [JSON] metadata
     *
     * @param ODataFeed &$odataFeed Feed to write
     * 
     * @return nothing
     */
    public function writeBeginFeed(ODataFeed &$odataFeed)
    {
        $this->startFeed($odataFeed);
        $this->writeFeedMetadata($odataFeed);
    }

    /**
     * Start writing an entry. This function perform the following sub-tasks:
     * (1). Using _startEntry write starting of a entry [Atom, JSON]
     * (2). Using _writeEntryMetadata write entry [Atom, JSON] metadata
     *
     * @param ODataEntry &$odataEntry Entry to write
     * 
     * @return nothing
     */
    public function writeBeginEntry(ODataEntry &$odataEntry)
    {
        $this->startEntry($odataEntry);
        $this->writeEntryMetadata($odataEntry);
    }

    /**
     * Start writing a link. This function perform the following sub-tasks:
     * (1). Using _startLink write starting of Atom link (Navigation link) 
     * [Atom, JSON]
     * (2). Using _writeLinkMetadata write link metadata [Atom, JSON]
     * Note: This method will not write the expanded result
     * 
     * @param ODataLink &$odataLink Link to write.
     * @param Boolean   $isExpanded Is link expanded or not.
     * 
     * @return nothing
     */
    public function writeBeginLink(ODataLink &$odataLink, $isExpanded)
    {
        $this->startLink($odataLink, $isExpanded);
        $this->writeLinkMetadata($odataLink, $isExpanded);
    }

    /**
     * Ending the Link according to how its opened. 
     * 
     * @param Boolean $isExpanded If link is expanded then end it accordingly.
     * 
     * @return nothing
     */
    public function writeEndLink($isExpanded)
    {
        $this->endLink($isExpanded);
    }
    /**
     * Write the given collection of properties. 
     * (properties of an entity or complex type)
     *
     * @param ODataPropertyContent &$odataPropertyContent Collection of properties.
     * 
     * @return nothing
     */
    public function writeBeginProperties(ODataPropertyContent &$odataPropertyContent)
    {
        foreach ($odataPropertyContent->odataProperty as $odataProperty) {
            $this->beginWriteProperty(
                $odataProperty, $odataPropertyContent->isTopLevel
            );
            if ($odataProperty->value == null) {
                $this->writeNullValue($odataProperty);
            } elseif ($odataProperty->value instanceof ODataPropertyContent) {
                $this->beginComplexProperty($odataProperty);
                $this->writeBeginProperties($odataProperty->value);
                $this->endComplexProperty();
            } elseif ($odataProperty->value instanceof ODataBagContent) {
                $this->beginBagPropertyItem($odataProperty);
                $this->endBagPropertyItem();
            } else {
                $this->writePrimitiveValue($odataProperty);
            }
            $this->endWriteProperty($odataPropertyContent);
        }
    }

    /**
     * Start writing a top level url using _startUrl [Atom, JSON]
     * 
     * @param ODataURL &$oDataUrl Start writing Requested OdataUrl.
     * 
     * @return nothing
     */
    public function writeBeginUrl(ODataURL &$oDataUrl)
    {
        $this->startUrl($oDataUrl);
    }

    /**
     * Start writing a top level url collection using _startCollection [Atom, JSON]
     * 
     * @param ODataURLCollection &$odataUrlCollection Start Writing Collection of Url
     * 
     * @return nothing
     */
    public function writeBeginUrlCollection(ODataURLCollection &$odataUrlCollection)
    {
        $this->startUrlCollection($odataUrlCollection);
    }

    /**
     * End writing an ODataFeed/ODataEntry/ODataURL/ODataURLCollection/ODataProperty
     * Uses  endFeed, endEntry, endUrl, endUrlCollection and endWriteProperty
     * 
     * @param Object $kind Object of top level request.
     * 
     * @return nothing
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
?>
