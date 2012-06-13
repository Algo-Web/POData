<?php
/** 
 * Type to represent resource property
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
 * Type to represent resource property
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ResourceProperty
{
    /**
     * Property name.
     * 
     * @var string
     */
    private $_name;

    /**
     * Property MIME type.
     * 
     * @var string
     */
    private $_mimeType;

    /**
     * Property Kind, the possible values are:
     *  ResourceReference
     *  ResourceSetReference
     *  ComplexType
     *  ComplexType + Bag
     *  PrimitiveType
     *  PrimitiveType + Bag
     *  PrimitiveType + Key
     *  PrimitiveType + ETag
     *  
     * @var ResourcePropertyKind
     */
    private $_kind;

    /**
     * ResourceType describes this property
     * 
     * @var ResourceType
     */
    private $_propertyResourceType;

    /**
     * Construct a new instance of ResouceProperty
     * 
     * @param string               $name                  Name of the property
     * @param string               $mimeType              Mime type of the property
     * @param ResourcePropertyKind $kind                  The kind of property
     * @param ResourceType         &$propertyResourceType ResourceType of the 
     *                                                    property
     * 
     * @throws InvalidOperationException
     */
    public function __construct($name, $mimeType, $kind, 
        ResourceType &$propertyResourceType
    ) {
        if (!$this->_isValidResourcePropertyKind($kind)) {
            throw new \InvalidArgumentException(
                Messages::resourcePropertyInvalidKindParameter('$kind')
            );
        }
    
        if (!$this->_isResourceKindValidForPropertyKind($kind, $propertyResourceType->getResourceTypeKind())) {
            throw new \InvalidArgumentException(
                Messages::resourcePropertyPropertyKindAndResourceTypeKindMismatch(
                    '$kind', '$propertyResourceType'
                )
            );
        }

        $this->_name = $name;
        $this->_mimeType = $mimeType;
        $this->_kind = $kind;
        $this->_propertyResourceType = $propertyResourceType;
    }

    /**
     * Check whether current property is of kind specified by the parameter
     * 
     * @param ResourcePropertyKind $kind kind to check
     * 
     * @return boolean
     */
    public function isKindOf($kind)
    {
        return ($this->_kind & $kind) == $kind;
    }

    /**
     * Get the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
  
    /**
     * Get property MIME type
     * 
     * @return string
     */
    public function getMIMEType()
    {
        return $this->_mimeType;
    }

    /**
     * Get property kind
     * 
     * @return ResourcePropertyKind
     */
    public function getKind()
    {
        return $this->_kind;
    }

    /**
     * Get the resource type for this property
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_propertyResourceType;
    }

    /**
     * Get the kind of resource type
     * 
     * @return ResourceTypeKind
     */
    public function getTypeKind()
    {
        return $this->_propertyResourceType->getResourceTypeKind();
    }

    /**
     * Get the instance type. If the property is of kind 'Complex', 
     * 'ResourceReference' or 'ResourceSetReference' then this function returns 
     * refernece to ReflectionClass instance for the type. If the property of 
     * kind 'Primitive' then this function returns ITYpe instance for the type.
     * 
     * @return ReflectionClass/IType
     */
    public function getInstanceType()
    {
        return $this->_propertyResourceType->getInstanceType();
    }

    /**
     * Check once kind is of another kind.
     * 
     * @param ResourcePropertyKind $kind1 First kind
     * @param ResourcePropertyKind $kind2 second kind
     *  
     * @return boolean
     */
    public static function sIsKindOf($kind1, $kind2)
    {
        return ($kind1 & $kind2) == $kind2;
    }
  
    /**
     * Checks whether resource property kind is valid or not
     * 
     * @param ResourcePropertyKind $kind The kind to validate
     * 
     * @return boolean
     */
    private function _isValidResourcePropertyKind($kind)
    {
        return 
          !($kind != ResourcePropertyKind::RESOURCE_REFERENCE &&
            $kind != ResourcePropertyKind::RESOURCESET_REFERENCE &&
            $kind != ResourcePropertyKind::COMPLEX_TYPE &&
            ($kind != (ResourcePropertyKind::COMPLEX_TYPE | ResourcePropertyKind::BAG)) &&
            $kind  != ResourcePropertyKind::PRIMITIVE &&
            ($kind != (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::BAG)) &&
            ($kind != (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY)) &&
            ($kind != (ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::ETAG)));
    }

    /**
     * Check the specified resource kind is valid resource kind for property kind
     * 
     * @param ResourcePropertyKind $pKind The kind of resource property
     * @param ResourceTypeKind     $rKind The kind of resource type
     * 
     * @return boolean True if resource type kind and property kind matches 
     *                      otherwise false.
     */
    private function _isResourceKindValidForPropertyKind($pKind, $rKind)
    {
        if (self::sIsKindOf($pKind, ResourcePropertyKind::PRIMITIVE) 
            && $rKind != ResourceTypeKind::PRIMITIVE
        ) {
            return false;
        }

        if (self::sIsKindOf($pKind, ResourcePropertyKind::COMPLEX_TYPE) 
            && $rKind != ResourceTypeKind::COMPLEX
        ) {
            return false;
        }

        if ((self::sIsKindOf($pKind, ResourcePropertyKind::RESOURCE_REFERENCE) 
            || self::sIsKindOf($pKind, ResourcePropertyKind::RESOURCESET_REFERENCE))
            && $rKind != ResourceTypeKind::ENTITY
        ) {
            return false;
        }

        return true;
    }
}
?>