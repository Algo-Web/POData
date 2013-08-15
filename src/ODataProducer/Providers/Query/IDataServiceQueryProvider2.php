<?php

namespace ODataProducer\Providers\Query;

use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ODataProducer\Providers\Metadata\ResourceSet;

/**
 * Class IDataServiceQueryProvider2
 * @package ODataProducer\Providers\Query
 */
interface IDataServiceQueryProvider2
{

  /**
   * Library will use this function to check whether library has to
   * apply orderby, skip and top.
   * Note: Library will not delegate $select/$expand operation to IDSQP2
   * implementation, they will always handled by Library.
   * 
   * @return Boolean True If user want library to apply the query options
   *                 False If user is going to take care of orderby, skip
   *                 and top options
   */
  public function canApplyQueryOptions();
  
  /**
   * Gets the custom expression provider.
   * 
   * @return IExpressionProvider
   */
  public function getExpressionProvider();

    /**
     * Gets collection of entities belongs to an entity set
     * 
     * @param ResourceSet $resourceSet The entity set whose entities needs 
     *                                 to be fetched
     * @param String      $filter      filter condition if any need to be apply in the query
     * @param mixed       $select      select field set Fields which need to be fetch from 
     *                                 the Data-Source (This param will be always null, as library 
     *                                 will not delegate the $select and $expand operations)
     * @param mixed       $orderby     sorted order if we want to get the data in some 
     *                                 specific order
     * @param Number      $top         number of records which  need to be skip
     * @param String      $skiptoken   skiptoken value if we want to skip records till 
     *                                 that skiptoken value
     * 
     * @return object[]
     */
    public function getResourceSet(ResourceSet $resourceSet, $filter = null,
        $select = null, 
        $orderby = null, 
        $top = null, 
        $skiptoken = null
    );

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
     * @return Object|null Returns entity instance if found else null
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
     * @param String           $filter               filter condition if any need to be apply in the query
     * @param mixed            $select               select field set Fields which need to be fetch from 
     *                                               the Data-Source (This param will be always null, as library 
     *                                               will not delegate the $select and $expand operations)
     * @param mixed            $orderby              sort order if we want to get the data in some 
     *                                               specific order
     * @param Number           $top                  number of records which  need to be skip
     * @param String           $skip                 skiptoken value if we want to skip records till that skiptoken value
     *
     *                                               
     * @return object[] Array of related resource if exists, if no related resources found returns empty array
     *
     */
    public function  getRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        $filter = null, 
        $select = null, 
        $orderby = null, 
        $top = null, 
        $skip=null 
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