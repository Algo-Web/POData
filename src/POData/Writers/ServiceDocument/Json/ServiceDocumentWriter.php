<?php

namespace POData\Writers\ServiceDocument\Json;

use POData\Writers\Json\JsonWriter;
use POData\Common\ODataConstants;
use POData\Providers\MetadataQueryProviderWrapper;

/**
 * Class ServiceDocumentWriter
 * @package POData\Writers\ServiceDocument\Json
 */
class ServiceDocumentWriter
{
    /**
     * Json output writer.
     *      
     */
    private $_writer;

    /**
     * Holds reference to the wrapper over service metadata and query provider implementations
     * In this context this provider will be used for gathering metadata information only.
     *
     * @var MetadataQueryProviderWrapper
     */
    private $_metadataQueryProviderWrapper;
    
    /**
     * Constructs new instance of ServiceDocumentWriter
     * 
     * @param MetadataQueryProviderWrapper $provider Reference to the wrapper over 
     *                                               service metadata and 
     *                                               query provider implementations.
     * @param string                       $baseUri  Data service base uri from 
     *                                               which resources 
     *                                               should be resolved.
     */
    public function __construct(MetadataQueryProviderWrapper $provider, $baseUri)
    {
        $this->_metadataQueryProviderWrapper = $provider;
        $this->_writer = new JsonWriter('');
    }
    
    /**
     * Write the service document in JSON format.
     * 
     * @param Object &$dummy Dummy object
     * 
     * @return string
     */
    public function writeRequest(&$dummy)
    {

        $this->_writer
	        ->startObjectScope() // { "d" :
	        ->writeDataWrapper()
	        ->startObjectScope() // {
	        ->writeName(ODataConstants::ENTITY_SET) // "EntitySets"
            ->startArrayScope() // [
	    ;

        foreach ($this->_metadataQueryProviderWrapper->getResourceSets() as $resourceSetWrapper) {
            $this->_writer->writeValue($resourceSetWrapper->getName());
        }

        $this->_writer
	        ->endScope() // ]
	        ->endScope() // }
			->endScope() // }
		;

        return $this->_writer->getJsonOutput();
    }  
}