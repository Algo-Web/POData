<?php
/** 
 * Class helps to format error messages
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
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
namespace ODataProducer\Common;
use ODataProducer\Providers\Metadata\Type\IType;
/**
 * Class helps to format error messages
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class Messages
{
    /**
     * Format message for unterminated string literal error
     * 
     * @param int    $pos  Position of unterminated string literal in the text
     * @param string $text The text with unterminated string literal
     * 
     * @return string The formatted message
     */
    public static function expressionLexerUnterminatedStringLiteral($pos, $text)
    {
        return 'Unterminated string literal at position ' . $pos . ' in ' . $text;
    }
    
    /**
     * Format message for digit expected error
     * 
     * @param int $pos Position at which digit is expected
     * 
     * @return string The formatted message
     */
    public static function expressionLexerDigitExpected($pos)
    {
        return 'Digit expected at position ' . $pos;
    }

    /**
     * Format message for syntax error
     * 
     * @param int $pos Position at which syntax error found
     * 
     * @return string The formatted message
     */
    public static function expressionLexerSyntaxError($pos)
    {
        return 'Syntax Error at position ' . $pos;
    }

    /**
     * Format message for invalid character error
     * 
     * @param string $ch  The invalid character found 
     * @param int    $pos Position at which invalid character found
     * 
     * @return string The formatted message
     */
    public static function expressionLexerInvalidCharacter($ch, $pos)
    {
        return "Invalid character '$ch' at position $pos";
    }

    /**
     * Format message for an operator's incompactible operands types
     * 
     * @param string $operator The operator
     * @param string $str      The operand list seperated by comma
     * @param string $pos      Position at which operator with incompactible 
     *                         operands found
     * 
     * @return string The formatted message
     */
    public static function expressionParserInCompactibleTypes($operator, $str, $pos)
    {
        return "Operator '$operator' incompatible with operand types $str at position $pos";
    }

    /**
     * Format message for an unsupported null operation
     *
     * @param string $operator The operator
     * @param int    $pos      Position at which operator with null operands found
     * 
     * @return string The formatted message
     */
    public static function expressionParserOperatorNotSupportNull($operator, $pos)
    {
        return "The operator '$operator' at position $pos is not supported for the 'null' literal; only equality checks are supported";
    }
    
    /**
     * Format message for an unsupported guid operation
     *
     * @param string $operator The operator
     * @param int    $pos      Position at which operator with guid operands found
     * 
     * @return string The formatted message
     */
    public static function expressionParserOperatorNotSupportGuid($operator, $pos)
    {
        return "The operator '$operator' at position $pos is not supported for the Edm.Guid ; only equality checks are supported";
    }       
    
    /**
     * Format message for an unsupported binary operation
     *
     * @param string $operator The operator
     * @param int    $pos      Position at which operator with binary operands found
     * 
     * @return string The formatted message
     */
    public static function expressionParserOperatorNotSupportBinary($operator, $pos)
    {
        return "The operator '$operator' at position $pos is not supported for the Edm.Binary ; only equality checks are supported";
    }
    
    /**
     * Format message for an unrecognized literal
     * 
     * @param string $type    The expected literal type
     * @param string $literal The malformed literal
     * @param int    $pos     Position at which literal found
     * 
     * @return string The formatted message
     */
    public static function expressionParserUnrecognizedLiteral($type, $literal, $pos)
    {
        return "Unrecognized '$type' literal '$literal' in position '$pos'.";
    }

    /** 
     * Format message for an unknown function-call
     * 
     * @param string $str The unknown function name
     * @param int    $pos Position at which unknown function-call found
     * 
     * @return string The formatted message
     */
    public static function expressionParserUnknownFunction($str, $pos)
    {
        return "Unknown function '$str' at position $pos";
    }

    /**
     * Format message for non-boolean filter expression
     * 
     * @return string The formatted message
     */
    public static function expressionParser2BooleanRequired()
    {
        return 'Expression of type \'System.Boolean\' expected at position 0';
    }

    /**
     * Format message for unexpected expression
     * 
     * @param string $expressionClassName Name  of the unexpected expression
     * 
     * @return string The formatted message
     */
    public static function expressionParser2UnexpectedExpression($expressionClassName) 
    {
        return "Unexpected expression of type \'$expressionClassName\' found";
    }   
 
    /**
     * Format a message to show error when expression contains sub-property access of non-primitive property.
     * 
     * @return string The message
     */
    public static function expressionParser2NonPrimitivePropertyNotAllowed()
    {
    	return 'This data service does not support non-primitive types in the expression';
    }

    /** 
     * Format message for not applicable function error
     * 
     * @param string $functionName The name of the function called
     * @param string $protoTypes   Prototype of the functions considered
     * @param int    $position     Position at which function-call found
     * 
     * @return string The formatted message
     */
    public static function expressionLexerNoApplicableFunctionsFound($functionName, 
        $protoTypes, 
        $position
    ) {
        return "No applicable function found for '$functionName' at position $position with the specified arguments. The functions considered are: $protoTypes";
    }

    /**
     * Format message for property not found error
     * 
     * @param string $property The name of the property
     * @param string $type     The parent type in which property searched for
     * @param int    $position Position at which property mentioned
     * 
     * @return string The formatted message
     */
    public static function expressionLexerNoPropertyInType($property, $type, $position)
    {
        return "No property '$property' exists in type '$type' at position $position";
    }

    /**
     * Message to show error when the navigationPropertiesUsedInTheFilterClause
     * argument found as neither null or a non-empty array
     * 
     * @return The message
     */
    public static function filterInfoNaviUsedArgumentShouldBeNullOrNonEmptyArray()
    {
        return 'The argument navigationPropertiesUsedInTheFilterClause should be either null or a non-empty array';
    }

    /**
     * Format a message to show error when target resource property 
     * argument is not null or instance of ResourceProperty
     * 
     * @param string $argumentName The nmae of the target resource property argument
     * 
     * @return string The formatted message
     */
    public static function resourceAssociationSetPropertyMustBeNullOrInsatnceofResourceProperty($argumentName)
    {
        return "The argument '$argumentName' must be either null or instance of 'ResourceProperty";
    }

    /**
     * Format a message when a property is used as 
     * navigation property of a resource type which is actually not
     * 
     * @param string $propertyName     Property
     * @param string $resourceTypeName Resource type
     * 
     * @return string The formatted message
     */
    public static function resourceAssociationSetEndPropertyMustBeNavigationProperty($propertyName, $resourceTypeName)
    {
        return "The property $propertyName must be a navigation property of the resource type $resourceTypeName";
    }

    /**
     * Format a message for showing the error when a resource type is
     * not assignable to resource set
     * 
     * @param string $resourceTypeName Resource type
     * @param string $resourceSetName  Resource set name
     * 
     * @return string The formatted message
     */    
    public static function resourceAssociationSetEndResourceTypeMustBeAssignableToResourceSet($resourceTypeName, $resourceSetName)
    {
        return "The resource type $resourceTypeName must be assignable to the resource set $resourceSetName";
    }

    /**
     * Format a message for showing the error when trying to 
     * create an association set with both null resource property
     * 
     * @return string The formatted message
     */
    public static function resourceAssociationSetResourcePropertyCannotBeBothNull()
    {
        return 'Both the resource property of the association set cannot be null';
    }

    /**
     * Format a message for showing the error when trying to 
     * create a self referencing bidirectional association
     * 
     * @return string The formatted message
     */
    public static function resourceAssociationSetSelfReferencingAssociationCannotBeBiDirectional()
    {
        return 'Bidirectional self referencing association is not allowed';
    }

    /**
     * Format a message to show error when target resource property argument is 
     * not null or instance of ResourceProperty
     * 
     * @param string $argumentName The nmae of the target resource property argument
     * 
     * @return string The formatted message
     */
    public static function resourceAssociationTypeEndPropertyMustBeNullOrInsatnceofResourceProperty($argumentName)
    {
        return "The argument '$argumentName' must be either null or instance of 'ResourceProperty";
    }

    /**
     * Error message to show when both from and to property arguments are null
     * 
     * @return string The error message
     */
    public static function resourceAssociationTypeEndBothPropertyCannotBeNull()
    {
        return 'Both to and from property argument to ResourceAssociationTypeEnd constructor cannot be null';        
    }

    /**      
     * Format a message to show error when resourceset reference is 
     * used in $filter query option
     * 
     * @param string $property       The resourceset property used in query 
     * @param string $parentProperty The parent resource of property
     * @param int    $pos            Postion at which resource set has been used
     * 
     * @return string The formatted message
     */
    public static function expressionParserEntityCollectionNotAllowedInFilter($property, $parentProperty, $pos)
    {
        return "The '$property' is an entity collection property of '$parentProperty' (position: $pos), which cannot be used in \$filter query option";
    }

    /**
     * Format a message to show error when a non-integer value passed to 
     * a function, which expects integer parameter
     * 
     * @param var    $argument     The non-integer argument
     * @param string $functionName The name of function
     * 
     * @return string The formatted message
     */
    public static function commonArgumentShouldBeInteger($argument, $functionName)
    {        
        return "The argument to the function '$functionName' should be integer, non-integer value '$argument' passed";
    }

    /**
     * Format a message to show error when a negative value passed to a 
     * function, which expects non-negative parameter
     * 
     * @param var    $argument     The negative argument
     * @param string $functionName The name of function
     * 
     * @return string The formatted message
     */
    public static function commonArgumentShouldBeNonNegative($argument, $functionName)
    {
        return "The argument to the function '$functionName' should be non-negative, negative value '$argument' passed";
    }

    /**
     * Format a message to show error when a function expect a 
     * valid EdmPrimitiveType enum value, but it is not 
     * 
     * @param string $argumentName The argument name
     * @param string $functionName The function name
     * 
     * @return string The formatted message
     */
    public static function commonNotValidPrimitiveEDMType($argumentName, $functionName)
    {    
        return "The argument '$argumentName' to $functionName is not a valid EdmPrimitiveType Enum value";
    }
    /**
     * Error message to show when both page size and 
     * result collection size are specified 
     * 
     * @return string The message
     */
    public static function dataServiceConfigurationMaxResultAndPageSizeMuctuallyExclusive()
    {
        return 'Specification of \'entity set page size\' is mutually exclusive with the specification of \'maximum result per collection\' in configuration';    
    }

    /**     
     * Format a message to show error when configuration expects a 
     * name as resource set name but it is not
     *  
     * @param string $name The unresolved name
     * 
     * @return string The formatted message
     */
    public static function dataServiceConfigurationResourceSetNameNotFound($name)
    {
        return "The given name '$name' was not found in the entity sets";
    }

    /**
     * Format a message to show error when a function argument expected to 
     * EntitySetRights enum value but it is not
     * 
     * @param string $argument     The argument name
     * @param string $functionName The function name
     * 
     * @return string The formatted message
     */
    public static function dataServiceConfigurationRightsAreNotInRange($argument, $functionName)
    {
        return "The argument '$argument' of '$functionName' should be EntitySetRights enum value";
    }

    /**
     * Format a message to show error when a function argument expected to 
     * DataServiceProtocolVersion enum value but it is not
     * 
     * @param string $argument     The argument name
     * @param string $functionName The function name
     * 
     * @return string The formatted message
     */
    public static function dataServiceConfigurationInvalidVersion($argument, $functionName)
    {
        return "The argument '$argument' of '$functionName' should be DataServiceProtocolVersion enum value";
    }

    /**
     * Format a message to show error when a tyring to set a 
     * feature which is not supported in the version
     * 
     * @param string $feature       the feature name
     * @param string $supportedFrom odata supported version
     * 
     * @return string The formatted message
     */
    public static function dataServiceConfigurationFeatureVersionMismatch($feature, $supportedFrom)
    {
        return "The feature '$feature' is supported only for OData version '$supportedFrom' or greater";
    }

    /**
     * A message to show error when service developer disabled count request and
     * client requested for count.
     * 
     * @return string The message
     */
    public static function dataServiceConfigurationCountNotAccepted()
    {
        return 'The ability of the data service to return row count information is disabled. To enable this functionality, set the DataServiceConfiguration.AcceptCountRequests property to true.';        
    }

    /**
     * Format a message to show error when a tyring to set a 
     * base class for primitive type
     * 
     * @return string The message
     */
    public static function resourceTypeNoBaseTypeForPrimitive()
    {
        return 'Primitive type cannot have base type';
    }
 
    /**
     * Format a message to show error when tyring to 
     * set a primitive type as abstract
     * 
     * @return string The message
     */
    public static function resourceTypeNoAbstractForPrimitive()
    {
        return "Primitive type cannot be abstract";
    }

    /**
     * Format a message to show error when a primitive instance type
     * is not IType implementation
     * 
     * @param string $argument The name of instance type argument
     * 
     * @return string The message
     */
    public static function resourceTypeTypeShouldImplementIType($argument)
    {
        return "For primitive type the '$argument' argument should be an 'IType' implementor instance";
    }

    /**
     * Format a message to show error when instance type of a 
     * complex or entity type is not instance of ReflectionClass
     * 
     * @param string $argument The name of instance type argument
     * 
     * @return string The message
     */
    public static function resourceTypeTypeShouldReflectionClass($argument)
    {
        return "For entity type the '$argument' argument should be an 'ReflectionClass' instance";
    }

    /**
     * Format a message to show error when an entity type missing key properties
     * 
     * @param string $entityName The name of instance type argument
     * 
     * @return string The formatted message
     */
    public static function resourceTypeMissingKeyPropertiesForEntity($entityName)
    {
        return "The entity type '$entityName' does not have any key properties. Please make sure the key properties are defined for this entity type";
    }

    /**
     * The message to show error when trying to add 
     * property to 'Primitive' resource type
     * 
     * @return The message
     */
    public static function resourceTypeNoAddPropertyForPrimitive()
    {
        return 'Properties cannot be added to ResourceType instances with a ResourceTypeKind equal to \'Primitive\'';        
    }

    /**
     * The message to show error when trying to 
     * add key property to non-entity resource type
     * 
     * @return The message
     */
    public static function resourceTypeKeyPropertiesOnlyOnEntityTypes()
    {
        return 'Key properties can only be added to ResourceType instances with a ResourceTypeKind equal to \'EntityType\'';
    }

    /**
     * The message to show error when trying to add an 
     * etag property to non-entity resource type
     * 
     * @return The message
     */
    public static function resourceTypeETagPropertiesOnlyOnEntityTypes()
    {
        return 'ETag properties can only be added to ResourceType instances with a ResourceTypeKind equal to \'EntityType\'';
    }

    /**
     * Format a message to show error for 
     * duplication of resource property on resource type
     * 
     * @param string $propertyName     The property name
     * @param string $resourceTypeName The rtesource type name
     * 
     * @return string The formatted message
     */
    public static function resourceTypePropertyWithSameNameAlreadyExists($propertyName, $resourceTypeName)
    {
        return "Property with same name '$propertyName' already exists in type '$resourceTypeName'. Please make sure that there is no property with the same name defined in one of the ancestor types";
    }

    /**
     * The message to show error when trying to add a key property to derived type
     * 
     * @return The message
     */
    public function resourceTypeNoKeysInDerivedTypes()
    {
        return 'Key properties cannot be defined in derived types';
    }

    /**
     * The message to show error when trying to set a non-entity resource type as MLE
     * 
     * @return The message
     */
    public static function resourceTypeHasStreamAttributeOnlyAppliesToEntityType()
    {
        return 'Cannot apply the HasStreamAttribute, HasStreamAttribute is only applicable to entity types';
    }

    /**
     * The message to show error when trying to add a named stream on non-entity type
     * 
     * @return The message
     */
    public static function resourceTypeNamedStreamsOnlyApplyToEntityType()
    {
        return 'Named streams can only be added to entity types';
    }
    
    /**
     * Format a message to show error for 
     * duplication of named stream property on resource type
     * 
     * @param string $namedStreamName  The named stream name
     * @param string $resourceTypeName The resource Property
     * 
     * @return string The formatted message
     */
    public static function resourceTypeNamedStreamWithSameNameAlreadyExists($namedStreamName, $resourceTypeName)
    {
        return "Named stream with the name '$namedStreamName' already exists in type '$resourceTypeName'. Please make sure that there is no named stream with the same name defined in one of the ancestor types";
    }

    /**
     * Format a message to show error for invalid ResourcePropertyKind enum argument
     * 
     * @param string $argumentName The argument name
     * 
     * @return string The formatted message
     */
    public static function resourcePropertyInvalidKindParameter($argumentName)
    {
        return "The argument '$argumentName' is not a valid ResourcePropertyKind enum value or valid combination of ResourcePropertyKind enum values";
    }

    /**
     * Format a message to show error when ResourcePropertyKind and 
     * ResourceType's ResourceTypeKind mismatches
     * 
     * @param string $resourcePropertyKindArgName The ResourcePropertyKind a
     *                                            rgument name
     * @param string $resourceTypeArgName         The ResourceType argument name
     * 
     * @return string The formatted message
     */
    public static function resourcePropertyPropertyKindAndResourceTypeKindMismatch($resourcePropertyKindArgName, $resourceTypeArgName)
    {
        return "The '$resourcePropertyKindArgName' parameter does not match with the type of the resource type in parameter '$resourceTypeArgName'";
    }

    /**
     * The error message to show when tyring to 
     * associate resource set with non-entity
     * 
     * @return The message
     */
    public static function resourceSetContainerMustBeAssociatedWithEntityType()
    {
        return 'The ResourceTypeKind property of a ResourceType instance associated with a ResourceSet must be equal to \'EntityType\'';
    }

    /**
     * The error message to show when IDataServiceQueryProvider2::getExpressionProvider
     * method returns empty or null
     *
     * @return The message
     */
    public static function metadataQueryProviderExpressionProviderMustNotBeNullOrEmpty()
    {
        return 'The value returned by IDataServiceQueryProvider2::getExpressionProvider method must not be null or empty';
    }

    /**
     * The error message to show when IDataServiceQueryProvider2::getExpressionProvider
     * method returns non-object or an object which does not implement IExpressionProvider
     *
     * @return The message
     */
    public static function metadataQueryProviderInvalidExpressionProviderInstance()
    {
    	return 'The value returned by IDataServiceQueryProvider2::getExpressionProvider method must be an implementation of IExpressionProvider';
    }

    /**
     * The error message to show when IDataServiceMetadataProvider::getContainerName 
     * method returns empty container name
     * 
     * @return The message
     */
    public static function metadataQueryProviderWrapperContainerNameMustNotBeNullOrEmpty()
    {
        return 'The value returned by IDataServiceMetadataProvider::getContainerName method must not be null or empty';        
    }

    /**
     * The error message to show when 
     * IDataServiceMetadataProvider::getContainerNamespace 
     * method returns empty container name 
     * 
     * @return The message
     */
    public static function metadataQueryProviderWrapperContainerNamespaceMustNotBeNullOrEmpty()
    {
        return 'The value returned by IDataServiceMetadataProvider::getContainerNamespace method must not be null or empty';
    }

    /**
     * Format a message to show error when 
     * more than one entity set with the same name found 
     * 
     * @param string $entitySetName The name of the entity set
     * 
     * @return string The formatted message
     */
    public static function metadataQueryProviderWrapperEntitySetNameShouldBeUnique($entitySetName)
    {
        return "More than one entity set with the name '$entitySetName' was found. Entity set names must be unique";
    }

    /**
     * Format a message to show error when 
     * more than one entity type with the same name found 
     * 
     * @param string $entityTypeName The name of the entity type
     * 
     * @return string The formatted message
     */
    public static function metadataQueryProviderWrapperEntityTypeNameShouldBeUnique($entityTypeName)
    {
        return "More than one entity type with the name '$entityTypeName' was found. Entity type names must be unique.";
    }

    /**
     * Format a message to show error when IDSMP::getResourceSet 
     * returns inconsistent instance of ResourceSet 
     * 
     * @param string $resourceSetName      Name of the resource set
     * @param string $resourceTypeName     Name of the resource type
     * @param string $resourcePropertyName Name of the navigation property
     * 
     * @return The formatted message
     */
    public static function metadataQueryProviderWrapperIDSMPGetResourceSetReturnsInvalidResourceSet($resourceSetName, $resourceTypeName, $resourcePropertyName)
    {
        return "IDSMP::GetResourceSet retruns invalid instance of ResourceSet when invoked with params {ResourceSet with name $resourceSetName, ResourceType with name $resourceTypeName, ResourceProperty with name $resourcePropertyName}.";
    }

    /**
     * A message to show error when IDSQP::getResourceSet returns non-array
     * 
     * @param string $methodName method name
     * 
     * @return The message
     */
    public static function metadataQueryProviderWrapperIDSQPMethodReturnsNonArray($methodName)
    {
        return "The implementation of the method $methodName must return an array of entities belonging to the requested resource set or an empty array if there is no entities.";
    }

    /**
     * Format a message to show error when IDSMP::getResourceFromResourceSet 
     * returns an instnce which is not an instance of expected entity instance.
     * 
     * @param string $entityTypeName The name of expected entity type.
     * @param string $methodName     Method name
     * 
     * @return The formatted message
     */
    public static function metadataQueryProviderWrapperIDSQPMethodReturnsUnExpectedType($entityTypeName, $methodName)
    {
        return 'The implementation of the method ' . $methodName . ' must return an instance of type described by resource set\'s type(' . $entityTypeName .') or null if resource does not exists';
    }

    /**
     * A message to show error when IDSQP::getResourceFromResourceSet 
     * returns an entity instance with null key properties.
     * 
     * @param string $methodName Method name
     * 
     * @return The message
     */
    public static function metadataQueryProviderWrapperIDSQPMethodReturnsInstanceWithNullKeyProperties($methodName)
    {
        return 'The ' . $methodName . ' implementation returns an entity with null key propert(y|ies)';
    }

    /**
     * A message to show error when IDSQP::getResourceFromResourceSet 
     * returns an entity instance with keys
     * not matching with the expected keys in the uri predicate.
     * 
     * @param string $methodName Method name
     * 
     * @return The message
     */
    public static function metadataQueryProviderWrapperIDSQPMethodReturnsInstanceWithNonMatchingKeys($methodName)
    {
        return 'The ' . $methodName . ' implementation returns an instance with non-matching key';
    }
    
    /**
     * The error message to show for invalid navigation resource type
     * 
     * @return The message
     */
    public function navigationInvalidResourceType()
    {
        return 'Only possible Navigation types are Complex and Entity';
    }

    /**
     * Format a message to show error when actual number of key values given
     * in the key predicate is not matching with the expected number of key values 
     * 
     * @param string $segment       The segment with key predicate in question
     * @param int    $expectedCount The expected number of key values
     * @param int    $actualCount   The actual number of key values
     * 
     * @return string The formatted message
     */
    public static function keyDescriptorKeyCountNotMatching($segment, $expectedCount, $actualCount)
    {
        return "The predicate in the segment '$segment' expect $expectedCount keys but $actualCount provided";
    }

    /**
     * Format a message to show error when a required key is 
     * missing from key predicate of a segment  
     * 
     * @param string $segment      The segment with key predicate in question
     * @param string $expectedKeys The keys expected by the predicate
     * 
     * @return string The formatted message
     */
    public static function keyDescriptorMissingKeys($segment, $expectedKeys)
    {
        return "Missing keys in key predicate for the segment '$segment'. The key predicate expect the keys '$expectedKeys'";
    }

    /**
     * Format a message to show error when type of a key given in the 
     * predicate with named key values does not compatible with the expected type
     * 
     * @param string $segment      The segment with key predicate in question
     * @param string $keyProperty  Name of the key in question
     * @param string $expectedType Expected type of the key
     * @param string $actualType   Actual type of the key
     * 
     * @return string The formatted message
     */
    public static function keyDescriptorInCompatibleKeyType($segment, $keyProperty, $expectedType, $actualType)
    {
        return "Syntax error in the segment '$segment'. The value of key property '$keyProperty' should be of type " . $expectedType . ", given " . $actualType;
    }

    /**
     * Format a message to show error when type of a key given in the predicate
     * with positional key values does not compatible with the expected type
     * 
     * @param String $segment      The segment with key predicate in question
     * @param String $keyProperty  The Key property
     * @param Int    $position     The position of key
     * @param String $expectedType Expected type of the key
     * @param String $actualType   Actual type of the key
     * 
     * @return string The formatted message
     */
    public static function keyDescriptorInCompatibleKeyTypeAtPosition($segment, $keyProperty, $position, $expectedType, $actualType)
    {
        return "Syntax error in the segment '$segment'. The value of key property '$keyProperty' at position $position should be of type " . $expectedType . ", given " . $actualType;
    }

    /**
     * Format a message to show error when trying to access 
     * KeyDescriptor::_validatedNamedValues before 
     * invoking KeyDescriptor::validate function
     * 
     * @return The message
     */
    public static function keyDescriptorValidateNotCalled()
    {
        return "Invoking KeyDescriptor::getValidatedNamedValues requires KeyDescriptor::validate to be called before";
    }

    /**
     * Message to show error when there is a syntax error in the query
     * 
     * @return string The message
     */    
    public static function syntaxError()
    {
        return 'Bad Request - Error in query syntax';
    }

    /**
     * Format a message to show error when given url is malformed
     * 
     * @param string $url The malformed url
     * 
     * @return string The formatted message
     */
    public static function urlMalformedUrl($url)
    {
        return "Bad Request - The url '$url' is malformed";
    }

    /**
     * Format a message to show error when segment with 
     * multiple positional keys present in the request uri
     * 
     * @param string $segment The segment with multiple positional keys
     * 
     * @return string The formatted message
     */
    public static function segmentParserKeysMustBeNamed($segment)
    {
        return "Segments with multiple key values must specify them in 'name=value' form. For the segment $segment use named keys";
    }

    /**
     * Format a message to show error when a leaft segment 
     * ($batch, $value, $metadata, $count, a bag property, 
     * a named media resource or void service operation) is followed by a segment
     * 
     * @param string $leafSegment The leaf segment
     * 
     * @return string The formatted message
     */
    public static function segmentParserMustBeLeafSegment($leafSegment)
    {
        return "The request URI is not valid. The segment '$leafSegment' must be the last segment in the URI because it is one of the following: \$batch, \$value, \$metadata, \$count, a bag property, a named media resource, or a service operation that does not return a value.";
    }

    /**
     * Format a message to show error when a segment follows a post link segment
     * 
     * @param string $postPostLinkSegment The segment following post link segment
     * 
     * @return string The formatted message
     */
    public static function segmentParserNoSegmentAllowedAfterPostLinkSegment($postPostLinkSegment)
    {
        return "The request URI is not valid. The segment '$postPostLinkSegment' is not valid. Since the uri contains the \$links segment, there must be only one segment specified after that.";
    }

    /**
     * Format a message to show error when a segment otherthan 
     * $value is followed by primitive segment
     * 
     * @param string $segment                  The segment follows 
     *                                         primitive property segment 
     * @param string $primitivePropertySegment The primitive property segment
     * 
     * @return string The formatted message
     */
    public static function segmentParserOnlyValueSegmentAllowedAfterPrimitivePropertySegment($segment, $primitivePropertySegment)
    {
        return "The segment '$segment' in the request URI is not valid. Since the segment '$primitivePropertySegment' refers to a primitive type property, the only supported value from the next segment is '\$value'.";
    }

    /**
     * Format a message to show error when try to query a collection segment
     * 
     * @param string $collectionSegment The segment representing collection
     * 
     * @return string The formatted message
     */
    public static function segmentParserCannotQueryCollection($collectionSegment)
    {
        return "The request URI is not valid. Since the segment '$collectionSegment' refers to a collection, this must be the last segment in the request URI. All intermediate segments must refer to a single resource.";
    }

    /**
     * Format a message to show error when a count segment is followed by singleton
     * 
     * @param string $segment The singleton segment
     * 
     * @return string The formatted message
     */
    public static function segmentParserCountCannotFollowSingleton($segment)
    {
        return "The request URI is not valid, since the segment '$segment' refers to a singleton, and the segment '\$count' can only follow a resource collection.";
    }

    /**
     * Format a message to show error when a link segment is 
     * followed by non-entity segment
     * 
     * @param string $segment The segment follows primitive property segment
     * 
     * @return string The formatted message
     */
    public static function segmentParserLinkSegmentMustBeFollowedByEntitySegment($segment)
    {
        return "The request URI is not valid. The segment '$segment' must refer to a navigation property since the previous segment identifier is '\$links'.";
    }

    /**
     * A message to show error when no segment follows a link segment
     * 
     * @return The message
     */
    public static function segmentParserMissingSegmentAfterLink()
    {
        return "The request URI is not valid. There must a segment specified after the '\$links' segment and the segment must refer to a entity resource.";
    }

    /**
     * Format a message to show error when a segment 
     * found on the root which cannot be applied on root 
     * 
     * @param string $segment The segment found
     * 
     * @return string The formatted message
     */
    public static function segmentParserSegmentNotAllowedOnRoot($segment)
    {
        return "The request URI is not valid, the segment '$segment' cannot be applied to the root of the service";
    }

    /**
     * Message to show error when there is a inconsistency while parsing segments
     * 
     * @return string The message
     */
    public static function segmentParserInconsistentTargetKindState()
    {
        return "Paring of segments failed for inconsistent target kind state, contact provider";
    }

    /**
     * Format a message to show error when expecting a 
     * property kind not found while paring segments 
     * 
     * @param string $expectedKind The exptected property kind as string
     * 
     * @return string
     */
    public static function segmentParserUnExpectedPropertyKind($expectedKind)
    {
        return "Paring of segments failed expecting $expectedKind, contact provider";
    }

    /**
     * Format a message to show error when trying to apply count on non-resource 
     * 
     * @param string $segment The non-resource segment
     * 
     * @return string The message
     */
    public static function segmentParserCountCannotBeApplied($segment)
    {
        return "The request URI is not valid, \$count cannot be applied to the segment '$segment' since \$count can only follow a resource segment.";        
    }

    /**
     * Format a message to show error when a resource not found
     * 
     * @param string $segment The segment follows primitive property segment
     * 
     * @return string The formatted message
     */
    public static function uriProcessorResourceNotFound($segment)
    {
        return "Resource not found for the segment '$segment'";
    }

    /**
     * The message to show error when trying to 
     * access a resourceset which is forbidden
     * 
     * @return string The message
     */
    public static function uriProcessorForbidden()
    {
        return 'Forbidden';
    }

    /**
     * A message to show error when 
     * IDataServiceMetadataProvider::GetResourceAssociationSet() returns different
     * AssociationSet when called with 'ResourceAssociationSetEnd' instances that 
     * are expected to the ends of same association set.
     *   
     * @return string The error message
     */
    public static function metadataAssociationTypeSetBidirectionalAssociationMustReturnSameResourceAssociationSetFromBothEnd()
    {
        return 'When the ResourceAssociationSet is bidirectional, IDataServiceMetadataProvider::getResourceAssociationSet() must return the same ResourceAssociationSet when call from both ends.';
    }

    /**
     * Format a message to show error when multiple ResourceAssociationSets
     * have a ResourceAssociationSetEnd referring to the 
     * same EntitySet through the same AssociationType.
     * 
     * @param string $resourceSet1Name Name of the first association set
     * @param string $resourceSet2Name Name of the second association set
     * @param string $entitySetName    Name of the entity set
     * 
     * @return string The formatted message
     */
    public static function metadataAssociationTypeSetMultipleAssociationSetsForTheSameAssociationTypeMustNotReferToSameEndSets($resourceSet1Name, $resourceSet2Name, $entitySetName)
    {
        return "ResourceAssociationSets '$resourceSet1Name' and '$resourceSet2Name' have a ResourceAssociationSetEnd referring to the same EntitySet '$entitySetName' through the same AssociationType. Make sure that if two or more AssociationSets refer to the same AssociationType, the ends must not refer to the same EntitySet. (this could happen if multiple entity sets have entity types that have a common ancestor and the ancestor has a property of derived entity types)";
    }

    /**
     * Format a message to show error when IDSMP::getDerivedTypes returns a
     * type which is not null or array of ResourceType
     * 
     * @param string $resourceTypeName Resource type name
     * 
     * @return string The formatted message
     */
    public static function metadataAssociationTypeSetInvalidGetDerivedTypesReturnType($resourceTypeName)
    {
        return "Return type of IDSMP::getDerivedTypes should be either null or array of 'ResourceType', check implementation of IDSMP::getDerivedTypes for the resource type '$resourceTypeName'.";        
    }

    /**
     * Format a message to show error when entity type of an entity set has a
     * derived type with named stream property(ies).
     * 
     * @param string $entitySetName   The entity set name
     * @param string $derivedTypeName The full name of the derived type
     * 
     * @return string The formatted message
     */
    public static function metadataResourceTypeSetNamedStreamsOnDerivedEntityTypesNotSupported($entitySetName, $derivedTypeName)
    {
        return "Named streams are not supported on derived entity types. Entity Set '$entitySetName' has a instance of type '$derivedTypeName', which is an derived entity type and has named streams. Please remove all named streams from type '$derivedTypeName'.";
    }

    /**
     * Format a message to show error when complex type having derived type
     * is used as item type of a bag property
     * 
     * @param string $complexTypeName The name of the bag's complex type
     * having derived type
     * 
     * @return string The formatted message
     */
    public static function metadataResourceTypeSetBagOfComplexTypeWithDerivedTypes($complexTypeName)
    {
        return "Complex type '$complexTypeName' has derived types and is used as the item type in a bag. Only bags containing complex types without derived types are supported.";
    }

    /**
     * Message to show error when expecting entity or 
     * complex type, but a different type found 
     * 
     * @return The error message
     */
    public static function metadataWriterExpectingEntityOrComplexResourceType() 
    {
        return 'Unexpected resource type found, expecting either ResourceTypeKind::ENTITY or ResourceTypeKind::COMPLEX';
    }

    /**
     * Format a message to show error when no association set 
     * found for a navigation property
     * 
     * @param string $navigationPropertyName The name of the navigation property.
     * @param string $resourceTypeName       The resource type on which the
     *                                       navigation property is defined.
     * 
     * @return string The formatted message
     */
    public static function metadataWriterNoResourceAssociationSetForNavigationProperty($navigationPropertyName, $resourceTypeName)
    {
        return "No visible ResourceAssociationSet found for navigation property '$navigationPropertyName' on type '$resourceTypeName'. There must be at least one ResourceAssociationSet for each navigation property.";
    }

    /**
     * Message to show error when type of 'ExpandedProjectionNode::addNode' 
     * parameter is neither ProjectionNode nor ExpandedProjectionNode
     * 
     * @return string The error message
     */
    public static function expandedProjectionNodeArgumentTypeShouldbeProjection()
    {
        return 'The argument to ExpandedProjectionNode::addNode should be either ProjectionNode or ExpandedProjectionNode';
    }

    /**
     * Format a message to show error when parser failed to 
     * resolve a property in select or expand path
     * 
     * @param string  $resourceTypeName The name of resource type
     * @param string  $propertyName     Sub path segment, that comes after
     *                                  the segment of type  $resourceTypeName
     * @param boolean $isSelect         True if error found while parsing select
     *                                  clause, false for expand
     * 
     * @return string The formatted message
     */
    public static function expandProjectionParserPropertyNotFound($resourceTypeName, $propertyName, $isSelect)
    {
        $clause = $isSelect ? 'select' : 'expand';
        return  "Error in the $clause clause. Type '$resourceTypeName' does not have a property named '$propertyName'.";
    }

    /**
     * Format a message to show error when expand path 
     * contain non-navigation property.
     * 
     * @param string $resourceTypeName The resource type name
     * @param string $propertyName     The proeprty name
     * 
     * @return string The formatted message
     */
    public static function expandProjectionParserExpandCanOnlyAppliedToEntity($resourceTypeName, $propertyName)
    {
        return  "Error in the expand clause. Expand path can contain only navigation property, the property '$propertyName' defined in '$resourceTypeName' is not a navigation property";
    }

    /**
     * Format a message to show error when a primitive property is used as
     * navigation property in select clause
     * 
     * @param string $resourceTypeName     The resource type on which the 
     *                                     primitive property defined
     * @param string $primitvePropertyName The primitive property used as 
     *                                     navigation property
     * 
     * @return string The formatted message
     */
    public static function expandProjectionParserPrimitivePropertyUsedAsNavigationProperty($resourceTypeName, $primitvePropertyName)
    {
        return "Property '$primitvePropertyName' on type '$resourceTypeName' is of primitive type and cannot be used as a navigation property.";
    }

    /**
     * Format a message to show error when a complex type is used as
     * navigation property in select clause
     * 
     * @param string $resourceTypeName The name of the resource type on which
     *                                 complex property is defined
     * @param string $complextTypeName The name of complex type     
     * 
     * @return string The formatted message
     */
    public static function expandProjectionParserComplexPropertyAsInnerSelectSegment($resourceTypeName, $complextTypeName)
    {
        return "select doesn't support selection of properties of complex type. The property '$complextTypeName' on type '$resourceTypeName' is a complex type.";        
    }

    /**
     * Format a message to show error when a bag type is used as 
     * navigation property in select clause
     * 
     * @param string $resourceTypeName The name of the resource type on which 
     *                                 bag property is defined
     * @param string $bagPropertyName  The name of the bag property
     * 
     * @return string The formatted message
     */
    public static function expandProjectionParserBagPropertyAsInnerSelectSegment($resourceTypeName, $bagPropertyName)
    {
        return "The selection from property '$bagPropertyName' on type '$resourceTypeName' is not valid. The select query option does not support selection items from a bag property.";               
    } 

    /**
     * Message to show error when parser come across a type which is expected
     * to be Entity type, but actually it is not
     * 
     * @return The message
     */
    public static function expandProjectionParserUnexpectedPropertyType()
    {
        return 'Property type unexpected, expecting navigation property (ResourceReference or ResourceTypeReference).';
    }

    /**
     * Format a message to show error when found selection traversal of a
     * navigation property with out expansion
     * 
     * @param string $propertyName The navigation property in select path
     * which is not in expand path
     * 
     * @return string The formatted message
     */
    public static function expandProjectionParserPropertyWithoutMatchingExpand($propertyName)
    {
        return 'Only navigation properties specified in expand option can be travered in select option,In order to treaverse the navigation property \'' . $propertyName . '\', it should be first expanded';
    }

    /**
     * Message to show error when the orderByPathSegments argument found as
     * not a non-empty array
     * 
     * @return The message
     */
    public static function orderByInfoPathSegmentsArgumentShouldBeNonEmptyArray()
    {
        return 'The argument orderByPathSegments should be a non-empty array';
    }

    /**
     * Message to show error when the navigationPropertiesUsedInTheOrderByClause
     * argument found as neither null or a non-empty array
     * 
     * @return The message
     */
    public static function orderByInfoNaviUSedArgumentShouldBeNullOrNonEmptyArray()
    {
        return 'The argument navigationPropertiesUsedInTheOrderByClause should be either null or a non-empty array';
    }

    /**
     * Message to show error when the orderBySubPathSegments argument found as 
     * not a non-empty array
     * 
     * @return The message
     */
    public static function orderByPathSegmentOrderBySubPathSegmentArgumentShouldBeNonEmptyArray()
    {
        return 'The argument orderBySubPathSegments should be a non-empty array';
    }

    /**
     * Format a message to show error when parser failed to resolve a 
     * property in orderby path
     * 
     * @param string $resourceTypeName The name of resource type
     * @param string $propertyName     Sub path segment, that comes after the 
     *                                 segment of type  $resourceTypeName     
     * 
     * @return string The formatted message
     */
    public static function orderByParserPropertyNotFound($resourceTypeName, $propertyName)
    {        
        return  "Error in the 'orderby' clause. Type '$resourceTypeName' does not have a property named '$propertyName'.";
    }

    /**
     * Format a message to show error when found a bag property used 
     * in orderby clause 
     * 
     * @param string $bagPropertyName The name of the bag property
     * 
     * @return string The formatted message
     */
    public static function orderByParserBagPropertyNotAllowed($bagPropertyName)
    {
        return "orderby clause does not support Bag property in the path, the property '$bagPropertyName' is a bag property";    
    }

    /**
     * Format a message to show error when found a primitve property used as 
     * intermediate segment in orderby clause
     * 
     * @param string $propertyName The name of primitive property
     * 
     * @return string The formatted message
     */
    public static function orderByParserPrimitiveAsIntermediateSegment($propertyName)
    {
        return "The primitive property '$propertyName' cannnot be used as intermediate segment, it should be last segment";
    }

    /**
     * Format a message to show error when found binary property used as sort key
     * 
     * @param string $binaryPropertyName The name of binary property
     * 
     * @return string The formatted message
     */
    public static function orderbyParserSortByBinaryPropertyNotAllowed($binaryPropertyName)
    {
        return "Binary property is not allowed in orderby clause, '$binaryPropertyName'";
    }

    /**
     * Format a message to show error when found a resource set reference 
     * property in the oriderby clause
     * 
     * @param string $propertyName The name of resource set reference property
     * @param string $definedType  Defined type
     * 
     * @return string The formatted message
     */
    public static function orderbyParserResourceSetReferenceNotAllowed($propertyName, $definedType)
    {
        return "Navigation property points to a collection cannot be used in orderby clause, The property '$propertyName' defined on type '$definedType' is such a property";
    }

    /**
     * Format a message to show error when a navigation property is used as
     * sort key in orderby clause
     * 
     * @param string $navigationPropertyName The name of the navigation property
     * 
     * @return string The formatted message
     */
    public static function orderByParserSortByNavigationPropertyIsNotAllowed($navigationPropertyName)
    {
        return "Navigation property cannot be used as sort key, '$navigationPropertyName'";
    }

    /**
     * Format a message to show error when a complex property is used as
     * sort key in orderby clause
     * 
     * @param string $complexPropertyName The name of the complex property
     * 
     * @return string The formatted message
     */
    public static function orderByParserSortByComplexPropertyIsNotAllowed($complexPropertyName)
    {
        return "Complex property cannot be used as sort key, the property '$complexPropertyName' is a complex property";
    }

    /**
     * Message to show error when orderby parser found unexpected state
     * 
     * @return string The error message
     */
    public static function orderByParserUnExpectedState()
    {
        return 'Unexpected state while parsing orderby clause';
    }

    /**
     * Message to show error when orderby parser come across a type
     * which is not expected
     * 
     * @return The message
     */
    public static function orderByParserUnexpectedPropertyType()
    {
        return 'Property type unexpected';
    }

    /**
     * Format a message to show error when the orderby parser fails to
     * create an instance of request uri resource type
     * 
     * @return string The formatted message
     */
    public static function orderByParserFailedToCreateDummyObject()
    {
        return 'OrderBy Parser failed to create dummy object from request uri resource type';
    }

    /**
     * Format a message to show error when orderby parser failed to 
     * access some of the properties of dummy object
     * 
     * @param string $propertyName     Property name
     * @param string $parentObjectName Parent object name
     * 
     * @return string The formatted message
     */
    public static function orderByParserFailedToAccessOrInitializeProperty($propertyName, $parentObjectName)
    {
        return "OrderBy parser failed to access or initialize the property $propertyName of $parentObjectName";
    }

    /**
     * Format a message to show error when data service failed to
     * access some of the properties of dummy object
     * 
     * @param string $propertyName     Property name
     * @param string $parentObjectName Parent object name
     * 
     * @return string The formatted message
     */
    public static function dataServiceFailedToAccessProperty($propertyName, $parentObjectName)
    {
        return "Data Service failed to access or initialize the property $propertyName of $parentObjectName";
    }

    /**
     * Message to show error when found empty anscestor list.
     * 
     * @return The message
     */
    public static function orderByLeafNodeArgumentShouldBeNonEmptyArray()
    {
        return 'There should be atleast one anscestor for building the sort function';
    }

    /**
     * Format a message to show error when found a invalid property name
     * 
     * @param string $resourceTypeName The name of the resource type 
     * @param string $propertyName     The name of the property
     * 
     * @return string The formatted message
     */
    public static function badRequestInvalidPropertyNameSpecified($resourceTypeName, $propertyName)
    {
        return "Error processing request stream. The property name '$propertyName' specified for type '$resourceTypeName' is not valid. (Check the resource set of the navigation property '$propertyName' is visible)";
    }

    /**
     * Message to show error when parameter collection to
     * AnonymousFunction constructor includes parameter that does not start with '$'
     * 
     * @return string The message
     */
    public static function anonymousFunctionParameterShouldStartWithDollorSymbol()
    {
        return 'The parameter names in parameter array should start with dollor symbol';
    }

    /**
     * Format a message to show error when skiptoken parser fails
     * to parse due to syntax error.
     * 
     * @param string $skipToken Skip token
     * 
     * @return string The formatted message
     */
    public static function skipTokenParserSyntaxError($skipToken)
    {
        return "Bad Request - Error in the syntax of skiptoken '$skipToken'";        
    }

    /**
     * Message to show error when orderByInfo argument to SkipTokenParser is
     * non null and not an instance of OrderByInfo
     * 
     * @return string The message
     */
    public static function skipTokenParserUnexpectedTypeOfOrderByInfoArg()
    {
        return 'The argument orderByInfo should be either null ot instance of OrderByInfo class';
    }

    /**
     * Format a message to show error when number of keys in the 
     * skiptoken does not matches with the number of keys required for ordering.
     * 
     * @param int    $skipTokenValuesCount Number of keys in the skiptoken
     * @param string $skipToken            The skiptoken as string
     * @param int    $expectedCount        Expected number of skiptoken keys
     * 
     * @return string The formatted message
     */
    public static function skipTokenParserSkipTokenNotMatchingOrdering($skipTokenValuesCount, $skipToken, $expectedCount)
    {
        return "The number of keys '$skipTokenValuesCount' in skip token with value '$skipToken' did not match the number of ordering constraints '$expectedCount' for the resource type.";
    }

    /**
     * Format a message to show error when skiptoken parser 
     * found null value for key. 
     * 
     * @param string $skipToken The skiptoken as string
     * 
     * @return string The formatted message
     */
    public static function skipTokenParserNullNotAllowedForKeys($skipToken)
    {
        return "The skiptoken value $skipToken contain null value for key";        
    }

    /**
     * Format a message to show error when skiptoken parser found values in
     * skiptoken which is not compatible with the
     * type of corrosponding orderby constraint.
     * 
     * @param string $skipToken                   Skip token
     * @param string $expectedTypeName            Expected type name
     * @param int    $position                    Position
     * @param string $typeProvidedInSkipTokenName The type provided in
     *                                            skip token name
     * 
     * @return string The formatted message
     */
    public static function skipTokenParserInCompatibleTypeAtPosition($skipToken, $expectedTypeName, $position, $typeProvidedInSkipTokenName)
    {
        return "The skiptoken value '$skipToken' contain a value of type '$typeProvidedInSkipTokenName' at position $position which is not compatible with the type '$expectedTypeName' of corrosponding orderby constraint.";
    }

    /**
     * Format a message to show error when one of the argument orderByPaths or
     * orderByValues is set and not both.
     * 
     * @param string $orderByPathsVarName  Name of the argument 
     *                                     holding orderByPathSegment
     * @param string $orderByValuesVarName Name of the argument holding 
     *                                     skip token values corrosponding 
     *                                     to orderby paths
     * 
     * @return string The formatted message
     */
    public static function skipTokenInfoBothOrderByPathAndOrderByValuesShouldBeSetOrNotSet($orderByPathsVarName, $orderByValuesVarName)
    {
        return "Either both the arguments $orderByPathsVarName and $orderByValuesVarName should be null or not-null";
    }

    /**
     * Format a message to show error when internalSkipTokenInfo failed to 
     * access some of the properties of key object
     *
     * @param string $propertyName Property name
     * 
     * @return string The formatted message
     */
    public static function internalSkipTokenInfoFailedToAccessOrInitializeProperty($propertyName)
    {
        return "internalSkipTokenInfo failed to access or initialize the property $propertyName";
    }

    /**
     * Format a message to show error when found a non-array passed to 
     * InternalSkipTokenInfo::search function.
     * 
     * @param string $argumentName The name of the argument expected to be array
     * 
     * @return string The formatted message
     */
    public static function internalSkipTokenInfoBinarySearchRequireArray($argumentName)
    {
        return "The argument '$argumentName' should be an array to perfrom binary search";
    }

    /**
     * Format a message to show error when client requested version is 
     * lower than the version required to intercept the response.
     * 
     * @param string $requestedVersion The client requested version.
     * @param string $requiredVersion  The minimum version required to 
     *                                 intercept the response.
     * 
     * @return string The formatted message
     */
    public static function requestDescriptionDataServiceVersionTooLow($requestedVersion, $requiredVersion)
    {
        return "Request version '$requestedVersion' is not supported for the request payload. The only supported version is '$requiredVersion'.";
    }

    /**
     * Format a message to show error when version required to intercept 
     * the response is greater than the configured maximum protocol version.
     *
     * @param string $requiredVersion   Required version
     * @param string $configuredVersion Configured version
     * 
     * @return string The formatted message
     */
    public static function requestDescriptionResponseVersionIsBiggerThanProtocolVersion($requiredVersion, $configuredVersion)    
    {
        return "The response requires that version $requiredVersion of the protocol be used, but the MaxProtocolVersion of the data service is set to $configuredVersion.";
    }

    /**
     * Format a message to show error when value of DataServiceVersion or 
     * MaxDataServiceVersion is invaild.
     * 
     * @param string $versionAsString String value of the version
     * @param string $headerName      Header name
     * 
     * @return string The formatted message
     */
    public static function requestDescriptionInvalidVersionHeader($versionAsString, $headerName)
    {
        return "The header $headerName has malformed version value $versionAsString";
    }

    /**
     * Format a message to show error when value of DataServiceVersion or
     * MaxDataServiceVersion is invaild.
     * 
     * @param string $requestHeaderName Name of the request header
     * @param string $requestedVersion  Requested version
     * @param string $availableVersions Available versions
     * 
     * @return string The formatted message
     */
    public static function requestDescriptionUnSupportedVersion($requestHeaderName, $requestedVersion, $availableVersions) 
    {
        return "The version value $requestedVersion in the header $requestHeaderName is not supported, available versions are $availableVersions";
    }

    /**
     * Format a message to show error when the requested uri is not 
     * based on the configured base service uri.
     * 
     * @param string $requestUri The uri requested by the client.
     * @param string $serviceUri The base service uri.
     * 
     * @return string The formatted message
     */
    public static function uriProcessorRequestUriDoesNotHaveTheRightBaseUri($requestUri, $serviceUri)
    {
        return "The URI '$requestUri' is not valid since it is not based on '$serviceUri'";
    }

    /**
     * Message to show error when query prcocessor found 
     * invalid value for $format option.
     * 
     * @return string The message
     */
    public static function queryProcessorInvalidValueForFormat()
    {
        return 'Invalid $format query option - the only acceptable values are "json" and "atom"';
    }

    /**
     * Message to show error when query processor found odata query option 
     * in the request uri which is not applicable for the
     * resource targetted by the resource path.  
     * 
     * @return string The message
     */
    public static function queryProcessorNoQueryOptionsApplicable()
    {
        return 'Query options $select, $expand, $filter, $orderby, $inlinecount, $skip, $skiptoken and $top are not supported by this request method or cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found $filter option in the 
     * request uri but is not applicable for the resource targetted by the 
     * resource path.
     * 
     * @return string The message
     */
    public static function queryProcessorQueryFilterOptionNotApplicable()
    {
        return 'Query option $filter cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found any $orderby, 
     * $inlinecount, $skip or $top options in the request uri but is not 
     * applicable for the resource targetted by the resource path.
     * 
     * @return string The message
     */
    public static function queryProcessorQuerySetOptionsNotApplicable()
    {
        return 'Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found skiptoken option in the 
     * request uri but is not applicable for the resource targetted by the 
     * resource path.
     * 
     * @return string The message
     */
    public static function queryProcessorSkipTokenNotAllowed()
    {
        return 'Query option $skiptoken cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found $expand option in the 
     * request uri but is not applicable for the resource targetted by the 
     * resource path.
     * 
     * @return string The message
     */
    public static function queryProcessorQueryExpandOptionNotApplicable()
    {
        return 'Query option $expand cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found usage of $inline count
     * option for a resource path ending with $count
     * 
     * @return string The message
     */
    public static function queryProcessorInlineCountWithValueCount()
    {
        return '$inlinecount cannot be applied to the resource segment $count';
    }

    /**
     * Message to show error when value of $inlinecount option found invalid.
     * 
     * @return string The message
     */
    public static function queryProcessorInvalidInlineCountOptionError()
    {
        return 'Unknown $inlinecount option, only "allpages" and "none" are supported';
    }

    /**
     * Format a message to show error when query processor found invalid 
     * value for a query option.
     * 
     * @param string $argName  The name of the argument
     * @param string $argValue The value of the argument
     * 
     * @return string The formatted message
     */
    public static function queryProcessorIncorrectArgumentFormat($argName, $argValue)
    {
        return "Incorrect format for $argName argument '$argValue'";
    }

    /**
     * Format a message to show error when query processor found $skiptoken
     * in the request uri targetting to a resource for which paging is not
     * enabled.
     * 
     * @param string $resourceSetName The name of the resource set.
     * 
     * @return string The formatted message
     */
    public static function queryProcessorSkipTokenCannotBeAppliedForNonPagedResourceSet($resourceSetName)
    {
        return "\$skiptoken cannot be applied to the resource set '$resourceSetName', since paging is not enabled for this resource set";
    }

    /**     
     * Format a message to show error when query processor found $select
     * or $expand which cannot be applied to resource targetted by the
     * request uri.
     * 
     * @param string $queryItem Query item
     * 
     * @return string The formatted message
     */
    public static function queryProcessorSelectOrExpandOptionNotApplicable($queryItem)
    {
        return "Query option $queryItem cannot be applied to the requested resource";
    }

    /**
     * Message to show error when query processor found $select clause but which is
     * disabled by the service developer.
     * 
     * @return string The message
     */
    public static function dataServiceConfigurationProjectionsNotAccepted()
    {
        return 'The ability to use the $select query option to define a projection in a data service query is disabled. To enable this functionality, call DataServiceConfiguration::setAcceptProjectionRequests method with argument as true.';
    }

    /**
     * Message to show error when the data service class does not 
     * implement IServiceProvider 
     * 
     * @return string The message
     */
    public static function dataServiceNotImplementsIServiceProvider()
    {
        return 'The data service class must implement IServiceProvider interface';
    }

    /**
     * Message to show error when IServiceProvider.GetService return null for 
     * IDataServiceMetadataProvider and IDataServiceQueryProvider.
     * 
     * @return string The message
     */
    public static function dataServiceMetadataQueryProviderNull()
    {
        return 'For custom providers, GetService should not return null for both IDataServiceMetadataProvider and IDataServiceQueryProvider types.';
    }

    /**
     * Message to show error when IServiceProvider.GetService 
     * return invalid object for IDataServiceMetadataProvider.
     * 
     * @return string The message
     */
    public static function dataServiceInvalidMetadataInstance()
    {
        return 'IServiceProvider.GetService for IDataServiceMetadataProvider returns invalid object.';
    }

    /**
     * Message to show error when IServiceProvider.GetService 
     * return invalid object for IDataServiceQueryProvider.
     * 
     * @return string The message
     */
    public static function dataServiceInvalidQueryInstance()
    {
        return 'IServiceProvider.GetService for IDataServiceQueryProvider returns invalid object.';
    }

    /**
     * Message to show error when IDataServiceStreamProvider.GetStreamETag returns invalid etag value.
     * 
     * @return string The message
     */
    public static function dataServiceStreamProviderWrapperGetStreamETagReturnedInvalidETagFormat()
    {
        return 'The method \'IDataServiceStreamProvider.GetStreamETag\' returned an entity tag with invalid format.';
    }

    /**
     * Message to show error when IDataServiceStreamProvider.GetStreamContentType returns null or empty string.
     * 
     * @return string The message
     */
    public static function dataServiceStreamProviderWrapperGetStreamContentTypeReturnsEmptyOrNull()
    {
        return 'The method \'IDataServiceStreamProvider.GetStreamContentType\' must not return a null or empty string.';
    }

    /**
     * Message to show error when IDataServiceStreamProvider.GetReadStream non stream.
     * 
     * @return string The message
     */
    public static function dataServiceStreamProviderWrapperInvalidStreamFromGetReadStream()
    {
        return 'IDataServiceStreamProvider.GetReadStream() must return a valid readable stream.';
    }

    /**
     * Message to show error when IDataServiceStreamProvider.GetReadStreamUri returns relative uri.
     * 
     * @return string The message
     */
    public static function dataServiceStreamProviderWrapperGetReadStreamUriMustReturnAbsoluteUriOrNull()
    {
        return 'The method IDataServiceStreamProvider.GetReadStreamUri must return an absolute Uri or null.';
    }

    /**
     * Message to show error when data service does not implement IDSSP or IDSSP2 interfaces.
     * 
     * @return string The message
     */
    public static function dataServiceStreamProviderWrapperMustImplementIDataServiceStreamProviderToSupportStreaming()
    {
        return 'To support streaming, the data service must implement IServiceProvider::GetService() to return an implementation of IDataServiceStreamProvider or IDataServiceStreamProvider2';
    }

    /**
     * Message to show error when try to configure data service version as 2 for which named stream is defined.
     * 
     * @return string The message
     */
    public static function dataServiceStreamProviderWrapperMaxProtocolVersionMustBeV3OrAboveToSupportNamedStreams()
    {
        return 'To support named streams, the MaxProtocolVersion of the data service must be set to DataServiceProtocolVersion.V3 or above.';
    }

    /**
     * Message to show error when data service does not provide implementation of IDDSP2 for which named stream is defined.
     * 
     * @return string The message
     */
    public static function dataServiceStreamProviderWrapperMustImplementDataServiceStreamProvider2ToSupportNamedStreams()
    {
        return 'To support named streams, the data service must implement IServiceProvider.GetService() to return an implementation of IDataServiceStreamProvider2 or the data source must implement IDataServiceStreamProvider2.';
    }

    /**
     * Message to show error when IDSSP/IDSSP2 implementation methods try to set etag or content type.
     * 
     * @param string $methodName Method name
     * 
     * @return string The formatted message
     */
    public static function dataServiceStreamProviderWrapperMustNotSetContentTypeAndEtag($methodName)
    {
        return "The method $methodName must not set the HTTP response headers 'Content-Type' and 'ETag'";
    }

    /**
     * Message to show error when IServiceProvider.GetService implementation returns invaild object when request for 
     * IDataServiceStreamProvider implementation.
     * 
     * @return string The message
     */
    public static function dataServiceStreamProviderWrapperInvalidStreamInstance()
    {
        return 'return \'IServiceProvider.GetService for IDataServiceStreamProvider returns invalid object.';
    }

    /**
     * Message to show error when IServiceProvider.GetService implementation returns invaild object when request for 
     * IDataServiceStreamProvider2 implementation.
     * 
     * @return string The message
     */
    public static function dataServiceStreamProviderWrapperInvalidStream2Instance()
    {
        return 'return \'IServiceProvider.GetService for IDataServiceStreamProvider2 returns invalid object.';
    }

    /**
     * Format a message to show error when object model serializer found
     * in-inconsistency in resource type and current runtime information.
     *
     * @param string $typeName The name of the resource type for which
     * serializer found inconsistency.
     * 
     * @return string The formatted message.
     */
    public static function badProviderInconsistentEntityOrComplexTypeUsage($typeName)
    {
        return "Internal Server Error. The type '$typeName' has inconsistent metadata and runtime type info.";
    }

    /**
     * Format a message to show error when object model serializer
     * found null key value. 
     * 
     * @param string $resourceTypeName The name of the resource type of the
     *                                 instance with null key. 
     * @param string $keyName          Name of the key with null value.
     * 
     * @return string The formatted message.
     */
    public static function badQueryNullKeysAreNotSupported($resourceTypeName, $keyName)
    {
        return "The serialized resource of type $resourceTypeName has a null value in key member '$keyName'. Null values are not supported in key members.";
    }

    /**
     * Format a message to show error when object model serializer failed to
     * access some of the properties of a type instance.
     * 
     * @param string $propertyName     The name of the property in question.
     * @param string $parentObjectName The entity instance in question.
     * 
     * @return string The formatted message
     */
    public static function objectModelSerializerFailedToAccessProperty($propertyName, $parentObjectName)
    {
        return "objectModelSerializer failed to access or initialize the property $propertyName of $parentObjectName, Please contact provider";
    }

    /**
     * Format a message to show error when object model serializer found loop
     * a in complex property instance.
     * 
     * @param string $complexPropertyName The name of the complex property with loop.
     * 
     * @return string The formatted message
     */
    public static function objectModelSerializerLoopsNotAllowedInComplexTypes($complexPropertyName)
    {
        return 'A circular loop was detected while serializing the property \''. $complexPropertyName . '\'. You must make sure that loops are not present in properties that return a bag or complex type.';
    }

    /**
     * Message to show error when the requested resource instance
     * cannot be serialized to requested format. 
     * 
     * @return string The message
     */
    public static function dataServiceExceptionUnsupportedMediaType()
    {
        return 'Unsupported media type requested.';
    }

    /**
     * Message to show error when media type header found malformed.
     * 
     * @return string The message
     */
    public static function httpProcessUtilityMediaTypeRequiresSemicolonBeforeParameter()
    {
        return "Media type requires a ';' character before a parameter definition.";
    }

    /**
     * Message to show error when media header value misses type segment.
     * 
     * @return The message
     */
    public static function httpProcessUtilityMediaTypeUnspecified()
    {
        return 'Media type is unspecified.';
    }

    /**
     * Message to show error when media header value misses slash after type.
     * 
     * @return The message
     */
    public static function httpProcessUtilityMediaTypeRequiresSlash()
    {
        return "Media type requires a '/' character.";
    }

    /**
     * Message to show error when media header value misses sub-type.
     * 
     * @return The message
     */
    public static function httpProcessUtilityMediaTypeRequiresSubType()
    {
        return 'Media type requires a subtype definition.';        
    }

    /**
     * Message to show error when media type misses parameter value.
     * 
     * @return The message
     */
    public static function httpProcessUtilityMediaTypeMissingValue()
    {
        return 'Media type is missing a parameter value.';
    }

    /**
     * Format a message to show error when media type parameter value contain escape
     * character but the value is not quoted.
     * 
     * @param string $parameterName Name of the parameter
     * 
     * @return The formatted message.
     */
    public static function httpProcessUtilityEscapeCharWithoutQuotes($parameterName)
    {
        return "Value for MIME type parameter '$parameterName' is incorrect because it contained escape characters even though it was not quoted.";
    } 

    /**
     * Format a message to show error when media type parameter value contain escape
     * character but the value at the end.
     * 
     * @param string $parameterName Name of the parameter
     * 
     * @return The formatted message.
     */
    public static function httpProcessUtilityEscapeCharAtEnd($parameterName)
    {
        return "Value for MIME type parameter '$parameterName' is incorrect because it terminated with escape character. Escape characters must always be followed by a character in a parameter value.";
    }

    /**
     * Format a message to show error when media parameter
     * value misses closing bracket.
     * 
     * @param string $parameterName Name of the parameter
     * 
     * @return The formatted message.
     */
    public static function httpProcessUtilityClosingQuoteNotFound($parameterName)
    {
        return "Value for MIME type parameter '$parameterName' is incorrect because the closing quote character could not be found while the parameter value started with a quote character.";
    }

    /**
     * Message to show error when the header found malformed. 
     * 
     * @return The formatted message.
     */
    public static function httpProcessUtilityMalformedHeaderValue()
    {
        return 'Malformed value in request header.';
    }

    /**
     * Message to show error when request contains eTag headers
     * but targetted resource type does not have eTag properties defined.
     * 
     * @return The message
     */
    public static function dataServiceNoETagPropertiesForType()
    {
        return 'If-Match or If-None-Match headers cannot be specified if the target type does not have etag properties defined.';        
    }

    /**
     * Message to show error when data service found the request eTag
     * does not match with entry eTag.
     * 
     * @return string The message
     */
    public static function dataServiceETagValueDoesNotMatch()
    {
        return 'The etag value in the request header does not match with the current etag value of the object.';        
    }

    /**
     * Format a message to show error when request eTag header has been 
     * specified but eTag is not allowed for the targetted resource.
     * 
     * @param string $uri Url
     * 
     * @return string The formatted message
     */
    public static function dataServiceETagCannotBeSpecified($uri)
    {
        return "If-Match or If-None-Match HTTP headers cannot be specified since the URI '$uri' refers to a collection of resources or has a \$count or \$link segment or has a \$expand as one of the query parameters.";        
    }

    /**
     * Message to show error when data service found presence of both
     * If-Match and if-None-Match headers.
     * 
     * @return string The message
     */
    public static function dataServiceBothIfMatchAndIfNoneMatchHeaderSpecified()
    {
        return "Both If-Match and If-None-Match HTTP headers cannot be specified at the same time. Please specify either one of the headers or none of them.";        
    }

    /**
     * Message to show error when data service found eTag
     * header for non-existing resource. 
     * 
     * @return string The message
     */
    public static function dataServiceETagNotAllowedForNonExistingResource()
    {
        return 'The resource targetted by the request does not exists, eTag header is not allowed for non-existing resource.';
    }

    /**
     * Message to show error when data service found a request method other than GET.
     * 
     * @param string $method Request method
     * 
     * @return string The formatted message
     */
    public static function dataServiceOnlyReadSupport($method)
    {
        return "This release of library support only GET (read) request, received a request with method $method";
    }

    /**
     * Format a message to show error when the uri that look like pointing to 
     * MLE but actaully it is not.
     * 
     * @param string $uri Url pointing to MLE
     * 
     * @return string The formatted message
     */
    public static function badRequestInvalidUriForMediaResource($uri)
    {
        return "The URI '$uri' is not valid. The segment before '\$value' must be a Media Link Entry or a primitive property.";
    }

    /**
     * Format a message to show error when library found non-odata
     * query option begins with $ character.
     * 
     * @param string $optionName Name of the query option
     * 
     * @return string The formatted message.
     */
    public static function dataServiceHostNonODataOptionBeginsWithSystemCharacter($optionName)
    {
        return "The query parameter '$optionName' begins with a system-reserved '$' character but is not recognized.";
    }

    /**
     * Format a message to show error when library found 
     * a query option without value. 
     * 
     * @param string $optionName Name of the query option
     * 
     * @return string The formatted message.
     */
    public static function dataServiceHostODataQueryOptionFoundWithoutValue($optionName)
    {
        return "Query parameter '$optionName' is specified, but it should be specified with value.";
    }

    /**
     * Format a message to show error when library found 
     * a query option specified multiple times. 
     * 
     * @param string $optionName Name of the query option
     * 
     * @return string The formatted message.
     */
    public static function dataServiceHostODataQueryOptionCannotBeSpecifiedMoreThanOnce($optionName)
    {
        return "Query parameter '$optionName' is specified, but it should be specified exactly once.";
    }

    /**
     * Message to show error when baseUrl given in service.config.xml is invalid.
     * 
     * @param boolean $notEndWithSvcOrHasQuery Base url end with svc or not
     * 
     * @return string The message.
     */
    public static function dataServiceHostMalFormedBaseUriInConfig($notEndWithSvcOrHasQuery = false)
    {
        if ($notEndWithSvcOrHasQuery) {
            'Malformed base service uri in the configuration file (should end with .svc, there should not be query or fragment in the base service uri)';
        }

        return 'Malformed base service uri in the configuration file';
    }

    /**
     * Format a message to show error when request uri is not 
     * based on configured relative uri.
     * 
     * @param string $requestUri  The request uri.
     * @param string $relativeUri The relative uri in service.config.xml.
     * 
     * @return string The formatted message.
     */
    public static function dataServiceHostRequestUriIsNotBasedOnRelativeUriInConfig($requestUri, $relativeUri)
    {
        return 'The request uri ' . $requestUri . ' is not valid as it is not based on the configured relative uri ' . $relativeUri;
    }

    /**
     * Message to show error when the service class mentioned in the 
     * configuration does not implement IRequestHandler interface.
     * 
     * @return string The message
     */
    public static function dispatcherServiceClassShouldImplementIRequestHandler()
    {
        return 'Service class specified in the configuration does not implements \'ODataProducer\IRequestHandler\', the service class should be derived from ODataProducer\DataService';
    }

    /**
     * Message to show error when the service class mentioned in the 
     * configuration does not implement IDataService interface.
     * 
     * @return string The message
     */
    public static function dispatcherServiceClassShouldImplementIDataService()
    {
        'Service class specified in the configuration does not implements \'ODataProducer\IDataService\', the service class should be derived from ODataProducer\DataService';
    }   
}
?>