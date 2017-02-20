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
    private $_text;

    /**
     * Length of text being parsed.
     *
     * @var int
     */
    private $_textLen;

    /**
     * Position on text being parsed.
     *
     * @var int
     */
    private $_textPos;

    /**
     * Character being processed.
     *
     * @var char
     */
    private $_ch;

    /**
     * ExpressionToken being processed.
     *
     * @var ExpressionToken
     */
    private $_token;

    /**
     * Initialize a new instance of ExpressionLexer.
     *
     * @param string $expression Expression to parse
     */
    public function __construct($expression)
    {
        $this->_text = $expression;
        $this->_textLen = strlen($this->_text);
        $this->_token = new ExpressionToken();
        $this->_setTextPos(0);
        $this->nextToken();
    }

    /**
     * To get the expression token being processed.
     *
     * @return ExpressionToken
     */
    public function getCurrentToken()
    {
        return $this->_token;
    }

    /**
     * To set the token being processed.
     *
     * @param ExpressionToken $token The expression token to set as current
     */
    public function setCurrentToken($token)
    {
        $this->_token = $token;
    }

    /**
     * To get the text being parsed.
     *
     * @return char[]
     */
    public function getExpressionText()
    {
        return $this->_text;
    }

    /**
     * Position of the current token in the text being parsed.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->_token->Position;
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
        while (Char::isWhiteSpace($this->_ch)) {
            $this->_nextChar();
        }

        $t = null;
        $tokenPos = $this->_textPos;
        switch ($this->_ch) {
            case '(':
                $this->_nextChar();
                $t = ExpressionTokenId::OPENPARAM;
                break;
            case ')':
                $this->_nextChar();
                $t = ExpressionTokenId::CLOSEPARAM;
                break;
            case ',':
                $this->_nextChar();
                $t = ExpressionTokenId::COMMA;
                break;
            case '-':
                $hasNext = $this->_textPos + 1 < $this->_textLen;
                if ($hasNext && Char::isDigit($this->_text[$this->_textPos + 1])) {
                    $this->_nextChar();
                    $t = $this->_parseFromDigit();
                    if (self::isNumeric($t)) {
                        break;
                    }

                    $this->_setTextPos($tokenPos);
                } elseif ($hasNext && $this->_text[$tokenPos + 1] == 'I') {
                    $this->_nextChar();
                    $this->_parseIdentifier();
                    $currentIdentifier = substr($this->_text, $tokenPos + 1, $this->_textPos - $tokenPos - 1);

                    if (self::_isInfinityLiteralDouble($currentIdentifier)) {
                        $t = ExpressionTokenId::DOUBLE_LITERAL;
                        break;
                    } elseif (self::_isInfinityLiteralSingle($currentIdentifier)) {
                        $t = ExpressionTokenId::SINGLE_LITERAL;
                        break;
                    }

                    // If it looked like '-INF' but wasn't we'll rewind and fall
                    // through to a simple '-' token.
                    $this->_setTextPos($tokenPos);
                }

                $this->_nextChar();
                $t = ExpressionTokenId::MINUS;
                break;
            case '=':
                $this->_nextChar();
                $t = ExpressionTokenId::EQUAL;
                break;
            case '/':
                $this->_nextChar();
                $t = ExpressionTokenId::SLASH;
                break;
            case '?':
                $this->_nextChar();
                $t = ExpressionTokenId::QUESTION;
                break;
            case '.':
                $this->_nextChar();
                $t = ExpressionTokenId::DOT;
                break;
            case '\'':
                $quote = $this->_ch;
                do {
                    $this->_nextChar();
                    while ($this->_textPos < $this->_textLen && $this->_ch != $quote) {
                        $this->_nextChar();
                    }

                    if ($this->_textPos == $this->_textLen) {
                        $this->_parseError(
                            Messages::expressionLexerUnterminatedStringLiteral(
                                $this->_textPos,
                                $this->_text
                            )
                        );
                    }

                    $this->_nextChar();
                } while ($this->_ch == $quote);
                $t = ExpressionTokenId::STRING_LITERAL;
                break;
            case '*':
                $this->_nextChar();
                $t = ExpressionTokenId::STAR;
                break;
            default:
                if (Char::isLetter($this->_ch) || $this->_ch == '_') {
                    $this->_parseIdentifier();
                    $t = ExpressionTokenId::IDENTIFIER;
                    break;
                }

                if (Char::isDigit($this->_ch)) {
                    $t = $this->_parseFromDigit();
                    break;
                }

                if ($this->_textPos == $this->_textLen) {
                    $t = ExpressionTokenId::END;
                    break;
                }

                $this->_parseError(
                    Messages::expressionLexerInvalidCharacter(
                        $this->_ch,
                        $this->_textPos
                    )
                );
        }

        $this->_token->Id = $t;
        $this->_token->Text = substr($this->_text, $tokenPos, $this->_textPos - $tokenPos);
        $this->_token->Position = $tokenPos;

        // Handle type-prefixed literals such as binary, datetime or guid.
        $this->_handleTypePrefixedLiterals();

        // Handle keywords.
        if ($this->_token->Id == ExpressionTokenId::IDENTIFIER) {
            if (self::_isInfinityOrNaNDouble($this->_token->Text)) {
                $this->_token->Id = ExpressionTokenId::DOUBLE_LITERAL;
            } elseif (self::_isInfinityOrNanSingle($this->_token->Text)) {
                $this->_token->Id = ExpressionTokenId::SINGLE_LITERAL;
            } elseif ($this->_token->Text == ODataConstants::KEYWORD_TRUE
                || $this->_token->Text == ODataConstants::KEYWORD_FALSE
            ) {
                $this->_token->Id = ExpressionTokenId::BOOLEAN_LITERAL;
            } elseif ($this->_token->Text == ODataConstants::KEYWORD_NULL) {
                $this->_token->Id = ExpressionTokenId::NULL_LITERAL;
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
        $savedTextPos = $this->_textPos;
        $savedChar = $this->_ch;
        $savedToken = clone $this->_token;
        $this->nextToken();
        $result = clone $this->_token;
        $this->_textPos = $savedTextPos;
        $this->_ch = $savedChar;
        $this->_token->Id = $savedToken->Id;
        $this->_token->Position = $savedToken->Position;
        $this->_token->Text = $savedToken->Text;

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
        if ($this->_token->Id != $tokenId) {
            $this->_parseError(
                Messages::expressionLexerSyntaxError(
                    $this->_textPos
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
        $identifier = $this->_token->Text;
        $this->nextToken();
        while ($this->_token->Id == ExpressionTokenId::DOT) {
            $this->nextToken();
            $this->validateToken(ExpressionTokenId::IDENTIFIER);
            $identifier = $identifier . '.' . $this->_token->Text;
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
    private static function _isInfinityOrNaNDouble($tokenText)
    {
        if (strlen($tokenText) == 3) {
            if ($tokenText[0] == 'I') {
                return self::_isInfinityLiteralDouble($tokenText);
            } elseif ($tokenText[0] == 'N') {
                return strncmp($tokenText, ODataConstants::XML_NAN_LITERAL, 3) == 0;
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
    private static function _isInfinityLiteralDouble($text)
    {
        return strcmp($text, ODataConstants::XML_INFINITY_LITERAL) == 0;
    }

    /**
     * Checks if the parameter ($tokenText) is INFf/INFF or NaNf/NaNF.
     *
     * @param string $tokenText Input token
     *
     * @return bool true if match found, false otherwise
     */
    private static function _isInfinityOrNanSingle($tokenText)
    {
        if (strlen($tokenText) == 4) {
            if ($tokenText[0] == 'I') {
                return self::_isInfinityLiteralSingle($tokenText);
            } elseif ($tokenText[0] == 'N') {
                return ($tokenText[3] == self::SINGLE_SUFFIX_LOWER
                    || $tokenText[3] == self::SINGLE_SUFFIX_UPPER)
                    && strncmp($tokenText, ODataConstants::XML_NAN_LITERAL, 3) == 0;
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
    private static function _isInfinityLiteralSingle($text)
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
    private function _handleTypePrefixedLiterals()
    {
        $id = $this->_token->Id;
        if ($id != ExpressionTokenId::IDENTIFIER) {
            return;
        }

        $quoteFollows = $this->_ch == '\'';
        if (!$quoteFollows) {
            return;
        }

        $tokenText = $this->_token->Text;

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

        $tokenPos = $this->_token->Position;
        do {
            $this->_nextChar();
        } while ($this->_ch != '\0' && $this->_ch != '\'');

        if ($this->_ch == '\0') {
            $this->_parseError(
                Messages::expressionLexerUnterminatedStringLiteral(
                    $this->_textPos,
                    $this->_text
                )
            );
        }

        $this->_nextChar();
        $this->_token->Id = $id;
        $this->_token->Text
            = substr($this->_text, $tokenPos, $this->_textPos - $tokenPos);
    }

    /**
     * Parses a token that starts with a digit.
     *
     * @return ExpressionTokenId The kind of token recognized
     */
    private function _parseFromDigit()
    {
        $result = null;
        $startChar = $this->_ch;
        $this->_nextChar();
        if ($startChar == '0' && $this->_ch == 'x' || $this->_ch == 'X') {
            $result = ExpressionTokenId::BINARY_LITERAL;
            do {
                $this->_nextChar();
            } while (ctype_xdigit($this->_ch));
        } else {
            $result = ExpressionTokenId::INTEGER_LITERAL;
            while (Char::isDigit($this->_ch)) {
                $this->_nextChar();
            }

            if ($this->_ch == '.') {
                $result = ExpressionTokenId::DOUBLE_LITERAL;
                $this->_nextChar();
                $this->_validateDigit();

                do {
                    $this->_nextChar();
                } while (Char::isDigit($this->_ch));
            }

            if ($this->_ch == 'E' || $this->_ch == 'e') {
                $result = ExpressionTokenId::DOUBLE_LITERAL;
                $this->_nextChar();
                if ($this->_ch == '+' || $this->_ch == '-') {
                    $this->_nextChar();
                }

                $this->_validateDigit();
                do {
                    $this->_nextChar();
                } while (Char::isDigit($this->_ch));
            }

            if ($this->_ch == 'M' || $this->_ch == 'm') {
                $result = ExpressionTokenId::DECIMAL_LITERAL;
                $this->_nextChar();
            } elseif ($this->_ch == 'd' || $this->_ch == 'D') {
                $result = ExpressionTokenId::DOUBLE_LITERAL;
                $this->_nextChar();
            } elseif ($this->_ch == 'L' || $this->_ch == 'l') {
                $result = ExpressionTokenId::INT64_LITERAL;
                $this->_nextChar();
            } elseif ($this->_ch == 'f' || $this->_ch == 'F') {
                $result = ExpressionTokenId::SINGLE_LITERAL;
                $this->_nextChar();
            }
        }

        return $result;
    }

    /**
     * Parses an identifier by advancing the current character.
     */
    private function _parseIdentifier()
    {
        do {
            $this->_nextChar();
        } while (Char::isLetterOrDigit($this->_ch) || $this->_ch == '_');
    }

    /**
     * Advance to next character.
     */
    private function _nextChar()
    {
        if ($this->_textPos < $this->_textLen) {
            ++$this->_textPos;
        }

        $this->_ch
            = $this->_textPos < $this->_textLen
             ? $this->_text[$this->_textPos] : '\0';
    }

    /**
     * Set the text position.
     *
     * @param int $pos Value to position
     */
    private function _setTextPos($pos)
    {
        $this->_textPos = $pos;
        $this->_ch
            = $this->_textPos < $this->_textLen
             ? $this->_text[$this->_textPos] : '\0';
    }

    /**
     * Validate current character is a digit.
     */
    private function _validateDigit()
    {
        if (!Char::isDigit($this->_ch)) {
            $this->_parseError(
                Messages::expressionLexerDigitExpected(
                    $this->_textPos
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
    private function _parseError($message)
    {
        throw ODataException::createSyntaxError($message);
    }
}
