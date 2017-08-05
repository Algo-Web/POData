<?php

namespace POData\Configuration;

use POData\Common\InvalidOperationException;
use POData\Common\Version;
use POData\Providers\Metadata\ResourceSet;

interface IServiceConfiguration
{
    /**
     * Gets maximum number of segments to be expanded allowed in a request.
     *
     * @return int
     */
    public function getMaxExpandCount();

    /**
     * Sets maximum number of segments to be expanded allowed in a request.
     *
     * @param  int  $maxExpandCount Maximum number of segments to be expanded
     * @return void
     */
    public function setMaxExpandCount($maxExpandCount);

    /**
     * Gets the maximum number of segments in a single $expand path.
     *
     * @return int
     */
    public function getMaxExpandDepth();

    /**
     * Sets the maximum number of segments in a single $expand path.
     *
     * @param  int  $maxExpandDepth Maximum number of segments in a single $expand path
     * @return void
     */
    public function setMaxExpandDepth($maxExpandDepth);

    /**
     * Gets maximum number of elements in each returned collection
     * (top-level or expanded).
     *
     * @return int
     */
    public function getMaxResultsPerCollection();

    /**
     * Sets maximum number of elements in each returned collection
     * (top-level or expanded).
     *
     * @param  int  $maxResultPerCollection Maximum number of elements in returned collection
     * @return void
     */
    public function setMaxResultsPerCollection($maxResultPerCollection);

    /**
     * Gets whether verbose errors should be used by default.
     *
     * @return bool
     */
    public function getUseVerboseErrors();

    /**
     * Sets whether verbose errors should be used by default.
     *
     * @param  bool $useVerboseError true to enable verbose error else false
     * @return void
     */
    public function setUseVerboseErrors($useVerboseError);

    /**
     * gets the access rights on the specified resource set.
     *
     * @param ResourceSet $resourceSet The resource set for which get the access rights
     *
     * @return EntitySetRights
     */
    public function getEntitySetAccessRule(ResourceSet $resourceSet);

    /**
     * sets the access rights on the specified resource set.
     *
     * @param string          $name   Name of resource set to set; '*' to indicate all
     * @param EntitySetRights $rights Rights to be granted to this resource
     *
     * @throws \InvalidArgumentException when the entity set rights are not known or the resource set is not known
     * @return void
     */
    public function setEntitySetAccessRule($name, $rights);

    /**
     * Gets the maximum page size for an entity set resource.
     *
     * @param ResourceSet $resourceSet Entity set for which to get the page size
     *
     * @return int
     */
    public function getEntitySetPageSize(ResourceSet $resourceSet);

    /**
     * Sets the maximum page size for an entity set resource.
     *
     * @param string $name     Name of entity set resource for which to set
     *                         the page size
     * @param int    $pageSize Page size for the entity set resource that is
     *                         specified in name
     *
     * @throws InvalidOperationException
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setEntitySetPageSize($name, $pageSize);

    /**
     * Gets whether requests with the $count path segment or the $inlinecount query
     * options are accepted.
     *
     * @return bool
     */
    public function getAcceptCountRequests();

    /**
     * Sets whether requests with the $count path segment or the $inlinecount
     * query options are accepted.
     *
     * @param  bool $acceptCountRequest true to accept count request, false to not
     * @return void
     */
    public function setAcceptCountRequests($acceptCountRequest);

    /**
     * Gets whether projection requests ($select) should be accepted.
     *
     * @return bool
     */
    public function getAcceptProjectionRequests();

    /**
     * Sets whether projection requests ($select) should be accepted.
     *
     * @param  bool $acceptProjectionRequest true to accept projection request, false to not
     * @return void
     */
    public function setAcceptProjectionRequests($acceptProjectionRequest);

    /**
     * Gets Maximum version of the response sent by server.
     *
     * @return Version
     */
    public function getMaxDataServiceVersion();

    /**
     * Sets Maximum version of the response sent by server.
     *
     * @param  ProtocolVersion $version The version to set
     * @return void
     */
    public function setMaxDataServiceVersion(ProtocolVersion $version);

    /**
     * Specify whether to validate the ETag or not.
     *
     * @param  bool $validate True if ETag needs to validated, false otherwise
     * @return void
     */
    public function setValidateETagHeader($validate);

    /**
     * Gets whether to validate the ETag or not.
     *
     * @return bool True if ETag needs to validated, false
     *              if its not to be validated, Note that in case
     *              of false library will not write the ETag header
     *              in the response even though the requested resource
     *              support ETag
     */
    public function getValidateETagHeader();
}
