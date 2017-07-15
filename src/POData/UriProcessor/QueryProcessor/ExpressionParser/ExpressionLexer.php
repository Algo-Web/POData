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
     * @var string
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
     * @var string
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
        return $this->token;
    }

    /**
     * To set the token being processed.
     *
     * @param ExpressionToken $token The expression token to set as current
     */
    public function setCurrentToken(ExpressionToken $token)
    {
        $this->token = $token;
    }

    /**
     * To get the text being parsed.
     *
     * @return string
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
            $this->_nextChar();
        }

        $t = null;
        $tokenPos = $this->textPos;
        switch ($this->ch) {
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
                $hasNext = $this->textPos + 1 < $this->textLen;
                if ($hasNext && Char::isDigit($this->text[$this->textPos + 1])) {
                    $this->_nextChar();
                    $t = $this->_parseFromDigit();
                    if (self::isNumeric($t)) {
                        break;
                    }
                } elseif ($hasNext && $this->text[$tokenPos + 1] == 'I') {
                    $this->_nextChar();
                    $this->_parseIdentifier();
                    $currentIdentifier = substr($this->text, $tokenPos + 1, $this->textPos - $tokenPos - 1);

                    if (self::_isInfinityLiteralDouble($currentIdentifier)) {
                        $t = ExpressionTokenId::DOUBLE_LITERAL;
                        break;
                    } elseif (self::_isInfinityLiteralSingle($currentIdentifier)) {
                        $t = ExpressionTokenId::SINGLE_LITERAL;
                        break;
                    }

                    // If it looked like '-INF' but wasn't we'll rewind and fall through to a simple '-' token.
                }
                $this->_setTextPos($tokenPos);
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
                $quote = $this->ch;
                do {
                    $this->_nextChar();
                    while ($this->textPos < $this->textLen && $this->ch != $quote) {
                        $this->_nextChar();
                    }

                    if ($this->textPos == $this->textLen) {
                        $this->_parseError(
                            Messages::expressionLexerUnterminatedStringLiteral(
                                $this->textPos,
                                $this->text
                            )
                        );
                    }

                    $this->_nextChar();
                } while ($this->ch == $quote);
                $t = ExpressionTokenId::STRING_LITERAL;
                break;
            case '*':
                $this->_nextChar();
                $t = ExpressionTokenId::STAR;
                break;
            default:
                if (Char::isLetter($this->ch) || $this->ch == '_') {
                    $this->_parseIdentifier();
                    $t = ExpressionTokenId::IDENTIFIER;
                    break;
                }

                if (Char::isDigit($this->ch)) {
                    $t = $this->_parseFromDigit();
                    break;
                }

                if ($this->textPos == $this->textLen) {
                    $t = ExpressionTokenId::END;
                    break;
                }

                $this->_parseError(
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
        $this->_handleTypePrefixedLiterals();

        // Handle keywords.
        if ($this->token->Id == ExpressionTokenId::IDENTIFIER) {
            if (self::_isInfinityOrNaNDouble($this->token->Text)) {
                $this->token->Id = ExpressionTokenId::DOUBLE_LITERAL;
            } elseif (self::_isInfinityOrNanSingle($this->token->Text)) {
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
        assert(2 >= strlen($this->ch));
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
            $this->_parseError(Messages::expressionLexerSyntaxError($this->textPos));
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
            $this->_nextChar();
        } while ($this->ch != '\0' && $this->ch != '\'');

        if ($this->ch == '\0') {
            $this->_parseError(
                Messages::expressionLexerUnterminatedStringLiteral(
                    $this->textPos,
                    $this->text
                )
            );
        }

        $this->_nextChar();
        $this->token->Id = $id;
        $this->token->Text
            = substr($this->text, $tokenPos, $this->textPos - $tokenPos);
    }

    /**
     * Parses a token that starts with a digit.
     *
     * @return ExpressionTokenId The kind of token recognized
     */
    private function _parseFromDigit()
    {
        $startChar = $this->ch;
        $this->_nextChar();
        if ($startChar == '0' && $this->ch == 'x' || $this->ch == 'X') {
            $result = ExpressionTokenId::BINARY_LITERAL;
            do {
                $this->_nextChar();
            } while (ctype_xdigit($this->ch));
        } else {
            $result = ExpressionTokenId::INTEGER_LITERAL;
            while (Char::isDigit($this->ch)) {
                $this->_nextChar();
            }

            if ($this->ch == '.') {
                $result = ExpressionTokenId::DOUBLE_LITERAL;
                $this->_nextChar();
                $this->_validateDigit();

                do {
                    $this->_nextChar();
                } while (Char::isDigit($this->ch));
            }

            if ($this->ch == 'E' || $this->ch == 'e') {
                $result = ExpressionTokenId::DOUBLE_LITERAL;
                $this->_nextChar();
                if ($this->ch == '+' || $this->ch == '-') {
                    $this->_nextChar();
                }

                $this->_validateDigit();
                do {
                    $this->_nextChar();
                } while (Char::isDigit($this->ch));
            }

            if ($this->ch == 'M' || $this->ch == 'm') {
                $result = ExpressionTokenId::DECIMAL_LITERAL;
                $this->_nextChar();
            } elseif ($this->ch == 'd' || $this->ch == 'D') {
                $result = ExpressionTokenId::DOUBLE_LITERAL;
                $this->_nextChar();
            } elseif ($this->ch == 'L' || $this->ch == 'l') {
                $result = ExpressionTokenId::INT64_LITERAL;
                $this->_nextChar();
            } elseif ($this->ch == 'f' || $this->ch == 'F') {
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
        } while (Char::isLetterOrDigit($this->ch) || $this->ch == '_');
    }

    /**
     * Advance to next character.
     */
    private function _nextChar()
    {
        if ($this->textPos < $this->textLen) {
            ++$this->textPos;
        }

        $nextChar = $this->textPos < $this->textLen ? $this->text[$this->textPos] : '\0';
        assert(2 >= strlen($nextChar));
        $this->ch = $nextChar;
    }

    /**
     * Set the text position.
     *
     * @param int $pos Value to position
     */
    private function _setTextPos($pos)
    {
        $this->textPos = $pos;
        $nextChar = $this->textPos < $this->textLen ? $this->text[$this->textPos] : '\0';
        assert(2 >= strlen($nextChar));
        $this->ch = $nextChar;
    }

    /**
     * Validate current character is a digit.
     */
    private function _validateDigit()
    {
        if (!Char::isDigit($this->ch)) {
            $this->_parseError(Messages::expressionLexerDigitExpected($this->textPos));
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
