<?php

declare(strict_types=1);

namespace POData\Configuration;

use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\Version;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceSet;

/**
 * Class ServiceConfiguration.
 * @package POData\Configuration
 */
class ServiceConfiguration implements IServiceConfiguration
{
    /**
     * Maximum number of segments to be expanded allowed in a request.
     */
    private $maxExpandCount;

    /**
     * Maximum number of segments in a single $expand path.
     */
    private $maxExpandDepth;

    /**
     * Maximum number of elements in each returned collection (top-level or expanded).
     */
    private $maxResultsPerCollection;

    /**
     * The provider for the web service.
     *
     * @var IMetadataProvider
     */
    private $provider;

    /**
     * Rights used for unspecified resource sets.
     *
     * @var EntitySetRights
     */
    private $defaultResourceSetRight;

    /**
     * Page size for unspecified resource sets.
     */
    private $defaultPageSize;

    /**
     * A mapping from entity set name to its right.
     *
     * @var EntitySetRights[]
     */
    private $resourceRights;

    /**
     * A mapping from entity sets to their page sizes.
     *
     * @var int[]
     */
    private $pageSizes = [];

    /**
     * Whether verbose errors should be returned by default.
     *
     * @var bool
     */
    private $useVerboseErrors;

    /**
     * Whether requests with the $count path segment or the $inlinecount
     * query options are accepted.
     */
    private $acceptCountRequest;

    /**
     * Whether projection requests ($select) should be accepted.
     */
    private $acceptProjectionRequest;

    /**
     * Maximum version of the response sent by server.
     *
     * @var ProtocolVersion
     */
    private $maxVersion;

    /**
     * Boolean value indicating whether to validate ETag header or not.
     */
    private $validateETagHeader;

    /**
     * Construct a new instance of ServiceConfiguration.
     *
     * @param IMetadataProvider $metadataProvider The metadata
     *                                            provider for the OData service
     */
    public function __construct(IMetadataProvider $metadataProvider)
    {
        $this->maxExpandCount          = PHP_INT_MAX;
        $this->maxExpandDepth          = PHP_INT_MAX;
        $this->maxResultsPerCollection = PHP_INT_MAX;
        $this->provider                = $metadataProvider;
        $this->defaultResourceSetRight = EntitySetRights::NONE();
        $this->defaultPageSize         = 0;
        $this->resourceRights          = [];
        $this->pageSizes               = [];
        $this->useVerboseErrors        = false;
        $this->acceptCountRequest      = false;
        $this->acceptProjectionRequest = false;

        $this->maxVersion = ProtocolVersion::V3(); //We default to the highest version

        $this->validateETagHeader = true;
    }

    /**
     * Gets maximum number of segments to be expanded allowed in a request.
     *
     * @return int
     */
    public function getMaxExpandCount()
    {
        return $this->maxExpandCount;
    }

    /**
     * Sets maximum number of segments to be expanded allowed in a request.
     *
     * @param int $maxExpandCount Maximum number of segments to be expanded
     */
    public function setMaxExpandCount($maxExpandCount)
    {
        $this->maxExpandCount = $this->checkIntegerNonNegativeParameter(
            $maxExpandCount,
            'setMaxExpandCount'
        );
    }

    /**
     * Gets the maximum number of segments in a single $expand path.
     *
     * @return int
     */
    public function getMaxExpandDepth(): int
    {
        return $this->maxExpandDepth;
    }

    /**
     * Sets the maximum number of segments in a single $expand path.
     *
     * @param int $maxExpandDepth Maximum number of segments in a single $expand path
     */
    public function setMaxExpandDepth($maxExpandDepth): void
    {
        $this->maxExpandDepth = $this->checkIntegerNonNegativeParameter(
            $maxExpandDepth,
            'setMaxExpandDepth'
        );
    }

    /**
     * Gets maximum number of elements in each returned collection
     * (top-level or expanded).
     *
     * @return int
     */
    public function getMaxResultsPerCollection()
    {
        return $this->maxResultsPerCollection;
    }

    /**
     * Sets maximum number of elements in each returned collection
     * (top-level or expanded).
     *
     * @param int $maxResultPerCollection Maximum number of elements
     *                                    in returned collection
     *
     * @throws InvalidOperationException
     */
    public function setMaxResultsPerCollection($maxResultPerCollection)
    {
        if ($this->isPageSizeDefined()) {
            throw new InvalidOperationException(
                Messages::configurationMaxResultAndPageSizeMutuallyExclusive()
            );
        }

        $this->maxResultsPerCollection = $this->checkIntegerNonNegativeParameter(
            $maxResultPerCollection,
            'setMaxResultsPerCollection'
        );
    }

    /**
     * Gets whether verbose errors should be used by default.
     *
     * @return bool
     */
    public function getUseVerboseErrors()
    {
        return $this->useVerboseErrors;
    }

    /**
     * Sets whether verbose errors should be used by default.
     *
     * @param bool $useVerboseError true to enable verbose error else false
     */
    public function setUseVerboseErrors($useVerboseError)
    {
        $this->useVerboseErrors = $useVerboseError;
    }

    /**
     * gets the access rights on the specified resource set.
     *
     * @param ResourceSet $resourceSet The resource set for which get the access
     *                                 rights
     *
     * @return EntitySetRights
     */
    public function getEntitySetAccessRule(ResourceSet $resourceSet): EntitySetRights
    {
        if (!array_key_exists($resourceSet->getName(), $this->resourceRights)) {
            return $this->defaultResourceSetRight;
        }

        return $this->resourceRights[$resourceSet->getName()];
    }

    /**
     * sets the access rights on the specified resource set.
     *
     * @param string          $name   Name of resource set to set; '*' to indicate all
     * @param EntitySetRights $rights Rights to be granted to this resource
     *
     * @throws \InvalidArgumentException when the entity set rights are not known or the resource set is not known
     */
    public function setEntitySetAccessRule(string $name, EntitySetRights $rights): void
    {
        if ($rights->getValue() < EntitySetRights::NONE || $rights->getValue() > EntitySetRights::ALL) {
            $msg = Messages::configurationRightsAreNotInRange('$rights', 'setEntitySetAccessRule');
            throw new \InvalidArgumentException($msg);
        }

        if (strcmp($name, '*') === 0) {
            $this->defaultResourceSetRight = $rights;
        } else {
            if (!$this->provider->resolveResourceSet($name)) {
                throw new \InvalidArgumentException(
                    Messages::configurationResourceSetNameNotFound($name)
                );
            }

            $this->resourceRights[$name] = $rights;
        }
    }

    /**
     * Gets the maximum page size for an entity set resource.
     *
     * @param ResourceSet $resourceSet Entity set for which to get the page size
     *
     * @return int
     */
    public function getEntitySetPageSize(ResourceSet $resourceSet)
    {
        if (!array_key_exists($resourceSet->getName(), $this->pageSizes)) {
            return $this->defaultPageSize;
        }

        return $this->pageSizes[$resourceSet->getName()];
    }

    /**
     * Sets the maximum page size for an entity set resource.
     *
     * @param string $name     Name of entity set resource for which to set the page size
     * @param int    $pageSize Page size for the entity set resource specified in name
     *
     * @throws InvalidOperationException
     * @throws \InvalidArgumentException
     */
    public function setEntitySetPageSize($name, $pageSize)
    {
        $checkPageSize = $this->checkIntegerNonNegativeParameter(
            $pageSize,
            'setEntitySetPageSize'
        );

        if ($this->maxResultsPerCollection != PHP_INT_MAX) {
            throw new InvalidOperationException(
                Messages::configurationMaxResultAndPageSizeMutuallyExclusive()
            );
        }

        if ($checkPageSize == PHP_INT_MAX) {
            $checkPageSize = 0;
        }

        if (strcmp($name, '*') === 0) {
            $this->defaultPageSize = $checkPageSize;
        } else {
            if (!$this->provider->resolveResourceSet($name)) {
                throw new \InvalidArgumentException(
                    Messages::configurationResourceSetNameNotFound($name)
                );
            }
            $this->pageSizes[$name] = $checkPageSize;
        }
    }

    /**
     * Gets whether requests with the $count path segment or the $inlinecount query
     * options are accepted.
     *
     * @return bool
     */
    public function getAcceptCountRequests()
    {
        return $this->acceptCountRequest;
    }

    /**
     * Sets whether requests with the $count path segment or the $inlinecount
     * query options are accepted.
     *
     * @param bool $acceptCountRequest true to accept count request,
     *                                 false to not
     */
    public function setAcceptCountRequests($acceptCountRequest)
    {
        $this->acceptCountRequest = $acceptCountRequest;
    }

    /**
     * Gets whether projection requests ($select) should be accepted.
     *
     * @return bool
     */
    public function getAcceptProjectionRequests()
    {
        return $this->acceptProjectionRequest;
    }

    /**
     * Sets whether projection requests ($select) should be accepted.
     *
     * @param bool $acceptProjectionRequest true to accept projection
     *                                      request, false to not
     */
    public function setAcceptProjectionRequests($acceptProjectionRequest)
    {
        $this->acceptProjectionRequest = $acceptProjectionRequest;
    }

    /**
     * Gets Maximum version of the response sent by server.
     *
     * @return Version
     */
    public function getMaxDataServiceVersion()
    {
        switch ($this->maxVersion) {
            case ProtocolVersion::V1():
                return new Version(1, 0);

            case ProtocolVersion::V2():
                return new Version(2, 0);

            case ProtocolVersion::V3():
            default:
                return new Version(3, 0);
        }
    }

    /**
     * Sets Maximum version of the response sent by server.
     *
     * @param ProtocolVersion $version The version to set
     */
    public function setMaxDataServiceVersion(ProtocolVersion $version)
    {
        $this->maxVersion = $version;
    }

    /**
     * Specify whether to validate the ETag or not.
     *
     * @param bool $validate True if ETag needs to validated, false otherwise
     */
    public function setValidateETagHeader($validate)
    {
        $this->validateETagHeader = $validate;
    }

    /**
     * Gets whether to validate the ETag or not.
     *
     * @return bool True if ETag needs to validated, false
     *              if its not to be validated, Note that in case
     *              of false library will not write the ETag header
     *              in the response even though the requested resource
     *              support ETag
     */
    public function getValidateETagHeader()
    {
        return $this->validateETagHeader;
    }

    /**
     * Checks that the parameter to a function is numeric and is not negative.
     *
     * @param int    $value        The value of parameter to check
     * @param string $functionName The name of the function that receives above value
     *
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    private function checkIntegerNonNegativeParameter(int $value, string $functionName): int
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(
                Messages::commonArgumentShouldBeNonNegative($value, $functionName)
            );
        }

        return $value;
    }

    /**
     * Whether size of a page has been defined for any entity set.
     *
     * @return bool
     */
    private function isPageSizeDefined()
    {
        return count($this->pageSizes) > 0 || $this->defaultPageSize > 0;
    }
}
