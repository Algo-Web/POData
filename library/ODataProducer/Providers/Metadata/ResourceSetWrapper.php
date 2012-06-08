<?php
/**
 * A wrapper class for a resource set and it's configuration (rights and page size)
 * described using DataServiceConfiguration
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Providers\Metadata;
use ODataProducer\Common\ODataException;
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\Configuration\EntitySetRights;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
/**
 * A wrapper class for a resource set.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ResourceSetWrapper
{
    /**
     * Reference to the wrapped resource set
     * 
     * @var ResourceSet
     */
    private $_resourceSet;

    /**
     * Reference to the EntitySetRights describing configured access to 
     * the wrapped resource set
     * 
     * @var EntitySetRights
     */
    private $_resourceSetRights;

    /**
     * The configured page size of this resource set
     * 
     * @var int
     */
    private $_resourceSetPageSize;

    /**
     * Constructs a new instance of ResourceSetWrapper
     * 
     * @param ResourceSet              $resourceSet   The resource set to wrap
     * @param DataServiceConfiguration $configuration Configuration to take 
     *                                                settings specific to wrapped 
     *                                                resource set
     */
    public function __construct(ResourceSet $resourceSet, 
        DataServiceConfiguration $configuration
    ) {
        $this->_resourceSet = $resourceSet;
        $this->_resourceSetRights 
            = $configuration->getEntitySetAccessRule($resourceSet);
        $this->_resourceSetPageSize 
            = $configuration->getEntitySetPageSize($resourceSet);
    }

    /**
     * Gets name of wrapped resource set
     * 
     * @return string Resource set name
     */
    public function getName()
    {
        return $this->_resourceSet->getName();
    }

    /**
     * Gets reference to the wrapped resource set
     * 
     * @return ResourceSet
     */
    public function getResourceSet()
    {
        return $this->_resourceSet;
    }

    /**
     * Gets reference to the resource type of wrapped resource set
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_resourceSet->getResourceType();
    }

    /**
     * Gets reference to the configured rights of the wrapped resource set
     * 
     * @return EntitySetRights
     */
    public function getResourceSetRights()
    {
        return $this->_resourceSetRights;
    }

    /**
     * Gets configured page size for the wrapped resource set 
     * 
     * @return int
     */
    public function getResourceSetPageSize()
    {
        return $this->_resourceSetPageSize;
    }

    /**
     * Whether the resource set is visible to OData consumers
     * 
     * @return boolean
     */
    public function isVisible()
    {
        return $this->_resourceSetRights != EntitySetRights::NONE;
    }

    /**
     * Check wrapped resource set's resource type or any of the resource type derived
     * from the this resource type has named stream associated with it.
     * 
     * @param MetadataQueryProviderWrapper $provider Metadata query provider wrapper
     * 
     * @return boolean
     */
    public function hasNamedStreams(MetadataQueryProviderWrapper $provider)
    {
        $hasNamedStream = $this->_resourceSet->getResourceType()->hasNamedStream();
        // This will check only the resource type associated with 
        // the resource set, we need to check presence of named streams 
        // in resource type(s) which is derived form this resource type also.
        if (!$hasNamedStream) {
            $derivedTypes
                = $provider->getDerivedTypes(
                    $this->_resourceSet->getResourceType()
                );
            if (!is_null($derivedTypes)) {
                foreach ($derivedTypes as $derivedType) {
                    if ($derivedType->hasNamedStream()) {
                        $hasNamedStream = true;
                        break;
                    }
                }
            }
        }

        return $hasNamedStream;
    }

    /**
     * Check wrapped resource set's resource type or any of the resource type derived
     * from the this resource type has bag property associated with it.
     * 
     * @param MetadataQueryProviderWrapper $provider Metadata query provider wrapper
     * 
     * @return boolean
     */
    public function hasBagProperty(MetadataQueryProviderWrapper $provider)
    {
        $arrayToDetectLoop = array();
        $hasBagProperty = $this->_resourceSet->getResourceType()->hasBagProperty($arrayToDetectLoop);
        unset($arrayToDetectLoop);
        // This will check only the resource type associated with 
        // the resource set, we need to check presence of bag property 
        // in resource type which is derived form this resource type also.
        if (!$hasBagProperty) {
            $derivedTypes 
                = $provider->getDerivedTypes(
                    $this->_resourceSet->getResourceType()
                );
            if (!is_null($derivedTypes)) {
                foreach ($derivedTypes as $derivedType) {
                    $arrayToDetectLoop = array();
                    if ($derivedType->hasBagProperty($arrayToDetectLoop)) {
                        $hasBagProperty = true;
                        break;
                    }
                }
            }
        }

        return $hasBagProperty;
    }
    
    /**
     * Checks whether this request has the specified rights
     * 
     * @param EntitySetRights $requiredRights The rights to check
     * 
     * @return void
     * 
     * @throws ODataException exception if access to this resource set is forbidden
     */
    public function checkResourceSetRights($requiredRights)
    {
        if (($this->_resourceSetRights & $requiredRights) == 0) {
            ODataException::createForbiddenError();
        }
    }

    /**
     * Checks whether this request has the reading rights
     * 
     * @param boolean $singleResult Check for multiple result read if false else
     * single result read
     * 
     * @return void
     * 
     * @throws ODataException exception if read-access to this resource set is
     *                        forbidden
     */
    public function checkResourceSetRightsForRead($singleResult)
    {
        $this->checkResourceSetRights(
            $singleResult ? 
            EntitySetRights::READ_SINGLE : EntitySetRights::READ_MULTIPLE
        );
    }
}
?>