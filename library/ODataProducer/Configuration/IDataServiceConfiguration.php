<?php
/** 
 * An interface for modifying the configuration of an odata service
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Configuration
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
namespace ODataProducer\Configuration;
use ODataProducer\Providers\Metadata\ResourceSet;
/**
 * Interface for modifying the configuration of an odata service
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Configuration
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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