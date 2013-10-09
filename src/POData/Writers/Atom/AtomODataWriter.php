<?php

namespace POData\Writers\Atom;

use POData\Common\Version;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataURLCollection;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataMediaLink;
use POData\Writers\IODataWriter;
use POData\Common\ODataConstants;
use POData\Common\MimeTypes;
use POData\Common\ODataException;
use POData\Providers\ProvidersWrapper;

/**
 * Class AtomODataWriter
 * @package POData\Writers\Atom
 */
class AtomODataWriter implements IODataWriter
{
    /**
     * Writer to which output (CSDL Document) is sent
     * 
     * @var \XMLWriter
     */
    public $xmlWriter;

	/**
	 *
	 * The service base uri
	 * @var string
	 */
	protected $baseUri;

    /**
     * Construct a new instance of AtomODataWriter.
     * 
     * @param string  $absoluteServiceUri The absolute service Uri.
     */
    public function __construct($absoluteServiceUri)
    {
	    $this->baseUri = $absoluteServiceUri;

        $this->xmlWriter = new \XMLWriter();
        $this->xmlWriter->openMemory();
        $this->xmlWriter->startDocument('1.0', 'UTF-8', 'yes');
        $this->xmlWriter->setIndent(4);
    }

	/**
	 * Determines if the given writer is capable of writing the response or not
	 * @param Version $responseVersion the OData version of the response
	 * @param string $contentType the Content Type of the response
	 * @return boolean true if the writer can handle the response, false otherwise
	 */
	public function canHandle(Version $responseVersion, $contentType)
	{

		$parts = explode(";", $contentType);

		//first 2 parts are for service documents, second part is for Resources
		//TODO: i'm not sold about this first part not being constrained to v1 (or maybe v2)..but it's how WS DS works. See #94
		return in_array(MimeTypes::MIME_APPLICATION_XML, $parts) || in_array(MimeTypes::MIME_APPLICATION_ATOMSERVICE, $parts) || in_array(MimeTypes::MIME_APPLICATION_ATOM, $parts);
	}

	/**
	 * Write the given OData model in a specific response format
	 *
	 * @param  ODataURL|ODataURLCollection|ODataPropertyContent|ODataFeed|ODataEntry $model Object of requested content.
	 *
	 * @return AtomODataWriter
	 */
	public function write($model)
	{
		if ($model instanceof ODataURL) {
			return $this->writeURL($model);
		}

		if ($model instanceof ODataURLCollection) {
			return $this->writeURLCollection($model);
		}

		if ($model instanceof ODataPropertyContent) {
			return $this->writeProperties($model, true);
		}

		if ($model instanceof ODataFeed) {
			return $this->writeFeed($model, true);
		}

		if ($model instanceof ODataEntry) {
			return $this->writeEntry($model, true);
		}

		return $this;
	}

    /** 
     * @param ODataUrl $url the url to write.
     * 
     * @return AtomODataWriter
     */
	protected function writeURL(ODataURL $url)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_URI_ELEMENT_NAME);
        $this->xmlWriter->writeAttribute(ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_NAMESPACE);
        $this->xmlWriter->text($url->url);
        $this->xmlWriter->endElement();

	    return $this;
    }

    /**
     * Begin write odata links
     * 
     * @param ODataUrlCollection $urls Object of ODataUrlCollection to start writing collection of url.
     *
     * 
     * @return AtomODataWriter
     */
    public function writeUrlCollection(ODataURLCollection $urls)
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
        foreach ($urls->urls as $url) {
            $this->writeNodeValue(ODataConstants::ATOM_URI_ELEMENT_NAME, $url->url);
        }


	    if ($urls->nextPageLink!=null) {
		    $this->writeLinkNode($urls->nextPageLink, false);
	    }
	    $this->xmlWriter->endElement();

	    return $this;
        
    }

    /**
     * Begin write OData Feed
     * 
     * @param ODataFeed $feed Object of OData feed to start writing feed
     * @param boolean $isTopLevel indicates if this is the top level feed in the response
     * 
     * @return AtomODataWriter
     */
    protected function writeFeed(ODataFeed $feed, $isTopLevel = false)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_FEED_ELEMENT_NAME);
        if ($isTopLevel) {
            $this->writeBaseUriAndDefaultNamespaces();
        }

	    $this
		    ->writeNodeAttributeValue(
			    ODataConstants::ATOM_TITLE_ELELMET_NAME,
			    ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
			    MimeTypes::MIME_TEXTTYPE,
			    $feed->title
		    )
		    ->writeNodeValue(ODataConstants::ATOM_ID_ELEMENT_NAME, $feed->id)
		    ->writeNodeValue(ODataConstants::ATOM_UPDATED_ELEMENT_NAME, date(DATE_ATOM) )
		    ->writeLinkNode($feed->selfLink, false);

	    if ($feed->rowCount != null) {
		    $this->xmlWriter->startElementNs(
			    ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
			    ODataConstants::ROWCOUNT_ELEMENT, null
		    );
		    $this->xmlWriter->text($feed->rowCount);
		    $this->xmlWriter->endElement();
	    }

	    foreach ($feed->entries as $entry) {
		    $this->writeEntry($entry);
	    }

	    if ($feed->nextPageLink != null) {
		    $this->writeLinkNode($feed->nextPageLink, false);
	    }
	    $this->xmlWriter->endElement();


	    return $this;
    }


	/**
	 * Write top level entry
	 *
	 * @param ODataEntry $entry Object of ODataEntry
	 * @param boolean $isTopLevel
	 *
	 * @return AtomODataWriter
	 */
	protected function writeEntry(ODataEntry $entry, $isTopLevel = false)
	{
		$this->writeBeginEntry($entry, $isTopLevel);
		foreach ($entry->links as $link) {
			$this->writeLink($link);
		}

		$this
			->preWriteProperties($entry)
			->writeProperties($entry->propertyContent)
			->postWriteProperties($entry);

		$this->xmlWriter->endElement();


		return $this;
	}


    /**
     * Start writing a entry
     *
     * @param ODataEntry $entry Entry to write
     * @param boolean $isTopLevel
     * 
     * @return AtomODataWriter
     */
    protected function writeBeginEntry(ODataEntry $entry, $isTopLevel)
    {
        $this->xmlWriter->startElement(ODataConstants::ATOM_ENTRY_ELEMENT_NAME);
        if ($isTopLevel) {
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

	    $this
		    ->writeNodeValue(ODataConstants::ATOM_ID_ELEMENT_NAME, $entry->id)
		    ->writeNodeAttributeValue(
			    ODataConstants::ATOM_TITLE_ELELMET_NAME,
			    ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
			    MimeTypes::MIME_TEXTTYPE,
			    $entry->title
		    )
		    ->writeNodeValue(ODataConstants::ATOM_UPDATED_ELEMENT_NAME, date(DATE_ATOM));

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
			    $this->xmlWriter->startAttribute(ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME);
			    $this->xmlWriter->text(
				    "http://schemas.microsoft.com/ado/2007/08/dataservices/mediaresource/"
				    .$mediaLink->name
			    );
			    $this->xmlWriter->endAttribute();

			    $this->xmlWriter->startAttribute(ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME);
			    $this->xmlWriter->text($mediaLink->contentType);
			    $this->xmlWriter->endAttribute();

			    $this->xmlWriter->startAttribute(ODataConstants::ATOM_TITLE_ELELMET_NAME);
			    $this->xmlWriter->text($mediaLink->name);
			    $this->xmlWriter->endAttribute();

			    $this->xmlWriter->startAttribute(ODataConstants::ATOM_HREF_ATTRIBUTE_NAME);
			    $this->xmlWriter->text($mediaLink->editLink);
			    $this->xmlWriter->endAttribute();
			    $this->xmlWriter->endElement();
		    }
	    }

	    return $this;
    }




	/**
	 *
	 * @param ODataLink $link Link to write.
	 *
	 * @return AtomODataWriter
	 */
	protected function writeLink(ODataLink $link)
    {
        $this->writeLinkNode($link, $link->isExpanded);

	    if ($link->isExpanded) {
		    $this->xmlWriter->startElementNS(
			    ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
			    ODataConstants::ATOM_INLINE_ELEMENT_NAME,
			    null
		    );


	        if (!is_null($link->expandedResult)) {
		        if ($link->isCollection) {
			        $this->writeFeed($link->expandedResult);
		        } else {
			        $this->writeEntry($link->expandedResult);
		        }
	        }


		    $this->xmlWriter->endElement();
		    $this->xmlWriter->endElement();
	    }

	    return $this;
    }




	/**
	 * Write the given collection of properties.
	 * (properties of an entity or complex type)
	 *
	 * @param ODataPropertyContent $properties Collection of properties.
	 * @param bool $topLevel indicates if this property content is the top level response to be written
	 * @return AtomODataWriter
	 */
	protected function writeProperties(ODataPropertyContent $properties, $topLevel = false)
	{
		foreach ($properties->properties as $property) {
			$this->beginWriteProperty($property, $topLevel);

			if ($property->value == null) {
				$this->writeNullValue($property);
			} elseif ($property->value instanceof ODataPropertyContent) {
				$this->writeProperties($property->value, false);
			} elseif ($property->value instanceof ODataBagContent) {
				$this->writeBagContent($property->value);
			} else {
				$this->xmlWriter->text($property->value);
			}

			$this->xmlWriter->endElement();
		}

		return $this;
	}

    /**
     * Write the node which hold the entity properties as child
     * 
     * @param ODataEntry $entry ODataEntry object for pre writing properties.
     * 
     * @return AtomODataWriter
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
	            MimeTypes::MIME_APPLICATION_XML
            );
            $this->xmlWriter->startElementNS(
                ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX,
                ODataConstants::ATOM_PROPERTIES_ELEMENT_NAME,
                null
            );
        }

	    return $this;
    }

    /**
     * Write a property
     *
     * @param ODataProperty $property Property to be written
     * @param boolean       $isTopLevel     is link top level or not.
     * 
     * @return AtomODataWriter
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

	    return $this;
    }



    /**
     * Write after last property
     * 
     * @param ODataEntry $entry Entry object to post writing properties.
     *  
     * @return AtomODataWriter
     */
    public function postWriteProperties(ODataEntry $entry)
    {
        if (!$entry->isMediaLinkEntry) {
            $this->xmlWriter->endElement();
        }
        $this->xmlWriter->endElement();

	    return $this;
    }

    /**
     * Begin an item in a collection
     *  
     * @param ODataBagContent $bag Bag property object to begin write property
     *
     * 
     * @return AtomODataWriter
     */
    protected function writeBagContent(ODataBagContent $bag)
    {
        foreach ($bag->propertyContents as $content) {
            if ($content instanceof ODataPropertyContent) {
                $this->xmlWriter->startElementNs(
                    ODataConstants::ODATA_NAMESPACE_PREFIX, 
                    ODataConstants::COLLECTION_ELEMENT_NAME, 
                    null
                );
                    $this->writeProperties($content);
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

	    return $this;
    }



    /**
     * Write null value
     * 
     * @param ODataProperty $property ODataProperty object to write null value
     * according to property type.
     * 
     * @return AtomODataWriter
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
	    return $this;
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
     * @return string
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
     * @return AtomODataWriter
     */
    public function writeNodeValue($node, $value)
    {
        $this->xmlWriter->startElement($node);
        $this->xmlWriter->text($value);
        $this->xmlWriter->endElement();

	    return $this;
    }

    /**
     * Function to create element with one attribute and value. 
     *  
     * @param string $node           Element name
     * @param string $attribute      Attribute name
     * @param string $attributeValue Attribute value
     * @param string $nodeValue      Element value
     *  
     * @return AtomODataWriter
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

	    return $this;
    }

    /**
     * Function to create link element with arguments.
     * 
     * @param ODataLink $link      Link object to make link element
     * @param Boolean   $isExpanded Is link expanded or not.
     * 
     * @return AtomODataWriter
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

	    return $this;
    }

    /**
     * Function to write base uri and default namespaces for top level elements.
     * 
     * @return AtomODataWriter
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

	    return $this;
    }


    /**
     * XML prefix for the Atom namespace.
     *
     * @var string
     */
    const ATOM_NAMESPACE_PREFIX = 'atom';

    /**
     * XML prefix for the Atom Publishing Protocol namespace
     *
     * @var string
     */
    const APP_NAMESPACE_PREFIX = 'app';

    /**
     * @param ProvidersWrapper $providers
     * @return IODataWriter
     */
    public function writeServiceDocument(ProvidersWrapper $providers){
        $writer = $this->xmlWriter;
        $writer->startElementNs(null, ODataConstants::ATOM_PUBLISHING_SERVICE_ELEMENT_NAME, ODataConstants::APP_NAMESPACE);
        $writer->writeAttributeNs(ODataConstants::XML_NAMESPACE_PREFIX, ODataConstants::XML_BASE_ATTRIBUTE_NAME, null, $this->baseUri);
        $writer->writeAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, self::ATOM_NAMESPACE_PREFIX, null, ODataConstants::ATOM_NAMESPACE);
        $writer->writeAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, self::APP_NAMESPACE_PREFIX, null, ODataConstants::APP_NAMESPACE);

        $writer->startElement(ODataConstants::ATOM_PUBLISHING_WORKSPACE_ELEMNT_NAME);
        $writer->startElementNs(self::ATOM_NAMESPACE_PREFIX, ODataConstants::ATOM_TITLE_ELELMET_NAME, null);
        $writer->text(ODataConstants::ATOM_PUBLISHING_WORKSPACE_DEFAULT_VALUE);
        $writer->endElement();
        foreach ($providers->getResourceSets() as $resourceSetWrapper) {
            //start collection node
            $writer->startElement(ODataConstants::ATOM_PUBLISHING_COLLECTION_ELEMENT_NAME);
            $writer->writeAttribute(ODataConstants::ATOM_HREF_ATTRIBUTE_NAME, $resourceSetWrapper->getName());
            //start title node
            $writer->startElementNs(self::ATOM_NAMESPACE_PREFIX, ODataConstants::ATOM_TITLE_ELELMET_NAME, null);
            $writer->text($resourceSetWrapper->getName());
            //end title node
            $writer->endElement();
            //end collection node
            $writer->endElement();
        }

        //End workspace and service nodes
        $writer->endElement();
        $writer->endElement();

        return $this;
    }
}
