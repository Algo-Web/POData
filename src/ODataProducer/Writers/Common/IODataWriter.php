<?php

namespace ODataProducer\Writers\Common;

use ODataProducer\Common\ODataException;
use ODataProducer\ObjectModel\ODataURL;
use ODataProducer\ObjectModel\ODataURLCollection;
use ODataProducer\ObjectModel\ODataFeed;
use ODataProducer\ObjectModel\ODataEntry;
use ODataProducer\ObjectModel\ODataLink;
use ODataProducer\ObjectModel\ODataMediaLink;
use ODataProducer\ObjectModel\ODataBagContent;
use ODataProducer\ObjectModel\ODataPropertyContent;
use ODataProducer\ObjectModel\ODataProperty;
use ODataProducer\ObjectModel\XMLAttribute;


/**
 * Class IODataWriter
 * @package ODataProducer\Writers\Common
 */
interface IODataWriter
{
    /**
     * Start writing a feed
     *
     * @param ODataFeed &$odataFeed Feed to write
     * 
     * @return void
     */

    public function writeBeginFeed(ODataFeed &$odataFeed);

    /**
     * Start writing an entry.
     *
     * @param ODataEntry &$odataEntry Entry to write
     * 
     * @return void
     */
    public function writeBeginEntry(ODataEntry &$odataEntry);

    /**
     * Start writing a link.
     * 
     * @param ODataLink &$odataLink Link to write.
     * @param Boolean   $isExpanded If entry type is Expanded or not.
     * 
     * @return void
     */
    public function writeBeginLink(ODataLink &$odataLink, $isExpanded);

    /** 
     * Start writing a Properties.
     * 
     * @param ODataPropertyContent &$odataProperties ODataProperty Object to write.
     * 
     * @return void
     */
    public function writeBeginProperties(ODataPropertyContent &$odataProperties);
    
    /**
     * Start writing a top level url
     *  
     * @param ODataURL &$odataUrl ODataUrl object to write.
     * 
     * @return void
     */
    public function writeBeginUrl(ODataURL &$odataUrl);
    
    /**
     * Start writing a top level url collection
     * 
     * @param ODataUrlCollection &$odataUrls ODataUrlCollection to Write.
     * 
     * @return void
     */
    public function writeBeginUrlCollection(ODataURLCollection &$odataUrls); 

    /**
     * Finish writing an ODataEntry/ODataLink/ODataURL/ODataURLCollection.
     * 
     * @param ObjectType $kind Type of the top level object
     * 
     * @return void
     */
    public function writeEnd($kind);

    /**
     * Get the result as string
     *  
     * @return string Result in requested format i.e. Atom or JSON.
     * 
     * @return void
     */
    public function getResult();
}