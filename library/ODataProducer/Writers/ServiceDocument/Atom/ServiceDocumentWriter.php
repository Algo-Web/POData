<?php
/** 
 * Writer for service document in Atom format.
 *
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_ServiceDocument_Atom
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Writers\ServiceDocument\Atom;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
/** 
 * Service documenter class for atom
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_ServiceDocument_Atom
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ServiceDocumentWriter
{
    /**
     * Writer to which output (Service Document) is sent
     * 
     * @var XMLWriter
     */
    private $_xmlWriter;

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
        $this->_metadataQueryproviderWrapper = $provider;
        $this->_baseUri = $baseUri;
    }

    /**
     * Write the service document in Atom format.
     * 
     * @param Object &$dummy Dummy object
     * 
     * @return string
     */
    public function writeRequest(&$dummy)
    {
        $this->_xmlWriter = new \XMLWriter();
        $this->_xmlWriter->openMemory();

        $this->_xmlWriter->startElementNs(null, ODataConstants::ATOM_PUBLISHING_SERVICE_ELEMENT_NAME, ODataConstants::APP_NAMESPACE);
        $this->_xmlWriter->writeAttributeNs(ODataConstants::XML_NAMESPACE_PREFIX, ODataConstants::XML_BASE_ATTRIBUTE_NAME, null, $this->_baseUri);
        $this->_xmlWriter->writeAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, self::ATOM_NAMESPACE_PREFIX, null, ODataConstants::ATOM_NAMESPACE);
        $this->_xmlWriter->writeAttributeNs(ODataConstants::XMLNS_NAMESPACE_PREFIX, self::APP_NAMESPACE_PREFIX, null, ODataConstants::APP_NAMESPACE);

        $this->_xmlWriter->startElement(ODataConstants::ATOM_PUBLISHING_WORKSPACE_ELEMNT_NAME);
        $this->_xmlWriter->startElementNs(self::ATOM_NAMESPACE_PREFIX, ODataConstants::ATOM_TITLE_ELELMET_NAME, null);
        $this->_xmlWriter->text(ODataConstants::ATOM_PUBLISHING_WORKSPACE_DEFAULT_VALUE);
        $this->_xmlWriter->endElement();
        foreach ($this->_metadataQueryproviderWrapper->getResourceSets() as $resourceSetWrapper) {
            //start collection node
            $this->_xmlWriter->startElement(ODataConstants::ATOM_PUBLISHING_COLLECTION_ELEMENT_NAME);
            $this->_xmlWriter->writeAttribute(ODataConstants::ATOM_HREF_ATTRIBUTE_NAME, $resourceSetWrapper->getName());
            //start title node
            $this->_xmlWriter->startElementNs(self::ATOM_NAMESPACE_PREFIX, ODataConstants::ATOM_TITLE_ELELMET_NAME, null);
            $this->_xmlWriter->text($resourceSetWrapper->getName());
            //end title node
            $this->_xmlWriter->endElement();
            //end collection node
            $this->_xmlWriter->endElement();
        }

        //End workspace and service nodes
        $this->_xmlWriter->endElement();
        $this->_xmlWriter->endElement();

        $serviceDocumentInAtom = $this->_xmlWriter->outputMemory(true);
        return $serviceDocumentInAtom;
    }
}
?>