<?php
/** 
 * The class which implements this interface is responsible responding the queries
 * for entity set, entity instance and related entities
 * 
*/
namespace ODataProducer\Providers\Query;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ODataProducer\Providers\Metadata\ResourceSet;
/**
 * Query provider interface.
*
 */
interface IDataServiceQueryProvider
{
    /**
     * Gets collection of entities belongs to an entity set
     * 
     * @param ResourceSet $resourceSet The entity set whose entities needs 
     *                                 to be fetched
     * 
     * @return array(Object)/array()
     */
    public function getResourceSet(ResourceSet $resourceSet);

    /**
     * Gets an entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet   $resourceSet   The entity set from which an entity
     *                                     needs to be fetched
     * @param KeyDescriptor $keyDescriptor The key to identify the entity to be 
     *                                     fetched
     * 
     * @return Object/NULL Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, 
        KeyDescriptor $keyDescriptor
    );

    /**
     * Gets a related entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet      $sourceResourceSet    The entity set related to
     *                                               the entity to be fetched.
     * @param object           $sourceEntityInstance The related entity instance.
     * @param ResourceSet      $targetResourceSet    The entity set from which
     *                                               entity needs to be fetched.
     * @param ResourceProperty $targetProperty       The metadata of the target 
     *                                               property.
     * @param KeyDescriptor    $keyDescriptor        The key to identify the entity 
     *                                               to be fetched.
     * 
     * @return Object/NULL Returns entity instance if found else null
     */
    public function getResourceFromRelatedResourceSet(ResourceSet $sourceResourceSet,
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    );

    /**
     * Get related resource set for a resource
     * 
     * @param ResourceSet      $sourceResourceSet    The source resource set
     * @param mixed            $sourceEntityInstance The resource
     * @param ResourceSet      $targetResourceSet    The resource set of 
     *                                               the navigation property
     * @param ResourceProperty $targetProperty       The navigation property to be 
     *                                               retrieved
     *                                               
     * @return array(Objects)/array() Array of related resource if exists, if no 
     *                                related resources found returns empty array
     */
    public function  getRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    );

    /**
     * Get related resource for a resource
     * 
     * @param ResourceSet      $sourceResourceSet    The source resource set
     * @param mixed            $sourceEntityInstance The source resource
     * @param ResourceSet      $targetResourceSet    The resource set of 
     *                                               the navigation property
     * @param ResourceProperty $targetProperty       The navigation property to be 
     *                                               retrieved
     * 
     * @return Object/null The related resource if exists else null
     */
    public function getRelatedResourceReference(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    );
}
?>