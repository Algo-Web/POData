<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Providers\Metadata\Type\Char;

/**
 * Class ExpressionLexer.
 *
 * Lexical analyzer for Astoria URI expression parsing
 * Literals        Representation
 * --------------------------------------------------------------------
 * Null            null
 * Boolean         true | false
 * Int32           (digit+)
 * Int64           (digit+)(L|l)
 * Decimal         (digit+ ['.' digit+])(M|m)
 * Float (Single)  (digit+ ['.' digit+][e|E [+|-] digit+)(f|F)
 * Double          (digit+ ['.' digit+][e|E [+|-] digit+)
 * String          "'" .* "'"
 * DateTime        datetime"'"dddd-dd-dd[T|' ']dd:mm[ss[.fffffff]]"'"
 * Binary          (binary|X)'digit*'
 * GUID            guid'digit*
 */
class ExpressionLexer
{
    /**
     * Suffix for single literals.
     *
     * @var char
     */
    const SINGLE_SUFFIX_LOWER = 'f';

    /**
     * Suffix for single literals.
     *
     * @var char
     */
    const SINGLE_SUFFIX_UPPER = 'F';

    /**
     * Text being parsed.
     *
     * @var char[]
     */
    private $text;

    /**
     * Length of text being parsed.
     *
     * @var int
     */
    private $textLen;

    /**
     * Position on text being parsed.
     *
     * @var int
     */
    private $textPos;

    /**
     * Character being processed.
     *
     * @var char
     */
    private $ch;

    /**
     * ExpressionToken being processed.
     *
     * @var ExpressionToken
     */
    private $token;

    /**
     * Initialize a new instance of ExpressionLexer.
     *
     * @param string $expression Expression to parse
     */
    public function __construct($expression)
    {
        $this->text = $expression;
        $this->textLen = strlen($this->text);
        $this->token = new ExpressionToken();
        $this->setTextPos(0);
        $this->nextToken();
    }

    /**
     * To get the expression token being processed.
     *
     * @return ExpressionToken
     */
    public function getCurrentToken()
    {
        return $this->token;
    }

    /**
     * To set the token being processed.
     *
     * @param ExpressionToken $token The expression token to set as current
     */
    public function setCurrentToken($token)
    {
        $this->token = $token;
    }

    /**
     * To get the text being parsed.
     *
     * @return char[]
     */
    public function getExpressionText()
    {
        return $this->text;
    }

    /**
     * Position of the current token in the text being parsed.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->token->Position;
    }

    /**
     * Whether the specified token identifier is a numeric literal.
     *
     * @param ExpressionTokenId $id Token identifier to check
     *
     * @return bool true if it's a numeric literal; false otherwise
     */
    public static function isNumeric($id)
    {
        return
            $id == ExpressionTokenId::INTEGER_LITERAL
            || $id == ExpressionTokenId::DECIMAL_LITERAL
            || $id == ExpressionTokenId::DOUBLE_LITERAL
            || $id == ExpressionTokenId::INT64_LITERAL
            || $id == ExpressionTokenId::SINGLE_LITERAL;
    }

    /**
     * Reads the next token, skipping whitespace as necessary.
     */
    public function nextToken()
    {
        while (Char::isWhiteSpace($this->ch)) {
            $this->nextChar();
        }

        $t = null;
        $tokenPos = $this->textPos;
        switch ($this->ch) {
            case '(':
                $this->nextChar();
                $t = ExpressionTokenId::OPENPARAM;
                break;
            case ')':
                $this->nextChar();
                $t = ExpressionTokenId::CLOSEPARAM;
                break;
            case ',':
                $this->nextChar();
                $t = ExpressionTokenId::COMMA;
                break;
            case '-':
                $hasNext = $this->textPos + 1 < $this->textLen;
                if ($hasNext && Char::isDigit($this->text[$this->textPos + 1])) {
                    $this->nextChar();
                    $t = $this->parseFromDigit();
                    if (self::isNumeric($t)) {
                        break;
                    }

                    $this->setTextPos($tokenPos);
                } elseif ($hasNext && $this->text[$tokenPos + 1] == 'I') {
                    $this->nextChar();
                    $this->parseIdentifier();
                    $currentIdentifier = substr($this->text, $tokenPos + 1, $this->textPos - $tokenPos - 1);

                    if (self::isInfinityLiteralDouble($currentIdentifier)) {
                        $t = ExpressionTokenId::DOUBLE_LITERAL;
                        break;
                    } elseif (self::isInfinityLiteralSingle($currentIdentifier)) {
                        $t = ExpressionTokenId::SINGLE_LITERAL;
                        break;
                    }

                    // If it looked like '-INF' but wasn't we'll rewind and fall
                    // through to a simple '-' token.
                    $this->setTextPos($tokenPos);
                }

                $this->nextChar();
                $t = ExpressionTokenId::MINUS;
                break;
            case '=':
                $this->nextChar();
                $t = ExpressionTokenId::EQUAL;
                break;
            case '/':
                $this->nextChar();
                $t = ExpressionTokenId::SLASH;
                break;
            case '?':
                $this->nextChar();
                $t = ExpressionTokenId::QUESTION;
                break;
            case '.':
                $this->nextChar();
                $t = ExpressionTokenId::DOT;
                break;
            case '\'':
                $quote = $this->ch;
                do {
                    $this->nextChar();
                    while ($this->textPos < $this->textLen && $this->ch != $quote) {
                        $this->nextChar();
                    }

                    if ($this->textPos == $this->textLen) {
                        $this->parseError(
                            Messages::expressionLexerUnterminatedStringLiteral(
                                $this->textPos,
                                $this->text
                            )
                        );
                    }

                    $this->nextChar();
                } while ($this->ch == $quote);
                $t = ExpressionTokenId::STRING_LITERAL;
                break;
            case '*':
                $this->nextChar();
                $t = ExpressionTokenId::STAR;
                break;
            default:
                if (Char::isLetter($this->ch) || $this->ch == '_') {
                    $this->parseIdentifier();
                    $t = ExpressionTokenId::IDENTIFIER;
                    break;
                }

                if (Char::isDigit($this->ch)) {
                    $t = $this->parseFromDigit();
                    break;
                }

                if ($this->textPos == $this->textLen) {
                    $t = ExpressionTokenId::END;
                    break;
                }

                $this->parseError(
                    Messages::expressionLexerInvalidCharacter(
                        $this->ch,
                        $this->textPos
                    )
                );
        }

        $this->token->Id = $t;
        $this->token->Text = substr($this->text, $tokenPos, $this->textPos - $tokenPos);
        $this->token->Position = $tokenPos;

        // Handle type-prefixed literals such as binary, datetime or guid.
        $this->handleTypePrefixedLiterals();

        // Handle keywords.
        if ($this->token->Id == ExpressionTokenId::IDENTIFIER) {
            if (self::isInfinityOrNaNDouble($this->token->Text)) {
                $this->token->Id = ExpressionTokenId::DOUBLE_LITERAL;
            } elseif (self::isInfinityOrNanSingle($this->token->Text)) {
                $this->token->Id = ExpressionTokenId::SINGLE_LITERAL;
            } elseif ($this->token->Text == ODataConstants::KEYWORD_TRUE
                || $this->token->Text == ODataConstants::KEYWORD_FALSE
            ) {
                $this->token->Id = ExpressionTokenId::BOOLEAN_LITERAL;
            } elseif ($this->token->Text == ODataConstants::KEYWORD_NULL) {
                $this->token->Id = ExpressionTokenId::NULL_LITERAL;
            }
        }
    }

    /**
     * Returns the next token without advancing the lexer to next token.
     *
     * @return ExpressionToken
     */
    public function peekNextToken()
    {
        $savedTextPos = $this->textPos;
        $savedChar = $this->ch;
        $savedToken = clone $this->token;
        $this->nextToken();
        $result = clone $this->token;
        $this->textPos = $savedTextPos;
        $this->ch = $savedChar;
        $this->token->Id = $savedToken->Id;
        $this->token->Position = $savedToken->Position;
        $this->token->Text = $savedToken->Text;

        return $result;
    }

    /**
     * Validates the current token is of the specified kind.
     *
     * @param ExpressionTokenId $tokenId Expected token kind
     *
     * @throws ODataException if current token is not of the
     *                        specified kind
     */
    public function validateToken($tokenId)
    {
        if ($this->token->Id != $tokenId) {
            $this->parseError(
                Messages::expressionLexerSyntaxError(
                    $this->textPos
                )
            );
        }
    }

    /**
     * Starting from an identifier, reads alternate sequence of dots and identifiers
     * and returns the text for it.
     *
     * @return string The dotted identifier starting at the current identifier
     */
    public function readDottedIdentifier()
    {
        $this->validateToken(ExpressionTokenId::IDENTIFIER);
        $identifier = $this->token->Text;
        $this->nextToken();
        while ($this->token->Id == ExpressionTokenId::DOT) {
            $this->nextToken();
            $this->validateToken(ExpressionTokenId::IDENTIFIER);
            $identifier = $identifier . '.' . $this->token->Text;
            $this->nextToken();
        }

        return $identifier;
    }

    /**
     * Check if the parameter ($tokenText) is INF or NaN.
     *
     * @param string $tokenText Text to look in
     *
     * @return bool true if match found, false otherwise
     */
    private static function isInfinityOrNaNDouble($tokenText)
    {
        if (3 == strlen($tokenText)) {
            if ('I' == $tokenText[0]) {
                return self::isInfinityLiteralDouble($tokenText);
            } elseif ('N' == $tokenText[0]) {
                return 0 == strncmp($tokenText, ODataConstants::XML_NAN_LITERAL, 3);
            }
        }

        return false;
    }

    /**
     * Check if the parameter ($text) is INF.
     *
     * @param string $text Text to look in
     *
     * @return bool true if match found, false otherwise
     */
    private static function isInfinityLiteralDouble($text)
    {
        return 0 == strcmp($text, ODataConstants::XML_INFINITY_LITERAL);
    }

    /**
     * Checks if the parameter ($tokenText) is INFf/INFF or NaNf/NaNF.
     *
     * @param string $tokenText Input token
     *
     * @return bool true if match found, false otherwise
     */
    private static function isInfinityOrNanSingle($tokenText)
    {
        if (4 == strlen($tokenText)) {
            if ('I' == $tokenText[0]) {
                return self::isInfinityLiteralSingle($tokenText);
            } elseif ('N' == $tokenText[0]) {
                return ($tokenText[3] == self::SINGLE_SUFFIX_LOWER
                    || $tokenText[3] == self::SINGLE_SUFFIX_UPPER)
                    && 0 == strncmp($tokenText, ODataConstants::XML_NAN_LITERAL, 3);
            }
        }

        return false;
    }

    /**
     * Checks whether parameter ($text) EQUALS to 'INFf' or 'INFF' at position.
     *
     * @param string $text Text to look in
     *
     * @return bool true if the substring is equal using an ordinal comparison;
     *              false otherwise
     */
    private static function isInfinityLiteralSingle($text)
    {
        return strlen($text) == 4
            && ($text[3] == self::SINGLE_SUFFIX_LOWER
            || $text[3] == self::SINGLE_SUFFIX_UPPER)
            && strncmp($text, ODataConstants::XML_INFINITY_LITERAL, 3) == 0;
    }

    /**
     * Handles the literals that are prefixed by types.
     * This method modified the token field as necessary.
     *
     *
     * @throws ODataException
     */
    private function handleTypePrefixedLiterals()
    {
        $id = $this->token->Id;
        if ($id != ExpressionTokenId::IDENTIFIER) {
            return;
        }

        $quoteFollows = $this->ch == '\'';
        if (!$quoteFollows) {
            return;
        }

        $tokenText = $this->token->Text;

        if (strcasecmp('datetime', $tokenText) == 0) {
            $id = ExpressionTokenId::DATETIME_LITERAL;
        } elseif (strcasecmp('guid', $tokenText) == 0) {
            $id = ExpressionTokenId::GUID_LITERAL;
        } elseif (strcasecmp('binary', $tokenText) == 0
            || strcasecmp('X', $tokenText) == 0
            || strcasecmp('x', $tokenText) == 0
        ) {
            $id = ExpressionTokenId::BINARY_LITERAL;
        } else {
            return;
        }

        $tokenPos = $this->token->Position;
        do {
            $this->nextChar();
        } while ($this->ch != '\0' && $this->ch != '\'');

        if ($this->ch == '\0') {
            $this->parseError(
                Messages::expressionLexerUnterminatedStringLiteral(
                    $this->textPos,
                    $this->text
                )
            );
        }

        $this->nextChar();
        $this->token->Id = $id;
        $this->token->Text
            = substr($this->text, $tokenPos, $this->textPos - $tokenPos);
    }

    /**
     * Parses a token that starts with a digit.
     *
     * @return ExpressionTokenId The kind of token recognized
     */
    private function parseFromDigit()
    {
        $result = null;
        $startChar = $this->ch;
        $this->nextChar();
        if ($startChar == '0' && $this->ch == 'x' || $this->ch == 'X') {
            $result = ExpressionTokenId::BINARY_LITERAL;
            do {
                $this->nextChar();
            } while (ctype_xdigit($this->ch));
        } else {
            $result = ExpressionTokenId::INTEGER_LITERAL;
            while (Char::isDigit($this->ch)) {
                $this->nextChar();
            }

            if ($this->ch == '.') {
                $result = ExpressionTokenId::DOUBLE_LITERAL;
                $this->nextChar();
                $this->validateDigit();

                do {
                    $this->nextChar();
                } while (Char::isDigit($this->ch));
            }

            if ($this->ch == 'E' || $this->ch == 'e') {
                $result = ExpressionTokenId::DOUBLE_LITERAL;
                $this->nextChar();
                if ($this->ch == '+' || $this->ch == '-') {
                    $this->nextChar();
                }

                $this->validateDigit();
                do {
                    $this->nextChar();
                } while (Char::isDigit($this->ch));
            }

            if ($this->ch == 'M' || $this->ch == 'm') {
                $result = ExpressionTokenId::DECIMAL_LITERAL;
                $this->nextChar();
            } elseif ($this->ch == 'd' || $this->ch == 'D') {
                $result = ExpressionTokenId::DOUBLE_LITERAL;
                $this->nextChar();
            } elseif ($this->ch == 'L' || $this->ch == 'l') {
                $result = ExpressionTokenId::INT64_LITERAL;
                $this->nextChar();
            } elseif ($this->ch == 'f' || $this->ch == 'F') {
                $result = ExpressionTokenId::SINGLE_LITERAL;
                $this->nextChar();
            }
        }

        return $result;
    }

    /**
     * Parses an identifier by advancing the current character.
     */
    private function parseIdentifier()
    {
        do {
            $this->nextChar();
        } while (Char::isLetterOrDigit($this->ch) || $this->ch == '_');
    }

    /**
     * Advance to next character.
     */
    private function nextChar()
    {
        if ($this->textPos < $this->textLen) {
            ++$this->textPos;
        }

        $this->ch
            = $this->textPos < $this->textLen
             ? $this->text[$this->textPos] : '\0';
    }

    /**
     * Set the text position.
     *
     * @param int $pos Value to position
     */
    private function setTextPos($pos)
    {
        $this->textPos = $pos;
        $this->ch
            = $this->textPos < $this->textLen
             ? $this->text[$this->textPos] : '\0';
    }

    /**
     * Validate current character is a digit.
     */
    private function validateDigit()
    {
        if (!Char::isDigit($this->ch)) {
            $this->parseError(
                Messages::expressionLexerDigitExpected(
                    $this->textPos
                )
            );
        }
    }

    /**
     * Throws parser error.
     *
     * @param string $message The error message
     *
     * @throws ODataException
     */
    private function parseError($message)
    {
        throw ODataException::createSyntaxError($message);
    }
}
