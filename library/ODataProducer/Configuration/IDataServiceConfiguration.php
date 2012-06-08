<?php
/** 
 * An interface for modifying the configuration of an odata service
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Configuration
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Configuration;
use ODataProducer\Providers\Metadata\ResourceSet;
/**
 * Interface for modifying the configuration of an odata service
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Configuration
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
interface IDataServiceConfiguration
{
    /**
     * Gets maximum number of segments to be expanded allowed in a request
     * 
     * @return int
     */
    function getMaxExpandCount();

    /**     
     * Sets maximum number of segments to be expanded allowed in a request
     * 
     * @param int $maxExpandCount Maximum number of segments to be expanded
     * 
     * @return void
     */
    function setMaxExpandCount($maxExpandCount);

    /**
     * Gets the maximum number of segments in a single $expand path
     * 
     * @return int
     */
    function getMaxExpandDepth();

    /**
     * Sets the maximum number of segments in a single $expand path
     * 
     * @param int $maxExpandDepth Maximum number of segments in a single $expand path
     * 
     * @return void
     */
    function setMaxExpandDepth($maxExpandDepth);

    /**
     * Gets maximum number of elements in each returned collection 
     * (top-level or expanded)
     * 
     * @return int
     * 
     * @return void
     */
    function getMaxResultsPerCollection();

    /**
     * Sets maximum number of elements in each returned collection 
     * (top-level or expanded)
     * 
     * @param int $maxResultPerCollection Maximum number of elements 
     *                                    in returned collection
     * 
     * @return void
     */
    function setMaxResultsPerCollection($maxResultPerCollection);

    /**
     * Gets whether verbose errors should be used by default
     * 
     * @return boolean
     */
    function getUseVerboseErrors();
    
    /**
     * Sets whether verbose errors should be used by default
     * 
     * @param boolean $useVerboseError true to enable verbose error else false
     * 
     * @return void
     */
    function setUseVerboseErrors($useVerboseError);

    /**
     * gets the access rights on the specified resource set
     * 
     * @param ResourceSet $resourceSet The resource set for which get the access
     *                                 rights
     * 
     * @return EntitySetRights
     */
    function getEntitySetAccessRule(ResourceSet $resourceSet);

    /**
     * sets the access rights on the specified resource set
     *
     * @param string          $name   Name of resource set to set; '*' 
     *                                to indicate all 
     * @param EntitySetRights $rights Rights to be granted to this resource
     * 
     * @return void
     */
     function setEntitySetAccessRule($name, $rights);

    /**
     * Gets the maximum page size for an entity set resource
     * 
     * @param ResourceSet $resourceSet Entity set for which to get the page size
     * 
     * @return int
     */
     function getEntitySetPageSize(ResourceSet $resourceSet);

     /**
      * Sets the maximum page size for an entity set resource.
      * 
      * @param string $name     Name of entity set resource for which to set the 
      *                         page size.
      * @param int    $pageSize Page size for the entity set resource that is 
      *                         specified in name.
      * 
      * @return void
      */     
     function setEntitySetPageSize($name, $pageSize);

     /**
      * Gets whether requests with the $count path segment or the $inlinecount query 
      * options are accepted
      * 
      * @return boolean       
      */
     function getAcceptCountRequests();
     
     /**
      * Sets whether requests with the $count path segment or the $inlinecount query 
      * options are accepted
      * 
      * @param boolean $acceptCountRequest true to accept count request, false to not
      * 
      * @return void
      */
     function setAcceptCountRequests($acceptCountRequest);

     /**
      * Gets whether projection requests ($select) should be accepted
      * 
      * @return boolean       
      */
     function getAcceptProjectionRequests();
     
     /**
      * Sets whether projection requests ($select) should be accepted
      * 
      * @param boolean $acceptProjectionRequest true to accept projection request, 
      *                                         false to not
      * 
      * @return void
      */
     function setAcceptProjectionRequests($acceptProjectionRequest);

     /**
      * Gets maximum version of the response sent by server
      * 
      * @return DataServiceProtocolVersion
      */
     function getMaxDataServiceVersion();

    /**
     * Gets Maxumum version of the response sent by server.
     * 
     * @return Version
     */
     public function getMaxDataServiceVersionObject();

     /**
      * Sets maximum version of the response sent by server
      * 
      * @param DataServiceProtocolVersion $version The maximum version
      * 
      * @return void
      */
     function setMaxDataServiceVersion($version);

     /**
      * Specify whether to validate the ETag or not
      *
      * @param boolean $validate True if ETag needs to validated, false 
      *                          otherwise.
      *
      * @return void
      */
     function setValidateETagHeader($validate);

     /**
      * Gets whether to validate the ETag or not
      *
      * @return boolean True if ETag needs to validated, false 
      *                 if its not to be validated, Note that in case
      *                 of false library will not write the ETag header
      *                 in the response even though the requested resource
      *                 support ETag
      */
     function getValidateETagHeader();
}
?>