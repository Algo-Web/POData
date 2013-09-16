<?php

namespace POData\Writers;

use POData\Writers\Atom\AtomODataWriter;
use POData\Writers\Json\JsonODataWriter;
use POData\Writers\IODataWriter;
use POData\Providers\Metadata\Type\String;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\XMLAttribute;
use POData\Common\ODataException;

/**
 * Class ODataWriter
 * @package POData\Writers\Common
 */
class ODataWriter
{
    /**
     *
     * Reference to writer specialized for content type
     * @var IODataWriter
     */
    protected $iODataWriter;

    /**
     * Creates new instance of ODataWriter
     * 
     * @param string  $absoluteServiceUri The absolute service uri.
     * @param boolean $isPostV1           True if the server used version greater 
     * than 1 to generate the object model instance, False otherwise. 
     * @param string  $writerType         Type of the requested writer.(atom or json)
     */
    public function __construct($absoluteServiceUri, $isPostV1, $writerType) 
    {
        if ($writerType === 'json') {
            $this->iODataWriter = new JsonODataWriter($absoluteServiceUri, $isPostV1);
        } else {
            $this->iODataWriter = new AtomODataWriter($absoluteServiceUri, $isPostV1);
        }
    }

    /**
     * Create odata object model from the request description and transform it to 
     * required content type form
     * 
     * @param string $resultItem Object of requested content.
     * 
     * @return string Result in Atom or Json format 
     */
    public function writeRequest($resultItem)
    {
        if ($resultItem instanceof ODataURL) {
            $this->writeURL($resultItem);
        } else if ($resultItem instanceof ODataURLCollection) {
            $this->writeURLCollection($resultItem);
        } else if ($resultItem instanceof ODataPropertyContent) {
            $this->writeProperty($resultItem);
        } else if ($resultItem instanceof ODataFeed) { 
            $this->writeFeed($resultItem);
        } else if ($resultItem instanceof ODataEntry) {
            $this->writeEntry($resultItem);
        } 

        return $this->iODataWriter->getResult();
    }

    /**
     * Write top level link (url)
     * 
     * @param ODataURL $oDataUrl Object of ODataUrl
     * 
     * @return String Requested Url in format of Atom or JSON. 
     */
    protected function writeURL(ODataURL $oDataUrl)
    {
        $this->iODataWriter->writeBeginUrl($oDataUrl);
        $this->iODataWriter->writeEnd($oDataUrl);
    }

    /**
     * Write top level link collection
     * 
     * @param ODataURLCollection $oDataUrlCollection Object of ODataUrlCollection
     * 
     * @return String Requested UrlCollection in format of Atom or JSON.
     */
    protected function writeURLCollection (ODataURLCollection $oDataUrlCollection)
    {
        $this->iODataWriter->writeBeginUrlCollection($oDataUrlCollection);
        $this->iODataWriter->writeEnd($oDataUrlCollection);
    }

    /**
     * Write top level Feed/Collection 
     * 
     * @param ODataFeed $feed Object of ODataFeed
     * 
     * @return String Requested ODataFeed in format of Atom or JSON.
     */
    protected function writeFeed(ODataFeed $feed)
    {
        $this->iODataWriter->writeBeginFeed($feed);
        foreach ($feed->entries as $entry) {
            $this->writeEntry($entry);
        }
        $this->iODataWriter->writeEnd($feed);
    }

    /**
     * Write top level entry
     * 
     * @param ODataEntry $entry Object of ODataEntry
     * 
     * @return String Requested ODataEntry in format of Atom or JSON.
     */
    protected function writeEntry (ODataEntry $entry)
    {
        $this->iODataWriter->writeBeginEntry($entry);
        foreach ($entry->links as $link) {
            $this->iODataWriter->writeBeginLink($link, $link->isExpanded);

            if ($link->isExpanded && !is_null($link->expandedResult)) {
                if ($link->isCollection) {
                    $this->writeFeed($link->expandedResult);
                } else {
                    $this->writeEntry($link->expandedResult);
                }
            }
            $this->iODataWriter->writeEndLink($link->isExpanded);
        }
        $this->iODataWriter->preWriteProperties($entry);
        $this->iODataWriter->writeBeginProperties($entry->propertyContent);
        $this->iODataWriter->postWriteProperties($entry);
        $this->iODataWriter->writeEnd($entry);
    }

    /**
     * Write top level Property 
     * 
     * @param ODataPropertyContent $propertyContent Object of ODataPropertyContent
     * 
     * @return String Requested ODataProperty in format of Atom or JSON.
     */
    protected function writeProperty (ODataPropertyContent $propertyContent)
    {
        $this->iODataWriter->writeBeginProperties($propertyContent);
    }
}