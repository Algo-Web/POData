<?php
/**
 * Base type for nodes in OrderByTree, a node in 'OrderBy Tree' 
 * represents a sub path segment.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
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
namespace ODataProducer\UriProcessor\QueryProcessor\OrderByParser;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\ResourceType;
/**
 * Base type for nodes in OrderByTree
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
abstract class OrderByBaseNode
{
    /**
     * Name of the property corrosponds to the sub path segment 
     * represented by this node.
     * 
     * @var string
     */
    protected $propertyName;

    /**
     * Th resource property of the property corrosponds to the 
     * sub path segment represented by this node.
     * 
     * @var ResourceProperty
     */
    protected $resourceProperty;

    /**
     * Construct a new instance of OrderByBaseNode
     * 
     * @param string           $propertyName     Name of the property corrosponds to
     *                                           the sub path segment represented by 
     *                                           this node, this parameter will be 
     *                                           null if this node is root.
     * @param ResourceProperty $resourceProperty Resource property corrosponds to the
     *                                           sub path segment represented by this
     *                                           node, this parameter will be null if
     *                                           this node is root.
     */
    public function __construct($propertyName, $resourceProperty)
    {
        $this->propertyName = $propertyName;
        $this->resourceProperty = $resourceProperty;
    }

    /**
     * Gets resource type of the property corrosponds to the sub path segment 
     * represented by this node.
     * 
     * @return ResourceType
     */
    abstract public function getResourceType();

    /**
     * Free resource used by this node.
     * 
     * @return void
     */
    abstract public function free();

    /**
     * Gets the name of the property corrosponds to the sub path segment 
     * represented by this node.
     * 
     * @return  string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Gets the resource property of property corrosponds to the sub path 
     * segment represented by this node.
     * 
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->resourceProperty;
    }    
}
?>