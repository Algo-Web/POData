<?php
/**
 * Type to represent an association (relationship).
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
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
namespace ODataProducer\Providers\Metadata;
/**
 * Type to represent an association (relationship).
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ResourceAssociationType
{
    /**
     * Full name of the association
     * @var string
     */
    private $_fullName;

    /**
     * Name of the association
     * @var string
     */
    private $_name;
    
    /**
     * end1 for this association
     * @var ResourceAssociationTypeEnd
     */
    private $_end1;

    /**
     * end2 for this association
     * @var ResourceAssociationTypeEnd
     */
    private $_end2;

    /**
     * Construct new instance of ResourceAssociationType.
     * 
     * @param string                     $name          Name of the association
     * @param string                     $namespaceName NamespaceName of the 
     *                                                  association
     * @param ResourceAssociationTypeEnd $end1          First end of the association
     * @param ResourceAssociationTypeEnd $end2          Second end of the association
     */    
    public function __construct($name, $namespaceName, 
        ResourceAssociationTypeEnd $end1, 
        ResourceAssociationTypeEnd $end2
    ) {
        $this->_name = $name;
        $this->_fullName = !is_null($namespaceName) 
                          ? $namespaceName . '.' . $name : $name;
        $this->_end1 = $end1;
        $this->_end2 = $end2;
    }

    /**
     * Gets name of the association
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Gets full-name of the association
     * 
     * @return string
     */
    public function getFullName()
    {
        return $this->_fullName;
    }

    /**
     * Gets reference to first end.
     * 
     * @return ResourceAssociationTypeEnd
     */
    public function getEnd1()
    {
        return $this->_end1;
    }

    /**
     * Gets reference to second end.
     * 
     * @return ResourceAssociationTypeEnd
     */
    public function getEnd2()
    {
        return $this->_end2;
    }

    /**
     * Retrieve the end for the given resource type and property.
     * 
     * @param ResourceType     $resourceType     Resource type for the source end
     * @param ResourceProperty $resourceProperty Resource property for the source end
     * 
     * @return ResourceAssociationTypeEnd Association type end for the 
     *                                    given parameters
     */
    public function getResourceAssociationTypeEnd(ResourceType $resourceType, 
        $resourceProperty
    ) {
        if ($this->_end1->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->_end1;
        }
        
        if ($this->_end2->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->_end2;
        }
        
        return null;
    }

    /**
     * Retrieve the related end for the given resource set, type and property.
     * 
     * @param ResourceType     $resourceType     Resource type for the source end
     * @param ResourceProperty $resourceProperty Resource property for the source end
     * 
     * @return ResourceAssociationTypeEndRelated Association type end for the 
     *                                           given parameters
     */
    public function getRelatedResourceAssociationSetEnd(ResourceType $resourceType, 
        $resourceProperty
    ) {

        if ($this->_end1->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->_end2;
        }
        
        if ($this->_end2->isBelongsTo($resourceType, $resourceProperty)) {
            return $this->_end1;
        }
        
        return null;
    }
}
?>