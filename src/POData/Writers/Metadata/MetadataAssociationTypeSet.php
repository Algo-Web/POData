<?php

namespace POData\Writers\Metadata;

use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
use POData\Providers\Metadata\ResourceAssociationType;
use POData\Providers\Metadata\ResourceAssociationTypeEnd;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;

/**
 * Class MetadataAssociationTypeSet.
 *
 * This class validates all AssociationSet defined for the service and creates
 * AssociationType for each AssociationSet from the given provider.
 *
 * Iterate over all resource (entity) types belongs to visible resource (entity) sets
 * for each entity type retrieve its derived and base resource types. For each
 * navigation property in these types (base types, type, derived types) cache the
 * association set and build and group 'ResourceAssociationType' instances based on
 * namespace.
 * (will use the namespace of the resource type in the association type instance)
 */
class MetadataAssociationTypeSet extends MetadataBase
{
    /**
     * Array of namespace along with the resource association types in that namespace
     * Namespace will be the key and value will be array of
     * 'ResourceAssociationType' in that namespace
     * (as key: association_type_lookup_key, value:ResourceAssociationType))
     * array(namespace_name,
     *      array(association_type_lookup_key, ResourceAssociationType)
     *      )
     * Note1: This array will holds one entry per direction,
     * so for a bidirectional relationship
     * same AssociationType will appear twice.
     *
     * @var array(string, array(string, ResourceAssociationType))
     */
    private $_resourceAssociationTypes = [];

    /**
     * Array of unique 'ResourceAssociationType'
     * array(namespace_name, array(association_type_name, ResourceAssociationType)).
     *
     * @var array(string, array(string, ResourceAssociationType))
     */
    private $_uniqueResourceAssociationTypes = null;

    /**
     * Array of 'ResourceAssociationSet'
     * Note1: All resource sets belongs to the container namespace
     * Note2: This array will holds one entry per direction,
     * so for a bidirectional relationship same AssociationSet will appear twice,
     * so use the function 'getAssociationSets' to get the unique instance from
     * this array.
     *
     * @var ResourceAssociationSet[]
     */
    private $_resourceAssociationSets = [];

    /**
     * Array of unique 'ResourceAssociationSet'
     * array(association_set_name, ResourceAssociationType).
     *
     * @var ResourceAssociationSet[]
     */
    private $_uniqueResourceAssociationSets = null;

    /**
     * Construct new instance of MetadataAssociationTypeSet, this constructor
     * creates and caches resource association set and association type for
     * all resource (entity) sets.
     *
     * @param ProvidersWrapper $provider Reference to the
     *                                   service metadata and query provider wrapper
     */
    public function __construct(ProvidersWrapper $provider)
    {
        parent::__construct($provider);
        foreach ($this->providersWrapper->getResourceSets() as $resourceSetWrapper) {
            $this->_populateAssociationForSet($resourceSetWrapper);
        }
    }

    /**
     * Gets collection of association set.
     *
     * @return ResourceAssociationSet[]
     */
    public function getAssociationSets()
    {
        if (is_null($this->_uniqueResourceAssociationSets)) {
            $this->_uniqueResourceAssociationSets = [];
            foreach ($this->_resourceAssociationSets as $lookupName => $resourceAssociationSet) {
                $resourceAssociationSetName = $resourceAssociationSet->getName();
                if (!array_key_exists($resourceAssociationSetName, $this->_uniqueResourceAssociationSets)) {
                    $this->_uniqueResourceAssociationSets[$resourceAssociationSetName] = $resourceAssociationSet;
                }
            }
        }

        return $this->_uniqueResourceAssociationSets;
    }

    /**
     * Gets collection of association types belongs to the given namespace,
     * creates a collection for the namespace if its not already there,
     * This array of association types in a namespace will contains
     * one entry per direction, so for a bidirectional relationship
     * same AssociationType (having same association type name)
     * will appear twice with different cache (lookup) key.
     *
     * @param string $namespace The namespace name to get
     *                          the association types belongs to
     *
     * @return ResourceAssociationType[]
     */
    public function &getResourceAssociationTypesForNamespace($namespace)
    {
        if (!array_key_exists($namespace, $this->_resourceAssociationTypes)) {
            $this->_resourceAssociationTypes[$namespace] = [];
        }

        return $this->_resourceAssociationTypes[$namespace];
    }

    /**
     * Gets unique collection of association type for the given namespace, the
     * 'getResourceAssociationTypesForNamespace' will also returns
     * collection of association type for a given namespace but will contain
     * duplicate association type in case of bi-directional relationship.
     *
     * @param string $namespace Namespace name to get
     *                          the association type belongs to
     *
     * @return ResourceAssociationType[]
     */
    public function getUniqueResourceAssociationTypesForNamespace($namespace)
    {
        if (is_null($this->_uniqueResourceAssociationTypes)) {
            $this->_uniqueResourceAssociationTypes = [];
            foreach ($this->_resourceAssociationTypes as $nameSpaceName => $resourceAssociationTypesWithLookupKey) {
                $this->_uniqueResourceAssociationTypes[$nameSpaceName] = [];
                foreach ($resourceAssociationTypesWithLookupKey as $lookupKey => $resourceAssociationType) {
                    $resourceAssociationTypeName = $resourceAssociationType->getName();
                    if (!array_key_exists($resourceAssociationTypeName, $this->_uniqueResourceAssociationTypes[$nameSpaceName])) {
                        $this->_uniqueResourceAssociationTypes[$namespace][$resourceAssociationTypeName] = $resourceAssociationType;
                    }
                }
            }
        }

        if (array_key_exists($namespace, $this->_uniqueResourceAssociationTypes)) {
            return array_values($this->_uniqueResourceAssociationTypes[$namespace]);
        }

        return [];
    }

    /**
     * Populate association set and type for the given resource (entity) set.
     *
     * @param ResourceSetWrapper $resourceSetWrapper The resource set to inspect
     *
     * @throws InvalidOperationException If IDSMP::getDerivedTypes
     *                                   returns invalid type
     */
    private function _populateAssociationForSet(ResourceSetWrapper $resourceSetWrapper)
    {
        $derivedTypes = $this->providersWrapper->getDerivedTypes($resourceSetWrapper->getResourceType());

        //Populate ResourceAssociationSet and ResourceAssociationType for derived types
        foreach ($derivedTypes as $derivedType) {
            $this->_populateAssociationForSetAndType($resourceSetWrapper, $derivedType);
        }

        //Populate ResourceAssociationSet and ResourceAssociationType
        //for this type and base types
        $resourceType = $resourceSetWrapper->getResourceType();
        while ($resourceType != null) {
            $this->_populateAssociationForSetAndType($resourceSetWrapper, $resourceType);
            $resourceType = $resourceType->getBaseType();
        }
    }

    /**
     * Populate association for the given resouce set and its resource type
     * (derived, base or own type).
     *
     * @param ResourceSetWrapper $resourceSetWrapper The resource set to inspect
     * @param ResourceType       $resourceType       The resource type to inspect
     *
     * @throws InvalidOperationException If validation fails at
     *                                   _getResourceAssociationSet
     * @throws ODataException            If validation fails at
     *                                   ProvidersWrapper::getResourceAssociationSet
     */
    private function _populateAssociationForSetAndType(ResourceSetWrapper $resourceSetWrapper, ResourceType $resourceType)
    {
        $properties = $resourceType->getPropertiesDeclaredOnThisType();
        foreach ($properties as $property) {
            if ($property->getTypeKind() == ResourceTypeKind::ENTITY) {
                $resourceAssociationSet = $this->_getResourceAssociationSet($resourceSetWrapper, $resourceType, $property);
                if (!is_null($resourceAssociationSet)) {
                    $resourceAssociationSet->resourceAssociationType = $this->_getResourceAssociationType($resourceAssociationSet, $resourceSetWrapper, $resourceType, $property);
                }
            }
        }
    }

    /**
     * Gets and validate the ResourceAssociationSet instance for the
     * given source resource association end
     * This function first searches the ResourceAssociationSet cache,
     * if found return it otherwise
     * get the association set from metadata wrapper,
     * validate, cache and return it.
     *
     * @param ResourceSetWrapper $resourceSet        Resource set of the
     *                                               source association end
     * @param ResourceType       $resourceType       Resource type of the
     *                                               source association end
     * @param ResourceProperty   $navigationProperty Resource property of the
     *                                               source association end
     *
     * @throws InvalidOperationException If validation of AssociationSet fails
     * @throws ODataException            If validation fails at
     *                                   ProvidersWrapper::getResourceAssociationSet
     *
     * @return ResourceAssociationSet|null The association set instance for the
     *                                     given association set end,
     *                                     NULL if the metadata wrapper
     *                                     returns NULL
     *                                     (either IDSMP implementation
     *                                     returns null or
     *                                     target resource set is invisible)
     */
    private function _getResourceAssociationSet(ResourceSetWrapper $resourceSet, ResourceType $resourceType, ResourceProperty $navigationProperty)
    {
        $associationSetLookupKey = $resourceSet->getName() . '_' . $resourceType->getFullName() . '_' . $navigationProperty->getName();
        if (array_key_exists($associationSetLookupKey, $this->_resourceAssociationSets)) {
            return $this->_resourceAssociationSets[$associationSetLookupKey];
        }

        $resourceAssociationSet = $this->providersWrapper->getResourceAssociationSet($resourceSet, $resourceType, $navigationProperty);
        if (is_null($resourceAssociationSet)) {
            //Either the related ResourceSet is invisible or IDSMP implementation returns null
            return;
        }

        /*
         * @var ResourceAssociationSetEnd
         */
        $relatedEnd = $resourceAssociationSet->getRelatedResourceAssociationSetEnd(
            $resourceSet->getResourceSet(),
            $resourceType,
            $navigationProperty
        );

        //For bidirectional relationship IDSMP::getResourceAssociationSet should
        //return same association set when called from either end
        if (!is_null($relatedEnd->getResourceProperty())) {
            //No need to check whether the following call returns NULL,
            //because the above call
            //providersWrapper::getResourceAssociationSet
            //causes the metadata wrapper to check the visibility of
            //related resource set and cache the corresponding wrapper.
            //If found invisible it would have return NULL,
            //which we are any way handling above.
            $relatedResourceSetWrapper = $this->providersWrapper->validateResourceSetAndGetWrapper($relatedEnd->getResourceSet());
            $reverseResourceAssociationSet = $this->providersWrapper->getResourceAssociationSet($relatedResourceSetWrapper, $relatedEnd->getResourceType(), $relatedEnd->getResourceProperty());
            if (is_null($reverseResourceAssociationSet) || (!is_null($reverseResourceAssociationSet) && $resourceAssociationSet->getName() != $reverseResourceAssociationSet->getName())) {
                throw new InvalidOperationException(Messages::metadataAssociationTypeSetBidirectionalAssociationMustReturnSameResourceAssociationSetFromBothEnd());
            }
        }

        $reverseAssociationSetLookupKey = null;
        if (!is_null($relatedEnd->getResourceProperty())) {
            $reverseAssociationSetLookupKey = $relatedEnd->getResourceSet()->getName() . '_' . $relatedEnd->getResourceProperty()->getResourceType()->getFullName() . '_' . $relatedEnd->getResourceProperty()->getName();
        } else {
            $reverseAssociationSetLookupKey = $relatedEnd->getResourceSet()->getName() . '_Null_' . $resourceType->getFullName() . '_' . $navigationProperty->getName();
        }

        if (array_key_exists($reverseAssociationSetLookupKey, $this->_resourceAssociationSets)) {
            throw new InvalidOperationException(Messages::metadataAssociationTypeSetMultipleAssociationSetsForTheSameAssociationTypeMustNotReferToSameEndSets($this->_resourceAssociationSets[$reverseAssociationSetLookupKey]->getName(), $resourceAssociationSet->getName(), $relatedEnd->getResourceSet()->getName()));
        }

        $this->_resourceAssociationSets[$associationSetLookupKey] = $resourceAssociationSet;
        $this->_resourceAssociationSets[$reverseAssociationSetLookupKey] = $resourceAssociationSet;

        return $resourceAssociationSet;
    }

    /**
     * Gets the ResourceAssociationType instance for the given
     * ResourceAssociationSet and one of it's end.
     *
     * This function first searches the ResourceAssociationType cache,
     * if found return it otherwise create the Association type for the given
     * association set, cache and return it.
     *
     * Creation of ResourceAssociationType includes two sub-tasks:
     *  1. Deciding name of 'ResourceAssociationType'
     *  (see the function _getAssociationTypeName)
     *  2. Deciding names for two 'ResourceAssociationTypeEnd' of the
     *  'ResourceAssociationType'
     *  Refer ./AssociationSetAndTypeNamingRules.txt for naming rules.
     *
     * @param ResourceAssociationSet $resourceAssociationSet Association set to get the association type
     * @param ResourceSetWrapper     $resourceSet            Resource set for one of the ends of given association set
     * @param ResourceType           $resourceType           Resource type for one of the ends of given association set
     * @param ResourceProperty       $navigationProperty     Resource property for one of the ends of given association set
     *
     * @return ResourceAssociationType The association type for the given association set
     */
    private function _getResourceAssociationType(ResourceAssociationSet $resourceAssociationSet, ResourceSetWrapper $resourceSet, ResourceType $resourceType, ResourceProperty $navigationProperty)
    {
        $resourceTypeNamespace = $this->getResourceTypeNamespace($resourceType);
        $resourceAssociationTypesInNamespace = &$this->getResourceAssociationTypesForNamespace($resourceTypeNamespace);
        $associationTypeLookupKey = $resourceType->getName() . '_' . $navigationProperty->getName();
        if (array_key_exists($associationTypeLookupKey, $resourceAssociationTypesInNamespace)) {
            return $resourceAssociationTypesInNamespace[$associationTypeLookupKey];
        }

        //Generate resource association type end names
        //Refer ./AssociationSetAndTypeNamingRules.txt
        $isBiDirectional = $resourceAssociationSet->isBidirectional();
        $associationTypeEnd1Name = $resourceAssociationSet->getEnd1()->getResourceType()->getName();
        $associationTypeEnd2Name = $resourceAssociationSet->getEnd2()->getResourceType()->getName();
        if ($isBiDirectional) {
            $associationTypeEnd1Name .= '_' . $resourceAssociationSet->getEnd1()->getResourceProperty()->getName();
            $associationTypeEnd2Name .= '_' . $resourceAssociationSet->getEnd2()->getResourceProperty()->getName();
        }

        //Generate resource association type name
        //Refer ./AssociationSetAndTypeNamingRules.txt
        $resourceAssociationTypeName = $this->_getAssociationTypeName($resourceAssociationSet);
        //Create and cache the association type
        $resourceAssociationType = new ResourceAssociationType(
            $resourceAssociationTypeName,
            $resourceTypeNamespace,
            new ResourceAssociationTypeEnd(
                $associationTypeEnd1Name,
                $resourceAssociationSet->getEnd1()->getResourceType(),
                $resourceAssociationSet->getEnd1()->getResourceProperty(),
                $resourceAssociationSet->getEnd2()->getResourceProperty()
            ),
            new ResourceAssociationTypeEnd(
                $associationTypeEnd2Name,
                $resourceAssociationSet->getEnd2()->getResourceType(),
                $resourceAssociationSet->getEnd2()->getResourceProperty(),
                $resourceAssociationSet->getEnd1()->getResourceProperty()
            )
        );

        $resourceAssociationTypesInNamespace[$associationTypeLookupKey] = $resourceAssociationType;
        if ($isBiDirectional) {
            $relatedAssociationSetEnd = $resourceAssociationSet->getRelatedResourceAssociationSetEnd($resourceSet->getResourceSet(), $resourceType, $navigationProperty);
            $relatedEndLookupKey = $relatedAssociationSetEnd->getResourceType()->getName() . '_' . $relatedAssociationSetEnd->getResourceProperty()->getName();
            $resourceAssociationTypesInNamespace[$relatedEndLookupKey] = $resourceAssociationType;
        }

        return $resourceAssociationType;
    }

    /**
     * Generate association type name for a given association set,
     * this name is used as value of Name attribute of Association type node in the
     * metadata corresponding to the given association set.
     *
     * Refer ./AssociationSetAndTypeNamingRules.txt for naming rules.
     *
     * @param ResourceAssociationSet $resourceAssociationSet The association set
     *
     * @return string The association type name
     */
    private function _getAssociationTypeName(ResourceAssociationSet $resourceAssociationSet)
    {
        $end1 = !is_null($resourceAssociationSet->getEnd1()->getResourceProperty()) ?
                    $resourceAssociationSet->getEnd1() : $resourceAssociationSet->getEnd2();
        $end2 = $resourceAssociationSet->getRelatedResourceAssociationSetEnd($end1->getResourceSet(), $end1->getResourceType(), $end1->getResourceProperty());
        //e.g. Customer_Orders (w.r.t Northwind DB)
        $associationTypeName = $end1->getResourceType()->getName() . '_' . $end1->getResourceProperty()->getName();
        if (!is_null($end2->getResourceProperty())) {
            //Customer_Orders_Order_Customer
            $associationTypeName .= '_' . $end2->getResourceType()->getName() . '_' . $end2->getResourceProperty()->getName();
        }

        return $associationTypeName;
    }
}
