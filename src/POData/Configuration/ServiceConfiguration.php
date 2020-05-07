<?php

declare(strict_types=1);

namespace POData\Configuration;

use InvalidArgumentException;
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
     * @var string value to be used as line terminator
     */
    private $eol;

    /**
     * @var bool value to indicate if output should be printed human readable
     */
    private $prettyPrint;

    /**
     * Construct a new instance of ServiceConfiguration.
     *
     * @param IMetadataProvider|null $metadataProvider The metadata
     *                                                 provider for the OData service
     */
    public function __construct(?IMetadataProvider $metadataProvider)
    {
        $this->maxExpandCount = PHP_INT_MAX;
        $this->maxExpandDepth = PHP_INT_MAX;
        $this->maxResultsPerCollection = PHP_INT_MAX;
        $this->provider = $metadataProvider;
        $this->defaultResourceSetRight = EntitySetRights::NONE();
        $this->defaultPageSize = 0;
        $this->resourceRights = [];
        $this->pageSizes = [];
        $this->useVerboseErrors = false;
        $this->acceptCountRequest = false;
        $this->acceptProjectionRequest = false;

        $this->maxVersion = ProtocolVersion::V3(); //We default to the highest version

        $this->validateETagHeader = true;
        // basically display errors has a development value of on and a production value of off. so if not specified
        // use that
        $this->setPrettyOutput(in_array(strtolower(ini_get('display_errors')), array('1', 'on', 'true')));
        $this->setLineEndings(PHP_EOL);
    }

    /**
     * Sets if output should be well formatted for human review.
     *
     * @param bool $on True if output should be well formatted
     */
    public function setPrettyOutput(bool $on): void
    {
        $this->prettyPrint = $on;
    }

    /**
     * Sets the characters that represent line endings.
     *
     * @param string $eol the characters that should be used for line endings
     */
    public function setLineEndings(string $eol): void
    {
        $this->eol = $eol;
    }

    /**
     * Gets maximum number of segments to be expanded allowed in a request.
     *
     * @return int
     */
    public function getMaxExpandCount(): int
    {
        return $this->maxExpandCount;
    }

    /**
     * Sets maximum number of segments to be expanded allowed in a request.
     *
     * @param int $maxExpandCount Maximum number of segments to be expanded
     */
    public function setMaxExpandCount(int $maxExpandCount): void
    {
        $this->maxExpandCount = $this->checkIntegerNonNegativeParameter(
            $maxExpandCount,
            'setMaxExpandCount'
        );
    }

    /**
     * Checks that the parameter to a function is numeric and is not negative.
     *
     * @param int $value The value of parameter to check
     * @param string $functionName The name of the function that receives above value
     *
     * @return int
     * @throws InvalidArgumentException
     *
     */
    private function checkIntegerNonNegativeParameter(int $value, string $functionName): int
    {
        if ($value < 0) {
            throw new InvalidArgumentException(
                Messages::commonArgumentShouldBeNonNegative($value, $functionName)
            );
        }

        return $value;
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
    public function setMaxExpandDepth(int $maxExpandDepth): void
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
    public function getMaxResultsPerCollection(): int
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
    public function setMaxResultsPerCollection(int $maxResultPerCollection): void
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
     * Whether size of a page has been defined for any entity set.
     *
     * @return bool
     */
    private function isPageSizeDefined()
    {
        return count($this->pageSizes) > 0 || $this->defaultPageSize > 0;
    }

    /**
     * Gets whether verbose errors should be used by default.
     *
     * @return bool
     */
    public function getUseVerboseErrors(): bool
    {
        return $this->useVerboseErrors;
    }

    /**
     * Sets whether verbose errors should be used by default.
     *
     * @param bool $useVerboseError true to enable verbose error else false
     */
    public function setUseVerboseErrors(bool $useVerboseError): void
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
     * @param string $name Name of resource set to set; '*' to indicate all
     * @param EntitySetRights $rights Rights to be granted to this resource
     *
     * @throws InvalidArgumentException when the entity set rights are not known or the resource set is not known
     */
    public function setEntitySetAccessRule(string $name, EntitySetRights $rights): void
    {
        if ($rights->getValue() < EntitySetRights::NONE || $rights->getValue() > EntitySetRights::ALL) {
            $msg = Messages::configurationRightsAreNotInRange('$rights', 'setEntitySetAccessRule');
            throw new InvalidArgumentException($msg);
        }

        if (strcmp($name, '*') === 0) {
            $this->defaultResourceSetRight = $rights;
        } else {
            if (!$this->provider->resolveResourceSet($name)) {
                throw new InvalidArgumentException(
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
    public function getEntitySetPageSize(ResourceSet $resourceSet): int
    {
        if (!array_key_exists($resourceSet->getName(), $this->pageSizes)) {
            return $this->defaultPageSize ?? 0; // TODO: defaultPageSize should never be null. it is inisalized in constructor. why is this requied?
        }

        return $this->pageSizes[$resourceSet->getName()];
    }

    /**
     * Sets the maximum page size for an entity set resource.
     *
     * @param string $name Name of entity set resource for which to set the page size
     * @param int $pageSize Page size for the entity set resource specified in name
     *
     * @throws InvalidOperationException
     * @throws InvalidArgumentException
     */
    public function setEntitySetPageSize(string $name, int $pageSize): void
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
                throw new InvalidArgumentException(
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
    public function getAcceptCountRequests(): bool
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
    public function setAcceptCountRequests(bool $acceptCountRequest): void
    {
        $this->acceptCountRequest = $acceptCountRequest;
    }

    /**
     * Gets whether projection requests ($select) should be accepted.
     *
     * @return bool
     */
    public function getAcceptProjectionRequests(): bool
    {
        return $this->acceptProjectionRequest;
    }

    /**
     * Sets whether projection requests ($select) should be accepted.
     *
     * @param bool $acceptProjectionRequest true to accept projection
     *                                      request, false to not
     */
    public function setAcceptProjectionRequests(bool $acceptProjectionRequest): void
    {
        $this->acceptProjectionRequest = $acceptProjectionRequest;
    }

    /**
     * Gets Maximum version of the response sent by server.
     *
     * @return Version
     */
    public function getMaxDataServiceVersion(): Version
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
    public function setMaxDataServiceVersion(ProtocolVersion $version): void
    {
        $this->maxVersion = $version;
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
    public function getValidateETagHeader(): bool
    {
        return $this->validateETagHeader;
    }

    /**
     * Specify whether to validate the ETag or not.
     *
     * @param bool $validate True if ETag needs to validated, false otherwise
     */
    public function setValidateETagHeader(bool $validate): void
    {
        $this->validateETagHeader = $validate;
    }

    /**
     * Gets the value to be used for line endings.
     *
     * @return string the value to append at the end of lines
     */
    public function getLineEndings(): string
    {
        return $this->eol;
    }

    /**
     * Gets whether to format the output as human readable or single line.
     *
     * @return bool true if output should be formatted for human readability
     */
    public function getPrettyOutput(): bool
    {
        return $this->prettyPrint;
    }
}
