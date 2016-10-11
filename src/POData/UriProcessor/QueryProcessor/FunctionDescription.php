<?php

namespace POData\UriProcessor\QueryProcessor;

use POData\Common\ODataException;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Providers\Metadata\Type\Null1;
use POData\Providers\Metadata\Type\INavigationType;
use POData\Providers\Metadata\Type\Int64;
use POData\Providers\Metadata\Type\Int16;
use POData\Providers\Metadata\Type\Guid;
use POData\Providers\Metadata\Type\Single;
use POData\Providers\Metadata\Type\Double;
use POData\Providers\Metadata\Type\Decimal;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\EdmString;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\Void;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionToken;

/**
 * Class FunctionDescription
 *
 * Class to represent function signature including function-name
 *
 * @package POData\UriProcessor\QueryProcessor\FunctionDescription
 */
class FunctionDescription
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var IType
     */
    public $returnType;

    /**
     * @var IType[]
     */
    public $argumentTypes;

    /**
     * Create new instance of FunctionDescription
     * 
     * @param string  $name  Name of the function
     * @param IType   $returnType    Return type
     * @param IType[] $argumentTypes Parameter type
     */
    public function __construct($name, $returnType, $argumentTypes)
    {
        $this->name = $name;
        $this->returnType = $returnType;
        $this->argumentTypes = $argumentTypes;
    }

    /**      
     * Get the function prototype as string
     * 
     * @return string
     */
    public function getPrototypeAsString()
    {
        $str = $this->returnType->getFullTypeName() . ' ' . $this->name . '(';

	    foreach ($this->argumentTypes as $argumentType) {
            $str .= $argumentType->getFullTypeName() . ', ';
        }

        return rtrim($str, ', ') . ')';
    }

    /**      
     * Create function descriptions for supported function-calls in $filter option
     *
     * TODO: FIGURE OUT WHAT THE HECK THIS IS RETURNING!?!?
     *
     * @return array indexed by function name
     */
    public static function filterFunctionDescriptions()
    {
        $functions = array(
            //EdmString Functions
            'endswith'      =>
                array(
                    new FunctionDescription(
                        'endswith', new Boolean(),
                        array(new EdmString(), new EdmString())
                    )
                ),
            'indexof'       =>
                array(
                    new FunctionDescription(
                        'indexof', new Int32(),
                        array(new EdmString(), new EdmString())
                    )
                ),
            'replace'       =>
                array(
                    new FunctionDescription(
                        'replace', new EdmString(),
                        array(new EdmString(), new EdmString(), new EdmString())
                    )
                ),
            'startswith'    =>
                array(
                    new FunctionDescription(
                        'startswith', new Boolean(),
                        array(new EdmString(), new EdmString())
                    )
                ),
            'tolower'       =>
                array(
                    new FunctionDescription(
                        'tolower', new EdmString(),
                        array(new EdmString())
                    )
                ),
            'toupper'       =>
                array(
                    new FunctionDescription(
                        'toupper', new EdmString(),
                        array(new EdmString())
                    )
                ),
            'trim'          =>
                array(
                    new FunctionDescription(
                        'trim', new EdmString(),
                        array(new EdmString())
                    )
                ),
            'substring'     =>
                array(
                    new FunctionDescription(
                        'substring', new EdmString(),
                        array(new EdmString(), new Int32())
                    ),
                    new FunctionDescription(
                        'substring', new EdmString(),
                        array(new EdmString(), new Int32(), new Int32())
                    )
                ),
            'substringof'   =>
                array(
                    new FunctionDescription(
                        'substringof', new Boolean(),
                        array(new EdmString(), new EdmString())
                    )
                ),
            'concat'        =>
                array(
                    new FunctionDescription(
                        'concat', new EdmString(),
                        array(new EdmString(), new EdmString())
                    )
                ),
            'length'        =>
                array(
                    new FunctionDescription(
                        'length', new Int32(),
                        array(new EdmString())
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
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[]
     */
    public static function stringComparisonFunctions()
    {
        return array(
            new FunctionDescription(
                'strcmp', new Int32(), 
                array(new EdmString(), new EdmString())
            )
        );
    }

    /**
     * Get function description for datetime comparison
     * 
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[]
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
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[]
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
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[]
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
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[]
     */
    public static function arithmeticOperationFunctions()
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
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[] indexed by function name
     */
    public static function addOperationFunctions()
    {
        return self::arithmeticOperationFunctions();
    }

    /**
     * Get function descriptions for arithmetic subtract operations
     * 
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[] indexed by function name
     */
    public static function subtractOperationFunctions()
    {
        return self::arithmeticOperationFunctions();
    }

    /**      
     * Get function descriptions for logical operations
     * 
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[]
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
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[]
     */
    public static function relationalOperationFunctions()
    {
        return array_merge(
            self::arithmeticOperationFunctions(),
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
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[]
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
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription
     */
    public static function isNullCheckFunction(IType $type)
    {
        return new FunctionDescription('is_null', new Boolean(), array($type));
    }
    
    /**      
     * Get function description for unary negate operator
     * 
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[]
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
     * @param AbstractExpression[] $argExpressions  Array of argument expression
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

        throw ODataException::createSyntaxError(
            Messages::expressionParserInCompatibleTypes(
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
                self::arithmeticOperationFunctions(),
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
                throw ODataException::createSyntaxError(
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
                throw ODataException::createSyntaxError(
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
                throw ODataException::createSyntaxError(
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
            self::stringComparisonFunctions()
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
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription[] Array of matching functions
     */
    public static function verifyFunctionExists($expressionToken)
    {
        if (!array_key_exists($expressionToken->Text, self::filterFunctionDescriptions())) {
            throw ODataException::createSyntaxError(
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
     * @param \POData\UriProcessor\QueryProcessor\FunctionDescription[] $functions       List of functions to be checked
     * @param AbstractExpression[]  $argExpressions  Function argument expressions
     * @param ExpressionToken            $expressionToken Expression token
     * 
     * @throws ODataException
     * 
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription
     */
    public static function verifyFunctionCallOpArguments($functions, 
        $argExpressions, $expressionToken
    ) {
        $function 
            = self::findFunctionWithPromotion($functions, $argExpressions, false);
        if ($function == null) {
            $protoTypes = null;
            foreach ($functions as $function) {
                $protoTypes .=  $function->getPrototypeAsString() . '; ';
            }

            throw ODataException::createSyntaxError(
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
     * @param \POData\UriProcessor\QueryProcessor\FunctionDescription[] $functionDescriptions List of functions
     * @param AbstractExpression[]  $argExpressions       Function argument expressions
     * @param Boolean                    $promoteArguments     Function argument
     * 
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription|null Reference to the matching function if
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