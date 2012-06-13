<?php
/** 
 * Type to represent association (relationship) end. 
 * 
 * Entities (described using ResourceType) can have relationship between them.
 * A relationship (described using ResourceAssociationType) composed of two ends
 * (described using ResourceAssociationTypeEnd). 
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
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
/**
 * Type to represent association (relationship) end.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ResourceAssociationTypeEnd
{
    /**
     * Name of the association end
     * @var string
     */
    private $_name;
    
    /**
     * Type of the entity in the relationship end
     * @var ResourceType
     */
    private $_resourceType;

    /**
     * Entity property involved in the relationship end
     * @var ResourceProperty
     */
    private $_resourceProperty;

    /**
     * Property of the entity involved in the relationship end points to this end.
     * The multiplicity of this end is determined from the fromProperty.
     * @var ResourceProperty
     */
    private $_fromProperty;
    
    /**
     * Construct new instance of ResourceAssociationTypeEnd
     * 
     * @param string                $name             name of the end
     * @param ResourceType          $resourceType     resource type that the end 
     *                                                refers to
     * @param ResourceProperty/NULL $resourceProperty property of the end, can be 
     *                                                NULL if relationship is 
     *                                                uni-directional
     * @param ResourceProperty/NULL $fromProperty     Property on the related end 
     *                                                that points to this end, can 
     *                                                be NULL if relationship is 
     *                                                uni-directional
     */
    public function __construct($name, ResourceType $resourceType, 
        $resourceProperty, 
        $fromProperty
    ) {
        if (is_null($resourceProperty) && is_null($fromProperty)) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationTypeEndBothPropertyCannotBeNull()
            );
        }

        if (!is_null($fromProperty) 
            && !($fromProperty instanceof ResourceProperty)
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationTypeEndPropertyMustBeNullOrInsatnceofResourceProperty(
                    '$fromProperty'
                )
            );
        }

        if (!is_null($resourceProperty) 
            && !($resourceProperty instanceof ResourceProperty)
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationTypeEndPropertyMustBeNullOrInsatnceofResourceProperty(
                    '$$resourceProperty'
                )
            );
        }
        
        $this->_name = $name;
        $this->_resourceType = $resourceType;
        $this->_resourceProperty = $resourceProperty;
        $this->_fromProperty = $fromProperty;
    }

    /**
     * To check this relationship belongs to a specfic entity property
     *  
     * @param ResourceType          $resourceType     The type of the entity
     * @param ResourceProperty/NULL $resourceProperty The property in the entity
     * 
     * @return boolean
     */
    public function isBelongsTo(ResourceType $resourceType, $resourceProperty) 
    {
        $flag1 = is_null($resourceProperty);
        $flag2 = is_null($this->_resourceProperty);
        if ($flag1 != $flag2) {
            return false;
        }

        if ($flag1 === true) {
            return strcmp(
                $resourceType->getFullName(), 
                $this->_resourceType->getFullName()
            ) == 0;
        }

        return strcmp(
            $resourceType->getFullName(), $this->_resourceType->getFullName()
        ) == 0
        && (strcmp(
            $resourceProperty->getName(), $this->_resourceProperty->getName()
        ) == 0);
    }

    /**
     * Get the name of the end
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get the resource type that the end refers to
     * 
     * @return string
     */
    public function getResourceType()
    {
        return $this->_resourceType;
    }

    /**
     * Get the property of the end
     * 
     * @return string
     */
    public function getResourceProperty()
    {
        return $this->_resourceProperty;
    }

    /**
     * Get the Mulitplicity of the relationship end
     * 
     * @return string
     */
    public function getMultiplicity()
    {
        if (!is_null($this->_fromProperty) 
            && $this->_fromProperty->getKind() == ResourcePropertyKind::RESOURCE_REFERENCE
        ) {
            return ODataConstants::ZERO_OR_ONE;
        }
        
        return ODataConstants::MANY;
    }
}
?>