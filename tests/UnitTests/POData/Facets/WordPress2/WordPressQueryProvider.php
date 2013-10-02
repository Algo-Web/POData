<?php

namespace UnitTests\POData\Facets\WordPress2;


/** 
 * Implementation of IDataServiceQueryProvider.
 * 
 */

use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Query\IQueryProvider;
use POData\Common\ODataException;
use POData\UriProcessor\QueryProcessor\ExpressionParser\IExpressionProvider;
use stdClass;

/** The name of the database for WordPress */
define('DB_NAME', 'wordpress');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');


class WordPressQueryProvider implements IQueryProvider
{
	/**
	 * The not implemented error message
	 * @var string
	 */
	private $_message = 'This functionality is not implemented as the class is only for testing IExpressionProvider for MySQL';

    /**
     * Reference to the custom expression provider
     *
     * @var IExpressionProvider
     */
    private $_wordPressMySQLExpressionProvider;
    
    /**
     * Constructs a new instance of WordPressQueryProvider
     * 
     */
    public function __construct()
    {
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
    	if (is_null($this->_wordPressMySQLExpressionProvider)) {
    		$this->_wordPressMySQLExpressionProvider = new WordPressDSExpressionProvider();
    	}
    	
    	return $this->_wordPressMySQLExpressionProvider;
    }
    

    public function getResourceSet(
	    ResourceSet $resourceSet,
	    $filter=null,
	    $orderby=null,
	    $top=null,
	    $skip=null)
    {
    	ODataException::createNotImplementedError($this->_message);
    }
    

    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {
    	ODataException::createNotImplementedError($this->_message);
    }
    

    public function  getRelatedResourceSet(
	    ResourceSet $sourceResourceSet,
        stdClass $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        $filter=null,
        $orderby=null,
        $top=null,
        $skip=null
    ) {
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
    

    public function getRelatedResourceReference(
	    ResourceSet $sourceResourceSet,
    	stdClass $sourceEntityInstance,
    	ResourceSet $targetResourceSet,
    	ResourceProperty $targetProperty
    ) {
    	ODataException::createNotImplementedError($this->_message);
    }
}