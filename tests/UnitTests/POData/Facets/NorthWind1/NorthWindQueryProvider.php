<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Query\IQueryProvider;
use POData\Common\ODataException;

use UnitTests\POData\Facets\NorthWind1\NorthWindExpressionProvider;

// Note: This QP2 implementation is to test IDSQP2::getExpressionProvider functionality 
// we will not test the actual data, instead the sql query generated.

class NorthWindQueryProvider implements IQueryProvider
{
	/**
	 * The not implemented error message
	 * @var string
	 */
	private $_message = 'This functionality is not implemented as the class is only for testing IExpressionProvider for SQL-Server';


    /**
     * Constructs a new instance of NorthWindQueryProvider
     * 
     */
    public function __construct()
    {
        $this->_northWindSQLSRVExpressionProvider = null;
    }

	public function canApplyQueryOptions(){
		ODataException::createNotImplementedError($this->_message);
	}

	public function getExpressionProvider()
	{
		return new NorthWindExpressionProvider();
	}

    /**
     * Gets collection of entities belongs to an entity set
     * Note: Dummy implementation, actual functionality is not implemented
     * 
     * @param ResourceSet $resourceSet        The entity set whose entities 
     *                                        needs to be fetched.
     * @param string           $filterOption  Contains the filter condition
     * @param string           $select        For future purpose,no need to pass it
     * @param string           $orderby       For future purpose,no need to pass it
     * @param string           $top           For future purpose,no need to pass it
     * @param string           $skip          For future purpose,no need to pass it
     * 
     * @return array(Object)
     */
    public function getResourceSet(ResourceSet $resourceSet, $filterOption = null, 
        $select=null, $orderby=null, $top=null, $skip=null
    ) {
    	ODataException::createNotImplementedError($this->_message);
    }

    /**
     * Gets an entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet   $resourceSet   The entity set from which 
     *                                     an entity needs to be fetched
     * @param KeyDescriptor $keyDescriptor The key to identify the entity to be fetched
     * 
     * @return stdClass|null Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {
    	ODataException::createNotImplementedError($this->_message);
    }
    
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
     * @return stdClass|null Returns entity instance if found else null
     */
    public function  getResourceFromRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
    	ODataException::createNotImplementedError($this->_message);
    }

    /**
     * Get related resource set for a resource
     * 
     * @param ResourceSet      $sourceResourceSet    The source resource set
     * @param mixed            $sourceEntityInstance The resource
     * @param ResourceSet      $targetResourceSet    The resource set of 
     *                                               the navigation property
     * @param ResourceProperty $targetProperty       The navigation property to be 
     *                                               retrieved
     * @param string           $filterOption         Contains the filter condition 
     *                                               to append with query.
     * @param string           $select               For future purpose,no need to pass it
     * @param string           $orderby              For future purpose,no need to pass it
     * @param string           $top                  For future purpose,no need to pass it
     * @param string           $skip                 For future purpose,no need to pass it
     *                                                
     * @return stdClass[] Array of related resource if exists, if no 
     *                                related resources found returns empty array
     */
    public function  getRelatedResourceSet(
	    ResourceSet $sourceResourceSet,
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty, 
        $filterOption = null,
        $select=null, $orderby=null, $top=null, $skip=null
    ) {
    	ODataException::createNotImplementedError($this->_message);
    }

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
     * @return stdClass|null The related resource if exists else null
     */
    public function getRelatedResourceReference(
	    ResourceSet $sourceResourceSet,
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
    	ODataException::createNotImplementedError($this->_message);
    }

}