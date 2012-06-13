<?php
/** 
 * Writer for service document in Atom format.
 *
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_ServiceDocument_Atom
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer\Writers\ServiceDocument\Atom;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
/** 
 * Service documenter class for atom
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_ServiceDocument_Atom
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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