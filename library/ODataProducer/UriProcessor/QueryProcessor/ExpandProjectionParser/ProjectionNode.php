<?php
/** 
 * ExpandProjectionParser will create a 'Projection Tree' from the $expand 
 * and/or $select query options, Each path segement in the $expand/$select
 * will be represented by a node in the proejction tree, A path segment in
 * $expand option (which is not appear in expand option) will be represented
 * using a type derived from this type 'ExpandedProjectionNode' and a path 
 * segment in $select option will be represented using 'ProjectionNode'. 
 * The root of the projection tree will be represented using the type 
 * 'RootProjectionNode' which is derived from the type 'ExpandedProjectionNode'
 * 
 *               'ProjectionNode'
 *                       |
 *                       |
 *            'ExpandedProjectionNode'
 *                       |
 *                       |
 *              'RootProjectionNode'
 * 
 * Note: In the context of library we use the term 'Projection' to represent
 * both expansion and selection.
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
use ODataProducer\Providers\Metadata\ResourceProperty;
/**
 * Type to represent a selected property using $select.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpandProjectionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ProjectionNode
{
    /**
     * The name of the property to be projected. When this node represents a
     * select path segment then this member holds the name of the property to
     * select, when this node represents an expand path segment then this 
     * member holds the name of the property (a navigation property) to expand,
     * if this node represents root of the projection tree, this field will be
     * null.
     *
     * @var string
     */
    protected $propertyName;

    /**
     * The resource type of the property to be projected. if this node 
     * represents root of the projection tree, this field will be null.
     *     
     * @var ResourceProperty
     */
    protected $resourceProperty;

    /**
     * Constructs a new instance of ProjectionNode.
     * 
     * @param string           $propertyName     Name of the property to 
     *                                           be projected.
     * @param ResourceProperty $resourceProperty The resource type of the
     *                                           property to be projected.
     */
    public function __construct($propertyName, $resourceProperty)
    {
        $this->propertyName = $propertyName;
        $this->resourceProperty = $resourceProperty;
    }

    /**
     * Gets name of the property to be projected, if this is root node then
     * name will be null.
     * 
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Gets reference to the resource property instance for the property to be
     * projected, if this is root node then name will be null.
     * 
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->resourceProperty;
    }
}
?>