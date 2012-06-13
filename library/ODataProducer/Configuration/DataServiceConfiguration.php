<?php
/** 
 * Provides an implementation of the IDataServiceConfiguration interface
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
use ODataProducer\Providers\Metadata\IDataServiceMetadataProvider;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Common\Messages;
use ODataProducer\Common\Version;
use ODataProducer\Common\NotImplementedException;
use ODataProducer\Common\InvalidOperationException;
/**
 * implementation of the IDataServiceConfiguration.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Configuration
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class DataServiceConfiguration implements IDataServiceConfiguration
{
    /**
     * Maximum number of segments to be expanded allowed in a request     
     */
    private $_maxExpandCount;

    /**
     * Maximum number of segments in a single $expand path
     */
    private $_maxExpandDepth;

    /**
     * Maximum number of elements in each returned collection (top-level or expanded)
     */
    private $_maxResultsPerCollection;

    /**
     * The provider for the web service
     * 
     * @var IDataServiceMetadataProvider
     */
    private $_provider;

    /**
     * Rights used for unspecified resource sets
     * 
     * @var EntitySetRights
     */
    private $_defaultResourceSetRight;

    /**
     * Page size for unspecified resource sets
     */
    private $_defaultPageSize;

    /**
     * A mapping from entity set name to its right
     * 
     * @var array<string, EntitySetRights>
     */
    private $_resourceRights;

    /**
     * A mapping from entity sets to their page sizes
     * 
     * @var array<string, int> 
     */
    private $_pageSizes;

    /**
     * Whether verbose errors should be returned by default
     * 
     * @var boolean
     */
    private $_useVerboseErrors;

    /**
     * Whether requests with the $count path segment or the $inlinecount 
     * query options are accepted
     */
    private $_acceptCountRequest;

    /**
     * Whether projection requests ($select) should be accepted
     */
    private $_acceptProjectionRequest;

    /**
     * Maxumum version of the response sent by server
     */
    private $_maxProtocolVersion;

    /**
     * Boolean value indicating whether to validate ETag header or not
     */
    private $_validateETagHeader;
    
    /**
     * Construct a new instance of DataServiceConfiguration
     * 
     * @param IDataServiceMetadataProvider $metadataProvider The metadata 
     * provider for the OData service
     */
    public function __construct(IDataServiceMetadataProvider $metadataProvider)
    {
        $this->_maxExpandCount = PHP_INT_MAX;
        $this->_maxExpandDepth = PHP_INT_MAX;
        $this->_maxResultsPerCollection = PHP_INT_MAX;
        $this->_provider = $metadataProvider;
        $this->_defaultResourceSetRight = EntitySetRights::NONE;
        $this->_defaultPageSize = 0;
        $this->_resourceRights = array();
        $this->_pageSizes = array();
        $this->_useVerboseErrors = false;
        $this->_acceptCountRequest = false;
        $this->_acceptProjectionRequest = false;
        $this->_maxProtocolVersion = DataServiceProtocolVersion::V1;
        $this->_validateETagHeader = true;
    }
    
    /**
     * Gets maximum number of segments to be expanded allowed in a request
     * 
     * @return int
     */
    public function getMaxExpandCount()
    {
        return $this->_maxExpandCount;
    }

    /**
     * Sets maximum number of segments to be expanded allowed in a request
     * 
     * @param int $maxExpandCount Maximum number of segments to be expanded
     * 
     * @return void
     */
    public function setMaxExpandCount($maxExpandCount)
    {
        $this->_maxExpandCount 
            = $this->_checkIntegerNonNegativeParameter(
                $maxExpandCount, 'setMaxExpandCount'
            );
    }

    /**
     * Gets the maximum number of segments in a single $expand path
     * 
     * @return int
     */
    public function getMaxExpandDepth()
    {
        return $this->_maxExpandDepth;
    }

    /**
     * Sets the maximum number of segments in a single $expand path
     * 
     * @param int $maxExpandDepth Maximum number of segments in a single $expand path
     * 
     * @return void
     */
    public function setMaxExpandDepth($maxExpandDepth)
    {
        $this->_maxExpandDepth 
            = $this->_checkIntegerNonNegativeParameter(
                $maxExpandDepth, 
                'setMaxExpandDepth'
            );
    }

    /**
     * Gets maximum number of elements in each returned collection 
     * (top-level or expanded)
     * 
     * @return int
     * 
     * @return void
     */
    public function getMaxResultsPerCollection()
    {
        return $this->_maxResultsPerCollection;
    }

    /**
     * Sets maximum number of elements in each returned collection 
     * (top-level or expanded)
     * 
     * @param int $maxResultPerCollection Maximum number of elements
     *                                    in returned collection
     * 
     * @return void
     */
    public function setMaxResultsPerCollection($maxResultPerCollection)
    {
        if ($this->_isPageSizeDefined()) {
            throw new InvalidOperationException(
                Messages::dataServiceConfigurationMaxResultAndPageSizeMuctuallyExclusive()
            );
        }
        
        $this->_maxResultsPerCollection 
            = $this->_checkIntegerNonNegativeParameter(
                $maxResultPerCollection, 'setMaxResultsPerCollection'
            );
    }

    /**
     * Gets whether verbose errors should be used by default
     * 
     * @return boolean
     */
    public function getUseVerboseErrors()
    {
        return $this->_useVerboseErrors;
    }

    /**
     * Sets whether verbose errors should be used by default
     * 
     * @param boolean $useVerboseError true to enable verbose error else false
     * 
     * @return void
     */
    public function setUseVerboseErrors($useVerboseError)
    {
        $this->_useVerboseErrors = $useVerboseError;
    }

    /**
     * gets the access rights on the specified resource set
     * 
     * @param ResourceSet $resourceSet The resource set for which get the access 
     *                                 rights
     * 
     * @return EntitySetRights
     */
    public function getEntitySetAccessRule(ResourceSet $resourceSet)
    {
        if (!array_key_exists($resourceSet->getName(), $this->_resourceRights)) {
            return $this->_defaultResourceSetRight;
        }
        
        return $this->_resourceRights[$resourceSet->getName()];
    }

    /**
     * sets the access rights on the specified resource set
     *
     * @param string          $name   Name of resource set to set; '*' 
     *                                to indicate all 
     * @param EntitySetRights $rights Rights to be granted to this resource
     * 
     * @return void
     */
    public function setEntitySetAccessRule($name, $rights)
    {
        if ($rights < EntitySetRights::NONE || $rights > EntitySetRights::ALL) {
            throw new \InvalidArgumentException(
                Messages::dataServiceConfigurationRightsAreNotInRange(
                    '$rights', 'setEntitySetAccessRule'
                )
            );
        }
        
        if (strcmp($name, '*') === 0) {
            $this->_defaultResourceSetRight = $rights;
        } else {            
            if (!$this->_provider->resolveResourceSet($name)) {
                throw new \InvalidArgumentException(
                    Messages::dataServiceConfigurationResourceSetNameNotFound($name)
                );
            }
            
            $this->_resourceRights[$name] = $rights;
        }
    }

    /**
     * Gets the maximum page size for an entity set resource
     * 
     * @param ResourceSet $resourceSet Entity set for which to get the page size
     * 
     * @return int
     */
    public function getEntitySetPageSize(ResourceSet $resourceSet)
    {
        if (!array_key_exists($resourceSet->getName(), $this->_pageSizes)) {
            return $this->_defaultPageSize;
        }
        
        return $this->_pageSizes[$resourceSet->getName()];
    }

    /**
     * Sets the maximum page size for an entity set resource.
     * 
     * @param string $name     Name of entity set resource for which to set 
     *                         the page size.
     * @param int    $pageSize Page size for the entity set resource that is
     *                         specified in name.
     * 
     * @throws InvalidOperationException
     * @throws InvalidArgumentException
     * 
     * @return void
     */     
    public function setEntitySetPageSize($name, $pageSize)
    {
        $pageSize 
            = $this->_checkIntegerNonNegativeParameter(
                $pageSize, 
                'setEntitySetPageSize'
            );
        if ($this->_maxResultsPerCollection != PHP_INT_MAX) {
            throw new InvalidOperationException(
                Messages::dataServiceConfigurationMaxResultAndPageSizeMuctuallyExclusive()
            );
        }
        
        if ($pageSize == PHP_INT_MAX) {
            $pageSize = 0;
        }
        
        if (strcmp($name, '*') === 0) {
            $this->_defaultPageSize = $pageSize;
        } else {            
            if (!$this->_provider->resolveResourceSet($name)) {
                throw new \InvalidArgumentException(
                    Messages::dataServiceConfigurationResourceSetNameNotFound($name)
                );
            }
            
            $this->_pageSizes[$name] = $pageSize;
        }
    }

    /**
     * Gets whether requests with the $count path segment or the $inlinecount query 
     * options are accepted
     * 
     * @return boolean
     */
    public function getAcceptCountRequests()
    {
        return $this->_acceptCountRequest;
    }
     
    /**
     * Sets whether requests with the $count path segment or the $inlinecount 
     * query options are accepted
     * 
     * @param boolean $acceptCountRequest true to accept count request, 
     *                                    false to not
     * 
     * @return void
     */
    public function setAcceptCountRequests($acceptCountRequest)
    {   
        $this->_acceptCountRequest = $acceptCountRequest;
    }

    /**
     * Gets whether projection requests ($select) should be accepted
     * 
     * @return boolean       
     */
    public function getAcceptProjectionRequests()
    {
        return $this->_acceptProjectionRequest;
    }
 
    /**
     * Sets whether projection requests ($select) should be accepted
     * 
     * @param boolean $acceptProjectionRequest true to accept projection 
     *                                         request, false to not
     * 
     * @return void
     */
    public function setAcceptProjectionRequests($acceptProjectionRequest)
    {
        $this->_acceptProjectionRequest = $acceptProjectionRequest;
    }

    /**
     * Gets Maxumum version of the response sent by server
     * 
     * @return DataServiceProtocolVersion
     */
    public function getMaxDataServiceVersion()
    {
        return $this->_maxProtocolVersion;
    }

    /**
     * Gets Maxumum version of the response sent by server.
     * 
     * @return Version
     */
    public function getMaxDataServiceVersionObject()
    {
        switch ($this->_maxProtocolVersion) {
        case DataServiceProtocolVersion::V1:
            return new Version(1, 0);
            break;
        case DataServiceProtocolVersion::V2:
            return new Version(2, 0);
            break;
        case DataServiceProtocolVersion::V3:
            return new Version(3, 0);
            break;
        default:
            return new Version(1, 0);
        }
    }

    /**
     * Sets Maxumum version of the response sent by server
     * 
     * @param DataServiceProtocolVersion $version The version to set
     * 
     * @throws InvalidArgumentException
     * 
     * @return void
     */
    public function setMaxDataServiceVersion($version)
    {
        if ($version < DataServiceProtocolVersion::V1 
            || $version > DataServiceProtocolVersion::V3
        ) {
            throw new \InvalidArgumentException(
                Messages::dataServiceConfigurationInvalidVersion(
                    '$version', 'setMaxDataServiceVersion'
                )
            );
        }
        
        $this->_maxProtocolVersion = $version;
    }

     /**
      * Specify whether to validate the ETag or not
      *
      * @param boolean $validate True if ETag needs to validated, false 
      *                          otherwise.
      *
      * @return void
      */
     function setValidateETagHeader($validate)
     {
         $this->_validateETagHeader = $validate;
     }

     /**
      * Gets whether to validate the ETag or not
      *
      * @return boolean True if ETag needs to validated, false 
      *                 if its not to be validated, Note that in case
      *                 of false library will not write the ETag header
      *                 in the response even though the requested resource
      *                 support ETag
      */
     function getValidateETagHeader()
     {
         return $this->_validateETagHeader;
     }
     
    /**
     * Validate the specified configuration is supported in the currently 
     * selected version
     * Note: This is an internal method used by library
     *
     * @throws InvalidOperationException
     *
     * @return void
     */    
    public function validateConfigAganistVersion()
    {
        if ($this->_maxProtocolVersion < DataServiceProtocolVersion::V2 
            && ($this->_acceptProjectionRequest || $this->_acceptCountRequest)
        ) {
            throw new InvalidOperationException(
                Messages::dataServiceConfigurationFeatureVersionMismatch(
                    'projection and count request', 'V2'
                )
            );
        }
    }

    /**
     * Checks that the parameter to a function is numeric and is not negative
     * 
     * @param int    $value        The value of parameter to check
     * @param string $functionName The name of the function that receives above value
     * 
     * @throws InvalidOperationException
     * 
     * @return int
     */
    private function _checkIntegerNonNegativeParameter($value, $functionName)
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException(
                Messages::commonArgumentShouldBeInteger($value, $functionName)
            );
        }
        
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
     * @return boolean
     */
    private function _isPageSizeDefined()
    {
        return count($this->_pageSizes) > 0 || $this->_defaultPageSize > 0;
    }
}
?>