<?php

namespace POData\Writers;

use POData\Common\ODataException;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataProperty;


/**
 * Class IODataWriter
 * @package POData\Writers\Common
 */
interface IODataWriter
{
    /**
     * Start writing a feed
     *
     * @param ODataFeed $feed Feed to write
     * 
     * @return void
     */

    public function writeBeginFeed(ODataFeed $feed);

    /**
     * Start writing an entry.
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return void
     */
    public function writeBeginEntry(ODataEntry $entry);

    /**
     * Start writing a link.
     * 
     * @param ODataLink $link Link to write.
     * @param Boolean   $isExpanded If entry type is Expanded or not.
     * 
     * @return void
     */
    public function writeBeginLink(ODataLink $link, $isExpanded);

    /** 
     * Start writing a Properties.
     * 
     * @param ODataPropertyContent $properties ODataProperty Object to write.
     * 
     * @return void
     */
    public function writeBeginProperties(ODataPropertyContent $properties);
    
    /**
     * Start writing a top level url
     *  
     * @param ODataURL $url ODataUrl object to write.
     * 
     * @return void
     */
    public function writeBeginUrl(ODataURL $url);
    
    /**
     * Start writing a top level url collection
     * 
     * @param ODataUrlCollection $urls ODataUrlCollection to Write.
     * 
     * @return void
     */
    public function writeBeginUrlCollection(ODataURLCollection $urls);

    /**
     * Finish writing an ODataEntry/ODataLink/ODataURL/ODataURLCollection.
     * 
     * @param ODataFeed|ODataEntry|ODataURL|ODataURLCollection|ODataProperty $kind Type of the top level object
     * 
     * @return void
     */
    public function writeEnd($kind);

    /**
     * Get the result as string
     *  
     * @return string Result in requested format i.e. Atom or JSON.
     */
    public function getResult();
}