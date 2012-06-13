<?php
/**
 * Type to represent association (relationship) set. 
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
use ODataProducer\Providers\Metadata\ResourceAssociationType;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\Messages;
/**
 * Type for association (relationship) set.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ResourceAssociationSet
{
    /**
     * name of the association set
     * @var string
     */        
    private $_name;

    /** 
     * End1 of association set
     * @var ResourceAssociationSetEnd
     */
    private $_end1;

    /** 
     * End2 of association set
     * @var ResourceAssociationSetEnd
     */
    private $_end2;

    /**     
     * Note: This property will be populated by the library, 
     * so IDSMP implementor should not set this.
     * The association type hold by this association set
     * 
     * @var ResourceAssociationType
     */
    public $resourceAssociationType;

    /**
     * Construct new instance of ResourceAssociationSet
     * 
     * @param string                    $name Name of the association set
     * @param ResourceAssociationSetEnd $end1 First end set participating 
     *                                        in the association set
     * @param ResourceAssociationSetEnd $end2 Second end set participating 
     *                                        in the association set
     * 
     * @throws \InvalidArgumentException
     */
    public function __construct($name, 
        ResourceAssociationSetEnd $end1, 
        ResourceAssociationSetEnd $end2
    ) {

        if (is_null($end1->getResourceProperty()) 
            && is_null($end2->getResourceProperty())
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetResourcePropertyCannotBeBothNull()
            );
        }

        if ($end1->getResourceType() == $end2->getResourceType() 
            && $end1->getResourceProperty() == $end2->getResourceProperty()
        ) {
                throw new \InvalidArgumentException(
                    Messages::resourceAssociationSetSelfReferencingAssociationCannotBeBiDirectional()
                );
        }
       
        $this->_name = $name;
        $this->_end1 = $end1;
        $this->_end2 = $end2;
    }

    /**
     * Retrieve the end for the given resource set, type and property.
     * 
     * @param ResourceSet      $resourceSet      Resource set for the end
     * @param ResourceType     $resourceType     Resource type for the end
     * @param ResourceProperty $resourceProperty Resource property for the end
     * 
     * @return ResourceAssociationSetEnd Resource association set end for the 
     *                                   given parameters
     */
    public function getResourceAssociationSetEnd(ResourceSet $resourceSet, 
        ResourceType $resourceType, ResourceProperty $resourceProperty
    ) {
        if ($this->_end1->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->_end1;
        }
        
        if ($this->_end2->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->_end2;
        }
        
        return null;
    }

    /**
     * Retrieve the related end for the given resource set, type and property.
     * 
     * @param ResourceSet      $resourceSet      Resource set for the end
     * @param ResourceType     $resourceType     Resource type for the end
     * @param ResourceProperty $resourceProperty Resource property for the end
     * 
     * @return ResourceAssociationSetEnd Related resource association set end 
     *                                   for the given parameters
     */
    public function getRelatedResourceAssociationSetEnd(ResourceSet $resourceSet, 
        ResourceType $resourceType, ResourceProperty $resourceProperty
    ) {
        if ($this->_end1->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->_end2;
        }
        
        if ($this->_end2->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->_end1;
        }
        
        return null;
    }

    /**
     * Get name of the association set
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get first end of the association set
     * 
     *  @return string
     */
    public function getEnd1()
    {
        return $this->_end1;
    }

    /**
     * Get second end of the association set
     * 
     *  @return string
     */
    public function getEnd2()
    {
        return $this->_end2;
    }

    /**
     * Whether this association set represents a two way relationship between 
     * resource sets
     * 
     * @return boolean true if relationship is bidirectional, otherwise false 
     */
    public function isBidirectional()
    {
        return (!is_null($this->_end1->getResourceProperty()) 
            && !is_null($this->_end2->getResourceProperty())
        );
    }
}
?>