<?php

namespace POData\Providers\Metadata\Type;

use POData\Common\Messages;
use POData\Common\NotImplementedException;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\ResourceType;

/**
 * Class Navigation
 * @package POData\Providers\Metadata\Type
 */
class Navigation implements INavigationType
{
    /**
     * 
     * The type describing this navigation
     * @var ResourceType 
     */
    private $_resourceType;

    /**
     * Creates new instance of Navigation
     * 
     * @param ResourceType $resourceType The resource type for this navigation.
     * @throws \InvalidArgumentException when the resource type kind is not complex or entity
     */
    public function __construct($resourceType)
    {
        if ($resourceType->getResourceTypeKind() != ResourceTypeKind::COMPLEX 
            && $resourceType->getResourceTypeKind() != ResourceTypeKind::ENTITY
        ) {            
            throw new \InvalidArgumentException(Messages::navigationInvalidResourceType() );
        }
        
        $this->_resourceType = $resourceType;
    }

    //Begin implementation of IType interface

    /**
     * Gets the type code
     * Note: implementation of IType::getTypeCode
     *   
     * @return TypeCode
     */
    public function getTypeCode()
    {
        return TypeCode::NAVIGATION;
    }

    /**
     * Checks this type (Navigation) is compatible with another type
     * Note: implementation of IType::isCompatibleWith
     * 
     * @param IType $type Type to check compatibility
     * 
     * @return boolean 
     */
    public function isCompatibleWith(IType $type)
    {
        if (!($type instanceof Navigation)) {
            return false;
        }
        
        return strcmp(
            $type->_resourceType->getFullName(), 
            $this->_resourceType->getFullName()
        ) == 0;
        
    }

    /**
     * Validate a value in Astoria uri is in a format for this type
     * Note: implementation of IType::validate
     * 
     * @param string $value     The value to validate 
     * @param string &$outValue The stripped form of $value that can 
     *                          be used in PHP expressions
     * 
     * @return boolean
     */
    public function validate($value, &$outValue)
    {
        if (!$value instanceof Navigation) {
            return false;
        }
        
        $outValue = $value;
        return true;
    }

    /**
     * Gets full name of this type in EDM namespace
     * Note: implementation of IType::getFullTypeName
     * 
     * @return string
     */
    public function getFullTypeName()
    {
        return $this->_resourceType->getFullName();
    }

    /**
     * Converts the given string value to navigation type.
     * 
     * @param string $stringValue value to convert.
     * 
     * @return void
     * 
     * @throws NotImplementedException
     */
    public function convert($stringValue)
    {
        throw new NotImplementedException();
    }

    /**
     * Convert the given value to a form that can be used in OData uri.
     * 
     * @param mixed $value value to convert.
     * 
     * @return void
     * 
     * @throws NotImplementedException
     */
    public function convertToOData($value)
    {
        throw new NotImplementedException();
    }

    //End implementation of IType interface

    //Begin implementation of INavigationType interface

    /**
     * Gets the resource type associated with the navigation type
     * 
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->_resourceType;
    }

    //End implementation of INavigationType interface
}