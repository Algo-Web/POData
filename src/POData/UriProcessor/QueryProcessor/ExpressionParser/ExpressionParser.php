<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Common\Messages;
use POData\Common\NotImplementedException;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Decimal;
use POData\Providers\Metadata\Type\Double;
use POData\Providers\Metadata\Type\Guid;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\Int64;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\Null1;
use POData\Providers\Metadata\Type\Single;
use POData\Providers\Metadata\Type\StringType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use ReflectionException;

/**
 * Class ExpressionParser.
 */
class ExpressionParser
{
    const RECURSION_LIMIT = 200;

    /**
     * The Lexical analyzer.
     *
     * @var ExpressionLexer
     */
    private $lexer;

    /**
     * The current recursion depth.
     *
     * @var int
     */
    private $recursionDepth;

    /**
     * The ResourceType on which $filter condition needs to be applied.
     *
     * @var ResourceType
     */
    private $resourceType;

    /**
     * @var bool
     */
    private $isPHPExpressionProvider;

    /**
     * True if the filter expression contains level 2 property access, for example
     * Customers?$filter=Address/LineNumber eq 12
     * Customer?$filter=Order/OrderID gt 1234
     * False otherwise.
     *
     * @var bool
     */
    private $hasLevel2PropertyInTheExpression;

    /**
     * Construct a new instance of ExpressionParser.
     *
     * @param string       $text                    The expression to parse
     * @param ResourceType $resourceType            The resource type of the resource targeted by the resource path
     * @param bool         $isPHPExpressionProvider
     *
     * TODO Expression parser should not depend on the fact that end user is implementing IExpressionProvider or not
     * @throws ODataException
     */
    public function __construct($text, ResourceType $resourceType, $isPHPExpressionProvider)
    {
        $this->lexer                            = new ExpressionLexer($text);
        $this->resourceType                     = $resourceType;
        $this->isPHPExpressionProvider          = $isPHPExpressionProvider;
        $this->hasLevel2PropertyInTheExpression = false;
    }

    /**
     * Checks whether the expression contains level 2 property access.
     *
     * @return bool
     */
    public function hasLevel2Property()
    {
        return $this->hasLevel2PropertyInTheExpression;
    }

    /**
     * Resets parser with new expression string.
     *
     * @param  string         $text Reset the expression to parse
     * @throws ODataException
     */
    public function resetParser($text)
    {
        $this->lexer          = new ExpressionLexer($text);
        $this->recursionDepth = 0;
    }

    /**
     * Parse the expression in filter option.
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws NotImplementedException
     * @return AbstractExpression
     */
    public function parseFilter()
    {
        return $this->parseExpression();
    }

    /**
     * Start parsing the expression.
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws NotImplementedException
     * @return AbstractExpression
     */
    private function parseExpression(): AbstractExpression
    {
        $this->recurseEnter();
        $expr = $this->parseLogicalOr();
        $this->recurseLeave();

        return $expr;
    }

    /**
     * Increment recursion count and throw error if beyond limit.
     *
     *
     * @throws ODataException If max recursion limit hits
     */
    private function recurseEnter(): void
    {
        ++$this->recursionDepth;
        if ($this->recursionDepth == self::RECURSION_LIMIT) {
            throw ODataException::createSyntaxError('Recursion limit reached.');
        }
    }

    /**
     * Parse logical or (or).
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws NotImplementedException
     * @return AbstractExpression
     */
    private function parseLogicalOr(): AbstractExpression
    {
        $this->recurseEnter();
        $left = $this->parseLogicalAnd();
        while ($this->tokenIdentifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_OR)) {
            $logicalOpToken = clone $this->getCurrentToken();
            $this->getLexer()->nextToken();
            $right = $this->parseLogicalAnd();
            FunctionDescription::verifyLogicalOpArguments($logicalOpToken, $left, $right);
            $left = new LogicalExpression(
                $left,
                $right,
                ExpressionType::OR_LOGICAL()
            );
        }

        $this->recurseLeave();

        return $left;
    }

    /**
     * Parse logical and (and).
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws NotImplementedException
     * @return AbstractExpression
     */
    private function parseLogicalAnd(): AbstractExpression
    {
        $this->recurseEnter();
        $left = $this->parseComparison();
        while ($this->tokenIdentifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_AND)) {
            $logicalOpToken = clone $this->getCurrentToken();
            $this->getLexer()->nextToken();
            $right = $this->parseComparison();
            FunctionDescription::verifyLogicalOpArguments($logicalOpToken, $left, $right);
            $left = new LogicalExpression($left, $right, ExpressionType::AND_LOGICAL());
        }

        $this->recurseLeave();

        return $left;
    }

    /**
     * Parse comparison operation (eq, ne, gt, ge, lt, le).
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws NotImplementedException
     * @return AbstractExpression
     */
    private function parseComparison(): AbstractExpression
    {
        $this->recurseEnter();
        $left = $this->parseAdditive();
        while ($this->getCurrentToken()->isComparisonOperator()) {
            $comparisonToken = clone $this->getCurrentToken();
            $this->getLexer()->nextToken();
            $right = $this->parseAdditive();
            $left  = self::generateComparisonExpression(
                $left,
                $right,
                $comparisonToken,
                $this->isPHPExpressionProvider
            );
        }

        $this->recurseLeave();

        return $left;
    }

    /**
     * Parse additive operation (add, sub).
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws NotImplementedException
     * @return AbstractExpression
     */
    private function parseAdditive(): AbstractExpression
    {
        $this->recurseEnter();
        $left = $this->parseMultiplicative();
        while ($this->getCurrentToken()->identifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_ADD)
            || $this->getCurrentToken()->identifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_SUB)) {
            $additiveToken = clone $this->getCurrentToken();
            $this->getLexer()->nextToken();
            $right        = $this->parseMultiplicative();
            $opReturnType = FunctionDescription::verifyAndPromoteArithmeticOpArguments($additiveToken, $left, $right);
            if ($additiveToken->identifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_ADD)) {
                $left = new ArithmeticExpression($left, $right, ExpressionType::ADD(), $opReturnType);
            } else {
                $left = new ArithmeticExpression($left, $right, ExpressionType::SUBTRACT(), $opReturnType);
            }
        }

        $this->recurseLeave();

        return $left;
    }

    /**
     * Parse multiplicative operators (mul, div, mod).
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws NotImplementedException
     * @return AbstractExpression
     */
    private function parseMultiplicative(): AbstractExpression
    {
        $this->recurseEnter();
        $left = $this->parseUnary();
        while ($this->getCurrentToken()->identifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_MULTIPLY)
            || $this->getCurrentToken()->identifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_DIVIDE)
            || $this->getCurrentToken()->identifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_MODULO)
        ) {
            $multiplyToken = clone $this->getCurrentToken();
            $this->getLexer()->nextToken();
            $right        = $this->parseUnary();
            $opReturnType = FunctionDescription::verifyAndPromoteArithmeticOpArguments(
                $multiplyToken,
                $left,
                $right
            );
            if ($multiplyToken->identifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_MULTIPLY)) {
                $left = new ArithmeticExpression($left, $right, ExpressionType::MULTIPLY(), $opReturnType);
            } elseif ($multiplyToken->identifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_DIVIDE)) {
                $left = new ArithmeticExpression($left, $right, ExpressionType::DIVIDE(), $opReturnType);
            } else {
                $left = new ArithmeticExpression($left, $right, ExpressionType::MODULO(), $opReturnType);
            }
        }

        $this->recurseLeave();

        return $left;
    }

    /**
     * Parse unary operator (- ,not).
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws NotImplementedException
     * @return AbstractExpression
     */
    private function parseUnary(): AbstractExpression
    {
        $this->recurseEnter();

        if ($this->getCurrentToken()->getId() == ExpressionTokenId::MINUS()
            || $this->getCurrentToken()->identifierIs(/* @scrutinizer ignore-type */ ODataConstants::KEYWORD_NOT)
        ) {
            $op = clone $this->getCurrentToken();
            $this->getLexer()->nextToken();
            if ($op->getId() == ExpressionTokenId::MINUS()
                && (ExpressionLexer::isNumeric($this->getCurrentToken()->getId()))
            ) {
                $numberLiteral           = $this->getCurrentToken();
                $numberLiteral->Text     = '-' . $numberLiteral->Text;
                $numberLiteral->Position = $op->Position;
                $this->setCurrentToken($numberLiteral);
                $this->recurseLeave();

                return $this->parsePrimary();
            }

            $expr = $this->parsePrimary();
            FunctionDescription::validateUnaryOpArguments($op, $expr);
            if ($op->getId() == ExpressionTokenId::MINUS()) {
                $expr = new UnaryExpression($expr, ExpressionType::NEGATE(), $expr->getType());
            } else {
                $expr = new UnaryExpression($expr, ExpressionType::NOT_LOGICAL(), new Boolean());
            }

            $this->recurseLeave();

            return $expr;
        }

        $this->recurseLeave();

        return $this->parsePrimary();
    }

    /**
     * Get the current token from lexer.
     *
     * @return ExpressionToken
     */
    private function getCurrentToken(): ExpressionToken
    {
        return $this->getLexer()->getCurrentToken();
    }

    /**
     * Retrieve current lexer instance.
     *
     * @return ExpressionLexer
     */
    public function getLexer(): ExpressionLexer
    {
        return $this->lexer;
    }

    /**
     * Set the current token in lexer.
     *
     * @param ExpressionToken $token The token to set as current token
     */
    private function setCurrentToken(ExpressionToken $token): void
    {
        $this->getLexer()->setCurrentToken($token);
    }

    /**
     * Decrement recursion count.
     */
    private function recurseLeave(): void
    {
        --$this->recursionDepth;
    }

    /**
     * Start parsing the primary.
     *
     * @throws ODataException
     * @throws ReflectionException
     * @throws NotImplementedException
     * @return AbstractExpression
     */
    private function parsePrimary(): AbstractExpression
    {
        $this->recurseEnter();
        $expr = $this->parsePrimaryStart();
        while (true) {
            if ($this->getCurrentToken()->getId() == ExpressionTokenId::SLASH()) {
                $this->getLexer()->nextToken();
                $expr = $this->parsePropertyAccess($expr);
            } else {
                break;
            }
        }

        $this->recurseLeave();

        return $expr;
    }

    /**
     * Parse primary tokens [literals, identifiers (e.g. function call), open param for sub expressions].
     *
     * @throws NotImplementedException
     * @throws ReflectionException
     * @throws ODataException
     * @return AbstractExpression
     */
    private function parsePrimaryStart(): AbstractExpression
    {
        switch ($this->getLexer()->getCurrentToken()->getId()) {
            case ExpressionTokenId::BOOLEAN_LITERAL():
                return $this->parseTypedLiteral(new Boolean());
            case ExpressionTokenId::DATETIME_LITERAL():
                return $this->parseTypedLiteral(new DateTime());
            case ExpressionTokenId::DECIMAL_LITERAL():
                return $this->parseTypedLiteral(new Decimal());
            case ExpressionTokenId::NULL_LITERAL():
                return $this->parseNullLiteral();
            case ExpressionTokenId::IDENTIFIER():
                return $this->parseIdentifier();
            case ExpressionTokenId::STRING_LITERAL():
                return $this->parseTypedLiteral(new StringType());
            case ExpressionTokenId::INT64_LITERAL():
                return $this->parseTypedLiteral(new Int64());
            case ExpressionTokenId::INTEGER_LITERAL():
                return $this->parseTypedLiteral(new Int32());
            case ExpressionTokenId::DOUBLE_LITERAL():
                return $this->parseTypedLiteral(new Double());
            case ExpressionTokenId::SINGLE_LITERAL():
                return $this->parseTypedLiteral(new Single());
            case ExpressionTokenId::GUID_LITERAL():
                return $this->parseTypedLiteral(new Guid());
            case ExpressionTokenId::BINARY_LITERAL():
                throw new NotImplementedException(
                    'Support for binary is not implemented'
                );
            //return $this->parseTypedLiteral(new Binary());
            case ExpressionTokenId::OPENPARAM():
                return $this->parseParenExpression();
            default:
                throw ODataException::createSyntaxError('Expression expected.');
        }
    }

    /**
     * Parse primitive type literal.
     *
     * @param IType $targetType Expected type of the current literal
     *
     * @throws ODataException
     * @return ConstantExpression
     */
    private function parseTypedLiteral(IType $targetType): ConstantExpression
    {
        $literal = $this->getLexer()->getCurrentToken()->Text;
        $outVal  = null;
        if (!$targetType->validate($literal, $outVal)) {
            throw ODataException::createSyntaxError(
                Messages::expressionParserUnrecognizedLiteral(
                    $targetType->getFullTypeName(),
                    $literal,
                    $this->getLexer()->getCurrentToken()->Position
                )
            );
        }

        $result = new ConstantExpression($outVal, $targetType);
        $this->getLexer()->nextToken();

        return $result;
    }

    /**
     * Parse null literal.
     *
     * @throws ODataException
     * @return ConstantExpression
     */
    private function parseNullLiteral(): ConstantExpression
    {
        $this->getLexer()->nextToken();

        return new ConstantExpression(null, new Null1());
    }

    /**
     * Parse an identifier.
     *
     * @throws NotImplementedException
     * @throws ReflectionException
     * @throws ODataException
     * @return FunctionCallExpression|PropertyAccessExpression
     */
    private function parseIdentifier(): AbstractExpression
    {
        $this->validateToken(ExpressionTokenId::IDENTIFIER());

        // An open paren here would indicate calling a method
        $identifierIsFunction = $this->getLexer()->peekNextToken()->getId() == ExpressionTokenId::OPENPARAM();
        if ($identifierIsFunction) {
            return $this->parseIdentifierAsFunction();
        } else {
            return $this->parsePropertyAccess(null);
        }
    }

    /**
     * Validate the current token.
     *
     * @param ExpressionTokenId $expressionTokenId Token to check
     *                                             with current token
     *
     * @throws ODataException
     */
    private function validateToken(ExpressionTokenId $expressionTokenId): void
    {
        if ($this->getCurrentToken()->getId() != $expressionTokenId) {
            throw ODataException::createSyntaxError('Syntax error.');
        }
    }

    /**
     * Try to parse an identifier which is followed by an open bracket as an astoria URI function call.
     *
     * @throws NotImplementedException
     * @throws ReflectionException
     * @throws ODataException
     * @return FunctionCallExpression
     */
    private function parseIdentifierAsFunction(): FunctionCallExpression
    {
        $functionToken = clone $this->getCurrentToken();
        $functions     = FunctionDescription::verifyFunctionExists($functionToken);
        $this->getLexer()->nextToken();
        $paramExpressions = $this->parseArgumentList();
        $function         = FunctionDescription::verifyFunctionCallOpArguments(
            $functions,
            $paramExpressions,
            $functionToken
        );

        return new FunctionCallExpression($function, $paramExpressions);
    }

    /**
     * Start parsing argument list of a function-call.
     *
     * @throws NotImplementedException
     * @throws ReflectionException
     * @throws ODataException
     * @return array<AbstractExpression>
     */
    private function parseArgumentList(): array
    {
        if ($this->getCurrentToken()->getId() != ExpressionTokenId::OPENPARAM()) {
            throw ODataException::createSyntaxError('Open parenthesis expected.');
        }

        $this->getLexer()->nextToken();
        $args = $this->getCurrentToken()->getId() != ExpressionTokenId::CLOSEPARAM()
            ? $this->parseArguments() : [];
        if ($this->getCurrentToken()->getId() != ExpressionTokenId::CLOSEPARAM()) {
            throw ODataException::createSyntaxError('Close parenthesis expected.');
        }

        $this->getLexer()->nextToken();

        return $args;
    }

    /**
     * Parse arguments of a function-call.
     *
     * @throws NotImplementedException
     * @throws ReflectionException
     * @throws ODataException
     * @return array<AbstractExpression>
     */
    private function parseArguments(): array
    {
        $argList = [];
        while (true) {
            $argList[] = $this->parseExpression();
            if ($this->getCurrentToken()->getId() != ExpressionTokenId::COMMA()) {
                break;
            }

            $this->getLexer()->nextToken();
        }

        return $argList;
    }

    /**
     * Parse a property access.
     *
     * @param PropertyAccessExpression|null $parentExpression Parent expression
     *
     * @throws ReflectionException
     * @throws ODataException
     * @return PropertyAccessExpression
     */
    private function parsePropertyAccess(PropertyAccessExpression $parentExpression = null): PropertyAccessExpression
    {
        $identifier = $this->getCurrentToken()->getIdentifier();
        if (null === $parentExpression) {
            $parentResourceType = $this->resourceType;
        } else {
            $parentResourceType                     = $parentExpression->getResourceType();
            $this->hasLevel2PropertyInTheExpression = true;
        }

        $resourceProperty = $parentResourceType->resolveProperty($identifier);
        if (null === $resourceProperty) {
            throw ODataException::createSyntaxError(
                Messages::expressionLexerNoPropertyInType(
                    $identifier,
                    $parentResourceType->getFullName(),
                    $this->getCurrentToken()->Position
                )
            );
        }

        if (($resourceProperty->getKind()) == ResourcePropertyKind::RESOURCESET_REFERENCE()) {
            throw ODataException::createSyntaxError(
                Messages::expressionParserEntityCollectionNotAllowedInFilter(
                    $resourceProperty->getName(),
                    $parentResourceType->getFullName(),
                    $this->getCurrentToken()->Position
                )
            );
        }

        $exp = new PropertyAccessExpression($resourceProperty, $parentExpression);
        $this->getLexer()->nextToken();

        return $exp;
    }

    /**
     * Parse Sub expression.
     *
     * @throws NotImplementedException
     * @throws ReflectionException
     * @throws ODataException
     * @return AbstractExpression
     */
    private function parseParenExpression(): AbstractExpression
    {
        if ($this->getCurrentToken()->getId() != ExpressionTokenId::OPENPARAM()) {
            throw ODataException::createSyntaxError('Open parenthesis expected.');
        }

        $this->getLexer()->nextToken();
        $expr = $this->parseExpression();
        if ($this->getCurrentToken()->getId() != ExpressionTokenId::CLOSEPARAM()) {
            throw ODataException::createSyntaxError('Close parenthesis expected.');
        }

        $this->getLexer()->nextToken();

        return $expr;
    }

    /**
     * Generates Comparison Expression.
     *
     * @param AbstractExpression $left                    The LHS expression
     * @param AbstractExpression $right                   The RHS expression
     * @param ExpressionToken    $expressionToken         The comparison expression token
     * @param bool               $isPHPExpressionProvider
     *
     * @throws ODataException
     * @return FunctionCallExpression|UnaryExpression|RelationalExpression
     */
    private static function generateComparisonExpression(
        $left,
        $right,
        $expressionToken,
        $isPHPExpressionProvider
    ): AbstractExpression {
        FunctionDescription::verifyRelationalOpArguments($expressionToken, $left, $right);

        //We need special handling for comparison of following types:
        //1. EdmString
        //2. DateTime
        //3. Guid
        //4. Binary
        //Will make these comparison as function calls, which will
        // be converted to language specific function call by expression
        // provider
        $string = new StringType();
        if ($left->typeIs($string) && $right->typeIs($string)) {
            $strcmpFunctions = FunctionDescription::stringComparisonFunctions();
            $left            = new FunctionCallExpression($strcmpFunctions[0], [$left, $right]);
            $right           = new ConstantExpression(0, new Int32());
        }

        $dateTime = new DateTime();
        if ($left->typeIs($dateTime) && $right->typeIs($dateTime)) {
            $dateTimeCmpFunctions = FunctionDescription::dateTimeComparisonFunctions();
            $left                 = new FunctionCallExpression($dateTimeCmpFunctions[0], [$left, $right]);
            $right                = new ConstantExpression(0, new Int32());
        }

        $guid = new Guid();
        if ($left->typeIs($guid) && $right->typeIs($guid)) {
            $guidEqualityFunctions = FunctionDescription::guidEqualityFunctions();
            $left                  = new FunctionCallExpression($guidEqualityFunctions[0], [$left, $right]);
            $right                 = new ConstantExpression(true, new Boolean());
        }

        $binary = new Binary();
        if ($left->typeIs($binary) && $right->typeIs($binary)) {
            $binaryEqualityFunctions = FunctionDescription::binaryEqualityFunctions();
            $left                    = new FunctionCallExpression($binaryEqualityFunctions[0], [$left, $right]);
            $right                   = new ConstantExpression(true, new Boolean());
        }

        $null = new Null1();
        if ($left->typeIs($null) || $right->typeIs($null)) {
            // If the end user is responsible for implementing IExpressionProvider
            // then the sub-tree for a nullability check would be:

            //          RelationalExpression(EQ/NE)
            //                    |
            //               ------------
            //               |           |
            //               |           |
            //            CustomerID    NULL

            // Otherwise (In case of default PHPExpressionProvider):

            //  CustomerID eq null
            //  ==================

            //              FunctionCallExpression(is_null)
            //                       |
            //                       |- Signature => bool (typeof(CustomerID))
            //                       |- args => {CustomerID}

            //  CustomerID ne null
            //  ==================

            //              UnaryExpression (not)
            //                       |
            //              FunctionCallExpression(is_null)
            //                       |
            //                       |- Signature => bool (typeof(CustomerID))
            //                       |- args => {CustomerID}

            if ($isPHPExpressionProvider) {
                $arg                       = $left->typeIs($null) ? $right : $left;
                $isNullFunctionDescription = new FunctionDescription('is_null', new Boolean(), [$arg->getType()]);
                switch ($expressionToken->Text) {
                    case ODataConstants::KEYWORD_EQUAL:
                        return new FunctionCallExpression($isNullFunctionDescription, [$arg]);
                    case ODataConstants::KEYWORD_NOT_EQUAL:
                        return new UnaryExpression(
                            new FunctionCallExpression($isNullFunctionDescription, [$arg]),
                            ExpressionType::NOT_LOGICAL(),
                            new Boolean()
                        );
                }
            }
        }

        switch ($expressionToken->Text) {
            case ODataConstants::KEYWORD_EQUAL:
                return new RelationalExpression(
                    $left,
                    $right,
                    ExpressionType::EQUAL()
                );
            case ODataConstants::KEYWORD_NOT_EQUAL:
                return new RelationalExpression(
                    $left,
                    $right,
                    ExpressionType::NOTEQUAL()
                );
            case ODataConstants::KEYWORD_GREATERTHAN:
                return new RelationalExpression(
                    $left,
                    $right,
                    ExpressionType::GREATERTHAN()
                );
            case ODataConstants::KEYWORD_GREATERTHAN_OR_EQUAL:
                return new RelationalExpression(
                    $left,
                    $right,
                    ExpressionType::GREATERTHAN_OR_EQUAL()
                );
            case ODataConstants::KEYWORD_LESSTHAN:
                return new RelationalExpression(
                    $left,
                    $right,
                    ExpressionType::LESSTHAN()
                );
            default:
                return new RelationalExpression(
                    $left,
                    $right,
                    ExpressionType::LESSTHAN_OR_EQUAL()
                );
        }
    }

    /**
     * Check the current token is of a specific kind.
     *
     * @param ExpressionTokenId $expressionTokenId Token to check
     *                                             with current token
     *
     * @return bool
     */
    private function tokenIdentifierIs($expressionTokenId): bool
    {
        return $this->getCurrentToken()->identifierIs($expressionTokenId);
    }
}
