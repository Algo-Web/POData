<?php
/**
 * Contains IODataWriter class is interface of OData Writer.
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
 * @package   ODataProducer_Writers_Common
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