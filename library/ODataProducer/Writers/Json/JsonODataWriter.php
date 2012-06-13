<?php
/**
 * JSON format OData writer 
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Json
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
namespace ODataProducer\Writers\Json;
use ODataProducer\ObjectModel\ODataFeed;
use ODataProducer\ObjectModel\ODataEntry;
use ODataProducer\ObjectModel\ODataURLCollection;
use ODataProducer\ObjectModel\ODataURL;
use ODataProducer\ObjectModel\ODataLink;
use ODataProducer\ObjectModel\ODataPropertyContent;
use ODataProducer\ObjectModel\ODataBagContent;
use ODataProducer\ObjectModel\ODataProperty;
use ODataProducer\ObjectModel\ODataMediaLink;
use ODataProducer\Writers\Json\JsonWriter;
use ODataProducer\Writers\Common\BaseODataWriter;
use ODataProducer\Common\Version;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\InvalidOperationException;
/**
 * JSON format OData writer.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Json
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class JsonODataWriter extends BaseODataWriter
{
    /**
     * Json output writer.
     *
     */
    private $_writer;
  
    /**
     * Odata version.
     *      
     */
    private $_isPostV1;
  
    /**
     * Constructs and initializes the Json output writer.
     * 
     * @param String  $absoluteServiceUri Absolute url
     * @param Boolean $isPostV1           OData version above to v1 or not
     * 
     * @return Void
     */
    public function __construct($absoluteServiceUri, $isPostV1)
    {
        $this->_writer = new JsonWriter('');
        $this->_isPostV1 = $isPostV1;
    }
  
    /**
     * Enter the top level scope.
     *
     * @return void
     */
    protected function enterTopLevelScope()
    {
        // { "d" :
        $this->_writer->startObjectScope();
        $this->_writer->writeDataWrapper();
    }
  
    /**
     * Leave the top level scope.
     * 
     * @return void
     */
    protected function leaveTopLevelScope()
    {
        // }
        $this->_writer->endScope();
    }
  
    /**
     * begin write odata url
     * 
     * @param ODataURL &$odataUrl OData url to write
     * 
     * @return void
     */
    protected function startUrl(ODataURL &$odataUrl)
    {
        $this->enterTopLevelScope();
        $this->_writer->startObjectScope();
      
        $this->_writer->writeName(ODataConstants::JSON_URI_STRING);
        $this->_writer->writeValue($odataUrl->oDataUrl);
      
        $this->_writer->endScope();
        $this->_writer->endScope();
    }
    
    /** 
     * begin write odata links
     * 
     * @param ODataURLCollection &$odataUrlCollection url collection to write
     * 
     * @return void
     */
    protected function startUrlCollection(ODataURLCollection &$odataUrlCollection)
    {
        $this->enterTopLevelScope();
        if ($this->_isPostV1) {
            // {
            $this->_writer->startObjectScope();
      
            // Json Format V2:
            // "__results":
            $this->_writer->writeDataArrayName();
        }
        // [
        $this->_writer->startArrayScope();
        foreach ($odataUrlCollection->oDataUrls as $odataUrl) {
            $this->_writer->startObjectScope();
            $this->_writer->writeName(ODataConstants::JSON_URI_STRING);
            $this->_writer->writeValue($odataUrl->oDataUrl);
            $this->_writer->endScope();
        }
    }
  
    /**
     * Start writing a feed
     *
     * @param ODataFeed &$odataFeed Feed to write
     * 
     * @return void 
     */
    protected function startFeed(ODataFeed &$odataFeed)
    {
        if ($odataFeed->isTopLevel) {
            $this->enterTopLevelScope();
        }
    
        if ($this->_isPostV1) {
            // {
            $this->_writer->startObjectScope();
            // Json Format V2:
            // "__results":
            $this->_writer->writeDataArrayName();
        }

        // [
        $this->_writer->startArrayScope();
    }
  
    /**
     * Write feed metadata
     *
     * @param ODataFeed &$odataFeed Feed to write
     * 
     * @return void 
     */
    protected function writeFeedMetadata(ODataFeed &$odataFeed)
    {    
    }
  
    /**
     * End writing feed
     *
     * @param ODataFeed &$odataFeed Feed to write
     * 
     * @return void 
     */
    protected function endFeed(ODataFeed &$odataFeed)
    {
        // ]
        $this->_writer->endScope();
    
        if ($this->_isPostV1) {
            if ($odataFeed->isTopLevel) {
                $this->writeRowCount($odataFeed->rowCount);
            }
            $this->writeNextPageLink($odataFeed->nextPageLink);

            // }, End object scope for V2
            $this->_writer->endScope();
        }
        if ($odataFeed->isTopLevel) {
            $this->leaveTopLevelScope();
        }
    }
  
    /**
     * Start writing a entry
     *
     * @param ODataEntry &$odataEntry Entry to write
     * 
     * @return void 
     */
    protected function startEntry(ODataEntry &$odataEntry)
    {
        if ($odataEntry->isTopLevel) {
            $this->enterTopLevelScope();

            if ($this->_isPostV1) {
                // {
                $this->_writer->startObjectScope();

                // Json Format V2:
                // "__results":
                $this->_writer->writeDataArrayName();
            }
        }

        // {
        $this->_writer->startObjectScope();  
    }
  
    /**
     * Write metadata information for the entry.
     *
     * @param ODataEntry &$odataEntry Entry to write metadata for.
     * 
     * @return void 
     */
    protected function writeEntryMetadata(ODataEntry &$odataEntry)
    {
        // __metadata : { uri: "Uri", type: "Type" [Media Link Properties] }
        if ($odataEntry->id != null 
            || $odataEntry->type != null 
            || $odataEntry->eTag != null
        ) {
            // "__metadata"
            $this->_writer->writeName(ODataConstants::JSON_METADATA_STRING);
            $this->_writer->startObjectScope();

            // Write uri value only for entity types
            if ($odataEntry->id != null) {
                $this->_writer->writeName(ODataConstants::JSON_URI_STRING);
                $this->_writer->writeValue($odataEntry->id);
            }
        
            // Write the etag property, if the entry has etag properties.
            if ($odataEntry->eTag != null) {
                $this->_writer->writeName(ODataConstants::JSON_ETAG_STRING);
                $this->_writer->writeValue($odataEntry->eTag);
            }

            // Write the type property, if the entry has type properties.
            if ($odataEntry->type != null) {
                $this->_writer->writeName(ODataConstants::JSON_TYPE_STRING);
                $this->_writer->writeValue($odataEntry->type);
            }
        }
      
        // Media links.
        if ($odataEntry->isMediaLinkEntry) {
            if ($odataEntry->mediaLink != null) {
                $this->_writer->writeName(ODataConstants::JSON_EDITMEDIA_STRING);
                $this->_writer->writeValue($odataEntry->mediaLink->editLink);

                $this->_writer->writeName(ODataConstants::JSON_MEDIASRC_STRING);
                $this->_writer->writeValue($odataEntry->mediaLink->srcLink);
          
                $this->_writer->writeName(ODataConstants::JSON_CONTENTTYPE_STRING);
                $this->_writer->writeValue($odataEntry->mediaLink->contentType);

                if ($odataEntry->mediaLink->eTag != null) {
                    $this->_writer->writeName(ODataConstants::JSON_MEDIAETAG_STRING);
                    $this->_writer->writeValue($odataEntry->mediaLink->eTag);
                }
          
                $this->_writer->endScope();
            }

            // writing named resource streams
            foreach ($odataEntry->mediaLinks as $mediaLink) {
                $this->_writer->writeName($mediaLink->name);
                $this->_writer->startObjectScope();

                $this->_writer->writeName(ODataConstants::JSON_MEDIASRC_STRING);
                $this->_writer->writeValue($mediaLink->srcLink);

                $this->_writer->writeName(ODataConstants::JSON_CONTENTTYPE_STRING);
                $this->_writer->writeValue($mediaLink->contentType);

                if ($mediaLink->eTag != null) {
                    $this->_writer->writeName(ODataConstants::JSON_MEDIAETAG_STRING);
                    $this->_writer->writeValue($mediaLink->eTag);
                }

                $this->_writer->endScope();
            }
        } else { 
            $this->_writer->endScope();
        }
    }
  
    /**
     * Write end of entry.
     *
     * @param ODataEntry &$odataEntry entry to end
     * 
     * @return void  
     */
    protected function endEntry(ODataEntry &$odataEntry)
    {
        // }
        $this->_writer->endScope();

        if ($odataEntry->isTopLevel) {
            if ($this->_isPostV1) {
                // }, End object scope for V2
                $this->_writer->endScope();
            }

            $this->leaveTopLevelScope();
        }
    }
  
    /**
     * Start writing a link.
     *
     * @param ODataLink &$odataLink Link to write
     * @param Boolean   $isExpanded expanded or not
     * 
     * @return void  
     */
    protected function startLink(ODataLink &$odataLink, $isExpanded)
    {
        // "<linkname>" :
        $this->_writer->writeName($odataLink->title);
    }
  
    /**
     * Start writing a link metadata.
     *
     * @param ODataLink &$odataLink Link to write
     * @param Boolean   $isExpanded expanded or not
     * 
     * @return void  
     */
    protected function writeLinkMetadata(ODataLink &$odataLink, $isExpanded)
    {
        if (!$odataLink->expandedResult) {
            $this->_writer->startObjectScope();
            $this->_writer->writeName(ODataConstants::JSON_DEFERRED_STRING);
            $this->_writer->startObjectScope();
            $this->_writer->writeName(ODataConstants::JSON_URI_STRING);
            $this->_writer->writeValue($odataLink->url);
            $this->_writer->endScope();
        }
    }
  
    /**
     * Write end of link.
     *
     * @param Boolean $isExpanded expanded or not
     * 
     * @return void 
     */
    protected function endLink($isExpanded)
    {
        if (!$isExpanded) {
            // }
            $this->_writer->endScope();
        }
    }
  
    /**
     * Writes the row count.
     *
     * @param int $count Row count value.
     * 
     * @return void
     */
    protected function writeRowCount($count)
    {
        if ($count != null) {
            $this->_writer->writeName(ODataConstants::JSON_ROWCOUNT_STRING);
            $this->_writer->writeValue($count);
        }
    }
  
    /**
     * Writes the next page link.
     *
     * @param string $nextPageLinkUri Uri for next page link.
     * 
     * @return void
     */
    protected function writeNextPageLink($nextPageLinkUri)
    {
        // "__next" : uri 
        if ($nextPageLinkUri != null) {
            $this->_writer->writeName(ODataConstants::JSON_NEXT_STRING);
            $this->_writer->writeValue($nextPageLinkUri->url);
        }
    }
  
    /**
     * Pre Write Properties.
     * Do nothing for json
     * 
     * @param ODataEntry &$odataEntry OData entry to write.
     * 
     * @return void 
     */
    public function preWriteProperties(ODataEntry &$odataEntry)
    {
    }
  
    /**
     * Begin write property.
     *
     * @param ODataProperty &$odataProperty property to write.
     * @param Boolean       $isTopLevel     is top level or not.
     * 
     * @return void 
     */
    protected function beginWriteProperty(ODataProperty &$odataProperty, $isTopLevel)
    {
        if ($isTopLevel) {
            $this->enterTopLevelScope();
            if ($this->_isPostV1) {
                // {
                $this->_writer->startObjectScope();
                // Json Format V2:
                // "__results":
                $this->_writer->writeDataArrayName();
            }

            // {
            $this->_writer->startObjectScope();
        }

        $this->_writer->writeName($odataProperty->name);
    }
  
    /**
     * Begin write complex property.
     *
     * @param ODataProperty &$odataProperty property to write.
     * 
     * @return void 
     */
    protected function beginComplexProperty(ODataProperty &$odataProperty)
    {
        // {
        $this->_writer->startObjectScope();

        // __metadata : { Type : "typename" }
        $this->_writer->writeName(ODataConstants::JSON_METADATA_STRING);
        $this->_writer->startObjectScope();

        $this->_writer->writeName(ODataConstants::JSON_TYPE_STRING);
        $this->_writer->writeValue($odataProperty->typeName);

        $this->_writer->endScope();
    }
  
    /**
     * End write complex property.
     *
     * @return void
     */
    protected function endComplexProperty()
    {
        // }
        $this->_writer->endScope();
    }
  
    /**
     * Begin an item in a collection
     *  
     * @param ODataProperty &$odataBagProperty bag property to write
     * 
     * @return void 
     */
    protected function beginBagPropertyItem(ODataProperty &$odataBagProperty)
    {
        // {
        $this->_writer->startObjectScope();

        // __metadata : { Type : "typename" }
        $this->_writer->writeName(ODataConstants::JSON_METADATA_STRING);
        $this->_writer->startObjectScope();

        $this->_writer->writeName(ODataConstants::JSON_TYPE_STRING);
        $this->_writer->writeValue($odataBagProperty->typeName);

        // }
        $this->_writer->endScope();

        // "__results":
        $this->_writer->writeDataArrayName();

        // [
        $this->_writer->startArrayScope();

        foreach ($odataBagProperty->value->propertyContents as $odataPropertyContent) {
            if ($odataPropertyContent instanceof ODataPropertyContent) {
                $this->_writer->startObjectScope();
                $this->writeBeginProperties($odataPropertyContent);
                $this->_writer->endScope();
            } else {
                // retrieving the collection datatype in order 
                //to write in json specific format, with in chords or not
                preg_match('#\((.*?)\)#', $odataBagProperty->typeName, $type);
                $this->_writer->writeValue($odataPropertyContent, $type[1]);
            }
        }

        // ]
        $this->_writer->endScope();
    }
    
    /**
     * End an item in a collection
     * 
     * @return void 
     */
    protected function endBagPropertyItem()
    {
        // }
        $this->_writer->endScope();
    }
    
    /**
     * Write end of odata url
     * 
     * @param ODataURL &$odataUrl OData url to end
     * 
     * @return void 
     */
    protected function endUrl(ODataURL &$odataUrl)
    {
      
    }
    
    /**
     * Write end of odata links
     * 
     * @param ODataURLCollection &$odataUrlCollection odata url collection to end
     * 
     * @return void 
     */
    protected function endUrlCollection(ODataURLCollection &$odataUrlCollection)
    {
        // ]
        $this->_writer->endScope();

        if ($this->_isPostV1) {
            $this->writeRowCount($odataUrlCollection->count);
            $this->writeNextPageLink($odataUrlCollection->nextPageLink);
            // }, End object scope for V2
            $this->_writer->endScope();
        }

        $this->leaveTopLevelScope();
    }
     
    /**
     * End write property.
     *
     * @param object $kind kind of operatino to end
     * 
     * @return void 
     */
    protected function endWriteProperty($kind)
    {   
        if ($kind->isTopLevel) {
            // }
            $this->_writer->endScope();
            if ($this->_isPostV1) {
                // }
                $this->_writer->endScope();
            }

            $this->leaveTopLevelScope();
        } 
    }
  
    /**
     * post write properties
     *
     * @param ODataEntry &$odataEntry OData entry
     *
     * @return void 
     */
    public function postWriteProperties(ODataEntry &$odataEntry)
    {
    }
  
    /**
     * write null value.
     *
     * @param ODataProperty &$odataProperty odata property
     *
     * @return void 
     */
    protected function writeNullValue(ODataProperty &$odataProperty)
    {
        $this->_writer->writeValue("null");
    }
  
    /**
     * serialize exception.
     * 
     * @param ODataException &$exception              Exception to serialize
     * @param Boolean        $serializeInnerException if set to true
     * 
     * serialize the inner exception if $exception is an ODataException.
     * 
     * @return void  
     */
    public static function serializeException(ODataException &$exception, $serializeInnerException)
    {
        $writer = new JsonWriter('');
        // Wrapper for error.
        $writer->startObjectScope();
        // "error"
        $writer->writeName(ODataConstants::JSON_ERROR);
        $writer->startObjectScope();

        // "code"
        if ($exception->getCode() != null) {
            $writer->writeName(ODataConstants::JSON_ERROR_CODE);
            $writer->writeValue($exception->getCode());
        }

        // "message"
        $writer->writeName(ODataConstants::JSON_ERROR_MESSAGE);
        $writer->startObjectScope();
        // "lang"
        $writer->writeName(ODataConstants::XML_LANG_ATTRIBUTE_NAME);
        //if ($exception->getMsgLang() != null) {
        $writer->writeValue('en-US');
        //}
        // "value"
        $writer->writeName(ODataConstants::JSON_ERROR_VALUE);
        $writer->writeValue($exception->getMessage());

        $writer->endScope();
        $writer->endScope();
        $writer->endScope();
        return $writer->getJsonOutput();
    }
  
    /**
     * attempts to convert the specified primitive value to a serializable string.
     *
     * @param ODataProperty &$odataProperty value to convert.
     * 
     * @return void 
     */
    protected function writePrimitiveValue(ODataProperty &$odataProperty)
    {
        $this->_writer->writeValue($odataProperty->value, $odataProperty->typeName);
    }
  
    /**
     * Get the Json final output.
     *
     * @return void 
     */
    protected function getOutput()
    {
        return $this->_writer->getJsonOutput();
    }
}
?>