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
     * Holds reference to the wrapper over service metadata and 
     * query provider implemenations
     * In this context this provider will be used for 
     * gathering metadata informations only.
     *      
     * @var MetadataQueryProviderWrapper
     */
    private $_metadataQueryproviderWrapper;
    
    /**
     * Constructs new instance of ServiceDocumentWriter
     * 
     * @param MetadataQueryProviderWrapper $provider Reference to the wrapper over 
     *                                               service metadata and 
     *                                               query provider implemenations.
     * @param string                       $baseUri  Data service base uri from 
     *                                               which resources 
     *                                               should be resolved.
     */
    public function __construct(MetadataQueryProviderWrapper $provider, $baseUri)
    {
        $this->_metadataQueryproviderWrapper = $provider;
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
        // { "d" :
        $this->_writer->startObjectScope();
        $this->_writer->writeDataWrapper();
        // {
        $this->_writer->startObjectScope();
        // "EntitySets"
        $this->_writer->writeName(ODataConstants::ENTITY_SET);
        // [
        $this->_writer->startArrayScope();
        foreach ($this->_metadataQueryproviderWrapper->getResourceSets() as $resourceSetWrapper) {
            $this->_writer->writeValue($resourceSetWrapper->getName());
        }
        // ]
        $this->_writer->endScope();
        // }
        $this->_writer->endScope();
        // }
        $this->_writer->endScope();
        //result
        $serviceDocumentInJson = $this->_writer->getJsonOutput();
        return $serviceDocumentInJson;
    }  
}