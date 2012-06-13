<?php
/**
 * Type to represent association (relationship) set end. 
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
use ODataProducer\Common\Messages;
/**
 * Type to represent association (relationship) set end. 
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ResourceAssociationSetEnd
{
    /**
     * Resource set for the association end.
     * @var ResourceSet
     */
    private $_resourceSet;

    /**
     * Resource type for the association end.
     * @var ResourceType
     */
    private $_resourceType;

    /**
     * Resource property for the association end.
     * @var ResourceProperty
     */
    private $_resourceProperty;

    /**
     * Construct new instance of ResourceAssociationSetEnd
     * Note: The $resourceSet represents collection of an entity, The 
     * $resourceType can be this entity's type or type of any of the 
     * base resource of this entity, on which the navigation property 
     * represented by $resourceProperty is defined.
     *   
     * @param ResourceSet      $resourceSet      Resource set for the association end
     * @param ResourceType     $resourceType     Resource type for the association end
     * @param ResourceProperty $resourceProperty Resource property for the association end
     * 
     * @throws \InvalidArgumentException
     */
    public function __construct(ResourceSet $resourceSet, 
        ResourceType $resourceType, $resourceProperty
    ) {
        if (!is_null($resourceProperty) 
            && !($resourceProperty instanceof ResourceProperty)
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetPropertyMustBeNullOrInsatnceofResourceProperty(
                    '$resourceProperty'
                )
            );
        }

        if (!is_null($resourceProperty) 
            && (is_null($resourceType->tryResolvePropertyTypeByName($resourceProperty->getName())) || (($resourceProperty->getKind() != ResourcePropertyKind::RESOURCE_REFERENCE) && ($resourceProperty->getKind() != ResourcePropertyKind::RESOURCESET_REFERENCE)))
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetEndPropertyMustBeNavigationProperty(
                    $resourceProperty->getName(), $resourceType->getFullName()
                )
            );
        }
        
        if (!$resourceSet->getResourceType()->isAssignableFrom($resourceType) 
            && !$resourceType->isAssignableFrom($resourceSet->getResourceType())
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetEndResourceTypeMustBeAssignableToResourceSet(
                    $resourceType->getFullName(), $resourceSet->getName()
                )
            );
        }
        
        $this->_resourceSet = $resourceSet;
        $this->_resourceType = $resourceType;
        $this->_resourceProperty = $resourceProperty;
    }

    /**
     * To check this relationship belongs to a specfic resource set, type 
     * and property
     * 
     * @param ResourceSet      $resourceSet      Resource set for the association
     *                                           end
     * @param ResourceType     $resourceType     Resource type for the association
     *                                           end
     * @param ResourceProperty $resourceProperty Resource property for the 
     *                                           association end
     * 
     * @return boolean
     */
    public function isBelongsTo(ResourceSet $resourceSet, 
        ResourceType $resourceType, ResourceProperty $resourceProperty
    ) {
        return (strcmp($resourceSet->getName(), $this->_resourceSet->getName()) == 0 
            && $this->_resourceType->isAssignableFrom($resourceType) 
            && ((is_null($resourceProperty) && is_null($this->_resourceProperty)) ||
                  (!is_null($resourceProperty) && !is_null($this->_resourceProperty) && (strcmp($resourceProperty->getName(), $this->_resourceProperty->getName()) == 0)))
        );
    }

    /**
     * Gets reference to resource set
     * 
     * @return ResourceSet
     */
    public function getResourceSet()
    {
        return $this->_resourceSet;
    }

    /**
     * Gets reference to resource type 
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_resourceType;
    }

    /**
     * Gets reference to resource property
     * 
     * @return ResourceProperty
     */
    public function getResourceProperty()
    {
        return $this->_resourceProperty;
    }
}
?>