<?php
/** 
 * Writer for service document in JSON format.
 *
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_ServiceDocument_Json
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Writers\ServiceDocument\Json;
use ODataProducer\Writers\Json\JsonWriter;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
/** 
 * Service documenter class for json
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_ServiceDocument_Json
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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
?>