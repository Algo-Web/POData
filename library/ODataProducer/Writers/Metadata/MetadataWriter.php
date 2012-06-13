<?php
/** 
 * Writer for service metdata
 *
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Metadata
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
namespace ODataProducer\Writers\Metadata;
use ODataProducer\Providers\Metadata\ResourceAssociationSet;
use ODataProducer\Providers\Metadata\ResourceAssociationTypeEnd;
use ODataProducer\Providers\Metadata\ResourceAssociationSetEnd;
use ODataProducer\Providers\Metadata\ResourcePropertyKind;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Providers\MetadataEdmSchemaVersion;
use ODataProducer\Common\Version;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\InvalidOperationException;
/** 
 * Writer class for service metadata
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class MetadataWriter
{
    /**
     * Writer to which output (CSDL Document) is sent
     * 
     * @var XMLWriter
     */
    private $_xmlWriter;

    /**
     * Hold reference to the MetadataManager instance, 
     * which can be used for retrieving details about all ResourceType, 
     * ResourceSet, AssociationType and AssociationSet defined in the service.
     * 
     * @var MetadataManager
     */
    private $_metadataManager;

    /**
     * Holds reference to the wrapper over service metadata and 
     * query provider implemenations In this context this provider will be 
     * used for gathering metadata informations only.
     *      
     * @var MetadataQueryProviderWrapper
     */
    private $_metadataQueryproviderWrapper;

    /**
     * Data service base uri from which resources should be resolved
     * 
     * @var string
     */
    private $_baseUri;

    /**
     * Encoding used for the output (CSDL document)
     * 
     * @var string
     */
    private $_encoding;

    /**
     * The DataServiceVersion for this metadata.
     * 
     * @var Version
     */
    private $_dataServiceVersion;
    
    /**
     * Creates new instance of MetadataWriter
     * 
     * @param MetadataQueryProviderWrapper $provider Reference to the  
     * service metadata and query provider wrapper
     */
    public function __construct(MetadataQueryProviderWrapper $provider)
    {
        $this->_metadataQueryproviderWrapper = $provider; 
    }

    /**
     * Wrtite the metadata in CSDL format. 
     * 
     * @return string
     */
    public function writeMetadata()
    {   
        try {
            $this->_metadataManager = MetadataManager::create($this->_metadataQueryproviderWrapper);
            
        } catch (\Exception $exception) {
            throw $exception;
        }

        $this->_dataServiceVersion = new Version(1, 0);
        $edmSchemaVersion = $this->_metadataQueryproviderWrapper->getEdmSchemaVersion();
        $this->_metadataManager->getDataServiceAndEdmSchemaVersions($this->_dataServiceVersion, $edmSchemaVersion);
        $this->_xmlWriter = new \XMLWriter();
        $this->_xmlWriter->openMemory();
        $this->_xmlWriter->setIndent(4);
        $this->_writeToplevelElements($this->_dataServiceVersion->toString());
        $resourceTypesInContainerNamespace = array();
        $containerNamespace = $this->_metadataQueryproviderWrapper->getContainerNamespace();
        foreach ($this->_metadataManager->getResourceTypesAlongWithNamespace() as $resourceTypeNamespace => $resourceTypesWithName) {
            if ($resourceTypeNamespace == $containerNamespace) {                
                foreach ($resourceTypesWithName as $resourceTypeName => $resourceType) {
                    $resourceTypesInContainerNamespace[] = $resourceType;
                }
            } else {
                $associationsInThisNamespace = $this->_metadataManager->getResourceAssociationTypesForNamespace($resourceTypeNamespace);
                $this->_writeSchemaElement($resourceTypeNamespace, $edmSchemaVersion);
                $uniqueAssociationsInThisNamespace = $this->_metadataManager->getUniqueResourceAssociationTypesForNamespace($resourceTypeNamespace);
                $this->_writeResourceTypes(array_values($resourceTypesWithName), $associationsInThisNamespace);
                $this->_writeAssociationTypes($uniqueAssociationsInThisNamespace);
            }            
        }

        //write Container schema node and define required nmaespaces
        $this->_writeSchemaElement($resourceTypeNamespace, $edmSchemaVersion);
        if (!empty($resourceTypesInContainerNamespace)) {
            //Get assocation types in container namespace as array of 
            //key-value pairs (with key as association type
            //lookup key i.e. ResourceType::Name_NavigationProperty::Name. 
            //Same association will appear twice for di-directional relationship 
            //(duplicate value will be there in this case)
            $associationsInThisNamespace = $this->_metadataManager->getResourceAssociationTypesForNamespace($containerNamespace);
            //Get association type in container namespace as array of unique values
            $uniqueAssociationsInThisNamespace = $this->_metadataManager->getUniqueResourceAssociationTypesForNamespace($containerNamespace);
            $this->_writeResourceTypes($resourceTypesInContainerNamespace, $associationsInThisNamespace);
            $this->_writeAssociationTypes($uniqueAssociationsInThisNamespace);
        }

        $this->_writeEntityContainer();
        //End container Schema node
        $this->_xmlWriter->endElement();
        
        //End edmx:Edmx and edmx:DataServices nodes
        $this->_xmlWriter->endElement();
        $this->_xmlWriter->endElement();
        $metadataInCsdl = $this->_xmlWriter->outputMemory(true);
        return $metadataInCsdl;
    }

    /**
     * Gets data service version for this metadata.
     * 
     * @return Version
     */
    public function getDataServiceVersion()
    {
        return $this->_dataServiceVersion;
    }

    /**
     * Write top level 'Edmx' and 'DataServices' nodes with associated attributes
     * 
     * @param Version $dataServiceVersion version of the data service
     * 
     * @return nothing
     */
    private function _writeToplevelElements($dataServiceVersion)
    {
        $this->_xmlWriter->startElementNs(ODataConstants::EDMX_NAMESPACE_PREFIX, ODataConstants::EDMX_ELEMENT, ODataConstants::EDMX_NAMESPACE_1_0);
        $this->_xmlWriter->writeAttribute(ODataConstants::EDMX_VERSION, ODataConstants::EDMX_VERSION_VALUE);
        $this->_xmlWriter->startElementNs(ODataConstants::EDMX_NAMESPACE_PREFIX, ODataConstants::EDMX_DATASERVICES_ELEMENT, ODataConstants::EDMX_NAMESPACE_1_0);
        $this->_xmlWriter->writeAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, null, ODataConstants::ODATA_METADATA_NAMESPACE);
        $this->_xmlWriter->writeAttributeNs(ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, ODataConstants::ODATAVERSIONHEADER, null, $dataServiceVersion);
    }

    /**
     * Write 'Schema' node with associated attributes
     * 
     * @param string                   $schemaNamespace  schema namespace
     * @param MetadataEdmSchemaVersion $edmSchemaVersion edm schema version
     * 
     * @return nothing
     */
    private function _writeSchemaElement($schemaNamespace, $edmSchemaVersion)
    {
        $this->_xmlWriter->startElementNs(null, ODataConstants::SCHEMA, $this->_getSchemaNamespaceUri($edmSchemaVersion));
        $this->_xmlWriter->writeAttribute(ODataConstants::NAMESPACE1, $schemaNamespace);
        $this->_xmlWriter->writeAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_NAMESPACE_PREFIX, null, ODataConstants::ODATA_NAMESPACE);
        $this->_xmlWriter->writeAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, null, ODataConstants::ODATA_METADATA_NAMESPACE);
    }

    /**
     * Write all resource types (entity and complex types)
     * 
     * @param array $resourceTypes                            resource types array
     * array(ResourceType)
     * @param array $associationTypesInResourceTypesNamespace collection of 
     * association types for the given resource types
     * array(string, AssociationType)
     * 
     * @return nothing
     */
    private function _writeResourceTypes($resourceTypes, $associationTypesInResourceTypesNamespace)
    {
        foreach ($resourceTypes as $resourceType) {
            if ($resourceType->getResourceTypeKind() == ResourceTypeKind::ENTITY) {
                $this->_writeEntityType($resourceType, $associationTypesInResourceTypesNamespace);
            } else if ($resourceType->getResourceTypeKind() == ResourceTypeKind::COMPLEX) {
                $this->_writeComplexType($resourceType);
            } else {
                ODataException::createInternalServerError(Messages::metadataWriterExpectingEntityOrComplexResourceType());
            }
        }
    }

    /**
     * Write an entity type and associated attributes.
     * 
     * @param ResourceType $resourceType                            Resource type
     * @param array        $associationTypesInResourceTypeNamespace Collection of 
     * association types for the given resource types
     * array(string, AssociationType)
     * 
     * @return nothing
     */
    private function _writeEntityType(ResourceType $resourceType, $associationTypesInResourceTypeNamespace)
    {
        $this->_xmlWriter->startElement(ODataConstants::ENTITY_TYPE);
        $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $resourceType->getName());
        if ($resourceType->isAbstract()) {
            $this->_xmlWriter->writeAttribute(ODataConstants::ABSTRACT1, "true");
        }

        if ($resourceType->isMediaLinkEntry() && (!$resourceType->hasBaseType() || ($resourceType->hasBaseType() && $resourceType->getBaseType()->isMediaLinkEntry()))) {
            $this->_xmlWriter->writeAttributeNs(ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, ODataConstants::DATAWEB_ACCESS_HASSTREAM_ATTRIBUTE, null, "true");            
        }

        if ($resourceType->hasBaseType()) {
            $this->_xmlWriter->writeAttribute(ODataConstants::BASE_TYPE, $resourceType->getBaseType()->getFullName());
        } else {
            $this->_xmlWriter->startElement(ODataConstants::KEY);
            foreach ($resourceType->getKeyProperties() as $resourceProperty) {
                $this->_xmlWriter->startElement(ODataConstants::PROPERTY_REF);
                $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $resourceProperty->getName());
                $this->_xmlWriter->endElement();
            }

            $this->_xmlWriter->endElement();
        }

        $this->_writeProperties($resourceType, $associationTypesInResourceTypeNamespace);
        $this->_writeNamedStreams($resourceType);
        $this->_xmlWriter->endElement();
    }

    /**
     * Write a complex type and associated attributes
     * 
     * @param ResourceType $complexType resource type
     * 
     * @return nothing
     */
    private function _writeComplexType(ResourceType $complexType)
    {
        $this->_xmlWriter->startElement(ODataConstants::COMPLEX_TYPE);
        $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $complexType->getName());
        $this->_writeProperties($complexType, null);
        $this->_xmlWriter->endElement();
    }

    /**
     * Write properties of a resource type (entity or complex type)
     * 
     * @param ResourceType $resourceType                            The Entity 
     * or Complex resource type.
     * @param array        $associationTypesInResourceTypeNamespace When the 
     * resource type represents an entity, This will be an array of AssociationType 
     * in the namespace same as resource type namespace, array will be 
     * key-value pair with key as association type lookup name and value as
     * association type, this parameter will be null if the resource type
     * represents a complex type
     * array(string, AssociationType)
     * 
     * @return nothing
     */
    private function _writeProperties(ResourceType $resourceType, $associationTypesInResourceTypeNamespace)
    {
        foreach ($this->_metadataManager->getAllVisiblePropertiesDeclaredOnThisType($resourceType) as $resourceProperty) {
            if ($resourceProperty->isKindOf(ResourcePropertyKind::BAG)) {
                $this->_writeBagProperty($resourceProperty);
            } else if ($resourceProperty->isKindOf(ResourcePropertyKind::PRIMITIVE)) {
                $this->_writePrimitiveProperty($resourceProperty);
            } else if ($resourceProperty->isKindOf(ResourcePropertyKind::COMPLEX_TYPE)) {
                $this->_writeComplexProperty($resourceProperty);
            } else if ($resourceProperty->isKindOf(ResourcePropertyKind::RESOURCE_REFERENCE) 
                || $resourceProperty->isKindOf(ResourcePropertyKind::RESOURCESET_REFERENCE)
            ) {
                 $this->_writeNavigationProperty($resourceType, $associationTypesInResourceTypeNamespace, $resourceProperty);
            } else {
                 //Unexpected ResourceProperty, expected 
                 //Bag/Primitive/Complex/Navigation Property   
            }            
        }
    }

    /**
     * Write a bag property and associated attributes
     * 
     * @param ResourceProperty $bagProperty bag property
     * 
     * @return nothing
     */
    private function _writeBagProperty(ResourceProperty $bagProperty)
    {
        $this->_xmlWriter->startElement(ODataConstants::PROPERTY);
        $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $bagProperty->getName());
        $this->_xmlWriter->writeAttribute(ODataConstants::TYPE1, ODataConstants::EDM_BAG_TYPE);
        $this->_xmlWriter->writeAttribute(ODataConstants::NULLABLE, "false");
        $this->_xmlWriter->startElement(ODataConstants::TYPE_REF);
        $this->_xmlWriter->writeAttribute(ODataConstants::TYPE1, $bagProperty->getResourceType()->getFullName());
        $this->_xmlWriter->writeAttribute(ODataConstants::NULLABLE, "false");
        $this->_xmlWriter->endElement();
        $this->_xmlWriter->endElement();
    }

    /**
     * Write a primitive property and associated attributes
     * 
     * @param ResourceProperty $primitiveProperty primitive resource property
     * 
     * @return nothing 
     */
    private function _writePrimitiveProperty(ResourceProperty $primitiveProperty)
    {
        $this->_xmlWriter->startElement(ODataConstants::PROPERTY);
        $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $primitiveProperty->getName());
        $this->_xmlWriter->writeAttribute(ODataConstants::TYPE1, $primitiveProperty->getResourceType()->getFullName());
        $this->_writePrimitivePropertyFacets($primitiveProperty);
        if (!is_null($primitiveProperty->getMIMEType())) {
            $this->_xmlWriter->writeAttributeNs(ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, ODataConstants::DATAWEB_MIMETYPE_ATTRIBUTE_NAME, null, $primitiveProperty->getMIMEType());
        }

        if ($primitiveProperty->isKindOf(ResourcePropertyKind::ETAG)) {
            $this->_xmlWriter->writeAttribute(ODataConstants::CONCURRENCY_ATTRIBUTE, ODataConstants::CONCURRENCY_FIXEDVALUE);        
        }

        $this->_xmlWriter->endElement();
    }

    /**
     * Write a complex property and associated attributes.
     * 
     * @param ResourceProperty $complexProperty complex property
     * 
     * @return nothing
     */
    private function _writeComplexProperty(ResourceProperty $complexProperty)
    {
        $this->_xmlWriter->startElement(ODataConstants::PROPERTY);
        $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $complexProperty->getName());
        $this->_xmlWriter->writeAttribute(ODataConstants::TYPE1, $complexProperty->getResourceType()->getFullName());
        $this->_xmlWriter->writeAttribute(ODataConstants::NULLABLE, "false");
        $this->_xmlWriter->endElement();
    }

    /**
     * Write a navigation property
     * 
     * @param ResourceType     $resourceType                            Resource type
     * @param array            $associationTypesInResourceTypeNamespace Collection 
     * of association types for the given resource types
     * array(ResourceAssociatedType)
     * @param ResourceProperty $navigationProperty                      Navigation 
     * property
     * 
     * @throws InvalidOperationException
     * @return nothing
     */
    private function _writeNavigationProperty(ResourceType $resourceType, $associationTypesInResourceTypeNamespace, ResourceProperty $navigationProperty)
    {
        $associationTypeLookupName = $resourceType->getName() . '_' . $navigationProperty->getName();
        if (!array_key_exists($associationTypeLookupName, $associationTypesInResourceTypeNamespace)) {
            throw new InvalidOperationException(Messages::metadataWriterNoResourceAssociationSetForNavigationProperty($navigationProperty->getName(), $resourceType->getName()));
        }

        $associationType = $associationTypesInResourceTypeNamespace[$associationTypeLookupName];
        $thisEnd = $associationType->getResourceAssociationTypeEnd($resourceType, $navigationProperty);
        $relatedEnd = $associationType->getRelatedResourceAssociationSetEnd($resourceType, $navigationProperty);
        
        $this->_xmlWriter->startElement(ODataConstants::NAVIGATION_PROPERTY);
        $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $navigationProperty->getName());
        $this->_xmlWriter->writeAttribute(ODataConstants::RELATIONSHIP, $associationType->getFullName());
        $this->_xmlWriter->writeAttribute(ODataConstants::FROM_ROLE, $thisEnd->getName());
        $this->_xmlWriter->writeAttribute(ODataConstants::TO_ROLE, $relatedEnd->getName());
        $this->_xmlWriter->endElement();
    }

    /**
     * Write primitive property facets. 
     * 
     * @param ResourceProperty $primitveProperty primitive property
     * 
     * @return nothing
     */
    private function _writePrimitivePropertyFacets(ResourceProperty $primitveProperty)
    {
        $nullable = true;
        if ($primitveProperty->isKindOf(ResourcePropertyKind::KEY)) {
            $nullable = false;    
        }

        $this->_xmlWriter->writeAttribute(ODataConstants::NULLABLE, $nullable ? "true" : "false");
    }

    /**
     * Write all named streams in the given entity type
     * 
     * @param ResourceType $resourceType resource type
     * 
     * @return nothing
     */
    private function _writeNamedStreams(ResourceType $resourceType)
    {
        $namedStreams = $resourceType->getNamedStreamsDeclaredOnThisType();
        if (!empty($namedStreams)) {
            $this->_xmlWriter->startElementNs(null, ODataConstants::DATAWEB_NAMEDSTREAMS_ELEMENT, ODataConstants::ODATA_METADATA_NAMESPACE);
            foreach ($namedStreams as $namedStreamName => $resourceStreamInfo) {
                $this->_xmlWriter->startElementNs(null, ODataConstants::DATAWEB_NAMEDSTREAM_ELEMENT, ODataConstants::ODATA_METADATA_NAMESPACE);
                $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $resourceStreamInfo->getName());
                $this->_xmlWriter->endElement();
            }

            $this->_xmlWriter->endElement();
        }
    }

    /**
     * Write all association type
     * 
     * @param array(ResourceAssociationType) $resourceAssociationTypes collection of
     * resource association types
     * 
     * @return nothing
     */
    private function _writeAssociationTypes($resourceAssociationTypes)
    {
        foreach ($resourceAssociationTypes as $resourceAssociationType) {
            $this->_xmlWriter->startElement(ODataConstants::ASSOCIATION);
            $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $resourceAssociationType->getName());
            $this->_writeAssociationTypeEnd($resourceAssociationType->getEnd1());
            $this->_writeAssociationTypeEnd($resourceAssociationType->getEnd2());
            $this->_xmlWriter->endElement();
        }
    }

    /**
     * Write an association type end.
     * 
     * @param ResourceAssociationTypeEnd $resourceAssociationTypeEnd Resource 
     * association type end
     * 
     * @return nothing
     */
    private function _writeAssociationTypeEnd(ResourceAssociationTypeEnd $resourceAssociationTypeEnd)
    {
        $this->_xmlWriter->startElement(ODataConstants::END);
        $this->_xmlWriter->writeAttribute(ODataConstants::ROLE, $resourceAssociationTypeEnd->getName());
        $this->_xmlWriter->writeAttribute(ODataConstants::TYPE1, $resourceAssociationTypeEnd->getResourceType()->getFullName());
        $this->_xmlWriter->writeAttribute(ODataConstants::MULTIPLICITY, $resourceAssociationTypeEnd->getMultiplicity());
        $this->_xmlWriter->endElement();
    }

    /**
     * Write entity container 
     * 
     * @return nothing
     */
    private function _writeEntityContainer()
    {
        $this->_xmlWriter->startElement(ODataConstants::ENTITY_CONTAINER);
        $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $this->_metadataQueryproviderWrapper->getContainerName());
        $this->_xmlWriter->writeAttributeNs(ODataConstants::ODATA_METADATA_NAMESPACE_PREFIX, ODataConstants::ISDEFAULT_ENTITY_CONTAINER_ATTRIBUTE, null, "true");
        foreach ($this->_metadataManager->getResourceSets() as $resourceSet) {
            $this->_xmlWriter->startElement(ODataConstants::ENTITY_SET);
            $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $resourceSet->getName());
            $this->_xmlWriter->writeAttribute(ODataConstants::ENTITY_TYPE, $resourceSet->getResourceType()->getFullName());
            $this->_xmlWriter->endElement();
        }

        $this->_writeAssociationSets();
        $this->_xmlWriter->endElement();
    }

    /**
     * Write all association sets.
     * 
     * @return nothing
     */
    private function _writeAssociationSets()
    {
        foreach ($this->_metadataManager->getAssociationSets() as $associationSetName => $associationSet) {
            $this->_xmlWriter->startElement(ODataConstants::ASSOCIATION_SET);
            $this->_xmlWriter->writeAttribute(ODataConstants::NAME, $associationSetName);
            $this->_xmlWriter->writeAttribute(ODataConstants::ASSOCIATION, $associationSet->resourceAssociationType->getFullName());
            $this->_writeAssocationSetEnds($associationSet);
            $this->_xmlWriter->endElement();
        }
    }

    /**
     * Write both ends of the given association set.
     * 
     * @param ResourceAssociationSet $associationSet resource association set
     * 
     * @return nothing
     */
    private function _writeAssocationSetEnds(ResourceAssociationSet $associationSet)
    {
        $associationTypeEnd1 =  $associationSet->resourceAssociationType->getResourceAssociationTypeEnd($associationSet->getEnd1()->getResourceType(), $associationSet->getEnd1()->getResourceProperty());
        $associationTypeEnd2 =  $associationSet->resourceAssociationType->getResourceAssociationTypeEnd($associationSet->getEnd2()->getResourceType(), $associationSet->getEnd2()->getResourceProperty());
        $this->_xmlWriter->startElement(ODataConstants::END);
        $this->_xmlWriter->writeAttribute(ODataConstants::ROLE, $associationTypeEnd1->getName());
        $this->_xmlWriter->writeAttribute(ODataConstants::ENTITY_SET, $associationSet->getEnd1()->getResourceSet()->getName());
        $this->_xmlWriter->endElement();
        $this->_xmlWriter->startElement(ODataConstants::END);
        $this->_xmlWriter->writeAttribute(ODataConstants::ROLE, $associationTypeEnd2->getName());
        $this->_xmlWriter->writeAttribute(ODataConstants::ENTITY_SET, $associationSet->getEnd2()->getResourceSet()->getName());
        $this->_xmlWriter->endElement();
    }

    /**
     * Gets the edmx schema namespace uri for the given schema version 
     * 
     * @param MetadataEdmSchemaVersion $metadataEdmSchemaVersion metadata edm
     * schema version
     * 
     * @return string The schema namespace uri
     */
    private function _getSchemaNamespaceUri($metadataEdmSchemaVersion)
    {
        switch ($metadataEdmSchemaVersion) {
        case MetadataEdmSchemaVersion::VERSION_1_DOT_0:
            return ODataConstants::CSDL_VERSION_1_0;

        case MetadataEdmSchemaVersion::VERSION_1_DOT_1:
            return ODataConstants::CSDL_VERSION_1_1;

        case MetadataEdmSchemaVersion::VERSION_1_DOT_2:
            return ODataConstants::CSDL_VERSION_1_2;
            
        case MetadataEdmSchemaVersion::VERSION_2_DOT_0:
            return ODataConstants::CSDL_VERSION_2_0;

        case MetadataEdmSchemaVersion::VERSION_2_DOT_2:
            return ODataConstants::CSDL_VERSION_2_2;

        default:
            return ODataConstants::CSDL_VERSION_2_2;
        }
    }
}
?>