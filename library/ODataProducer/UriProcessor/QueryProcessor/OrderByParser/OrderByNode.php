<?php
/**
 * Type to represent non-leaf node of 'OrderBy Tree' (the root node and 
 * intermediate nodes[complex/navigation]).
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
use ODataProducer\Providers\Metadata\ResourceSetWrapper;
/**
 * Type to represent non-leaf node of 'OrderBy Tree'.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class OrderByNode extends OrderByBaseNode
{
    /**
     * The resource set wrapper associated with this node, this will 
     * be null if this node represents a complex sub path segment
     * 
     * @var ResourceSetWrapper
     */
    private $_resourceSetWrapper;

    /**
     * list of child nodes.
     * 
     * @var array(OrderByNode/OrderByLeafNode)
     */
    private $_childNodes = array();

    /**
     * Construct a new instance of OrderByNode
     * 
     * @param string             $propertyName       Name of the property corrosponds
     *                                               to the sub path
     *                                               segment represented by 
     *                                               this node, this parameter
     *                                               will be null if this
     *                                               node is root.
     * @param ResourceProperty   $resourceProperty   Resource property corrosponds 
     *                                               to the sub path 
     *                                               segment represented by this
     *                                               node, this parameter
     *                                               will be null if 
     *                                               this node is root.
     * @param ResourceSetWrapper $resourceSetWrapper The resource set wrapper
     *                                               associated with the sub path 
     *                                               segment represented by this 
     *                                               node, this will be null 
     *                                               if this node represents a 
     *                                               complex sub path segment
     */
    public function __construct($propertyName, $resourceProperty, $resourceSetWrapper)
    {
        // This must be the parameter state at this point, we won't chek 
        // these as this is an internal class
        //if ($resourceProperty != NULL)
        //{
        //    Node represents navigation or complex
        //    if ($resourceProperty::Kind == Complex)
        //        assert($resourceSetWrapper == null);
        //    else if ($resourceProperty::Kind == ResourceReference) 
        //        assert($resourceSetWrapper !== null);
        //} else {
        //    Node represents root
        //    assert($resourceSetWrapper != null)
        //}
        parent::__construct($propertyName, $resourceProperty);
        $this->_resourceSetWrapper = $resourceSetWrapper;
    }

    /**
     * (non-PHPdoc)
     * 
     * @see library/ODataProducer/QueryProcessor/OrderByParser/ODataProducer\QueryProcessor\OrderByParser.OrderByBaseNode::free()
     * 
     * @return void
     */
    public function free()
    {
        foreach ($this->_childNodes as $childNode) {
            $childNode->free();
        }       
    }

    /**
     * (non-PHPdoc)
     * 
     * @see library/ODataProducer/QueryProcessor/OrderByParser/ODataProducer\QueryProcessor\OrderByParser.OrderByBaseNode::getResourceType()
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->resourceProperty->getResourceType();
    }

    /**
     * To get reference to the resource set wrapper, this will be null 
     * if this node represents a complex sub path segment
     * 
     * @return ResourceSetWrapper
     */
    public function getResourceSetWrapper()
    {
        return $this->_resourceSetWrapper;
    }

    /**
     * Find a child node with given name, if no such child node then return NULL. 
     * 
     * @param string $propertyName Name of the property to get the 
     *                             corrosponding node
     * 
     * @return OrderByNode/OrderByLeafNode/NULL
     */
    public function findNode($propertyName)
    {
        if (array_key_exists($propertyName, $this->_childNodes)) {
            return $this->_childNodes[$propertyName];
        }

        return null;
    }

    /**
     * To add a child node to the list of child nodes.
     * 
     * @param OrderByNode/OrderByLeafNode $node The child node.
     * 
     * @return void
     * 
     * @throws InvalidArgumentException
     */
    public function addNode($node)
    {
        // if (!($node instanceof OrderByNode) 
        //     && !($node instanceof OrderByLeafNode)
        // ) {
            //Error
        // }

        $this->_childNodes[$node->getPropertyName()] = $node;
    }
}
?>