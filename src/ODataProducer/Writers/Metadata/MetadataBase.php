<?php

namespace ODataProducer\Writers\Metadata;

use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\MetadataQueryProviderWrapper;

/**
 * Class MetadataBase
 * @package ODataProducer\Writers\Metadata
 */
class MetadataBase
{
    /**
     * Holds reference to the wrapper over service metadata and 
     * query provider implementations.
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