<?php

namespace UnitTests\POData\Facets\NorthWind4;

use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Query\IQueryProvider;
use POData\Common\ODataException;
use POData\UriProcessor\QueryProcessor\ExpressionParser\IExpressionProvider;
use stdClass;
// Note: This QP2 implementation is to test IDSQP2::getExpressionProvider functionality 
// we will not test the actual data, instead the sql query generated.

class NorthWindQueryProvider4 implements IQueryProvider
{
	/**
	 * The not implemented error message
	 * @var string
	 */
	private $_message = 'This functionality is not implemented as the class is only for testing IExpressionProvider for SQL-Server';

    /**
     * Reference to the custom expression provider
     * 
     * @var IExpressionProvider
     */
    private $_northWindSQLSRVExpressionProvider;

    /**
     * Constructs a new instance of NorthWindQueryProvider
     * 
     */
    public function __construct()
    {
        $this->_northWindSQLSRVExpressionProvider = null;
    }

	public function handlesOrderedPaging(){
		ODataException::createNotImplementedError($this->_message);
	}

    /**
     * (non-PHPdoc)
     * @see POData\Providers\Query.IQueryProvider::getExpressionProvider()
     */
    public function getExpressionProvider()
    {
    	if (is_null($this->_northWindSQLSRVExpressionProvider)) {
    		$this->_northWindSQLSRVExpressionProvider = new NorthWindDSExpressionProvider4();
    	}

    	return $this->_northWindSQLSRVExpressionProvider;
    }



    public function getResourceSet(
	    ResourceSet $resourceSet,
	    $filterOption = null,
        $orderby=null,
        $top=null,
        $skip=null
    ) {
    	ODataException::createNotImplementedError($this->_message);
    }



    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {
    	ODataException::createNotImplementedError($this->_message);
    }
    

    public function getResourceFromRelatedResourceSet(
	    ResourceSet $sourceResourceSet,
	    stdClass $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
    	ODataException::createNotImplementedError($this->_message);
    }


    public function getRelatedResourceSet(
	    ResourceSet $sourceResourceSet,
		stdClass $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty, 
        $filterOption = null,
        $orderby=null,
        $top=null,
        $skip=null
    ) {
    	ODataException::createNotImplementedError($this->_message);
    }



    public function getRelatedResourceReference(
	    ResourceSet $sourceResourceSet,
		stdClass $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
    	ODataException::createNotImplementedError($this->_message);
    }


}