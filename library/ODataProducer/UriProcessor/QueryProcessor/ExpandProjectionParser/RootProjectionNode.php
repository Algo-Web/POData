<?php
/** 
 * ExpandProjectParser will create a 'Projection Tree' from the $expand 
 * and/or $select query options, this type is used to represent root of
 * the 'Projection Tree', the root holds details about the resource set
 * pointed by the resource path uri (ResourceSet, OrderInfo, skip, top,
 * pageSize etc..) and flags indicating whether projection and expansions
 * are specifed.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpandProjectionParser
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
namespace ODataProducer\UriProcessor\QueryProcessor\ExpandProjectionParser;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourceSetWrapper;
/**
 * Type to represent root of projection tree.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpandProjectionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class RootProjectionNode extends ExpandedProjectionNode
{
    /**
     * Flag indicates whether expansions were specifed in the query or not
     * 
     * @var boolean
     */
    private $_expansionSpecifed = false;

    /**
     * Flag indicates whether selections were specifed in the query or not
     * 
     * @var boolean
     */
    private $_selectionSpecifed = false;

    /**
     * Flag indicates whether any of the expaned resource set is paged or not
     * 
     * @var boolean
     */
    private $_hasPagedExpandedResult = false;

    /**
     * The base resource type of entities identifed by the resource path uri, 
     * this is usually the base resource type of the resource set to which 
     * the entites belongs to, but it can happen that it's a derived type of 
     * the resource set base type.
     * 
     * @var ResourceType
     */
    private $_baseResourceType;

    /**
     * Constructs a new instance of 'RootProjectionNode' representing root 
     * of 'Projection Tree'
     * 
     * @param ResourceSetWrapper  $resourceSetWrapper  ResourceSetWrapper of 
     *                                                 the resource pointed 
     *                                                 by the resource path.
     * @param InternalOrderByInfo $internalOrderByInfo Details of ordering 
     *                                                 to be applied to the 
     *                                                 resource set pointed 
     *                                                 by the resource path.
     * @param int                 $skipCount           Number of resources to 
     *                                                 be skipped from the 
     *                                                 resource set pointed 
     *                                                 by the resource path.
     * @param int                 $takeCount           Number of resources to 
     *                                                 be taken from the 
     *                                                 resource set pointed 
     *                                                 by the resource path.
     * @param int                 $maxResultCount      The maximum limit 
     *                                                 configured for the 
     *                                                 resource set.
     * @param ResourceType        $baseResourceType    Resource type of the 
     *                                                 resource pointed 
     *                                                 by the resource path.
     */
    public function __construct(ResourceSetWrapper $resourceSetWrapper, 
        $internalOrderByInfo, $skipCount, $takeCount, $maxResultCount, 
        ResourceType $baseResourceType
    ) {
        $this->_baseResourceType = $baseResourceType;
        parent::__construct(
            null, null, $resourceSetWrapper, $internalOrderByInfo, 
            $skipCount, $takeCount, $maxResultCount
        );
    }

    /**
     * Gets reference to the base resource type of entities identifed by
     * the resource path uri this is usually the base resource type of the
     * resource set to which the entites belongs to but it can happen that 
     * it's a derived type of the resource set base type.
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_baseResourceType;
    }

    /**
     * Mark expansions are used in the query or not
     * 
     * @param boolean $isExpansionSpecified True if expansion found, 
     *                                      False else.
     * 
     * @return void
     */
    public function setExpansionSpecified($isExpansionSpecified = true)
    {
        $this->_expansionSpecifed = $isExpansionSpecified;
    }

    /**
     * Check whether expansion were specified in the query 
     * 
     * @return boolean
     */
    public function isExpansionSpecified()
    {
        return $this->_expansionSpecifed;
    }

    /**
     * Mark selections are used in the query or not
     * 
     * @param boolean $isSelectionSpecified True if selection found, 
     *                                      False else.
     * 
     * @return void
     */
    public function setSelectionSpecified($isSelectionSpecified = true)
    {
        $this->_selectionSpecifed = $isSelectionSpecified;
    }

    /**
     * Check whether selection were specified in the query 
     * 
     * @return boolean
     */
    public function isSelectionSpecified()
    {
        return $this->_selectionSpecifed;
    }

    /**
     * Mark paged expanded result will be there or not
     * 
     * @param boolean $hasPagedExpandedResult True if found paging on expanded
     *                                        result, False else.
     * 
     * @return void
     */
    public function setPagedExpandedResult($hasPagedExpandedResult = true)
    {
        $this->_hasPagedExpandedResult = $hasPagedExpandedResult;
    }

    /**
     * Check whether any of the expanded resource set is paged.
     * 
     * @return boolean
     */
    public function hasPagedExpandedResult()
    {
        return $this->_hasPagedExpandedResult;
    }
}
?>