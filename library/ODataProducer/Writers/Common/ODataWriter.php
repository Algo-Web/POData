<?php
/**
 * Contains ODataWriter class for write content in format (Atom or JSON)
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
use ODataProducer\Writers\Atom\AtomODataWriter;
use ODataProducer\Writers\Json\JsonODataWriter;
use ODataProducer\Writers\Common\IODataWriter;
use ODataProducer\Providers\Metadata\Type\String;
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
use ODataProducer\Common\ODataException;

/** 
 * Base class for write the request result to content format (Atom or json).
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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
    public function writeRequest ($resultItem)
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

        unset ($resultItem);
        return $this->iODataWriter->getResult();
    }

    /**
     * Write top level link (url)
     * 
     * @param ODataURL $oDataUrl Object of ODataUrl
     * 
     * @return String Requested Url in format of Atom or JSON. 
     */
    protected function writeURL (ODataURL $oDataUrl)
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
     * @param ODataFeed $odataFeed Object of ODataFeed
     * 
     * @return String Requested ODataFeed in format of Atom or JSON.
     */
    protected function writeFeed (ODataFeed $odataFeed)
    {
        $this->iODataWriter->writeBeginFeed($odataFeed);
        foreach ($odataFeed->entries as $odataEntry) {
            $this->writeEntry($odataEntry);
        }
        $this->iODataWriter->writeEnd($odataFeed);
    }

    /**
     * Write top level entry
     * 
     * @param ODataEntry $odataEntry Object of ODataEntry
     * 
     * @return String Requested ODataEntry in format of Atom or JSON.
     */
    protected function writeEntry (ODataEntry $odataEntry)
    {
        $this->iODataWriter->writeBeginEntry($odataEntry);
        foreach ($odataEntry->links as $odataLink) {
            $this->iODataWriter->writeBeginLink(
                $odataLink, $odataLink->isExpanded
            );
            if ($odataLink->isExpanded && !is_null($odataLink->expandedResult)) {
                if ($odataLink->isCollection) {
                    $this->writeFeed($odataLink->expandedResult);
                } else {
                    $this->writeEntry($odataLink->expandedResult);
                }
            }
            $this->iODataWriter->writeEndLink($odataLink->isExpanded);
        }
        $this->iODataWriter->preWriteProperties($odataEntry);
        $this->iODataWriter->writeBeginProperties($odataEntry->propertyContent);
        $this->iODataWriter->postWriteProperties($odataEntry);
        $this->iODataWriter->writeEnd($odataEntry);
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
?>