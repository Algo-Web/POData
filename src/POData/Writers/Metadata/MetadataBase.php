<?php

namespace POData\Writers\Metadata;

use POData\Providers\Metadata\ResourceType;
use POData\Providers\ProvidersWrapper;

/**
 * Class MetadataBase
 * @package POData\Writers\Metadata
 */
class MetadataBase
{
    /**
     * Holds reference to the wrapper over service metadata and 
     * query provider implementations.
     * 
     * @var ProvidersWrapper
     */
    protected $providersWrapper;

    /**
     * Constructs a new instance of MetadataBase
     * 
     * @param ProvidersWrapper $provider Reference to service metadata
     *                                               and query provider wrapper
     */
    public function __construct(ProvidersWrapper $provider)
    {
        $this->providersWrapper = $provider;
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
            $resourceTypeNamespace = $this->providersWrapper->getContainerNamespace();
        }

        return $resourceTypeNamespace;
    }
}