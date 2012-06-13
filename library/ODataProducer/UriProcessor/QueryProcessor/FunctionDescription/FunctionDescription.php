<?php
/** 
 * Class to represent function signature including function-name
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_FunctionDescription
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
namespace ODataProducer\UriProcessor\QueryProcessor\FunctionDescription;
use ODataProducer\Common\NotImplementedException;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\Messages;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Providers\Metadata\Type\Null1;
use ODataProducer\Providers\Metadata\Type\INavigationType;
use ODataProducer\Providers\Metadata\Type\Int64;
use ODataProducer\Providers\Metadata\Type\Int16;
use ODataProducer\Providers\Metadata\Type\Guid;
use ODataProducer\Providers\Metadata\Type\Single;
use ODataProducer\Providers\Metadata\Type\Double;
use ODataProducer\Providers\Metadata\Type\Decimal;
use ODataProducer\Providers\Metadata\Type\DateTime;
use ODataProducer\Providers\Metadata\Type\Int32;
use ODataProducer\Providers\Metadata\Type\String;
use ODataProducer\Providers\Metadata\Type\Boolean;
use ODataProducer\Providers\Metadata\Type\Void;
use ODataProducer\Providers\Metadata\Type\Binary;
use ODataProducer\Providers\Metadata\Type\IType;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
/**
 * Function signatures for operations supported in $filter clause.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_FunctionDescription
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class FunctionDescription
{
    /**
     * @var string
     */
    public $functionName;

    /**
     * @var IType
     */
    public $returnType;

    /**
     * @var array(IType)
     */
    public $argumentTypes;

    /**
     * Create new instance of FunctionDescription
     * 
     * @param string  $functionName  Name of the function
     * @param IType   $returnType    Return type
     * @param IType[] $argumentTypes Parameter type
     */
    public function __construct($functionName, $returnType, $argumentTypes)
    {
        $this->functionName = $functionName;
        $this->returnType = $returnType;
        $this->argumentTypes = $argumentTypes;
    }

    /**      
     * Get the function prototype as string
     * 
     * @return string
     */
    public function getProtoTypeAsString()
    {
        $str = $this->returnType->getFullTypeName() 
            . ' ' . $this->functionName . '(';
        foreach ($this->argumentTypes as $argumentType) {
            $str .= $argumentType->getFullTypeName() . ', ';
        }

        return rtrim($str, ', ') . ')';
    }

    /**      
     * Create function descriptions for supported function-calls in $filter option
     * 
     * @return array(string, FunctionDescription)
     */
    public static function filterFunctionDescriptions()
    {
        $functions = array(
                            //String Functions
                            'endswith'      => 
                                array(
                                    new FunctionDescription(
                                        'endswith', new Boolean(), 
                                        array(new String(), new String())
                                    )
                                ),
                            'indexof'       => 
                                array(
                                    new FunctionDescription(
                                        'indexof', new Int32(), 
                                        array(new String(), new String())
                                    )
                                ),
                            'replace'       => 
                                array(
                                    new FunctionDescription(
                                        'replace', new String(), 
                                        array(new String(), new String(), new String())
                                    )
                                ),
                            'startswith'    => 
                                array(
                                    new FunctionDescription(
                                        'startswith', new Boolean(), 
                                        array(new String(), new String())
                                    )
                                ),
                            'tolower'       => 
                                array(
                                    new FunctionDescription(
                                        'tolower', new String(), 
                                        array(new String())
                                    )
                                ),
                            'toupper'       => 
                                array(
                                    new FunctionDescription(
                                        'toupper', new String(), 
                                        array(new String())
                                    )
                                ),
                            'trim'          => 
                                array(
                                    new FunctionDescription(
                                        'trim', new String(), 
                                        array(new String())
                                    )
                                ),
                            'substring'     => 
                                array(
                                    new FunctionDescription(
                                        'substring', new String(), 
                                        array(new String(), new Int32())
                                    ),
                                    new FunctionDescription(
                                        'substring', new String(), 
                                        array(new String(), new Int32(), new Int32())
                                    )
                                ),
                            'substringof'   => 
                                array(
                                    new FunctionDescription(
                                        'substringof', new Boolean(), 
                                        array(new String(), new String())
                                    )
                                ),
                            'concat'        => 
                                array(
                                    new FunctionDescription(
                                        'concat', new String(), 
                                        array(new String(), new String())
                                    )
                                ),
                            'length'        => 
                                array(
                                    new FunctionDescription(
                                        'length', new Int32(), 
                                        array(new String())
                                    )
                                ),
                            //DateTime functions
                            'year'          => 
                                array(
                                    new FunctionDescription(
                                        'year', new Int32(), 
                                        array(new DateTime())
                                    )
                                ),
                            'month'         => 
                                array(
                                    new FunctionDescription(
                                        'month', new Int32(), 
                                        array(new DateTime())
                                    )
                                ),
                            'day'           => 
                                array(
                                    new FunctionDescription(
                                        'day', new Int32(), 
                                        array(new DateTime())
                                    )
                                ),
                            'hour'          => 
                                array(
                                    new FunctionDescription(
                                        'hour', new Int32(), 
                                        array(new DateTime())
                                    )
                                ),
                            'minute'        => 
                                array(
                                    new FunctionDescription(
                                        'minute', new Int32(), 
                                        array(new DateTime())
                                    )
                                ),
                            'second'        => 
                                array(
                                    new FunctionDescription(
                                        'second', new Int32(), 
                                        array(new DateTime())
                                    )
                                ),
                            //Math Functions
                            'round'         => 
                                array(
                                    new FunctionDescription(
                                        'round', new Decimal(), 
                                        array(new Decimal())
                                    ),
                                    new FunctionDescription(
                                        'round', new Double(), 
                                        array(new Double())
                                    )
                                ), 
                            'ceiling'       => 
                                array(
                                    new FunctionDescription(
                                        'ceiling', new Decimal(), 
                                        array(new Decimal())
                                    ),
                                    new FunctionDescription(
                                        'ceiling', new Double(), 
                                        array(new Double())
                                    )
                                ),
                            'floor'         => 
                                array(
                                    new FunctionDescription(
                                        'floor', new Decimal(), 
                                        array(new Decimal())
                                    ),
                                    new FunctionDescription(
                                        'floor', new Double(), 
                                        array(new Double())
                                    )
                                )
                          );

        return $functions;
    }

    /** 
     * Get function description for string comparison
     * 
     * @return array(string, FunctionDescription)
     */
    public static function stringComparisionFunctions()
    {
        return array(
            new FunctionDescription(
                'strcmp', new Int32(), 
                array(new String(), new String())
            )
        );
    }

    /**
     * Get function description for datetime comparison
     * 
     * @return array(string, FunctionDescription)
     */
    public static function dateTimeComparisonFunctions()
    {
        return array(
            new FunctionDescription(
                'dateTimeCmp', new Int32(), 
                array(new DateTime(), new DateTime())
            )
        );
    }

    /**
     * Get function description for guid equality check
     * 
     * @return array(string, FunctionDescription)
     */
    public static function guidEqualityFunctions()
    {
        return array(
            new FunctionDescription(
                'guidEqual', new Boolean(), 
                array(new Guid(), new Guid())
            )
        );
    }
    
    /**
     * Get function description for binary equality check
     * 
     * @return array(string, FunctionDescription)
     */
    public static function binaryEqualityFunctions()
    {
        return array(
            new FunctionDescription(
                'binaryEqual', new Boolean(), 
                array(new Binary(), new Binary())
            )
        );
    }

    /**
     * Get function descriptions for arithmetic operations
     * 
     * @return array(string, FunctionDescription)
     */
    public static function arithmeticOpertionFunctions()
    {      
        return array(
            new FunctionDescription(
                'F', new int16(), 
                array(new int16(), new int16())
            ),
            new FunctionDescription(
                'F', new int32(), 
                array(new int32(), new int32())
            ),
            new FunctionDescription(
                'F', new int64(), 
                array(new int64(), new int64())
            ),
            new FunctionDescription(
                'F', new Single(), 
                array(new Single(), new Single())
            ),
            new FunctionDescription(
                'F', new Double(), 
                array(new Double(), new Double())
            ),
            new FunctionDescription(
                'F', new Decimal(), 
                array(new Decimal(), new Decimal())
            )
        );
    }

    /**      
     * Get function descriptions for arithmetic add operations
     * 
     * @return array(string, FunctionDescription)
     */
    public static function addOperationFunctions()
    {
        return self::arithmeticOpertionFunctions();
    }

    /**
     * Get function descriptions for arithmetic subtract operations
     * 
     * @return array(string, FunctionDescription)
     */
    public static function substractOperationFunctions()
    {
        return self::arithmeticOpertionFunctions();
    }

    /**      
     * Get function descriptions for logical operations
     * 
     * @return array(string, FunctionDescription)
     */
    public static function logicalOperationFunctions()
    {
        return array(
            new FunctionDescription(
                'F', new Boolean(), 
                array(new Boolean(), new Boolean())
            )
        );
    }

    /**
     * Get function descriptions for relational operations
     * 
     * @return array(string, FunctionDescription)
     */
    public static function relationalOperationFunctions()
    {
        return array_merge(
            self::arithmeticOpertionFunctions(),
            array(
                new FunctionDescription(
                    'F', new Boolean(), 
                    array(new Boolean(), new Boolean())
                ),
                new FunctionDescription(
                    'F', new DateTime(), 
                    array(new DateTime(), new DateTime())
                ),
                new FunctionDescription(
                    'F', new Guid(), 
                    array(new Guid(), new Guid())
                ),
                new FunctionDescription(
                    'F', new Boolean(), 
                    array(new Binary(), new Binary())
                )
            )
        );
    }

    /**
     * Get function descriptions for unary not operation
     * 
     * @return array(string, FunctionDescription)
     */
    public static function notOperationFunctions()
    {
        return array(
            new FunctionDescription(
                'F', new Boolean(), 
                array(new Boolean())
            )
        );
    }

    /**
     * Get function description for checking an operand is null or not
     * 
     * @param IType $type Type of the argument to null check function.
     * 
     * @return FunctionDescription
     */
    public static function isNullCheckFunction(IType $type)
    {
        return new FunctionDescription('is_null', new Boolean(), array($type));
    }
    
    /**      
     * Get function description for unary negate operator
     * 
     * @return array(string, FunctionDescription)
     */
    public static function negateOperationFunctions()
    {
        return array(
            new FunctionDescription('F', new Int16(), array(new Int16())),
            new FunctionDescription('F', new Int32(), array(new Int32())),
            new FunctionDescription('F', new Int64(), array(new Int64())),
            new FunctionDescription('F', new Single(), array(new Single())),
            new FunctionDescription('F', new Double(), array(new Double())),
            new FunctionDescription('F', new Decimal(), array(new Decimal()))
        );
    }

    /**
     * To throw ODataException for incompatible types
     * 
     * @param ExpressionToken           $expressionToken Expression token
     * @param array(AbstractExpression) $argExpressions  Array of argument expression
     * 
     * @throws ODataException
     * @return void
     */
    public static function incompatibleError($expressionToken, $argExpressions)
    {
        $string = null;
        foreach ($argExpressions as $argExpression) {
            $string .= $argExpression->getType()->getFullTypeName() . ', ';
        }

        $string = rtrim($string, ', ');
        $pos = strrpos($string, ', ');
        if ($pos !== false) {
            $string = substr_replace($string, ' and ', strrpos($string, ', '), 2);
        }

        ODataException::createSyntaxError(
            Messages::expressionParserInCompactibleTypes(
                $expressionToken->Text, 
                $string, $expressionToken->Position
            )
        );
    }

    /**      
     * Validate operands of an arithmetic operation and promote if required
     * 
     * @param ExpressionToken    $expressionToken The expression token
     * @param AbstractExpression $leftArgument    The left expression
     * @param AbstractExpression $rightArgument   The right expression
     * 
     * @return IType
     */
    public static function verifyAndPromoteArithmeticOpArguments($expressionToken, 
        $leftArgument, $rightArgument
    ) {
        $function  
            = self::findFunctionWithPromotion(
                self::arithmeticOpertionFunctions(), 
                array($leftArgument, $rightArgument)
            );
        if ($function == null) {
            self::incompatibleError(
                $expressionToken, array($leftArgument, $rightArgument)
            );
        }

        return $function->returnType;
    }
    
    /**      
     * Validate operands of an logical operation
     * 
     * @param ExpressionToken    $expressionToken The expression token
     * @param AbstractExpression $leftArgument    The left expression
     * @param AbstractExpression $rightArgument   The right expression
     * 
     * @return void
     * 
     * @throws ODataException
     */
    public static function verifyLogicalOpArguments($expressionToken, 
        $leftArgument, $rightArgument
    ) {
        $function = self::findFunctionWithPromotion(
            self::logicalOperationFunctions(), 
            array($leftArgument, $rightArgument), false
        );
        if ($function == null) {
            self::incompatibleError(
                $expressionToken, 
                array($leftArgument, $rightArgument)
            );
        }
    }

    /**
     * Validate operands of an relational operation
     * 
     * @param ExpressionToken    $expressionToken The expression token
     * @param AbstractExpression $leftArgument    The left argument expression
     * @param AbstractExpression $rightArgument   The right argument expression
     * 
     * @return void
     */
    public static function verifyRelationalOpArguments($expressionToken, 
        $leftArgument, $rightArgument
    ) {
        //for null operands only equality operators are allowed
        $null = new Null1();
        if ($leftArgument->typeIs($null) || $rightArgument->typeIs($null)) {
            if ((strcmp($expressionToken->Text, ODataConstants::KEYWORD_EQUAL) != 0) 
                && (strcmp($expressionToken->Text, ODataConstants::KEYWORD_NOT_EQUAL) != 0)
            ) {                
                ODataException::createSyntaxError(
                    Messages::expressionParserOperatorNotSupportNull(
                        $expressionToken->Text, 
                        $expressionToken->Position
                    )
                );
            }

            return;
        }

        //for guid operands only equality operators are allowed
        $guid = new Guid();
        if ($leftArgument->typeIs($guid) && $rightArgument->typeIs($guid)) {
            if ((strcmp($expressionToken->Text, ODataConstants::KEYWORD_EQUAL) != 0) 
                && (strcmp($expressionToken->Text, ODataConstants::KEYWORD_NOT_EQUAL) != 0)
            ) {                
                ODataException::createSyntaxError(
                    Messages::expressionParserOperatorNotSupportGuid(
                        $expressionToken->Text, $expressionToken->Position
                    )
                );
            }

            return;
        }       
        
        //for binary operands only equality operators are allowed
        $binary = new Binary();
        if ($leftArgument->typeIs($binary) && $rightArgument->typeIs($binary)) {
            if ((strcmp($expressionToken->Text, ODataConstants::KEYWORD_EQUAL) != 0) 
                && (strcmp($expressionToken->Text, ODataConstants::KEYWORD_NOT_EQUAL) != 0)
            ) {                
                ODataException::createSyntaxError(
                    Messages::expressionParserOperatorNotSupportBinary(
                        $expressionToken->Text, $expressionToken->Position
                    )
                );
            }

            return;
        }
        
        //TODO: eq and ne is valid for 'resource reference' 
        //navigation also verify here

        $functions = array_merge(
            self::relationalOperationFunctions(), 
            self::stringComparisionFunctions()
        );
        $function = self::findFunctionWithPromotion(
            $functions, array($leftArgument, $rightArgument), false
        );
        if ($function == null) {
            self::incompatibleError(
                $expressionToken, 
                array($leftArgument, $rightArgument)
            );
        }
    }

    /**
     * Validate operands of a unary  operation
     * 
     * @param ExpressionToken    $expressionToken The expression token
     * @param AbstractExpression $argExpression   Argument expression
     * 
     * @throws ODataException
     * 
     * @return void
     */
    public static function validateUnaryOpArguments($expressionToken, $argExpression)
    {
        //Unary not
        if (strcmp($expressionToken->Text, ODataConstants::KEYWORD_NOT) == 0 ) {
            $function = self::findFunctionWithPromotion(
                self::notOperationFunctions(), 
                array($argExpression)
            );
            if ($function == null) {
                self::incompatibleError($expressionToken, array($argExpression));
            }

            return;
        }

        //Unary minus (negation)
        if (strcmp($expressionToken->Text, '-') == 0) {
            if (self::findFunctionWithPromotion(self::negateOperationFunctions(), array($argExpression)) == null) {
                self::incompatibleError($expressionToken, array($argExpression));
            }
        }
    }
    
    /**
     * Check am identifier is a valid filter function
     * 
     * @param ExpressionToken $expressionToken The expression token      
     * 
     * @throws ODataException
     * 
     * @return array(FunctionDescription) Array of matching functions
     */
    public static function verifyFunctionExists($expressionToken)
    {
        if (!array_key_exists($expressionToken->Text, self::filterFunctionDescriptions())) {
            ODataException::createSyntaxError(
                Messages::expressionParserUnknownFunction(
                    $expressionToken->Text, 
                    $expressionToken->Position
                )
            );
            
        }

        $filterFunctions =  self::filterFunctionDescriptions();
        return $filterFunctions[$expressionToken->Text];
    }

    /**
     * Validate operands (arguments) of a function call operation and return 
     * matching function
     * 
     * @param array(FunctionDescription) $functions       List of functions to 
     *                                                    be checked
     * @param array(AbstractExpression)  $argExpressions  Function argument
     *                                                    expressions
     * @param ExpressionToken            $expressionToken Expression token
     * 
     * @throws ODataException
     * 
     * @return FunctionDescription
     */
    public static function verifyFunctionCallOpArguments($functions, 
        $argExpressions, $expressionToken
    ) {
        $function 
            = self::findFunctionWithPromotion($functions, $argExpressions, false);
        if ($function == null) {
            $protoTypes = null;
            foreach ($functions as $function) {
                $protoTypes .=  $function->getProtoTypeAsString() . '; ';
            }

            ODataException::createSyntaxError(
                Messages::expressionLexerNoApplicableFunctionsFound(
                    $expressionToken->Text, 
                    $protoTypes, 
                    $expressionToken->Position
                )
            );
        }

        return $function;
    }

    /**
     * Finds a function from the list of functions whose argument types matches 
     * with types of expressions
     * 
     * @param array(FunctionDescription) $functionDescriptions List of functions
     * @param array(AbstractExpression)  $argExpressions       Function argument 
     *                                                         expressions 
     * @param Boolean                    $promoteArguments     Function argument
     * 
     * @return FunctionDescription/NULL Reference to the matching function if 
     *                                  found else NULL
     */
    public static function findFunctionWithPromotion($functionDescriptions, 
        $argExpressions, $promoteArguments = true
    ) {
        $argCount = count($argExpressions);
        $applicableFunctions = array();
        foreach ($functionDescriptions as $functionDescription) {
            if (count($functionDescription->argumentTypes) == $argCount) {
                $applicableFunctions[] = $functionDescription;
            }
        }

        if (empty($applicableFunctions)) {
            return null;
        }

        //Check for exact match
        foreach ($applicableFunctions as $function) {
            $i = 0;
            foreach ($function->argumentTypes as $argumentType) {
                if (!$argExpressions[$i]->typeIs($argumentType)) {
                    break;
                }

                $i++;
            }

            if ($i == $argCount) {
                return $function;
            }
        }

        //Check match with promotion
        $promotedTypes = array();
        foreach ($applicableFunctions as $function) {
            $i = 0;
            $promotedTypes = array();
            foreach ($function->argumentTypes as $argumentType) {
                if (!$argumentType->isCompatibleWith($argExpressions[$i]->getType())) {
                    break;
                }

                $promotedTypes[] = $argumentType;
                $i++;
            }

            if ($i == $argCount) {
                $i = 0;
                if ($promoteArguments) {
                    //Promote Argument Expressions
                    foreach ($argExpressions as $expression) {
                        $expression->setType($promotedTypes[$i++]);
                    }
                }

                return $function;
            }
        }

        return null;
    }
}