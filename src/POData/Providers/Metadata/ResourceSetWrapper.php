<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\IServiceConfiguration;
use POData\Providers\ProvidersWrapper;

/**
 * Class ResourceSetWrapper.
 *
 * A wrapper class for a resource set and it's configuration (rights and page size)
 * described using ServiceConfiguration
 */
class ResourceSetWrapper extends ResourceSet
{
    /**
     * Reference to the wrapped resource set.
     *
     * @var ResourceSet
     */
    private $resourceSet;

    /**
     * Reference to the EntitySetRights describing configured access to
     * the wrapped resource set.
     *
     * @var EntitySetRights
     */
    private $resourceSetRights;

    /**
     * The configured page size of this resource set.
     *
     * @var int
     */
    private $resourceSetPageSize;

    /**
     * Constructs a new instance of ResourceSetWrapper.
     *
     * @param ResourceSet           $resourceSet   The resource set to wrap
     * @param IServiceConfiguration $configuration Configuration to take settings specific to wrapped resource set
     */
    public function __construct(ResourceSet $resourceSet, IServiceConfiguration $configuration)
    {
        $this->resourceSet         = $resourceSet;
        $this->resourceSetRights   = $configuration->getEntitySetAccessRule($resourceSet);
        $this->resourceSetPageSize = $configuration->getEntitySetPageSize($resourceSet);
    }

    /**
     * Gets name of wrapped resource set.
     *
     * @return string Resource set name
     */
    public function getName(): string
    {
        return $this->resourceSet->getName();
    }

    /**
     * Gets reference to the resource type of wrapped resource set.
     *
     * @return ResourceEntityType
     */
    public function getResourceType(): ResourceEntityType
    {
        return $this->resourceSet->getResourceType();
    }

    /**
     * Gets reference to the wrapped resource set.
     *
     * @return ResourceSet
     */
    public function getResourceSet(): ResourceSet
    {
        return $this->resourceSet;
    }

    /**
     * Gets reference to the configured rights of the wrapped resource set.
     *
     * @return EntitySetRights
     */
    public function getResourceSetRights(): EntitySetRights
    {
        return $this->resourceSetRights;
    }

    /**
     * Gets configured page size for the wrapped resource set.
     *
     * @return int
     */
    public function getResourceSetPageSize(): int
    {
        return $this->resourceSetPageSize;
    }

    /**
     * Whether the resource set is visible to OData consumers.
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->resourceSetRights != EntitySetRights::NONE();
    }

    /**
     * Check wrapped resource set's resource type or any of the resource type derived
     * from the this resource type has named stream associated with it.
     *
     * @param ProvidersWrapper $provider
     *
     * @throws ODataException
     * @throws InvalidOperationException
     * @return bool
     */
    public function hasNamedStreams(ProvidersWrapper $provider): bool
    {
        $hasNamedStream = $this->resourceSet->getResourceType()->hasNamedStream();
        // This will check only the resource type associated with
        // the resource set, we need to check presence of named streams
        // in resource type(s) which is derived form this resource type also.
        if (!$hasNamedStream) {
            $derivedTypes = $provider->getDerivedTypes($this->resourceSet->getResourceType());
            foreach ($derivedTypes as $derivedType) {
                if ($derivedType->hasNamedStream()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check wrapped resource set's resource type or any of the resource type derived
     * from the this resource type has bag property associated with it.
     *
     * @param ProvidersWrapper $provider Metadata query provider wrapper
     *
     * @throws ODataException
     * @throws InvalidOperationException
     * @return bool
     */
    public function hasBagProperty(ProvidersWrapper $provider): bool
    {
        $arrayToDetectLoop = [];
        $hasBagProperty    = $this->resourceSet->getResourceType()->hasBagProperty($arrayToDetectLoop);
        unset($arrayToDetectLoop);
        // This will check only the resource type associated with
        // the resource set, we need to check presence of bag property
        // in resource type which is derived form this resource type also.
        if (true !== $hasBagProperty) {
            $derivedTypes = $provider->getDerivedTypes($this->resourceSet->getResourceType());
            foreach ($derivedTypes as $derivedType) {
                $arrayToDetectLoop = [];
                if ($derivedType->hasBagProperty($arrayToDetectLoop)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks whether this request has the reading rights.
     *
     * @param bool $singleResult Check for multiple result read if false else single result read
     *
     * @throws ODataException exception if read-access to this resource set is forbidden
     */
    public function checkResourceSetRightsForRead($singleResult): void
    {
        $this->checkResourceSetRights(
            $singleResult ?
                EntitySetRights::READ_SINGLE() : EntitySetRights::READ_MULTIPLE()
        );
    }

    /**
     * Checks whether this request has the specified rights.
     *
     * @param EntitySetRights $requiredRights The rights to check
     *
     * @throws ODataException exception if access to this resource set is forbidden
     */
    public function checkResourceSetRights(EntitySetRights $requiredRights): void
    {
        if (($this->resourceSetRights->getValue() & $requiredRights->getValue()) == 0) {
            throw ODataException::createForbiddenError();
        }
    }
}
