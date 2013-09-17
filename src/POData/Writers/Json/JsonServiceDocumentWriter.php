<?php

namespace POData\Writers\Json;

use POData\Writers\Json\JsonWriter;
use POData\Common\ODataConstants;
use POData\Providers\MetadataQueryProviderWrapper;
use POData\Writers\IServiceDocumentWriter;

/**
 * Class ServiceDocumentWriter
 * @package POData\Writers\ServiceDocument\Json
 */
class JsonServiceDocumentWriter implements IServiceDocumentWriter
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
     */
    public function __construct(MetadataQueryProviderWrapper $provider)
    {
        $this->_metadataQueryProviderWrapper = $provider;
    }
    
    /**
     * Write the service document in JSON format.
     * 
     * @return string
     */
    public function getOutput()
    {
	    $writer = new JsonWriter("");
	    $writer
	        ->startObjectScope() // { "d" :
	        ->writeDataWrapper()
	        ->startObjectScope() // {
	        ->writeName(ODataConstants::ENTITY_SET) // "EntitySets"
            ->startArrayScope() // [
	    ;

        foreach ($this->_metadataQueryProviderWrapper->getResourceSets() as $resourceSetWrapper) {
	        $writer->writeValue($resourceSetWrapper->getName());
        }

	    $writer
	        ->endScope() // ]
	        ->endScope() // }
			->endScope() // }
		;

        return $writer->getJsonOutput();
    }  
}