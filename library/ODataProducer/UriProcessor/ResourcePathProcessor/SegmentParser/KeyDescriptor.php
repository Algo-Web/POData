<?php
/** 
 * A type used to represent Key (identifier) for an entity (resource), This class 
 * can parse an Astoria KeyPredicate, KeyPredicate will be in one of the following 
 * two formats:
 *  1) KeyValue                                      : If the Entry has a single key 
 *                                                     Property the predicate may 
 *                                                     include only the value of the
 *                                                     key Property.
 *      e.g. 'ALFKI' in Customers('ALFKI')
 *  2) Property = KeyValue [, Property = KeyValue]*  : If the key is made up of two 
 *                                                     or more Properties, then its 
 *                                                     value must be stated using 
 *                                                     name/value pairs.
 *      e.g. 'ALFKI' in Customers(CustomerID = 'ALFKI'), 
 *          "OrderID=10248,ProductID=11" in Order_Details(OrderID=10248,ProductID=11)
 *       
 * Entity's identifier is a collection of value for key properties. These values 
 * can be named or positional, depending on how they were specified in the URI.
 *  e.g. Named values:      
 *         Customers(CustomerID = 'ALFKI'), Order_Details(OrderID=10248,ProductID=11)
 *       Positional values: 
 *         Customers('ALFKI'), Order_Details(10248, 11)
 * Note: Currenlty WCF Data Service does not support multiple 'Positional values' so 
 *       Order_Details(10248, 11) is not valid, but this class can parse both types. 
 * Note: This type is also used to parse and validate skiptoken value as they are 
 *       comma seperated positional values.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
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
namespace ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser;
use ODataProducer\Common\InvalidOperationException;
use ODataProducer\Common\ODataException;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\Type\Single;
use ODataProducer\Providers\Metadata\Type\Int64;
use ODataProducer\Providers\Metadata\Type\Double;
use ODataProducer\Providers\Metadata\Type\Decimal;
use ODataProducer\Providers\Metadata\Type\String;
use ODataProducer\Providers\Metadata\Type\Guid;
use ODataProducer\Providers\Metadata\Type\DateTime;
use ODataProducer\Providers\Metadata\Type\Boolean;
use ODataProducer\Providers\Metadata\Type\Int32;
use ODataProducer\Providers\Metadata\Type\Null1;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\ExpressionTokenId;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\ExpressionToken;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\ExpressionLexer;
use ODataProducer\Common\Messages;
/**
 * Type to represent description of key.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class KeyDescriptor
{

    /**
     * Holds collection of named key values
     * For e.g. the keypredicate Order_Details(OrderID=10248,ProductID=11) will
     * stored in this array as:
     * Array([OrderID] => Array( [0] => 10248 [1] => Object(Int32)),
     *       [ProductID] => Array( [0] => 11 [1] => Object(Int32)))
     * Note: This is mutually exclusive with $_positionalValues. These values 
     * are not validated aganist entity's ResourceType, validation will happen 
     * once validate function is called, $_validatedNamedValues will hold 
     * validated values.
     * 
     * @var array
     */
    private $_namedValues;

    /**
     * Holds collection of positional key values
     * For e.g. the keypredicate Order_Details(10248, 11) will
     * stored in this array as:
     * Array([0] => Array( [0] => 10248 [1] => Object(Int32)),
     *       [1] => Array( [0] => 11 [1] => Object(Int32)))
     * Note: This is mutually exclusive with $_namedValues. These values are not 
     * validated aganist entity's ResourceType, validation will happen once validate
     * function is called, $_validatedNamedValues will hold validated values
     * 
     * @var array
     */
    private $_positionalValues;

    /**
     * Holds collection of positional or named values as named values. The validate
     * function populates this collection
     * 
     * @var array
     */
    private $_validatedNamedValues;

    /**
     * Creates new instance of KeyDescriptor
     * Note: The arguments $namedValues and $positionalValues are mutually 
     * exclusive. Either both or one will be empty array
     * 
     * @param array $namedValues      Collection of named key values
     * @param array $positionalValues Collection of positional key values
     */
    private function __construct($namedValues, $positionalValues)
    {
        $this->_namedValues = $namedValues;
        $this->_positionalValues = $positionalValues;
        $this->_validatedNamedValues = null;
    }

    /**
     * Gets collection of named key values
     * 
     * @return array(string, array(string, IType))
     */
    public function getNamedValues()
    {
        return $this->_namedValues;
    }

    /**
     * Gets collection of positional key values
     * 
     * @return array(int, array(string, IType))
     */
    public function getPositionalValues()
    {
        return $this->_positionalValues;
    }

    /**
     * Gets collection of positional key values by reference
     * 
     * @return array(int, array(string, IType))
     */
    public function &getPositionalValuesByRef()
    {
        return $this->_positionalValues;
    }

    /**
     * Gets validated named key values, this array will be populated 
     * in validate function
     * 
     * @return array(string, array(string, IType))
     * 
     * @throws InvalidOperationException if this function invoked 
     *                                   before invoking validate 
     *                                   function.
     */
    public function getValidatedNamedValues()
    {
        if ($this->_validatedNamedValues === null) {
            throw new InvalidOperationException(
                Messages::keyDescriptorValidateNotCalled()
            );
        }

        return $this->_validatedNamedValues;
    }

    /**
     * Checks whether the key values have name
     * 
     * @return boolean
     */
    public function areNamedValues()
    {
        return !empty($this->_namedValues);
    }

    /**
     * Check whether this KeyDesciption has any key values
     * 
     * @return boolean
     */
    public function isEmpty()
    {
         return empty($this->_namedValues) 
             && empty($this->_positionalValues);
    }

    /**
     * Gets number of values in the key
     * 
     * @return int
     */
    public function valueCount()
    {
        if ($this->isEmpty()) {
            return 0;
        } else if (!empty($this->_namedValues)) {
            return count($this->_namedValues);
        }
        
        return count($this->_positionalValues);
    }

    /**
     * Attempts to parse value(s) of resource key(s) from the given key predicate
     *  and creates instance of KeyDescription representing the same, Once parsing 
     *  is done one should call validate function to validate the created 
     *  KeyDescription. 
     *    
     * @param string        $keyPredicate   The predicate to parse
     * @param KeyDescriptor &$keyDescriptor On return, Description of key after 
     *                                      parsing
     * 
     * @return boolean True if the given values were parsed; false if there was 
     *                 a syntactic error
     */
    public static function tryParseKeysFromKeyPredicate($keyPredicate, 
        &$keyDescriptor
    ) {
        return self::_tryParseKeysFromKeyPredicate(
            $keyPredicate, true, false, $keyDescriptor
        );
    }

    /**
     * Attempt to parse comma seperated values representing a skiptoken and creates
     * instance of KeyDescriptor representing the same.
     * 
     * @param string        $skipToken      The skiptoken value to parse
     * @param KeyDescriptor &$keyDescriptor On return, Description of values 
     *                                      after parsing
     * 
     * @return boolean True if the given values were parsed; false if there was 
     *                 a syntactic error
     */
    public static function tryParseValuesFromSkipToken($skipToken, &$keyDescriptor)
    {
        return self::_tryParseKeysFromKeyPredicate(
            $skipToken, false, true, $keyDescriptor
        );
    }

    /**
     * Validate this KeyDescriptor, If valid, this function populates 
     * _validatedNamedValues array with key as keyName and value as an array of 
     * key value and key type.
     * 
     * @param string       $segmentAsString The segment in the form identifer
     *                                      (keyPredicate) which this descriptor 
     *                                      represents 
     * @param ResourceType $resourceType    The type of the idenfier in the segment
     * 
     * @return void
     * 
     * @throws ODataException If validation fails.
     */
    public function validate($segmentAsString, ResourceType $resourceType)
    {
        if ($this->isEmpty()) {
            $this->_validatedNamedValues = array();
            return;
        }

        $keyProperties = $resourceType->getKeyProperties();
        $keyPropertiesCount = count($keyProperties);        
        if (!empty($this->_namedValues)) {
            if (count($this->_namedValues) != $keyPropertiesCount) {
                ODataException::createSyntaxError(
                    Messages::keyDescriptorKeyCountNotMatching(
                        $segmentAsString, $keyPropertiesCount, 
                        count($this->_namedValues)
                    )
                );
            }

            foreach ($keyProperties as $keyName => $keyResourceProperty) {
                if (!array_key_exists($keyName, $this->_namedValues)) {
                    $keysAsString = null;
                    foreach (array_keys($keyProperties) as $key) {
                        $keysAsString .= $key . ', ';
                    }
                    
                    $keysAsString = rtrim($keysAsString, ' ,');
                    ODataException::createSyntaxError(
                        Messages::keyDescriptorMissingKeys(
                            $segmentAsString, $keysAsString
                        )
                    );
                }

                $typeProvided = $this->_namedValues[$keyName][1];
                $expectedType = $keyResourceProperty->getInstanceType();
                if (!$expectedType->isCompatibleWith($typeProvided)) {
                    ODataException::createSyntaxError(
                        Messages::keyDescriptorInCompatibleKeyType(
                            $segmentAsString, $keyName, 
                            $expectedType->getFullTypeName(), 
                            $typeProvided->getFullTypeName()
                        )
                    );
                }

                $this->_validatedNamedValues[$keyName] = $this->_namedValues[$keyName];
            }
        } else {            
            if (count($this->_positionalValues) != $keyPropertiesCount) {
                ODataException::createSyntaxError(
                    Messages::keyDescriptorKeyCountNotMatching(
                        $segmentAsString, $keyPropertiesCount, 
                        count($this->_positionalValues)
                    )
                );
            }

            $i = 0;
            foreach ($keyProperties as $keyName => $keyResourceProperty) {
                $typeProvided = $this->_positionalValues[$i][1];
                $expectedType = $keyResourceProperty->getInstanceType();

                if (!$expectedType->isCompatibleWith($typeProvided)) {
                    ODataException::createSyntaxError(
                        Messages::keyDescriptorInCompatibleKeyTypeAtPosition(
                            $segmentAsString, $keyResourceProperty->getName(), 
                            $i, $expectedType->getFullTypeName(), 
                            $typeProvided->getFullTypeName()
                        )
                    );
                }

                $this->_validatedNamedValues[$keyName] 
                    = $this->_positionalValues[$i];
                $i++;
            }
        }
    }

    /**
     * Attempts to parse value(s) of resource key(s) from the key predicate and 
     * creates instance of KeyDescription representing the same, Once parsing is 
     * done one should call validate function to validate the created 
     * KeyDescription 
     *    
     * @param string        $keyPredicate     The key predicate to parse
     * @param boolean       $allowNamedValues Set to true if paser should accept
     *                                        named values(Property = KeyValue), 
     *                                        if false then parser will fail on 
     *                                        such constructs
     * @param boolean       $allowNull        Set to true if parser should accept 
     *                                        null values for positional key 
     *                                        values, if false then parser will 
     *                                        fail on seeing null values
     * @param KeyDescriptor &$keyDescriptor   On return, Description of key after 
     *                                        parsing
     * 
     * @return boolean True if the given values were parsed; false if there was a 
     *                 syntactic error
     */
    private static function _tryParseKeysFromKeyPredicate($keyPredicate, 
        $allowNamedValues, $allowNull, &$keyDescriptor
    ) {
        $expressionLexer = new ExpressionLexer($keyPredicate);
        $currentToken = $expressionLexer->getCurrentToken();
        
        //Check for empty predicate e.g. Customers(  )
        if ($currentToken->Id == ExpressionTokenId::END) {
            $keyDescriptor = new KeyDescriptor(array(), array());
            return true;
        }

        $namedValues = array();
        $positionalValues = array();
        
        do {            
            if (($currentToken->Id == ExpressionTokenId::IDENTIFIER) 
                && $allowNamedValues
            ) {
                //named and positional values are mutually exclusive
                if (!empty($positionalValues)) {
                    return false;
                }

                //expecting keyName=keyValue, verify it
                $identifier = $currentToken->getIdentifier();
                $expressionLexer->nextToken();
                $currentToken = $expressionLexer->getCurrentToken();
                if ($currentToken->Id != ExpressionTokenId::EQUAL) {
                    return false;
                }
                
                $expressionLexer->nextToken();
                $currentToken = $expressionLexer->getCurrentToken();
                $value = null;
                if (!$currentToken->isKeyValueToken()) {
                    return false;
                }
                
                if (array_key_exists($identifier, $namedValues)) {
                    //Duplication of KeyName not allowed
                    return false;
                }

                //Get type of keyValue and validate keyValue
                $ouValue = $outType = null;
                if (!self::_getTypeAndValidateKeyValue(
                    $currentToken->Text, 
                    $currentToken->Id, 
                    $outValue, 
                    $outType
                )
                ) {
                    return false;
                }
                
                $namedValues[$identifier] = array($outValue, $outType);
                
            } else if ($currentToken->isKeyValueToken() 
                || ($currentToken->Id == ExpressionTokenId::NULL_LITERAL && $allowNull)
            ) {
                //named and positional values are mutually exclusive
                if (!empty($namedValues)) {
                    return false;
                }

                //Get type of keyValue and validate keyValue
                $ouValue = $outType = null;
                if (!self::_getTypeAndValidateKeyValue(
                    $currentToken->Text, 
                    $currentToken->Id, 
                    $outValue, 
                    $outType
                )
                ) {
                    return false;
                }
                
                $positionalValues[] = array($outValue, $outType);
            } else {
                return false;
            }
            
            $expressionLexer->nextToken();
            $currentToken = $expressionLexer->getCurrentToken();
            if ($currentToken->Id == ExpressionTokenId::COMMA) {
                $expressionLexer->nextToken();
                $currentToken = $expressionLexer->getCurrentToken();
                //end of text and comma, Trailing comma not allowed
                if ($currentToken->Id == ExpressionTokenId::END) {
                    return false;
                }
            }
            
        } while ($currentToken->Id != ExpressionTokenId::END);

        $keyDescriptor = new KeyDescriptor($namedValues, $positionalValues);
        return true;
    }

    /**
     * Get the type of an Astoria URI key value, validate the value aganist 
     * the type if valid this function provide the PHP value equivalent to 
     * the astoira URI key value
     * 
     * @param unknown           $value     The Astoria URI key value
     * @param ExpressionTokenId $tokenId   The tokenId for $value literal
     * @param unknown           &$outValue After the invocation, this parameter 
     *                                     holds the PHP value equivalent to $value, 
     *                                     if $value is not valid then this  
     *                                     parameter will be null
     * @param IType             &$outType  After the invocation, this parameter  
     *                                     holds the type of $value, if $value is  
     *                                     not a valid key value type then this  
     *                                     parameter will be null
     * 
     * @return boolean True if $value is a valid type else false
     */
    private static function _getTypeAndValidateKeyValue($value, $tokenId, &$outValue, &$outType)
    {     
        switch ($tokenId) {
        case ExpressionTokenId::BOOLEAN_LITERAL:
            $outType = new Boolean();
            break;
        case ExpressionTokenId::DATETIME_LITERAL:
            $outType = new DateTime();
            break;
        case ExpressionTokenId::GUID_LITERAL:
            $outType = new Guid();
            break;
        case ExpressionTokenId::STRING_LITERAL:
            $outType = new String();
            break;
        case ExpressionTokenId::INTEGER_LITERAL:
            $outType = new Int32();
            break;
        case ExpressionTokenId::DECIMAL_LITERAL:
            $outType = new Decimal();
            break;
        case ExpressionTokenId::DOUBLE_LITERAL:
            $outType = new Double();
            break;
        case ExpressionTokenId::INT64_LITERAL:
            $outType = new Int64();
            break;
        case ExpressionTokenId::SINGLE_LITERAL:
            $outType = new Single();
            break;
        case ExpressionTokenId::NULL_LITERAL:
            $outType = new Null1();
            break;
        default:
            $outType = null;
            return false;
        }

        if (!$outType->validate($value, $outValue)) {
            $outType = $outValue = null;
            return false;
        }

        return true;
    }
}
?>