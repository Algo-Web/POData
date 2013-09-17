<?php

namespace POData\Writers\Atom;

use POData\Common\ODataConstants;
use POData\Providers\MetadataQueryProviderWrapper;
use POData\Writers\IServiceDocumentWriter;

/**
 * Class ServiceDocumentWriter
 * @package POData\Writers\ServiceDocument\Atom
 */
class AtomServiceDocumentWriter implements IServiceDocumentWriter
{

    /**
     * Holds reference to the wrapper over service metadata and query provider implementations
     * In this context this provider will be used for gathering metadata information only.
     *
     * @var MetadataQueryProviderWrapper
     */
    private $_metadataQueryProviderWrapper;

    /**
     * Data service base uri from which resources should be resolved
     * 
     * @var string
     */
    private $_baseUri;

    /**
     * XML prefix for the Atom namespace.
     * 
     * @var string
     */
    const ATOM_NAMESPACE_PREFIX = 'atom';

    /**
     * XML prefix for the Atom Publishing Protocol namespace
     * 
     * @var string
     */
    const APP_NAMESPACE_PREFIX = 'app';

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
        $this->_metadataQueryProviderWrapper = $provider;
        $this->_baseUri = $baseUri;
    }

    /**
     * Write the service document in Atom format.
     * 
     * @return string
     */
    public function getOutput()
    {
        $writer = new \XMLWriter();
        $writer->openMemory();

        $writer->startElementNs(null, ODataConstants::ATOM_PUBLISHING_SERVICE_ELEMENT_NAME, ODataConstants::APP_NAMESPACE);
        $writer->writeAttributeNs(ODataConstants::XML_NAMESPACE_PREFIX, ODataConstants::XML_BASE_ATTRIBUTE_NAME, null, $this->_baseUri);
        $writer->writeAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, self::ATOM_NAMESPACE_PREFIX, null, ODataConstants::ATOM_NAMESPACE);
        $writer->writeAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, self::APP_NAMESPACE_PREFIX, null, ODataConstants::APP_NAMESPACE);

        $writer->startElement(ODataConstants::ATOM_PUBLISHING_WORKSPACE_ELEMNT_NAME);
        $writer->startElementNs(self::ATOM_NAMESPACE_PREFIX, ODataConstants::ATOM_TITLE_ELELMET_NAME, null);
        $writer->text(ODataConstants::ATOM_PUBLISHING_WORKSPACE_DEFAULT_VALUE);
        $writer->endElement();
        foreach ($this->_metadataQueryProviderWrapper->getResourceSets() as $resourceSetWrapper) {
            //start collection node
            $writer->startElement(ODataConstants::ATOM_PUBLISHING_COLLECTION_ELEMENT_NAME);
            $writer->writeAttribute(ODataConstants::ATOM_HREF_ATTRIBUTE_NAME, $resourceSetWrapper->getName());
            //start title node
            $writer->startElementNs(self::ATOM_NAMESPACE_PREFIX, ODataConstants::ATOM_TITLE_ELELMET_NAME, null);
            $writer->text($resourceSetWrapper->getName());
            //end title node
            $writer->endElement();
            //end collection node
            $writer->endElement();
        }

        //End workspace and service nodes
        $writer->endElement();
        $writer->endElement();

	    return $writer->outputMemory(true);
    }
}