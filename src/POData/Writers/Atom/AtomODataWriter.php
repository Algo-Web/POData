<?php

namespace POData\Writers\Atom;

use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataURLCollection;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataMediaLink;
use POData\Writers\BaseODataWriter;
use POData\Common\Version;
use POData\Common\ODataConstants;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Common\InvalidOperationException;


/**
 * Class AtomODataWriter
 * @package POData\Writers\Atom
 */
class AtomODataWriter extends BaseODataWriter
{
    /**
     * Writer to which output (CSDL Document) is sent
     * 
     * @var \XMLWriter
     */
    public $xmlWriter;


    /**
     * Construct a new instance of AtomODataWriter.
     * 
     * @param string  $absoluteServiceUri The absolute service Uri.
     * @param boolean $isPostV1           True if the server used version 
     *                                    greater than 1 to generate the 
     *                                    object model instance, False otherwise. 
     */
    public function __construct($absoluteServiceUri, $isPostV1)
    {
        parent::__construct($absoluteServiceUri, $isPostV1);
        $this->xmlWriter = new \XMLWriter();
        $this->xmlWriter->openMemory();
        $this->xmlWriter->startDocument('1.0', 'UTF-8', 'yes');
        $this->xmlWriter->setIndent(4);
    }

    /** 
     * Begin write OData url
     * 
     * @param ODataUrl $url Object of ODataUrl to start writing url.
     * 
     * @return void
     */
    protected function startUrl(ODataURL $url)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_URI_ELEMENT_NAME);
        $this->xmlWriter->writeAttribute(ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_NAMESPACE);
        $this->xmlWriter->text($url->oDataUrl);
        $this->xmlWriter->endElement();
    }

    /**
     * Begin write odata links
     * 
     * @param ODataUrlCollection $urls Object of ODataUrlCollection to start writing collection of url.
     *
     * 
     * @return void
     */
    protected function startUrlCollection(ODataURLCollection $urls)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_LINKS_ELEMENT_NAME);
        $this->xmlWriter->writeAttribute(
            ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_NAMESPACE
        );
        $this->xmlWriter->endAttribute();
        if ($urls->count != null) {
            $this->xmlWriter->writeAttributeNs(
                ODataConstants::XMLNS_NAMESPACE_PREFIX,
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
                null, 
                ODataConstants::ODATA_METADATA_NAMESPACE
            );
            $this->xmlWriter->endAttribute();
            $this->xmlWriter->startElementNs(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
                ODataConstants::ROWCOUNT_ELEMENT,
                null
            );
            $this->xmlWriter->text($urls->count);
            $this->xmlWriter->endElement();
        }
        foreach ($urls->oDataUrls as $url) {
            $this->writeNodeValue(ODataConstants::ATOM_URI_ELEMENT_NAME, $url->oDataUrl);
        }
        
    }

    /**
     * Begin write OData Feed
     * 
     * @param ODataFeed $feed Object of OData feed to start writing feed
     * 
     * @return void
     */
    protected function startFeed(ODataFeed $feed)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_FEED_ELEMENT_NAME);
        if ($feed->isTopLevel) {
            $this->writeBaseUriAndDefaultNamespaces();
        }
    }

    /**
     * Write feed meta data
     *
     * @param ODataFeed $feed Feed whose metadata to be written
     * 
     * @return void
     */
    protected function writeFeedMetadata(ODataFeed $feed)
    {
        $this->writeNodeAttributeValue(
            ODataConstants::ATOM_TITLE_ELELMET_NAME, 
            ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, 
            ODataConstants::MIME_TEXTTYPE,
            $feed->title
        );
        $this->writeNodeValue(ODataConstants::ATOM_ID_ELEMENT_NAME, $feed->id);
        $this->writeNodeValue(
            ODataConstants::ATOM_UPDATED_ELEMENT_NAME, 
            date(DATE_ATOM)
        );
        $this->writeLinkNode($feed->selfLink, false);
        if ($feed->rowCount != null) {
            $this->xmlWriter->startElementNs(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, 
                ODataConstants::ROWCOUNT_ELEMENT, null
            );
            $this->xmlWriter->text($feed->rowCount);
            $this->xmlWriter->endElement();
        }
    }

    /**
     * Write end of feed
     * 
     * @param ODataFeed $feed Feed object to end feed writing.
     * 
     * @return void
     */
    protected function endFeed(ODataFeed $feed)
    {
        if ($feed->nextPageLink != null) {
            $this->writeLinkNode($feed->nextPageLink, false);
        }
        $this->xmlWriter->endElement();
    }
    /**
     * Start writing a entry
     *
     * @param ODataEntry $entry Entry to write
     * 
     * @return void
     */
    protected function startEntry(ODataEntry $entry)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_ENTRY_ELEMENT_NAME);
        if ($entry->isTopLevel) {
            $this->writeBaseUriAndDefaultNamespaces();
        }

        if (!is_null($entry->eTag)) {
            $this->xmlWriter->startAttributeNs(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, 
                ODataConstants::ATOM_ETAG_ATTRIBUTE_NAME, 
                null
            );
            $this->xmlWriter->text($entry->eTag);
            $this->xmlWriter->endAttribute();
        }
    }

    /**
     * Write entry meta data
     *
     * @param ODataEntry $entry Entry whose metadata to be written
     * 
     * @return void
     */
    protected function writeEntryMetadata(ODataEntry $entry)
    {
        $this->writeNodeValue(ODataConstants::ATOM_ID_ELEMENT_NAME, $entry->id);
        $this->writeNodeAttributeValue(
            ODataConstants::ATOM_TITLE_ELELMET_NAME, 
            ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, 
            ODataConstants::MIME_TEXTTYPE, 
            $entry->title
        );
        $this->writeNodeValue(
            ODataConstants::ATOM_UPDATED_ELEMENT_NAME,
            date(DATE_ATOM)
        );
        $this->xmlWriter->startElement(ODataConstants::ATOM_AUTHOR_ELEMENT_NAME);
        $this->xmlWriter->startElement(ODataConstants::ATOM_NAME_ELEMENT_NAME);
        $this->xmlWriter->endElement();
        $this->xmlWriter->endElement();
        if ($entry->isMediaLinkEntry) {
            $this->xmlWriter->startElement(ODataConstants::ATOM_LINK_ELEMENT_NAME);
            if ($entry->mediaLink->eTag != null) {
                $this->xmlWriter->startAttributeNs(
                    ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, 
                    ODataConstants::ATOM_ETAG_ATTRIBUTE_NAME, 
                    null
                );
                $this->xmlWriter->text($entry->mediaLink->eTag);
                $this->xmlWriter->endAttribute();
            }
            $this->xmlWriter->startAttribute(
                ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME
            );
            $this->xmlWriter->text(ODataConstants::ATOM_EDIT_MEDIA_RELATION_ATTRIBUTE_VALUE);
            $this->xmlWriter->endAttribute();
            
            $this->xmlWriter->startAttribute(ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME);
            $this->xmlWriter->text($entry->mediaLink->contentType);
            $this->xmlWriter->endAttribute();
            
            $this->xmlWriter->startAttribute(ODataConstants::ATOM_TITLE_ELELMET_NAME);
            $this->xmlWriter->text($entry->mediaLink->name);
            $this->xmlWriter->endAttribute();
            
            $this->xmlWriter->startAttribute(ODataConstants::ATOM_HREF_ATTRIBUTE_NAME);
            $this->xmlWriter->text($entry->mediaLink->editLink);
            $this->xmlWriter->endAttribute();
            $this->xmlWriter->endElement();
            
            foreach ($entry->mediaLinks as $mediaLink) {
                $this->xmlWriter->startElement(ODataConstants::ATOM_LINK_ELEMENT_NAME);
                if ($mediaLink->eTag != null) {
                    $this->xmlWriter->startAttributeNs(
                        ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, 
                        ODataConstants::ATOM_ETAG_ATTRIBUTE_NAME, 
                        null
                    );
                    $this->xmlWriter->text($mediaLink->eTag);
                    $this->xmlWriter->endAttribute();
                }
                $this->xmlWriter->startAttribute(
                    ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME
                );
                $this->xmlWriter->text(
                    "http://schemas.microsoft.com/ado/2007/08/dataservices/mediaresource/"
                    .$mediaLink->name
                );
                $this->xmlWriter->endAttribute();

                $this->xmlWriter->startAttribute(
                    ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME
                );
                $this->xmlWriter->text($mediaLink->contentType);
                $this->xmlWriter->endAttribute();

                $this->xmlWriter->startAttribute(
                    ODataConstants::ATOM_TITLE_ELELMET_NAME
                );
                $this->xmlWriter->text($mediaLink->name);
                $this->xmlWriter->endAttribute();

                $this->xmlWriter->startAttribute(
                    ODataConstants::ATOM_HREF_ATTRIBUTE_NAME
                );
                $this->xmlWriter->text($mediaLink->editLink);
                $this->xmlWriter->endAttribute();
                $this->xmlWriter->endElement();
            }
        }
    }
    /**
     * Write end of entry
     *
     * @param ODataEntry $entry ODataEntry object to end entry.
     * 
     * @return void
     */
    protected function endEntry(ODataEntry $entry)
    {
        $this->xmlWriter->endElement();
    }

    /**
     * Start writing a link
     *
     * @param ODataLink $link Link to write
     * @param Boolean   $isExpanded Is link expanded or not.
     * 
     * @return void
     */
    protected function startLink(ODataLink $link, $isExpanded)
    {
        $this->writeLinkNode($link, $isExpanded);
    }

    /**
     * Write link meta data
     *
     * @param ODataLink $link Link whose metadata to be written
     * @param Boolean   $isExpanded Is link expanded or not.
     * 
     * @return void
     */
    protected function writeLinkMetadata(ODataLink $link, $isExpanded)
    {
        if ($isExpanded) {
            $this->xmlWriter->startElementNS(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
                ODataConstants::ATOM_INLINE_ELEMENT_NAME, 
                null
            );
        }
    }

    /**
     * Write end of link
     *
     * @param boolean $isExpanded is link expanded or not.
     * 
     * @return void
     */
    protected function endLink($isExpanded)
    {
        if ($isExpanded) {
            $this->xmlWriter->endElement();
            $this->xmlWriter->endElement();
        }        
    }

    /**
     * Write the node which hold the entity properties as child
     * 
     * @param ODataEntry $entry ODataEntry object for pre writing properties.
     * 
     * @return void
     */
    public function preWriteProperties(ODataEntry $entry)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_CATEGORY_ELEMENT_NAME);
        $this->xmlWriter->writeAttribute(
            ODataConstants::ATOM_CATEGORY_TERM_ATTRIBUTE_NAME, 
            $entry->type
        );
        $this->xmlWriter->writeAttribute(
            ODataConstants::ATOM_CATEGORY_SCHEME_ATTRIBUTE_NAME,
            ODataConstants::ODATA_SCHEME_NAMESPACE
        );
        $this->xmlWriter->endElement();
        $this->xmlWriter->startElement(ODataConstants::ATOM_CONTENT_ELEMENT_NAME);
        if ($entry->isMediaLinkEntry) {
            $this->xmlWriter->writeAttribute(
                ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
                $entry->mediaLink->contentType
            );
            $this->xmlWriter->writeAttribute(
                ODataConstants::ATOM_CONTENT_SRC_ATTRIBUTE_NAME,
                $entry->mediaLink->srcLink
            );
            $this->xmlWriter->endElement();
            $this->xmlWriter->startElementNS(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
                ODataConstants::ATOM_PROPERTIES_ELEMENT_NAME, null
            );
        } else {
            $this->xmlWriter->writeAttribute(
                ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
                ODataConstants::MIME_APPLICATION_XML
            );
            $this->xmlWriter->startElementNS(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
                ODataConstants::ATOM_PROPERTIES_ELEMENT_NAME,
                null
            );
        }
    }

    /**
     * Write a property
     *
     * @param ODataProperty $property Property to be written
     * @param boolean       $isTopLevel     is link top level or not.
     * 
     * @return void
     */
    protected function beginWriteProperty(ODataProperty $property, $isTopLevel)
    {
        $this->xmlWriter->startElementNS(
            ODataConstants::ODATA_NAMESPACE_PREFIX,
            $property->name,
            null
        );
        if ($property->typeName!=null) {
            $this->xmlWriter->startAttributeNs(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
                ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
                null
            );
            $this->xmlWriter->text($property->typeName);
        }
        if ($isTopLevel) {
            $this->xmlWriter->startAttribute(ODataConstants::XMLNS_NAMESPACE_PREFIX);
            $this->xmlWriter->text(ODataConstants::ODATA_METADATA_NAMESPACE);
            $this->xmlWriter->startAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_NAMESPACE_PREFIX, null);
            $this->xmlWriter->text(ODataConstants::ODATA_NAMESPACE);
            $this->xmlWriter->startAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, null);
            $this->xmlWriter->text(ODataConstants::ODATA_METADATA_NAMESPACE);
        }
        if ($property->typeName!=null || $isTopLevel) {
            $this->xmlWriter->endAttribute();
        }
    }

    /**
     * Write end of a property
     * 
     * @param ODataPropertyContent $property Object of property which want to end.
     * 
     * @return void
     */
    protected function endWriteProperty(ODataPropertyContent $property)
    {
        $this->xmlWriter->endElement();
    }

    /**
     * Write after last property
     * 
     * @param ODataEntry $entry Entry object to post writing properties.
     *  
     * @return void
     */
    public function postWriteProperties(ODataEntry $entry)
    {
        if (!$entry->isMediaLinkEntry) {
            $this->xmlWriter->endElement();
        }
        $this->xmlWriter->endElement();
    }

    /**
     * Begin a complex property
     * 
     * @param ODataProperty $property whose value hold the complex property
     * 
     * @return void
     */
    protected function beginComplexProperty(ODataProperty $property)
    {
        //Nothing
    }

    /**
     * End  complex property
     * 
     * @return void
     */
    protected function endComplexProperty()
    {

    }

    /**
     * Begin an item in a collection
     *  
     * @param ODataBagContent $bag Bag property object to begin write property
     *
     * 
     * @return void
     */
    protected function beginBagPropertyItem(ODataBagContent $bag)
    {
        foreach ($bag->propertyContents as $content) {
            if ($content instanceof ODataPropertyContent) {
                $this->xmlWriter->startElementNs(
                    ODataConstants::ODATA_NAMESPACE_PREFIX, 
                    ODataConstants::COLLECTION_ELEMENT_NAME, 
                    null
                );
                    $this->writeBeginProperties($content);
                    $this->xmlWriter->endElement();
            } else {  //probably just a primitive string
                    $this->xmlWriter->startElementNs(
                        ODataConstants::ODATA_NAMESPACE_PREFIX, 
                        ODataConstants::COLLECTION_ELEMENT_NAME, 
                        null
                    );
                    $this->xmlWriter->text($content);
                    $this->xmlWriter->endElement();
            }
        }
    }

    /**
     * End an item in a collection
     * 
     * @return void
     */
    protected function endBagPropertyItem()
    {

    }

    /**
     * Write end of OData url
     * 
     * @param ODataURL $url ODataUrl object to end top level url.
     * 
     * @return void
     */
    protected function endUrl(ODataURL $url)
    {
        $this->xmlWriter->endElement();
    }

    /**
     * Write end of OData links
     * 
     * @param ODataUrlCollection $urls ODataUrlCollection object to end url collection.
     *
     * 
     * @return void
     */
    protected function endUrlCollection(ODataURLCollection $urls)
    {
        if ($urls->nextPageLink!=null) {
            $this->writeLinkNode($urls->nextPageLink, false);
        }
        $this->xmlWriter->endElement();
    }

    /**
     * Write null value
     * 
     * @param ODataProperty $property ODataProperty object to write null value
     * according to property type.
     * 
     * @return void
     */
    protected function writeNullValue(ODataProperty $property)
    {
        if (!(($property instanceof ODataBagContent) || ($property instanceof ODataPropertyContent))) {
            $this->xmlWriter->writeAttributeNs(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, 
                ODataConstants::ATOM_NULL_ATTRIBUTE_NAME, 
                null, 
                ODataConstants::XML_TRUE_LITERAL
            );
        }
    }

    /**
     * Write basic (primitive) value
     *
     * @param ODataProperty $property Object of ODataProperty
     * 
     * @return void
     */
    protected function writePrimitiveValue(ODataProperty $property)
    {
        $this->xmlWriter->text($property->value);
    }

    /**
     * Get the final result as string
     * 
     * @return string output of requested data in Atom format.
     */
    public function getOutput()
    {
        $this->xmlWriter->endDocument();
        return $this->xmlWriter->outputMemory(true);
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
        $xmlWriter = new \XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->startDocument('1.0', 'UTF-8', 'yes');
        $xmlWriter->setIndent(4);

        $xmlWriter->startElement(ODataConstants::XML_ERROR_ELEMENT_NAME);
        //$xmlWriter->writeAttributeNs(
        //    ODataConstants::XMLNS_NAMESPACE_PREFIX, 
        //    ODataConstants::XML_NAMESPACE_PREFIX, 
        //    ODataConstants::XML_NAMESPACE, 
        //    null
        //);
        $xmlWriter->writeAttribute(
            ODataConstants::XMLNS_NAMESPACE_PREFIX, 
            ODataConstants::ODATA_METADATA_NAMESPACE
        );
        $xmlWriter->endAttribute();
        $xmlWriter->startElement(ODataConstants::XML_ERROR_CODE_ELEMENT_NAME);
        if ($exception->getCode() != null) {
            $xmlWriter->text($exception->getCode());
        }
        $xmlWriter->endElement();
        $xmlWriter->startElement(ODataConstants::XML_ERROR_MESSAGE_ELEMENT_NAME);
        $xmlWriter->text($exception->getMessage());
        $xmlWriter->endElement();
        $xmlWriter->endElement();
        $xmlWriter->endDocument();
        return $xmlWriter->outputMemory(true);
    }

    /**
     * Function to create element only contain value without argument.
     * 
     * @param String $node  Element name
     * @param String $value Element value
     * 
     * @return void
     */
    public function writeNodeValue($node, $value)
    {
        $this->xmlWriter->startElement($node);
        $this->xmlWriter->text($value);
        $this->xmlWriter->endElement();
    }

    /**
     * Function to create element with one attribute and value. 
     *  
     * @param string $node           Element name
     * @param string $attribute      Attribute name
     * @param string $attributeValue Attribute value
     * @param string $nodeValue      Element value
     *  
     * @return void
     */
    public function writeNodeAttributeValue(
        $node,
        $attribute,
        $attributeValue,
        $nodeValue
    ) {
        $this->xmlWriter->startElement($node);
        $this->xmlWriter->writeAttribute($attribute, $attributeValue);
        $this->xmlWriter->text($nodeValue);
        $this->xmlWriter->endElement();
    }

    /**
     * Function to create link element with arguments.
     * 
     * @param ODataLink $link      Link object to make link element
     * @param Boolean   $isExpanded Is link expanded or not.
     * 
     * @return void
     */
    protected function writeLinkNode(ODataLink $link, $isExpanded)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_LINK_ELEMENT_NAME);
        $this->xmlWriter->writeAttribute(
            ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME,
            $link->name
        );
        if ($link->type != null) {
            $this->xmlWriter->writeAttribute(
                ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, 
                $link->type
            );
        }
        if ($link->title != null) {
            $this->xmlWriter->writeAttribute(
                ODataConstants::ATOM_TITLE_ELELMET_NAME, 
                $link->title
            );
        }
        $this->xmlWriter->writeAttribute(
            ODataConstants::ATOM_HREF_ATTRIBUTE_NAME, 
            $link->url
        );
        if (!$isExpanded) {
            $this->xmlWriter->endElement();
        }
    }

    /**
     * Function to write base uri and default namespaces for top level elements.
     * 
     * @return void
     */
    public function writeBaseUriAndDefaultNamespaces()
    {
        $this->xmlWriter->writeAttribute(
            ODataConstants::XML_BASE_ATTRIBUTE_NAME_WITH_PREFIX,
            $this->baseUri
        );
        $this->xmlWriter->writeAttributeNs(
            ODataConstants::XMLNS_NAMESPACE_PREFIX, 
            ODataConstants::ODATA_NAMESPACE_PREFIX, 
            null, 
            ODataConstants::ODATA_NAMESPACE
        );
        $this->xmlWriter->writeAttributeNs(
            ODataConstants::XMLNS_NAMESPACE_PREFIX, 
            ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, 
            null, 
            ODataConstants::ODATA_METADATA_NAMESPACE
        );
        $this->xmlWriter->writeAttribute(
            ODataConstants::XMLNS_NAMESPACE_PREFIX, 
            ODataConstants::ATOM_NAMESPACE
        );
    }
}
