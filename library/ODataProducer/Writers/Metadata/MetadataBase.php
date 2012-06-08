<?php
/** 
 * Base class for MetadataAssociationTypeSet and MetadataResourceTypeSet classes.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_Metadata
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Writers\Metadata;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
/** 
 * Base class for MetadataAssociationTypeSet and MetadataResourceTypeSet classes.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_Metadata
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class MetadataBase
{
    /**
     * Holds reference to the wrapper over service metadata and 
     * query provider implemenations.
     * 
     * @var MetadataQueryProviderWrapper
     */
    protected $metadataQueryproviderWrapper;

    /**
     * Constructs a new instance of MetadataBase
     * 
     * @param MetadataQueryProviderWrapper $provider Reference to service metadata
     *                                               and query provider wrapper
     */
    public function __construct(MetadataQueryProviderWrapper $provider)
    {
        $this->metadataQueryproviderWrapper = $provider;
    }

    /**
     * Gets the namespace of the given resource type, 
     * if it is null, then default to the container namespace. 
     * 
     * @param ResourceType $resourceType The resource type
     * 
     * @return string The namespace of the resource type.
     */
    protected function getResourceTypeNamespace(ResourceType $resourceType)
    {
        $resourceTypeNamespace = $resourceType->getNamespace();
        if (empty($resourceTypeNamespace)) {
            $resourceTypeNamespace = $this->metadataQueryproviderWrapper->getContainerNamespace();
        }

        return $resourceTypeNamespace;
    }
}
?>