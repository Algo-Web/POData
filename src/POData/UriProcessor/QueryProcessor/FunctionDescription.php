<?php

namespace POData\UriProcessor\QueryProcessor;

use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Decimal;
use POData\Providers\Metadata\Type\Double;
use POData\Providers\Metadata\Type\Guid;
use POData\Providers\Metadata\Type\Int16;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\Int64;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\Null1;
use POData\Providers\Metadata\Type\Single;
use POData\Providers\Metadata\Type\StringType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionToken;

/**
 * Class FunctionDescription.
 *
 * Class to represent function signature including function-name
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
     * Create new instance of FunctionDescription.
     *
     * @param string  $name          Name of the function
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
     * Get the function prototype as string.
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
     * Create function descriptions for supported function-calls in $filter option.
     *
     * TODO: FIGURE OUT WHAT THE HECK THIS IS RETURNING!?!?
     *
     * @return array indexed by function name
     */
    public static function filterFunctionDescriptions()
    {
        $functions = [
            //EdmString Functions
            'endswith' => [
                    new self(
                        'endswith',
                        new Boolean(),
                        [new StringType(), new StringType()]
                    ),
                ],
            'indexof' => [
                    new self(
                        'indexof',
                        new Int32(),
                        [new StringType(), new StringType()]
                    ),
                ],
            'replace' => [
                    new self(
                        'replace',
                        new StringType(),
                        [new StringType(), new StringType(), new StringType()]
                    ),
                ],
            'startswith' => [
                    new self(
                        'startswith',
                        new Boolean(),
                        [new StringType(), new StringType()]
                    ),
                ],
            'tolower' => [
                    new self(
                        'tolower',
                        new StringType(),
                        [new StringType()]
                    ),
                ],
            'toupper' => [
                    new self(
                        'toupper',
                        new StringType(),
                        [new StringType()]
                    ),
                ],
            'trim' => [
                    new self(
                        'trim',
                        new StringType(),
                        [new StringType()]
                    ),
                ],
            'substring' => [
                    new self(

                        'substring',
                        new StringType(),
                        [new StringType(), new Int32()]
                    ),
                    new self(
                        'substring',
                        new StringType(),
                        [new StringType(), new Int32(), new Int32()]
                    ),
                ],
            'substringof' => [
                    new self(
                        'substringof',
                        new Boolean(),
                        [new StringType(), new StringType()]
                    ),
                ],
            'concat' => [
                    new self(
                        'concat',
                        new StringType(),
                        [new StringType(), new StringType()]
                    ),
                ],
            'length' => [
                    new self(
                        'length',
                        new Int32(),
                        [new StringType()]
                    ),
                ],
            //DateTime functions
            'year' => [
                    new self(
                        'year',
                        new Int32(),
                        [new DateTime()]
                    ),
                ],
            'month' => [
                    new self(
                        'month',
                        new Int32(),
                        [new DateTime()]
                    ),
                ],
            'day' => [
                    new self(
                        'day',
                        new Int32(),
                        [new DateTime()]
                    ),
                ],
            'hour' => [
                    new self(
                        'hour',
                        new Int32(),
                        [new DateTime()]
                    ),
                ],
            'minute' => [
                    new self(
                        'minute',
                        new Int32(),
                        [new DateTime()]
                    ),
                ],
            'second' => [
                    new self(
                        'second',
                        new Int32(),
                        [new DateTime()]
                    ),
                ],
            //Math Functions
            'round' => [
                    new self(
                        'round',
                        new Decimal(),
                        [new Decimal()]
                    ),
                    new self(
                        'round',
                        new Double(),
                        [new Double()]
                    ),
                ],
            'ceiling' => [
                    new self(
                        'ceiling',
                        new Decimal(),
                        [new Decimal()]
                    ),
                    new self(
                        'ceiling',
                        new Double(),
                        [new Double()]
                    ),
                ],
            'floor' => [
                    new self(
                        'floor',
                        new Decimal(),
                        [new Decimal()]
                    ),
                    new self(
                        'floor',
                        new Double(),
                        [new Double()]
                    ),
                ],
            ];

        return $functions;
    }

    /**
     * Get function description for string comparison.
     *
     * @return FunctionDescription[]
     */
    public static function stringComparisonFunctions()
    {
        return [
            new self(
                'strcmp',
                new Int32(),
                [new StringType(), new StringType()]
            ),
        ];
    }

    /**
     * Get function description for datetime comparison.
     *
     * @return FunctionDescription[]
     */
    public static function dateTimeComparisonFunctions()
    {
        return [
            new self(
                'dateTimeCmp',
                new Int32(),
                [new DateTime(), new DateTime()]
            ),
        ];
    }

    /**
     * Get function description for guid equality check.
     *
     * @return FunctionDescription[]
     */
    public static function guidEqualityFunctions()
    {
        return [
            new self(
                'guidEqual',
                new Boolean(),
                [new Guid(), new Guid()]
            ),
        ];
    }

    /**
     * Get function description for binary equality check.
     *
     * @return FunctionDescription[]
     */
    public static function binaryEqualityFunctions()
    {
        return [
            new self(
                'binaryEqual',
                new Boolean(),
                [new Binary(), new Binary()]
            ),
        ];
    }

    /**
     * Get function descriptions for arithmetic operations.
     *
     * @return FunctionDescription[]
     */
    public static function arithmeticOperationFunctions()
    {
        return [
            new self(
                'F',
                new int16(),
                [new int16(), new int16()]
            ),
            new self(
                'F',
                new int32(),
                [new int32(), new int32()]
            ),
            new self(
                'F',
                new int64(),
                [new int64(), new int64()]
            ),
            new self(
                'F',
                new Single(),
                [new Single(), new Single()]
            ),
            new self(
                'F',
                new Double(),
                [new Double(), new Double()]
            ),
            new self(
                'F',
                new Decimal(),
                [new Decimal(), new Decimal()]
            ),
        ];
    }

    /**
     * Get function descriptions for arithmetic add operations.
     *
     * @return FunctionDescription[] indexed by function name
     */
    public static function addOperationFunctions()
    {
        return self::arithmeticOperationFunctions();
    }

    /**
     * Get function descriptions for arithmetic subtract operations.
     *
     * @return FunctionDescription[] indexed by function name
     */
    public static function subtractOperationFunctions()
    {
        return self::arithmeticOperationFunctions();
    }

    /**
     * Get function descriptions for logical operations.
     *
     * @return FunctionDescription[]
     */
    public static function logicalOperationFunctions()
    {
        return [
            new self(
                'F',
                new Boolean(),
                [new Boolean(), new Boolean()]
            ),
        ];
    }

    /**
     * Get function descriptions for relational operations.
     *
     * @return FunctionDescription[]
     */
    public static function relationalOperationFunctions()
    {
        return array_merge(
            self::arithmeticOperationFunctions(),
            [
                new self(
                    'F',
                    new Boolean(),
                    [new Boolean(), new Boolean()]
                ),
                new self(
                    'F',
                    new DateTime(),
                    [new DateTime(), new DateTime()]
                ),
                new self(
                    'F',
                    new Guid(),
                    [new Guid(), new Guid()]
                ),
                new self(
                    'F',
                    new Boolean(),
                    [new Binary(), new Binary()]
                ),
            ]
        );
    }

    /**
     * Get function descriptions for unary not operation.
     *
     * @return FunctionDescription[]
     */
    public static function notOperationFunctions()
    {
        return [
            new self(
                'F',
                new Boolean(),
                [new Boolean()]
            ),
        ];
    }

    /**
     * Get function description for checking an operand is null or not.
     *
     * @param IType $type Type of the argument to null check function
     *
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription
     */
    public static function isNullCheckFunction(IType $type)
    {
        return new self('is_null', new Boolean(), [$type]);
    }

    /**
     * Get function description for unary negate operator.
     *
     * @return FunctionDescription[]
     */
    public static function negateOperationFunctions()
    {
        return [
            new self('F', new Int16(), [new Int16()]),
            new self('F', new Int32(), [new Int32()]),
            new self('F', new Int64(), [new Int64()]),
            new self('F', new Single(), [new Single()]),
            new self('F', new Double(), [new Double()]),
            new self('F', new Decimal(), [new Decimal()]),
        ];
    }

    /**
     * To throw ODataException for incompatible types.
     *
     * @param ExpressionToken      $expressionToken Expression token
     * @param AbstractExpression[] $argExpressions  Array of argument expression
     *
     * @throws ODataException
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
                $string,
                $expressionToken->Position
            )
        );
    }

    /**
     * Validate operands of an arithmetic operation and promote if required.
     *
     * @param ExpressionToken    $expressionToken The expression token
     * @param AbstractExpression $leftArgument    The left expression
     * @param AbstractExpression $rightArgument   The right expression
     *
     * @return IType
     */
    public static function verifyAndPromoteArithmeticOpArguments(
        $expressionToken,
        $leftArgument,
        $rightArgument
    ) {
        $function
            = self::findFunctionWithPromotion(
                self::arithmeticOperationFunctions(),
                [$leftArgument, $rightArgument]
            );
        if ($function == null) {
            self::incompatibleError(
                $expressionToken,
                [$leftArgument, $rightArgument]
            );
        }

        return $function->returnType;
    }

    /**
     * Validate operands of an logical operation.
     *
     * @param ExpressionToken    $expressionToken The expression token
     * @param AbstractExpression $leftArgument    The left expression
     * @param AbstractExpression $rightArgument   The right expression
     *
     * @throws ODataException
     */
    public static function verifyLogicalOpArguments(
        $expressionToken,
        $leftArgument,
        $rightArgument
    ) {
        $function = self::findFunctionWithPromotion(
            self::logicalOperationFunctions(),
            [$leftArgument, $rightArgument],
            false
        );
        if ($function == null) {
            self::incompatibleError(
                $expressionToken,
                [$leftArgument, $rightArgument]
            );
        }
    }

    /**
     * Validate operands of an relational operation.
     *
     * @param ExpressionToken    $expressionToken The expression token
     * @param AbstractExpression $leftArgument    The left argument expression
     * @param AbstractExpression $rightArgument   The right argument expression
     */
    public static function verifyRelationalOpArguments(
        $expressionToken,
        $leftArgument,
        $rightArgument
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
                        $expressionToken->Text,
                        $expressionToken->Position
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
                        $expressionToken->Text,
                        $expressionToken->Position
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
            $functions,
            [$leftArgument, $rightArgument],
            false
        );
        if ($function == null) {
            self::incompatibleError(
                $expressionToken,
                [$leftArgument, $rightArgument]
            );
        }
    }

    /**
     * Validate operands of a unary  operation.
     *
     * @param ExpressionToken    $expressionToken The expression token
     * @param AbstractExpression $argExpression   Argument expression
     *
     * @throws ODataException
     */
    public static function validateUnaryOpArguments($expressionToken, $argExpression)
    {
        //Unary not
        if (strcmp($expressionToken->Text, ODataConstants::KEYWORD_NOT) == 0) {
            $function = self::findFunctionWithPromotion(
                self::notOperationFunctions(),
                [$argExpression]
            );
            if ($function == null) {
                self::incompatibleError($expressionToken, [$argExpression]);
            }

            return;
        }

        //Unary minus (negation)
        if (strcmp($expressionToken->Text, '-') == 0) {
            if (self::findFunctionWithPromotion(self::negateOperationFunctions(), [$argExpression]) == null) {
                self::incompatibleError($expressionToken, [$argExpression]);
            }
        }
    }

    /**
     * Check am identifier is a valid filter function.
     *
     * @param ExpressionToken $expressionToken The expression token
     *
     * @throws ODataException
     *
     * @return FunctionDescription[] Array of matching functions
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

        $filterFunctions = self::filterFunctionDescriptions();

        return $filterFunctions[$expressionToken->Text];
    }

    /**
     * Validate operands (arguments) of a function call operation and return
     * matching function.
     *
     * @param \POData\UriProcessor\QueryProcessor\FunctionDescription[] $functions       List of functions to be checked
     * @param AbstractExpression[]                                      $argExpressions  Function argument expressions
     * @param ExpressionToken                                           $expressionToken Expression token
     *
     * @throws ODataException
     *
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription
     */
    public static function verifyFunctionCallOpArguments(
        $functions,
        $argExpressions,
        $expressionToken
    ) {
        $function
            = self::findFunctionWithPromotion($functions, $argExpressions, false);
        if ($function == null) {
            $protoTypes = null;
            foreach ($functions as $function) {
                $protoTypes .= $function->getPrototypeAsString() . '; ';
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
     * with types of expressions.
     *
     * @param \POData\UriProcessor\QueryProcessor\FunctionDescription[] $functionDescriptions List of functions
     * @param AbstractExpression[]                                      $argExpressions       Function argument expressions
     * @param bool                                                      $promoteArguments     Function argument
     *
     * @return \POData\UriProcessor\QueryProcessor\FunctionDescription|null Reference to the matching function if
     *                                                                      found else NULL
     */
    public static function findFunctionWithPromotion(
        $functionDescriptions,
        $argExpressions,
        $promoteArguments = true
    ) {
        $argCount = count($argExpressions);
        $applicableFunctions = [];
        foreach ($functionDescriptions as $functionDescription) {
            if (count($functionDescription->argumentTypes) == $argCount) {
                $applicableFunctions[] = $functionDescription;
            }
        }

        if (empty($applicableFunctions)) {
            return;
        }

        //Check for exact match
        foreach ($applicableFunctions as $function) {
            $i = 0;
            foreach ($function->argumentTypes as $argumentType) {
                if (!$argExpressions[$i]->typeIs($argumentType)) {
                    break;
                }

                ++$i;
            }

            if ($i == $argCount) {
                return $function;
            }
        }

        //Check match with promotion
        $promotedTypes = [];
        foreach ($applicableFunctions as $function) {
            $i = 0;
            $promotedTypes = [];
            foreach ($function->argumentTypes as $argumentType) {
                if (!$argumentType->isCompatibleWith($argExpressions[$i]->getType())) {
                    break;
                }

                $promotedTypes[] = $argumentType;
                ++$i;
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
    }
}
