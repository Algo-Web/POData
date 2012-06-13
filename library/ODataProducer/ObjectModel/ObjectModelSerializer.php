<?php
/** 
 * The object model serializer.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_ObjectModel
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
namespace ODataProducer\ObjectModel;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\InvalidOperationException;
use ODataProducer\UriProcessor\RequestCountOption;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetSource;
use ODataProducer\UriProcessor\RequestDescription;
use ODataProducer\DataService;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Providers\Metadata\ResourcePropertyKind;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Providers\Metadata\Type\Binary;
use ODataProducer\Providers\Metadata\Type\Boolean;
use ODataProducer\Providers\Metadata\Type\String;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\Messages;
/**
 * The object model serializer.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_ObjectModel
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ObjectModelSerializer extends ObjectModelSerializerBase
{
    /**
     * Creates new instance of ObjectModelSerializer.
     * 
     * @param DataService        &$dataService        Reference to data service 
     *                                                instance.
     * @param RequestDescription &$requestDescription Reference to the 
     *                                                type describing
     *                                                request submitted by the 
     *                                                client. 
     */
    public function __construct(DataService &$dataService, RequestDescription &$requestDescription)
    {
        parent::__construct($dataService, $requestDescription);
    }

    /**
     * Write a top level entry resource.
     * 
     * @param mixed &$entryObject Reference to the entry object to be written.
     * 
     * @return ODataEntry
     */
    public function writeTopLevelElement(&$entryObject)
    {
        $requestTargetSource = $this->requestDescription->getTargetSource();
        $odataEntry = new ODataEntry();
        $odataEntry->isTopLevel = true;
        $resourceType = null;
        if ($requestTargetSource == RequestTargetSource::ENTITY_SET) {
            $resourceType = $this->requestDescription->getTargetResourceType();
        } else {
            $this->assert(
                $requestTargetSource == RequestTargetSource::PROPERTY, 
                '$requestTargetSource == RequestTargetSource::PROPERTY'
            );
            $resourceProperty = $this->requestDescription->getProjectedProperty();
            //$this->assert($resourceProperty->getKind() == ResourcePropertyKind::RESOURCE_REFERENCE, '$resourceProperty->getKind() == ResourcePropertyKind::RESOURCE_REFERENCE');
            $resourceType = $resourceProperty->getResourceType();
        }

        $needPop = $this->pushSegmentForRoot();
        $this->_writeEntryElement(
            $entryObject, $resourceType, 
            $this->requestDescription->getRequestUri()->getUrlAsString(), 
            $this->requestDescription->getContainerName(), $odataEntry
        );
        $this->popSegment($needPop);
        return $odataEntry;        
    }

    /**
     * Write top level feed element.
     * 
     * @param array(mixed) &$entryObjects Array of entry resources to be written.
     * 
     * @return ODataFeed.
     */
    public function writeTopLevelElements(&$entryObjects)
    {
        $this->assert(is_array($entryObjects), 'is_array($entryObjects)');
        $requestTargetSource = $this->requestDescription->getTargetSource();
        $title = null;
        if ($requestTargetSource == RequestTargetSource::ENTITY_SET) {
            $title = $this->requestDescription->getContainerName();
        } else {
            $this->assert(
                $requestTargetSource == RequestTargetSource::PROPERTY, 
                '$requestTargetSource == RequestTargetSource::PROPERTY'
            );            
            $resourceProperty = $this->requestDescription->getProjectedProperty();
            $this->assert(
                $resourceProperty->getKind() == ResourcePropertyKind::RESOURCESET_REFERENCE, 
                '$resourceProperty->getKind() == ResourcePropertyKind::RESOURCESET_REFERENCE'
            );
            $title = $resourceProperty->getName();
        }

        $relativeUri = $this->requestDescription->getIdentifier(); 
        $odataFeed = new ODataFeed();
        $odataFeed->isTopLevel = true;
        if ($this->requestDescription->getRequestCountOption() == RequestCountOption::INLINE) {
            $odataFeed->rowCount = $this->requestDescription->getCountValue();
        }

        $needPop = $this->pushSegmentForRoot();
        $targetResourceType = $this->requestDescription->getTargetResourceType();
        $this->_writeFeedElements(
            $entryObjects,
            $targetResourceType,
            $title,
            $this->requestDescription->getRequestUri()->getUrlAsString(),
            $relativeUri,
            $odataFeed
        );
        $this->popSegment($needPop);
        return $odataFeed;
    }

    /**
     * Write top level url element.
     * 
     * @param mixed &$entryObject The entry resource whose url to be written.
     * 
     * @return ODataRL
     */
    public function writeUrlElement(&$entryObject)
    {
        $odataUrl = new ODataURL();
        if (!is_null($entryObject)) {
            $currentResourceType = $this->getCurrentResourceSetWrapper()->getResourceType();
            $relativeUri = $this->getEntryInstanceKey(
                $entryObject, 
                $currentResourceType,
                $this->getCurrentResourceSetWrapper()->getName()
            );
            
            $odataUrl->oDataUrl 
                = rtrim($this->absoluteServiceUri, '/') . '/' . $relativeUri;
        }

        return $odataUrl;
    }

    /**
     * Write top level url collection.
     * 
     * @param array(mixed) &$entryObjects Array of entry resources 
     * whose url to be written.
     * 
     * @return ODataURLCollection
     */
    public function writeUrlElements(&$entryObjects)
    {
        $odataUrlCollection = new ODataURLCollection();
        if (!empty($entryObjects)) {        
            $i = 0;
            foreach ($entryObjects as &$entryObject) {
                $odataUrlCollection->oDataUrls[$i] = $this->writeUrlElement($entryObject);
                $i++;
            }

            if ($i > 0 && $this->needNextPageLink(count($entryObjects))) {
                $odataUrlCollection->nextPageLink = $this->getNextLinkUri($entryObjects[$i - 1], $this->requestDescription->getRequestUri()->getUrlAsString());
            }
        }

        if ($this->requestDescription->getRequestCountOption() == RequestCountOption::INLINE) {
            $odataUrlCollection->count = $this->requestDescription->getCountValue();
        }

        return $odataUrlCollection;
    }

    /**
     * Write top level complex resource.
     * 
     * @param mixed                &$complexValue         The complex object to be 
     *                                                    written.
     * @param string               $propertyName          The name of the 
     *                                                    complex property.
     * @param ResourceType         &$resourceType         Describes the type of 
     *                                                    complex object.
     * @param ODataPropertyContent &$odataPropertyContent On return, this object
     *                                                    will hold complex value
     *                                                    which can be used by writer
     *
     * @return void
     */
    public function writeTopLevelComplexObject(&$complexValue,
        $propertyName, ResourceType &$resourceType,
        ODataPropertyContent &$odataPropertyContent
    ) {
        $odataPropertyContent->isTopLevel = true;
        $this->_writeComplexValue(
            $complexValue,
            $propertyName, $resourceType, null, 
            $odataPropertyContent
        );
    }

    /**
     * Write top level bag resource.
     * 
     * @param mixed                &$BagValue             The bag object to be 
     *                                                    written.
     * @param string               $propertyName          The name of the 
     *                                                    bag property.
     * @param ResourceType         &$resourceType         Describes the type of 
     *                                                    bag object.
     * @param ODataPropertyContent &$odataPropertyContent On return, this object 
     *                                                    will hold bag value which
     *                                                    can be used by writers.
     * 
     * @return void
     */
    public function writeTopLevelBagObject(&$BagValue,
        $propertyName, ResourceType &$resourceType,
        ODataPropertyContent &$odataPropertyContent 
    ) {
        $odataPropertyContent->isTopLevel = true;
        $this->_writeBagValue(
            $BagValue,
            $propertyName, $resourceType, null,
            $odataPropertyContent
        );
    }

    /**
     * Write top level primitive value.
     * 
     * @param mixed                &$primitiveValue       The primitve value to be 
     *                                                    written.
     * @param ResourceProperty     &$resourceProperty     Resource property 
     *                                                    describing the 
     *                                                    primitive property 
     *                                                    to be written. 
     * @param ODataPropertyContent &$odataPropertyContent On return, this object 
     *                                                    will hold
     *                                                    primitive value 
     *                                                    which can be used 
     *                                                    by writers.
     * 
     * @return void
     */
    public function writeTopLevelPrimitive(&$primitiveValue, 
        ResourceProperty &$resourceProperty, ODataPropertyContent &$odataPropertyContent
    ) {
        $odataPropertyContent->isTopLevel = true;
        $odataPropertyContent->odataProperty[] = new ODataProperty();
        $this->_writePrimitiveValue(
            $primitiveValue, 
            $resourceProperty, 
            $odataPropertyContent->odataProperty[0]
        );
    }

    /**
     * Write an entry element.
     * 
     * @param mixed        &$entryObject  Object representing entry element.
     * @param ResourceType &$resourceType Expected type of the entry object.
     * @param string       $absoluteUri   Absolute uri of the entry element.
     * @param string       $relativeUri   Relative uri of the entry element.
     * @param ODataEntry   &$odataEntry   OData entry object to write to.
     * 
     * @return void
     */
    private function _writeEntryElement(&$entryObject, 
        ResourceType &$resourceType,
        $absoluteUri, $relativeUri, ODataEntry &$odataEntry
    ) {
        if (is_null($entryObject)) {
            //According to atom standard an empty entry must have an Author
            //node.
        } else {
            $relativeUri = $this->getEntryInstanceKey(
                $entryObject, 
                $resourceType,
                $this->getCurrentResourceSetWrapper()->getName()
            );

            $absoluteUri = rtrim($this->absoluteServiceUri, '/') . '/' . $relativeUri;
            $title = $resourceType->getName();
            //TODO Resolve actual resource type
            $actualResourceType = $resourceType;
            $this->_writeMediaResourceMetadata(
                $entryObject,
                $actualResourceType,
                $title,
                $relativeUri,
                $odataEntry
            );

            $odataEntry->id = $absoluteUri;
            $odataEntry->eTag = $this->getETagForEntry($entryObject, $resourceType);
            $odataEntry->title = $title;
            $odataEntry->editLink = $relativeUri;
            $odataEntry->type = $actualResourceType->getFullName();
            $odataPropertyContent = new ODataPropertyContent();
            $this->_writeObjectProperties(
                $entryObject, 
                $actualResourceType,
                $absoluteUri,
                $relativeUri,
                $odataEntry,
                $odataPropertyContent
            );
            $odataEntry->propertyContent = $odataPropertyContent;
        }
    }

    /**
     * Writes the feed elements
     * 
     * @param array        &$entryObjects Array of entries in the feed element.
     * @param ResourceType &$resourceType The resource type of the f the elements 
     *                                    in the collection.
     * @param string       $title         Title of the feed element.
     * @param string       $absoluteUri   Absolute uri representing the feed element.
     * @param string       $relativeUri   Relative uri representing the feed element.
     * @param ODataFeed    &$odataFeed    Feed to write to.
     * 
     * @return void
     */
    private function _writeFeedElements(&$entryObjects, 
        ResourceType &$resourceType, $title, 
        $absoluteUri, $relativeUri, ODataFeed &$odataFeed
    ) {
        $this->assert(is_array($entryObjects), '_writeFeedElements::is_array($entryObjects)');
        $odataFeed->id = $absoluteUri;
        $odataFeed->title = $title;
        $odataFeed->selfLink = new ODataLink();
        $odataFeed->selfLink->name = ODataConstants::ATOM_SELF_RELATION_ATTRIBUTE_VALUE;
        $odataFeed->selfLink->title =  $title;
        $odataFeed->selfLink->url = $relativeUri;
        
        if (empty($entryObjects)) {
            //TODO // ATOM specification: if a feed contains no entries, 
            //then the feed should 
            //have at least one Author tag
        } else {
            $i = 0;
            foreach ($entryObjects as &$entryObject) {
                $odataFeed->entries[$i] = new ODataEntry();
                $this->_writeEntryElement($entryObject, $resourceType, null, null, $odataFeed->entries[$i]);
                $i++;
            }

            if ($this->needNextPageLink(count($entryObjects))) {
                $odataFeed->nextPageLink = $this->getNextLinkUri($entryObjects[$i - 1], $absoluteUri);
            }
        }
    }

    /**
     * Write values of properties of given entry (resource) or complex object.
     * 
     * @param mixed                $customObject          Entity or complex object 
     *                                                    with properties  
     *                                                    to write out.
     * @param ResourceType         &$resourceType         Resource type describing 
     *                                                    the metadata of 
     *                                                    the custom object.
     * @param string               $absoluteUri           Absolute uri for the given 
     *                                                    entry object 
     *                                                    NULL for complex object.
     * @param string               $relativeUri           Relative uri for the given 
     *                                                    custom object.
     * @param ODataEntry           &$odataEntry           ODataEntry instance to 
     *                                                    place links and
     *                                                    expansion of the 
     *                                                    entry object, 
     *                                                    NULL for complex object.
     * @param ODataPropertyContent &$odataPropertyContent ODataPropertyContent
     *                                                    instance in which
     *                                                    to place the values.
     * 
     * @return void
     */
    private function _writeObjectProperties($customObject, 
        ResourceType &$resourceType, 
        $absoluteUri, 
        $relativeUri, 
        &$odataEntry, 
        ODataPropertyContent &$odataPropertyContent
    ) {
        $resourceTypeKind = $resourceType->getResourceTypeKind();
        if (is_null($absoluteUri) == ($resourceTypeKind == ResourceTypeKind::ENTITY)
        ) {
            ODataException::createInternalServerError(
                Messages::badProviderInconsistentEntityOrComplexTypeUsage(
                    $resourceType->getName()
                )
            );
        }

        $this->assert(
            (($resourceTypeKind == ResourceTypeKind::ENTITY) && ($odataEntry instanceof ODataEntry)) 
            || (($resourceTypeKind == ResourceTypeKind::COMPLEX) && is_null($odataEntry)), 
            '(($resourceTypeKind == ResourceTypeKind::ENTITY) && ($odataEntry instanceof ODataEntry)) 
            || (($resourceTypeKind == ResourceTypeKind::COMPLEX) && is_null($odataEntry))'
        );
        $projectionNodes = null;
        $navigationProperties = null;
        if ($resourceTypeKind == ResourceTypeKind::ENTITY) {
            $projectionNodes = $this->getProjectionNodes();
            $navigationProperties = array();
        }

        if (is_null($projectionNodes)) {
            //This is the code path to handle properties of Complex type 
            //or Entry without projection (i.e. no expansion or selection)          
            $resourceProperties = array(); 
            if ($resourceTypeKind == ResourceTypeKind::ENTITY) {
                // If custom object is an entry then it can contain navigation 
                // properties which are invisible (because the corrosponding 
                // resource set is invisible).  
                // IDSMP::getResourceProperties will give collection of properties
                // which are visible.
                $currentResourceSetWrapper1 = $this->getCurrentResourceSetWrapper();
                $resourceProperties = $this->dataService
                    ->getMetadataQueryProviderWrapper()
                    ->getResourceProperties(
                        $currentResourceSetWrapper1, 
                        $resourceType
                    );
            } else {
                $resourceProperties = $resourceType->getAllProperties();
            }

            //First write out primitve types
            foreach ($resourceProperties as $name => $resourceProperty) {
                if ($resourceProperty->getKind() == ResourcePropertyKind::PRIMITIVE 
                    || $resourceProperty->getKind() == (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY) 
                    || $resourceProperty->getKind() == (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::ETAG)
                    || $resourceProperty->getKind() == (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY | ResourcePropertyKind::ETAG)
                ) {
                    $odataProperty = new ODataProperty();
                    $primitiveValue = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
                    $this->_writePrimitiveValue($primitiveValue, $resourceProperty, $odataProperty);
                    $odataPropertyContent->odataProperty[] = $odataProperty;
                }
            }

            //Write out bag and complex type
            $i = 0;
            foreach ($resourceProperties as $resourceProperty) {                
                if ($resourceProperty->isKindOf(ResourcePropertyKind::BAG)) {
                    //Handle Bag Property (Bag of Primitive or complex)
                    $propertyValue = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
                    $resourceType2 = $resourceProperty->getResourceType();
                    $this->_writeBagValue(
                        $propertyValue,
                        $resourceProperty->getName(),
                        $resourceType2,
                        $relativeUri . '/' . $resourceProperty->getName(),
                        $odataPropertyContent
                    );
                } else {
                    $resourcePropertyKind = $resourceProperty->getKind();
                    if ($resourcePropertyKind == ResourcePropertyKind::COMPLEX_TYPE) {
                        $propertyValue = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
                        $resourceType1 = $resourceProperty->getResourceType();
                        $this->_writeComplexValue(
                            $propertyValue,
                            $resourceProperty->getName(),
                            $resourceType1,
                            $relativeUri . '/' . $resourceProperty->getName(),
                            $odataPropertyContent
                        );
                    } else if ($resourceProperty->getKind() == ResourcePropertyKind::PRIMITIVE 
                        || $resourceProperty->getKind() == (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY) 
                        || $resourceProperty->getKind() == (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::ETAG)
                        || $resourceProperty->getKind() == (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY | ResourcePropertyKind::ETAG)
                    ) {
                        continue;  
                    } else {
                         $this->assert(
                             ($resourcePropertyKind == ResourcePropertyKind::RESOURCE_REFERENCE)
                             || ($resourcePropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE), 
                             '($resourcePropertyKind == ResourcePropertyKind::RESOURCE_REFERENCE)
                             || ($resourcePropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE)'
                         );

                        $navigationProperties[$i] = new NavigationPropertyInfo($resourceProperty, $this->shouldExpandSegment($resourceProperty->getName()));
                        if ($navigationProperties[$i]->expanded) {
                            $navigationProperties[$i]->value = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
                        }

                        $i++;                        
                    }
                }
            }
                
        } else { //This is the code path to handle projected properties of Entry
            $i = 0;
            foreach ($projectionNodes as $projectionNode) {
                $propertyName = $projectionNode->getPropertyName();
                $resourceProperty = $resourceType->tryResolvePropertyTypeByName($propertyName);
                $this->assert(!is_null($resourceProperty), '!is_null($resourceProperty)');
                
                if ($resourceProperty->getTypeKind() == ResourceTypeKind::ENTITY) {
                    $currentResourceSetWrapper2 = $this->getCurrentResourceSetWrapper();
                    $resourceProperties = $this->dataService
                        ->getMetadataQueryProviderWrapper()
                        ->getResourceProperties(
                            $currentResourceSetWrapper2, 
                            $resourceType
                        );
                    //Check for the visibility of this navigation property
                    if (array_key_exists($resourceProperty->getName(), $resourceProperties)) {
                        $navigationProperties[$i] = new NavigationPropertyInfo($resourceProperty, $this->shouldExpandSegment($propertyName));
                        if ($navigationProperties[$i]->expanded) {
                            $navigationProperties[$i]->value = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
                        }

                        $i++;
                        continue;
                    }
                }

                //Primitve, complex or bag property
                $propertyValue = $this->getPropertyValue($customObject, $resourceType, $resourceProperty);
                $propertyTypeKind = $resourceProperty->getKind();
                $propertyResourceType = $resourceProperty->getResourceType();
                $this->assert(!is_null($propertyResourceType), '!is_null($propertyResourceType)');
                if (ResourceProperty::sIsKindOf($propertyTypeKind, ResourcePropertyKind::BAG)) {
                    $bagResourceType = $resourceProperty->getResourceType();
                    $this->_writeBagValue(
                        $propertyValue,
                        $propertyName,
                        $bagResourceType,
                        $relativeUri . '/' . $propertyName,
                        $odataPropertyContent
                    );
                } else if (ResourceProperty::sIsKindOf($propertyTypeKind, ResourcePropertyKind::PRIMITIVE)) {
                    $odataProperty = new ODataProperty();
                    $this->_writePrimitiveValue($propertyValue, $resourceProperty, $odataProperty);
                    $odataPropertyContent->odataProperty[] = $odataProperty;
                } else if ($propertyTypeKind == ResourcePropertyKind::COMPLEX_TYPE) {
                    $complexResourceType = $resourceProperty->getResourceType();
                    $this->_writeComplexValue(
                        $propertyValue,
                        $propertyName,
                        $complexResourceType,
                        $relativeUri . '/' . $propertyName,
                        $odataPropertyContent
                    );
                } else {
                    //unexpected
                    $this->assert(false, '$propertyTypeKind = Primitive or Bag or ComplexType');
                }
            }
        }

        if (!is_null($navigationProperties)) {
            //Write out navigation properties (deferred or inline)
            foreach ($navigationProperties as $navigationPropertyInfo) {
                $propertyName = $navigationPropertyInfo->resourceProperty->getName();
                $type = $navigationPropertyInfo->resourceProperty->getKind() == ResourcePropertyKind::RESOURCE_REFERENCE ? 
                    'application/atom+xml;type=entry':
                    'application/atom+xml;type=feed';
                $link = new ODataLink();
                $link->name = ODataConstants::ODATA_RELATED_NAMESPACE . $propertyName; 
                $link->title = $propertyName;
                $link->type = $type;
                $link->url = $relativeUri . '/' . $propertyName;

                if ($navigationPropertyInfo->expanded) {
                    $propertyRelativeUri = $relativeUri . '/' . $propertyName;
                    $propertyAbsoluteUri = trim($absoluteUri, '/') . '/' . $propertyName;
                    $needPop = $this->pushSegmentForNavigationProperty($navigationPropertyInfo->resourceProperty);
                    $navigationPropertyKind = $navigationPropertyInfo->resourceProperty->getKind();
                    $this->assert(
                        $navigationPropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE 
                        || $navigationPropertyKind == ResourcePropertyKind::RESOURCE_REFERENCE, 
                        '$navigationPropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE 
                        || $navigationPropertyKind == ResourcePropertyKind::RESOURCE_REFERENCE'
                    );
                    $currentResourceSetWrapper = $this->getCurrentResourceSetWrapper();
                    $this->assert(!is_null($currentResourceSetWrapper), '!is_null($currentResourceSetWrapper)');
                    $link->isExpanded = true;
                    if (!is_null($navigationPropertyInfo->value)) {
                        if ($navigationPropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE) {
                            $inlineFeed = new ODataFeed();
                            $link->isCollection = true;
                            $currentResourceType = $currentResourceSetWrapper->getResourceType();
                            $this->_writeFeedElements(
                                $navigationPropertyInfo->value,
                                $currentResourceType, 
                                $propertyName, 
                                $propertyAbsoluteUri, 
                                $propertyRelativeUri, 
                                $inlineFeed
                            );
                            $link->expandedResult = $inlineFeed;
                        } else {
                            $inlineEntry = new ODataEntry();
                            $link->isCollection = false;
                            $currentResourceType1 = $currentResourceSetWrapper->getResourceType();
                            $this->_writeEntryElement(
                                $navigationPropertyInfo->value, 
                                $currentResourceType1, 
                                $propertyAbsoluteUri, 
                                $propertyRelativeUri, 
                                $inlineEntry
                            );
                            $link->expandedResult = $inlineEntry;
                        }
                    } else {
                        $link->expandedResult = null;
                    }

                    $this->popSegment($needPop);
                }

                $odataEntry->links[] = $link;
            }
        }
    }

    /**
     * Writes a primitive value and related information to the given
     * ODataProperty instance.
     * 
     * @param mixed            &$primitiveValue   The primitive value to write.
     * @param ResourceProperty &$resourceProperty The metadata of the primitive
     *                                            property value.
     * @param ODataProperty    &$odataProperty    ODataProperty instance to which
     *                                            the primitive value and related
     *                                            information to write out.
     *
     * @throws ODataException If given value is not primitive.
     * 
     * @return void
     */
    private function _writePrimitiveValue(&$primitiveValue, 
        ResourceProperty &$resourceProperty, ODataProperty &$odataProperty
    ) {
        if (is_object($primitiveValue)) {
            //TODO ERROR: The property 'PropertyName' 
            //is defined as primitive type but value is an object
        }

        
        $odataProperty->name = $resourceProperty->getName();
        $odataProperty->typeName = $resourceProperty->getInstanceType()->getFullTypeName();
        if (is_null($primitiveValue)) {
            $odataProperty->value = null;
        } else {
            $resourceType = $resourceProperty->getResourceType();
            $this->_primitiveToString(
                $resourceType,
                $primitiveValue,
                $odataProperty->value
            );
        }
    }

    /**
     * Write value of a complex object.
     * 
     * @param mixed                &$complexValue         Complex object to write.
     * @param string               $propertyName          Name of the 
     *                                                    complex property
     *                                                    whose value need 
     *                                                    to be written.
     * @param ResourceType         &$resourceType         Expected type 
     *                                                    of the property.
     * @param string               $relativeUri           Relative uri for the 
     *                                                    complex type element.
     * @param ODataPropertyContent &$odataPropertyContent Content to write to.
     * 
     * @return void
     */
    private function _writeComplexValue(&$complexValue,
        $propertyName, ResourceType &$resourceType, $relativeUri,
        ODataPropertyContent &$odataPropertyContent
    ) {
        $odataProperty = new ODataProperty();
        $odataProperty->name = $propertyName;
        if (is_null($complexValue)) {
            $odataProperty->value = null;
            $odataProperty->typeName = $resourceType->getFullName();
        } else {
            $content = new ODataPropertyContent();
            $actualType = $this->_complexObjectToContent(
                $complexValue,
                $propertyName,
                $resourceType,
                $relativeUri,
                $content
            );

            $odataProperty->typeName = $actualType->getFullName();
            $odataProperty->value = $content;
        }

        $odataPropertyContent->odataProperty[] = $odataProperty;
    }

    /**
     * Write value of a bag instance.
     *
     * @param array/NULL           &$BagValue             Bag value to write.
     * @param string               $propertyName          Property name of the bag.
     * @param ResourceType         &$resourceType         Type describing the 
     *                                                    bag value.
     * @param string               $relativeUri           Relative Url to the bag.
     * @param ODataPropertyContent &$odataPropertyContent On return, this object 
     *                                                    will hold bag value which 
     *                                                    can be used by writers.
     * 
     * @return void
     */
    private function _writeBagValue(&$BagValue,
        $propertyName, ResourceType &$resourceType, $relativeUri,
        ODataPropertyContent &$odataPropertyContent
    ) {
        $bagItemResourceTypeKind = $resourceType->getResourceTypeKind();
        $this->assert(
            $bagItemResourceTypeKind == ResourceTypeKind::PRIMITIVE
            || $bagItemResourceTypeKind == ResourceTypeKind::COMPLEX,
            '$bagItemResourceTypeKind == ResourceTypeKind::PRIMITIVE
            || $bagItemResourceTypeKind == ResourceTypeKind::COMPLEX'
        );

        $odataProperty = new ODataProperty();
        $odataProperty->name = $propertyName;
        $odataProperty->typeName = 'Collection(' . $resourceType->getFullName() .')';
        if (is_null($BagValue) || (is_array($BagValue) && empty ($BagValue))) {
            $odataProperty->value = null;
        } else {
            $odataBagContent = new ODataBagContent();
            foreach ($BagValue as $itemValue) {
                if (!is_null($itemValue)) {
                    if ($bagItemResourceTypeKind == ResourceTypeKind::PRIMITIVE) {
                        $primitiveValueAsString = null;
                        $this->_primitiveToString($resourceType, $itemValue, $primitiveValueAsString);
                        $odataBagContent->propertyContents[] = $primitiveValueAsString;
                    } else if ($bagItemResourceTypeKind == ResourceTypeKind::COMPLEX) {
                        $complexContent = new ODataPropertyContent();
                        $actualType = $this->_complexObjectToContent(
                            $itemValue,
                            $propertyName,
                            $resourceType,
                            $relativeUri,
                            $complexContent
                        );
                        //TODO add type in case of base type
                        $odataBagContent->propertyContents[] = $complexContent;
                    }
                }
            }

            $odataProperty->value = $odataBagContent;
        }

        $odataPropertyContent->odataProperty[] = $odataProperty;
    }

    /**
     * Write media resource metadata (for MLE and Named Streams)
     * 
     * @param mixed        &$entryObject  The entry instance being serialized.
     * @param ResourceType &$resourceType Resource type of the entry instance.
     * @param string       $title         Title for the current 
     *                                    current entry instance.
     * @param string       $relativeUri   Relative uri for the 
     *                                    current entry instance.
     * @param ODataEntry   &$odataEntry   OData entry to write to.
     * 
     * @return void
     */
    private function _writeMediaResourceMetadata(&$entryObject,
        ResourceType &$resourceType,
        $title,
        $relativeUri,
        ODataEntry &$odataEntry
    ) {
        if ($resourceType->isMediaLinkEntry()) {
            $odataEntry->isMediaLinkEntry = true;
            $streamProvider = $this->dataService->getStreamProvider();
            $eTag = $streamProvider->getStreamETag($entryObject, null);
            $readStreamUri = $streamProvider->getReadStreamUri($entryObject, null, $relativeUri);
            $mediaContentType = $streamProvider->getStreamContentType($entryObject, null);
            $mediaLink = new ODataMediaLink(
                $title,
                $streamProvider->getDefaultStreamEditMediaUri($relativeUri, null),
                $readStreamUri,
                $mediaContentType,
                $eTag
            );

            $odataEntry->mediaLink = $mediaLink;
        }

        if ($resourceType->hasNamedStream()) {
            foreach ($resourceType->getAllNamedStreams() as $title => $resourceStreamInfo) {
                $eTag = $streamProvider->getStreamETag($entryObject, $resourceStreamInfo);
                $readStreamUri = $streamProvider->getReadStreamUri($entryObject, $resourceStreamInfo, $relativeUri);
                $mediaContentType = $streamProvider->getStreamContentType($entryObject, $resourceStreamInfo);
                $odataEntry->mediaLinks[] = new ODataMediaLink(
                    $title,
                    $streamProvider->getDefaultStreamEditMediaUri($relativeUri, $resourceStreamInfo),
                    $readStreamUri,
                    $mediaContentType,
                    $eTag
                );
            }
        }
    }
    /**
     * Convert the given primitive value to string.
     * Note: This method will not handle null primitive value.
     * 
     * @param ResourceType &$primtiveResourceType Type of the primitive property
     *                                            whose value need to be converted.
     * @param mixed        $primitiveValue        Primitive value to convert.
     * @param string       &$stringValue          On return, this parameter will
     *                                            contain converted value.
     *                                            
     * @return void
     */
    private function _primitiveToString(ResourceType &$primtiveResourceType,
        $primitiveValue, &$stringValue
    ) {
        $type = $primtiveResourceType->getInstanceType();
        if ($type instanceof Boolean) {
            $stringValue = ($primitiveValue === true) ? 'true' : 'false';
        } else if ($type instanceof Binary) {
                $stringValue = base64_encode($primitiveValue);
        } else if ($type instanceof String) {
            $stringValue = utf8_encode($primitiveValue);
        } else {        
            $stringValue = strval($primitiveValue);
        }
    }

    /**
     * Write value of a complex object.
     * Note: This method will not handle null complex value.
     *
     * @param mixed                &$complexValue         Complex object to write.
     * @param string               $propertyName          Name of the 
     *                                                    complex property
     *                                                    whose value 
     *                                                    need to be written.
     * @param ResourceType         &$resourceType         Expected type of the 
     *                                                    property.
     * @param string               $relativeUri           Relative uri for the 
     *                                                    complex type element.
     * @param ODataPropertyContent &$odataPropertyContent Content to write to.
     *
     * @return ResourceType The actual type of the complex object.
     * 
     * @return void
     */
    private function _complexObjectToContent(&$complexValue,
        $propertyName, ResourceType &$resourceType, $relativeUri,
        ODataPropertyContent &$odataPropertyContent
    ) {
        $count = count($this->complexTypeInstanceCollection);
        for ($i = 0; $i < $count; $i++) {
            if ($this->complexTypeInstanceCollection[$i] === $complexValue) {
                throw new InvalidOperationException(Messages::objectModelSerializerLoopsNotAllowedInComplexTypes($propertyName));
            }
        }

        $this->complexTypeInstanceCollection[$count] = &$complexValue;

        //TODO function to resolve actual type from $resourceType
        $actualType = $resourceType;
        $odataEntry = null;
        $this->_writeObjectProperties(
            $complexValue, $actualType,
            null, $relativeUri, $odataEntry, $odataPropertyContent
        );
        unset($this->complexTypeInstanceCollection[$count]);
        return $actualType;
    }
}
?>