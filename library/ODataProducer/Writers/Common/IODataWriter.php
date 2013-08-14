<?php
/**
 * Contains IODataWriter class is interface of OData Writer.
 * 
*/
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
 * OData writer interface.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */

interface IODataWriter
{
    /**
     * Start writing a feed
     *
     * @param ODataFeed &$odataFeed Feed to write
     * 
     * @return nothing
     */

    public function writeBeginFeed(ODataFeed &$odataFeed);

    /**
     * Start writing an entry.
     *
     * @param ODataEntry &$odataEntry Entry to write
     * 
     * @return nothing
     */
    public function writeBeginEntry(ODataEntry &$odataEntry);

    /**
     * Start writing a link.
     * 
     * @param ODataLink &$odataLink Link to write.
     * @param Boolean   $isExpanded If entry type is Expanded or not.
     * 
     * @return nothing
     */
    public function writeBeginLink(ODataLink &$odataLink, $isExpanded);

    /** 
     * Start writing a Properties.
     * 
     * @param ODataPropertyContent &$odataProperties ODataProperty Object to write.
     * 
     * @return nothing
     */
    public function writeBeginProperties(ODataPropertyContent &$odataProperties);
    
    /**
     * Start writing a top level url
     *  
     * @param ODataURL &$odataUrl ODataUrl object to write.
     * 
     * @return nothing
     */
    public function writeBeginUrl(ODataURL &$odataUrl);
    
    /**
     * Start writing a top level url collection
     * 
     * @param ODataUrlCollection &$odataUrls ODataUrlCollection to Write.
     * 
     * @return nothing
     */
    public function writeBeginUrlCollection(ODataURLCollection &$odataUrls); 

    /**
     * Finish writing an ODataEntry/ODataLink/ODataURL/ODataURLCollection.
     * 
     * @param ObjectType $kind Type of the top level object
     * 
     * @return nothing
     */
    public function writeEnd($kind);

    /**
     * Get the result as string
     *  
     * @return string Result in requested format i.e. Atom or JSON.
     * 
     * @return nothing
     */
    public function getResult();
}
?>