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
use POData\Writers\Common\BaseODataWriter;
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
     * @var XMLWriter
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
     * Begin write odata url
     * 
     * @param ODataUrl &$oDataUrl Object of ODataUrl to start writing url.
     * 
     * @return void
     */
    protected function startUrl (ODataURL &$oDataUrl)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_URI_ELEMENT_NAME);
        $this->xmlWriter->writeAttribute(
            ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_NAMESPACE
        );
        $this->xmlWriter->text($oDataUrl->oDataUrl);
        $this->xmlWriter->endElement();
    }

    /**
     * Begin write odata links
     * 
     * @param ODataUrlCollection &$odataUrls Object of ODataUrlCollection
     * to start writing collection of url.
     * 
     * @return void
     */
    protected function startUrlCollection (ODataURLCollection &$odataUrls)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_LINKS_ELEMENT_NAME);
        $this->xmlWriter->writeAttribute(
            ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_NAMESPACE
        );
        $this->xmlWriter->endAttribute();
        if ($odataUrls->count!=null) {
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
            $this->xmlWriter->text($odataUrls->count);
            $this->xmlWriter->endElement();
        }
        foreach ($odataUrls->oDataUrls as $odataUrl) {
            $this->writeNodeValue(ODataConstants::ATOM_URI_ELEMENT_NAME, $odataUrl->oDataUrl);
        }
        
    }

    /**
     * Begin write odata Feed
     * 
     * @param ODataFeed &$odataFeed Object of OData feed to start writing feed
     * 
     * @return void
     */
    protected function startFeed(ODataFeed &$odataFeed)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_FEED_ELEMENT_NAME);
        if ($odataFeed->isTopLevel) {
            $this->writeBaseUriAndDefaultNamespaces();
        }
    }

    /**
     * Write feed meta data
     *
     * @param ODataFeed &$odataFeed Feed whose metadata to be written
     * 
     * @return void
     */
    protected function writeFeedMetadata(ODataFeed &$odataFeed)
    {
        $this->writeNodeAttributeValue(
            ODataConstants::ATOM_TITLE_ELELMET_NAME, 
            ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, 
            ODataConstants::MIME_TEXTTYPE,
            $odataFeed->title
        );
        $this->writeNodeValue(ODataConstants::ATOM_ID_ELEMENT_NAME, $odataFeed->id);
        $this->writeNodeValue(
            ODataConstants::ATOM_UPDATED_ELEMENT_NAME, 
            date(DATE_ATOM)
        );
        $this->writeLinkNode($odataFeed->selfLink, false);
        if ($odataFeed->rowCount != null) {
            $this->xmlWriter->startElementNs(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, 
                ODataConstants::ROWCOUNT_ELEMENT, null
            );
            $this->xmlWriter->text($odataFeed->rowCount);
            $this->xmlWriter->endElement();
        }
    }

    /**
     * Write end of feed
     * 
     * @param ODataFeed &$odataFeed Feed object to end feed writing.
     * 
     * @return void
     */
    protected function endFeed(ODataFeed &$odataFeed)
    {
        if ($odataFeed->nextPageLink!=null) {
            $this->writeLinkNode($odataFeed->nextPageLink, false);
        }
        $this->xmlWriter->endElement();
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
        $this->xmlWriter->startElement(ODataConstants::ATOM_ENTRY_ELEMENT_NAME);
        if ($odataEntry->isTopLevel) {
            $this->writeBaseUriAndDefaultNamespaces();
        }

        if (!is_null($odataEntry->eTag)) {
            $this->xmlWriter->startAttributeNs(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, 
                ODataConstants::ATOM_ETAG_ATTRIBUTE_NAME, 
                null
            );
            $this->xmlWriter->text($odataEntry->eTag);
            $this->xmlWriter->endAttribute();
        }
    }

    /**
     * Write entry meta data
     *
     * @param ODataEntry &$odataEntry Entry whose metadata to be written
     * 
     * @return void
     */
    protected function writeEntryMetadata(ODataEntry &$odataEntry)
    {
        $this->writeNodeValue(ODataConstants::ATOM_ID_ELEMENT_NAME, $odataEntry->id);
        $this->writeNodeAttributeValue(
            ODataConstants::ATOM_TITLE_ELELMET_NAME, 
            ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, 
            ODataConstants::MIME_TEXTTYPE, 
            $odataEntry->title
        );
        $this->writeNodeValue(
            ODataConstants::ATOM_UPDATED_ELEMENT_NAME,
            date(DATE_ATOM)
        );
        $this->xmlWriter->startElement(ODataConstants::ATOM_AUTHOR_ELEMENT_NAME);
        $this->xmlWriter->startElement(ODataConstants::ATOM_NAME_ELEMENT_NAME);
        $this->xmlWriter->endElement();
        $this->xmlWriter->endElement();
        if ($odataEntry->isMediaLinkEntry) {
            $this->xmlWriter->startElement(ODataConstants::ATOM_LINK_ELEMENT_NAME);
            if ($odataEntry->mediaLink->eTag != null) {
                $this->xmlWriter->startAttributeNs(
                    ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, 
                    ODataConstants::ATOM_ETAG_ATTRIBUTE_NAME, 
                    null
                );
                $this->xmlWriter->text($odataEntry->mediaLink->eTag);
                $this->xmlWriter->endAttribute();
            }
            $this->xmlWriter->startAttribute(
                ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME
            );
            $this->xmlWriter->text(ODataConstants::ATOM_EDIT_MEDIA_RELATION_ATTRIBUTE_VALUE);
            $this->xmlWriter->endAttribute();
            
            $this->xmlWriter->startAttribute(ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME);
            $this->xmlWriter->text($odataEntry->mediaLink->contentType);
            $this->xmlWriter->endAttribute();
            
            $this->xmlWriter->startAttribute(ODataConstants::ATOM_TITLE_ELELMET_NAME);
            $this->xmlWriter->text($odataEntry->mediaLink->name);
            $this->xmlWriter->endAttribute();
            
            $this->xmlWriter->startAttribute(ODataConstants::ATOM_HREF_ATTRIBUTE_NAME);
            $this->xmlWriter->text($odataEntry->mediaLink->editLink);
            $this->xmlWriter->endAttribute();
            $this->xmlWriter->endElement();
            
            foreach ($odataEntry->mediaLinks as $mediaLink) {
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
     * @param ODataEntry &$OdataEntry ODataEntry object to end entry.
     * 
     * @return void
     */
    protected function endEntry(ODataEntry &$OdataEntry)
    {
        $this->xmlWriter->endElement();
    }

    /**
     * Start writing a link
     *
     * @param ODataLink &$odatalink Link to write
     * @param Boolean   $isExpanded Is link expanded or not.
     * 
     * @return void
     */
    protected function startLink(ODataLink &$odatalink, $isExpanded)
    {
        $this->writeLinkNode($odatalink, $isExpanded);
    }

    /**
     * Write link meta data
     *
     * @param ODataLink &$odatalink Link whose metadata to be written
     * @param Boolean   $isExpanded Is link expanded or not.
     * 
     * @return void
     */
    protected function writeLinkMetadata(ODataLink &$odatalink, $isExpanded)
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
     * @param ODataEntry &$odataEntry ODataEntry object for pre writing properties.
     * 
     * @return void
     */
    public function preWriteProperties(ODataEntry &$odataEntry)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_CATEGORY_ELEMENT_NAME);
        $this->xmlWriter->writeAttribute(
            ODataConstants::ATOM_CATEGORY_TERM_ATTRIBUTE_NAME, 
            $odataEntry->type
        );
        $this->xmlWriter->writeAttribute(
            ODataConstants::ATOM_CATEGORY_SCHEME_ATTRIBUTE_NAME,
            ODataConstants::ODATA_SCHEME_NAMESPACE
        );
        $this->xmlWriter->endElement();
        $this->xmlWriter->startElement(ODataConstants::ATOM_CONTENT_ELEMENT_NAME);
        if ($odataEntry->isMediaLinkEntry) {
            $this->xmlWriter->writeAttribute(
                ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
                $odataEntry->mediaLink->contentType
            );
            $this->xmlWriter->writeAttribute(
                ODataConstants::ATOM_CONTENT_SRC_ATTRIBUTE_NAME,
                $odataEntry->mediaLink->srcLink
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
     * @param ODataProperty &$odataProperty Property to be written
     * @param boolean       $isTopLevel     is link top level or not.
     * 
     * @return void
     */
    protected function beginWriteProperty(ODataProperty &$odataProperty, $isTopLevel)
    {
        $this->xmlWriter->startElementNS(
            ODataConstants::ODATA_NAMESPACE_PREFIX,
            $odataProperty->name,
            null
        );
        if ($odataProperty->typeName!=null) {
            $this->xmlWriter->startAttributeNs(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
                ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
                null
            );
            $this->xmlWriter->text($odataProperty->typeName);
        }
        if ($isTopLevel) {
            $this->xmlWriter->startAttribute(ODataConstants::XMLNS_NAMESPACE_PREFIX);
            $this->xmlWriter->text(ODataConstants::ODATA_METADATA_NAMESPACE);
            $this->xmlWriter->startAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_NAMESPACE_PREFIX, null);
            $this->xmlWriter->text(ODataConstants::ODATA_NAMESPACE);
            $this->xmlWriter->startAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, null);
            $this->xmlWriter->text(ODataConstants::ODATA_METADATA_NAMESPACE);
        }
        if ($odataProperty->typeName!=null || $isTopLevel) {
            $this->xmlWriter->endAttribute();
        }
    }

    /**
     * Write end of a property
     * 
     * @param Object $kind Object of property which want to end.
     * 
     * @return void
     */
    protected function endWriteProperty($kind)
    {
        $this->xmlWriter->endElement();
    }

    /**
     * Write after last property
     * 
     * @param ODataEntry &$odataEntry Entry object to post writing properties.
     *  
     * @return void
     */
    public function postWriteProperties(ODataEntry &$odataEntry)
    {
        if (!$odataEntry->isMediaLinkEntry) {
            $this->xmlWriter->endElement();
        }
        $this->xmlWriter->endElement();
    }

    /**
     * Begin a complex property
     * 
     * @param ODataProperty &$odataProperty whose value hold the complex property
     * 
     * @return void
     */
    protected function beginComplexProperty(ODataProperty &$odataProperty)
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
     * @param ODataProperty &$odataBagProperty Bag property object 
     * to begin write property
     * 
     * @return void
     */
    protected function beginBagPropertyItem(ODataProperty &$odataBagProperty)
    {
        foreach ($odataBagProperty->value->propertyContents as  
            $odataPropertyContent) {
            if ($odataPropertyContent instanceof ODataPropertyContent) {
                $this->xmlWriter->startElementNs(
                    ODataConstants::ODATA_NAMESPACE_PREFIX, 
                    ODataConstants::COLLECTION_ELEMENT_NAME, 
                    null
                );
                    $this->writeBeginProperties($odataPropertyContent);
                    $this->xmlWriter->endElement();
            } else {
                    $this->xmlWriter->startElementNs(
                        ODataConstants::ODATA_NAMESPACE_PREFIX, 
                        ODataConstants::COLLECTION_ELEMENT_NAME, 
                        null
                    );
                    $this->xmlWriter->text($odataPropertyContent);
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
     * Write end of odata url
     * 
     * @param ODataURL &$odataUrl ODataUrl object to end top level url.
     * 
     * @return void
     */
    protected function endUrl(ODataURL &$odataUrl)
    {
        $this->xmlWriter->endElement();
    }

    /**
     * Write end of odata links
     * 
     * @param ODataUrlCollection &$odataUrlCollection ODataUrlCollection object 
     * to end url collection.
     * 
     * @return void
     */
    protected function endUrlCollection(ODataURLCollection &$odataUrlCollection)
    {
        if ($odataUrlCollection->nextPageLink!=null) {
            $this->writeLinkNode($odataUrlCollection->nextPageLink, false);
        }
        $this->xmlWriter->endElement();
    }

    /**
     * Write null value
     * 
     * @param ODataProperty &$odataProperty ODataProperty object to write null value
     * according to property type.
     * 
     * @return void
     */
    protected function writeNullValue(ODataProperty &$odataProperty)
    {
        if (!(($odataProperty instanceof ODataBagContent) 
            || ($odataProperty instanceof ODataPropertyContent))
        ) {
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
     * @param ODataProperty &$odataProperty Object of ODataProperty
     * 
     * @return void
     */
    protected function writePrimitiveValue(ODataProperty &$odataProperty)
    {
        $this->xmlWriter->text($odataProperty->value);
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
     * @param ODataException &$exception              Exception to serialize
     * @param boolean        $serializeInnerException if set to true,
     * serialize the inner exception if $exception is an ODataException.
     * 
     * @return void
     */
    public static function serializeException(ODataException &$exception, $serializeInnerException)
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
     * @param ODataLink &$link      Link object to make link element
     * @param Boolean   $isExpanded Is link expanded or not.
     * 
     * @return void
     */
    public function writeLinkNode(ODataLink &$link, $isExpanded)
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
