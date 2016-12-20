<?php
namespace POData\Readers\Atom;

use DOMXPath;
use DOMDocument;
use ReflectionClass;
use ReflectionProperty;

class AtomODataReader
{
    protected $objectContext;
    protected $domDocument;
    protected static $namespaces = array(
                                    'default' => 'http://www.w3.org/2005/Atom',
                                    'd' => 'http://schemas.microsoft.com/ado/2007/08/dataservices',
                                    'm' => 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata'
                                    );
    protected static $QUERY_ROOT_FEED = '/default:feed';
    protected static $QUERY_ROOT_ENTRY = '/default:entry';
    protected static $QUERY_TITLE = 'default:title';
    protected static $QUERY_ENTRY = 'default:entry';
    protected static $QUERY_ENTRY_ID = '/default:entry/default:id';
    protected static $QUERY_ID = 'default:id';
    protected static $QUERY_ENTRY_EDIT_LINK = '/default:entry/default:link[@rel="edit"]';
    protected static $QUERY_EDIT_LINK = 'default:link[@rel="edit"]';
    protected static $QUERY_FEED_OR_ENTRY_LINKS = 'default:link[@type="application/atom+xml;type=entry" or @type="application/atom+xml;type=feed"]';
    protected static $QUERY_EDIT_MEDIA_LINK = 'default:link[@rel="edit-media"]';
    protected static $QUERY_INLINE_FEED = 'm:inline/default:feed';
    protected static $QUERY_INLINE_ENTRY = 'm:inline/default:entry';
    protected static $QUERY_ENTRY_PROPERTIES = '/default:entry/default:content/m:properties/d:*';
    protected static $QUERY_CONTENT = 'default:content';
    protected static $QUERY_PROPERTIES1 = 'default:content/m:properties/d:*';
    protected static $QUERY_PROPERTIES2 = 'm:properties/d:*';
    protected static $QUERY_PROPERTY1 = 'default:content/m:properties/d:';
    protected static $QUERY_PROPERTY2 = 'm:properties/d:';
    protected static $QUERY_ERROR_MESSAGE = '/default:error/default:message';
    protected static $QUERY_INNER_EXCEPTION = '/default:error/default:innererror/default:internalexception/default:message';
    protected static $QUERY_NEXTLINK = 'default:link[@rel="next"]';
    protected static $QUERY_INLINECOUNT = 'm:count';
    protected static $QUERY_TYPE = 'm:type';
    protected static $ERROR_TAG = "<error xmlns=\"http://schemas.microsoft.com/ado/2007/08/dataservices/metadata\">";
    protected $_objectIDToNextLinkUri = array();
    protected $_inlineCount = -1;

    /**
     * @param string $xml
     * @param ObjectContext $objectContext
     */
    public function AtomParser($xml, $objectContext)
    {
        $this->domDocument = new DOMDocument();
        $this->domDocument->loadXML($xml);
        $this->objectContext = $objectContext;
    }

    /**
     * @param QueryOperationResponse $queryOperationResponse
     */
    public function EnumerateObjects(&$queryOperationResponse)
    {
        $result = array();
        $xPath = new DOMXPath($this->domDocument);
        self::ApplyNamespace($xPath);
        $feeds = $xPath->query(self::$QUERY_ROOT_FEED);

        if (!$feeds->length) {
            $entries = $xPath->query(self::$QUERY_ROOT_ENTRY);
            if (!$entries->length) {
                throw new InternalError(Resource::XMLWithoutFeedorEntry);
            }
            $entityType;
            $result[] = self::EnumerateEntry($entries->item(0), $entityType);
            $this->_objectIDToNextLinkUri[0] = null;
        } else {
            $result = self::EnumerateFeed($feeds->item(0), $feedType);
        }

        $queryOperationResponse->ObjectIDToNextLinkUrl = $this->_objectIDToNextLinkUri;
        $queryOperationResponse->Result = $result;
        $queryOperationResponse->CountValue = $this->_inlineCount;
    }

    /**
     * @param \DOMNode $feed
     */
    protected function EnumerateFeed($feed, &$feedType, $parentObject = null)
    {
        $entryCollection = array();
        $xPath = self::GetXPathForNode($feed);

        $titles = $xPath->query(self::$QUERY_TITLE);
        $feedType = $titles->item(0)->nodeValue;
        $nextLinks = $xPath->query(self::$QUERY_NEXTLINK);
        $nextLinkHref = null;
        foreach ($nextLinks as $nextLink) {
            $nextLinkHref = self::GetAttribute($nextLink, "href");
        }


        $entries = $xPath->query(self::$QUERY_ENTRY);
        foreach ($entries as $entry) {
            $entryCollection[] = $this->EnumerateEntry($entry, $entityType, $parentObject);
        }

        if ($parentObject == null) {
            $this->_objectIDToNextLinkUri[0] = $nextLinkHref;
            $inlineCount = $xPath->query(self::$QUERY_INLINECOUNT);
            if ($inlineCount->length) {
                $this->_inlineCount = $inlineCount->item(0)->nodeValue;
            }
        } else {
            if (isset($entryCollection[0])) {
                $this->_objectIDToNextLinkUri[$entryCollection[0]->getObjectID()] = $nextLinkHref;
            }
        }

        return $entryCollection;
    }

    /**
     * @param \DOMNode $entry
     */
    protected function EnumerateEntry($entry, &$entityType, $parentObject = null)
    {
        $xPath = self::GetXPathForNode($entry);

        $ids = $xPath->query(self::$QUERY_ID);
        $uri = $ids->item(0)->nodeValue;

        //Try to get EntitySet From edit link
        $entitySet = null;
        $editLinks = $xPath->query(self::$QUERY_EDIT_LINK);
        if ($editLinks->length) {
            $href = $this->GetAttribute($editLinks->item(0), 'href');
            if ($href) {
                if (($pos = strpos($href, '(')) !== false) {
                    $entitySet = substr($href, 0, $pos);
                }
            }
        }

        //If failed to get entity set name, then get it from url
        if (!$entitySet) {
            $entitySet = Utility::GetEntitySetFromUrl($uri);
        }

        $entityType = $this->objectContext->GetEntityTypeNameFromSet($entitySet);

        $atomEntry = new AtomEntry();
        $atomEntry->Identity = $uri;
        $atomEntry->EntityETag = $this->GetAttribute($entry, 'm:etag');

        self::CheckAndProcessMediaLinkEntryData($xPath, $atomEntry);

        $object = $this->objectContext->AddToObjectToResource($entityType, $atomEntry);

        if ($parentObject != null) {
            $this->objectContext->AddToBindings($parentObject, $entitySet, $object);
        }

        $links = $xPath->query(self::$QUERY_FEED_OR_ENTRY_LINKS);
        foreach ($links as $link) {
            self::EnumerateFeedorEntryLink($link, $object);
        }

        $relLinks = self::GetRelatedLinks($links);
        $class = new ReflectionClass(get_class($object));
        $method = $class->getMethod('setRelatedLinks');
        $method->invoke($object, $relLinks);



        $queryProperties = self::$QUERY_PROPERTIES1;
        $queryProperty = self::$QUERY_PROPERTY1;
        if ($atomEntry->MediaLinkEntry) {
            $queryProperties = self::$QUERY_PROPERTIES2;
            $queryProperty = self::$QUERY_PROPERTY2;
        }

        $clientType = ClientType::Create($entityType);
        self::HandleProperties($xPath, $queryProperty, $clientType, $object);
        return $object;
    }

    protected function EnumerateFeedorEntryLink($link, $object)
    {
        $xPath = self::GetXPathForNode($link);
        $feeds = $xPath->query(self::$QUERY_INLINE_FEED);

        foreach ($feeds as $feed) {
            $entryCollection = $this->EnumerateFeed($feed, $feedType, $object);
            $property = new ReflectionProperty($object, $feedType);
            $property->setValue($object, $entryCollection);
        }

        $entries = $xPath->query(self::$QUERY_INLINE_ENTRY);
        if ($entries->length) {
            $entry = $this->EnumerateEntry($entries->item(0), $entryType, $object);
            $entry = array($entry);
            $property = new ReflectionProperty($object, $entryType);
            $property->setValue($object, $entry);
        }
    }

    /**
     * @param \DOMNodeList $links
     */
    protected function GetRelatedLinks($links)
    {
        $relLinks = array();
        foreach ($links as $link) {
            $feedNode = $link->getElementsByTagNameNS(self::$namespaces['default'], 'feed');
            if ($feedNode->item(0) === null) {
                $relUri = self::GetAttribute($link, "href");
                $index = Utility::reverseFind($relUri, '/');
                $entityName = substr($relUri, $index + 1, strlen($relUri) - $index);
                $relLinks[$entityName] = $relUri;
            }
        }
        return $relLinks;
    }

    /**
     * @param DOMXPath $xPath
     * @param AtomEntry $atomEntry
     */
    protected static function CheckAndProcessMediaLinkEntryData($xPath, &$atomEntry)
    {
        $edit_media_links = $xPath->query(self::$QUERY_EDIT_MEDIA_LINK);
        if ($edit_media_links->length) {
            $edit_media_link = $edit_media_links->item(0);
            $atomEntry->EditMediaLink = self::GetAttribute($edit_media_link, 'href');
            if ($atomEntry->EditMediaLink == null) {
                throw new InternalError(Resource::MissingEditMediaLinkInResponseBody);
            }
            $atomEntry->StreamETag = self::GetAttribute($edit_media_link, 'm:etag');
        }

        $contents = $xPath->query(self::$QUERY_CONTENT);
        if ($contents->length) {
            $content = $contents->item(0);
            $streamUri = null;
            $streamUri = self::GetAttribute($content, 'src');
            if ($streamUri != null) {
                if ($content->nodeValue != null) {
                    throw new InternalError(Resource::ExpectedEmptyMediaLinkEntryContent);
                }
                $atomEntry->MediaLinkEntry = true;
                $atomEntry->MediaContentUri = $streamUri;
            }
        }
    }

    protected static function SetObjectProperty($object, $property)
    {
        $prefix = $property->prefix;
        $name = $property->nodeName;

        if ($prefix != "default") {
            $prefix = $prefix . ":";
            $pos = (($index = strpos($name, $prefix)) === false) ? 0 : $index + strlen($prefix);
            $name = substr($name, $pos);
        }

        $value = $property->nodeValue;
        try {
            $property = new ReflectionProperty($object, $name);

            //Do Atom format to PHP format conversion if required for property value ex:
            //if (strpos($property->getDocComment(), 'Edm.DateTime') == TRUE)
            //{
            //    $value = AtomDateToPHPDate()
            //}

            $property->setValue($object, $value);
        } catch (ReflectionException $ex) {
            // Ignore the error at the moment. TBD later.
        }
    }

    protected function GetXPathForNode($node)
    {
        $domDocument = self::GetDomDocumentFromNode($node);
        $xPath = new DOMXPath($domDocument);
        self::ApplyNamespace($xPath);
        return $xPath;
    }

    protected function GetDomDocumentFromNode($node)
    {
        $domDocument_From_Node = new DomDocument();
        $domNode = $domDocument_From_Node->importNode($node, true);
        $domDocument_From_Node->appendChild($domNode);
        return $domDocument_From_Node;
    }

    /**
     * @param \DOMNode $node
     * @param string $attributeName
     */
    protected static function GetAttribute($node, $attributeName)
    {
        $attributes = $node->attributes;
        foreach ($attributes as $attribute) {
            if ($attribute->nodeName == $attributeName) {
                return $attribute->value;
            }
        }
        //return "";
        return null;
    }

    /**
     * @param DOMXPath $xPath
     * @param string $propertyQuery
     */
    protected function HandleProperties($xPath, $propertyQuery, $clientType, $object)
    {

        if ($clientType->hasEPM()) {
            $epmProperties = $clientType->getRawEPMProperties();
            foreach ($epmProperties as $epmProperty) {
                $propertyName = $epmProperty->getName();
                $attributes = $epmProperty->getAttributes();

                $targetQuery = null;
                $synd = false;
                if ($epmProperty->hasEPM($synd)) {
                    if ($synd) {
                        $targetQuery = SyndicationItemProperty::GetSyndicationItemPathwithNS($attributes['FC_TargetPath']);
                    } else {
                        $targetQuery = $attributes['FC_TargetPathNS'];
                        $xPath->registerNamespace($attributes['FC_NsPrefix'], $attributes['FC_NsUri']);
                    }

                    $nodes = $xPath->Query($targetQuery);

                    if ($nodes->length) {
                        $value = null;
                        if (isset($attributes['NodeAttribute'])) {
                            $attribute = $attributes['FC_NsPrefix'] . ":" . $attributes['NodeAttribute'];
                            $value = self::GetAttribute($nodes->item(0), $attribute);
                            if ((is_null($value) &&
                                (isset($attributes['EdmType']) &&
                                 ($attributes['EdmType'] == 'Edm.Int16' ||
                                  $attributes['EdmType'] == 'Edm.Int32' ||
                                  $attributes['EdmType'] == 'Edm.Int64')))) {
                                    $value = '0';
                            }
                        } else {
                            $value = null;
                            if ($nodes->item(0)->hasChildNodes()) {
                                $value = $nodes->item(0)->firstChild->textContent;
                            } else {
                                $value = $nodes->item(0)->nodeValue;
                            }

                            if (empty($value)) {
                                $query1 = $propertyQuery . $propertyName;
                                $nodes1 = $xPath->Query($query1);
                                if ($nodes1->length) {
                                    $value1 = self::GetAttribute($nodes1->item(0), "m:null");
                                    if ($value1 == 'true') {
                                        $value = null;
                                    }
                                }
                            }
                        }

                        $property = new ReflectionProperty($object, $propertyName);
                        $property->setValue($object, $value);
                    } else {
                        //NOTE: Atom Entry not contains $targetQuery node its
                        //an error, becase in the case of projection also
                        //custmerizable feeds will be there.
                        //
                    }
                }
            }
        }

        $nonEpmProperties = $clientType->getRawNonEPMProperties(true);
        foreach ($nonEpmProperties as $nonEpmProperty) {
            $propertyName = $nonEpmProperty->getName();
            $propertyAttributes = $nonEpmProperty->getAttributes();

            //Now check for complex type. If type not start with 'Edm.'
            //it can be a complex type.
            if (isset($propertyAttributes['EdmType']) &&
               strpos($propertyAttributes['EdmType'], 'Edm.') !== 0) {
                $complexPropertyObject = null;
                $complexPropertyName = '';
                if ($this->CheckAndProcessComplexType($xPath, $propertyQuery, $propertyName, $complexPropertyName, $complexPropertyObject)) {
                    $property = new ReflectionProperty($object, $complexPropertyName);
                    $property->setValue($object, $complexPropertyObject);
                    continue;
                }
            }

            $query = $propertyQuery . $propertyName;
            $nodes = $xPath->Query($query);
            if ($nodes->length) {
                $value = $nodes->item(0)->nodeValue;
                $property = new ReflectionProperty($object, $propertyName);
                $property->setValue($object, $value);
            } else {
                //NOTE: Atom Entry not contains the required property
                //not a bug projection can lead to this case
            }
        }
    }

    /**
     * Check whether the in $xPath (which represents an entity), the
     * $propertyQuery.$propertyName represents a complex, if so
     * create an object of that type, polpulate it and return it.
     * @param DOMXPath $xPath
     * @param string $propertyQuery
     * @param string $propertyName
     * @param type [out] $complexPropertyName
     * @param type [out] $complexPropertyObject
     * @param string $complexPropertyName
     * @return bool
     */
    protected function CheckAndProcessComplexType($xPath, $propertyQuery, $propertyName, &$complexPropertyName, &$complexPropertyObject)
    {
        //Check and Process Complex Type
        //
        //make query string ex: "/m:properties/d:BoxArt"
        $query = $propertyQuery . $propertyName;
        $nodes = $xPath->Query($query);
        if (!$nodes->length) {
            return false;
        }

        //<d:BoxArt m:type="NetflixCatalog.BoxArt">
        //<d:MediumUrl>..</d:MediumUrl>
        //<d:SmallUrl>..</d:SmallUrl>
        //<d:LargeUrl>..</d:LargeUrl>
        //<d:HighDefinitionUrl m:null="true" />
        //</d:BoxArt>
        $type = $this->GetAttribute($nodes->item(0), 'm:type');

        if (!$type) {
            return false;
        }

        $complexType = '';

        //got NetflixCatalog.BoxArt
        $pisces = explode('.', $type);
        if (count($pisces) == 1) {
            $complexType = $pisces[0];
        } else {
            $complexType = $pisces[1];
        }

        try {
            $complexClientType = ClientType::Create($complexType);
            //here if complex def found
            $xPathComplex = self::GetXPathForNode($nodes->item(0));
            $class = new ReflectionClass($complexType);
            $complexPropertyObject = $class->newInstance();
            $complexPropertyName = $propertyName;
            $this->HandleProperties($xPathComplex, 'd:', $complexClientType, $complexPropertyObject);
        } catch (ReflectionException $exception) {
            //if no class definition is there in proxy, just continue
            //for raw copy
            return false;
        }

        return true;
    }

    /**
     * @param DOMXPath $xPath
     */
    protected static function ApplyNamespace($xPath)
    {
        foreach (self::$namespaces as $prefix => $namespaceURI) {
            $xPath->registerNamespace($prefix, $namespaceURI);
        }
    }

    /**
     * @param string $atomXML
     */
    public static function PopulateObject($atomXML, $object, &$uri, &$atomEntry)
    {
        $domDocument = new DomDocument();
        $domDocument->loadXML($atomXML);
        $xPath = new DOMXPath($domDocument);
        self::ApplyNamespace($xPath);
        $ids = $xPath->query(self::$QUERY_ENTRY_ID);
        $uri = $ids->item(0)->nodeValue;

        $properties = $xPath->query(self::$QUERY_ENTRY_PROPERTIES);
        foreach ($properties as $property) {
            self::SetObjectProperty($object, $property);
        }

        $atomEntry = new AtomEntry();
        ;
        self::CheckAndProcessMediaLinkEntryData($xPath, $atomEntry);
    }

    /**
     * @param string $atomXML
     */
    public static function PopulateMediaEntryKeyFields($atomXML, $object)
    {
        $domDocument = new DomDocument();
        $domDocument->loadXML($atomXML);
        $xPath = new DOMXPath($domDocument);
        self::ApplyNamespace($xPath);

        $type = ClientType::Create(get_class($object));
        $keyPropertyNames = $type->geyKeyProperties();
        foreach ($keyPropertyNames as $keyPropertyName) {
            $properties = $xPath->query(self::$QUERY_PROPERTY2 . $keyPropertyName);
            if ($properties->length) {
                $value = $properties->item(0)->nodeValue;
                $refProp = new ReflectionProperty($object, $keyPropertyName);
                $refProp->setValue($object, $value);
            }
        }
    }

    /**
     * @param string $atomXML
     */
    public static function GetEntityEtag($atomXML)
    {
        $domDocument = new DomDocument();
        $domDocument->loadXML($atomXML);
        $xPath = new DOMXPath($domDocument);
        self::ApplyNamespace($xPath);
        $entries = $xPath->query(self::$QUERY_ROOT_ENTRY);
        if ($entries->length) {
            return self::GetAttribute($entries->item(0), 'm:etag');
        }

        return null;
    }

    /**
     * @param string $errorXML
     */
    public static function GetErrorDetails($errorXML, &$outerError, &$innnerError)
    {
        if (strstr($errorXML, self::$ERROR_TAG) === false) {
            $innerError = "";
            $outerError = $errorXML;
        } else {
            $errorXML = str_replace("innererror xmlns=\"xmlns\"", "innererror", $errorXML);
            $domDocument = new DOMDocument();
            $domDocument->loadXML($errorXML);

            $xPath = new DOMXPath($domDocument);
            $xPath->registerNamespace('default', self::$namespaces['m']);

            $outerErrors = $xPath->query(self::$QUERY_ERROR_MESSAGE);
            if ($outerErrors->length) {
                $outerError = $outerErrors->item(0)->nodeValue;
            }

            $innerErrors = $xPath->query(self::$QUERY_INNER_EXCEPTION);
            if ($innerErrors->length) {
                $innnerError = $innerErrors->item(0)->nodeValue;
            }
        }
    }
}
