<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Query\IQueryProvider;
use POData\Common\ODataException;

use UnitTests\POData\Facets\NorthWind1\NorthWindExpressionProvider;
use stdClass;
// Note: This QP2 implementation is to test IDSQP2::getExpressionProvider functionality 
// we will not test the actual data, instead the sql query generated.

class NorthWindQueryProvider implements IQueryProvider
{
	/**
	 * The not implemented error message
	 * @var string
	 */
	private $_message = 'This functionality is not implemented as the class is only for testing IExpressionProvider for SQL-Server';



	public function handlesOrderedPaging(){
		ODataException::createNotImplementedError($this->_message);
	}

	public function getExpressionProvider()
	{
		return new NorthWindExpressionProvider();
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



    public function  getRelatedResourceSet(
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