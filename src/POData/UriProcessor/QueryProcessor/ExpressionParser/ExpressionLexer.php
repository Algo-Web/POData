<?php

declare(strict_types=1);

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
     * @param  string         $expression Expression to parse
     * @throws ODataException
     */
    public function __construct(string $expression)
    {
        $this->text    = $expression;
        $this->textLen = strlen($this->text);
        $this->token   = new ExpressionToken();
        $this->setTextPos(0);
        $this->nextToken();
    }

    /**
     * Set the text position.
     *
     * @param int $pos Value to position
     */
    private function setTextPos(int $pos): void
    {
        $this->textPos = $pos;
        $nextChar      = $this->textPos < $this->textLen ? $this->text[$this->textPos] : '\0';
        assert(2 >= strlen($nextChar));
        $this->ch = $nextChar;
    }

    /**
     * Reads the next token, skipping whitespace as necessary.
     * @throws ODataException
     */
    public function nextToken(): void
    {
        while (Char::isWhiteSpace($this->ch)) {
            $this->nextChar();
        }

        $t        = null;
        $tokenPos = $this->textPos;
        switch ($this->ch) {
            case '(':
                $this->nextChar();
                $t = ExpressionTokenId::OPENPARAM();
                break;
            case ')':
                $this->nextChar();
                $t = ExpressionTokenId::CLOSEPARAM();
                break;
            case ',':
                $this->nextChar();
                $t = ExpressionTokenId::COMMA();
                break;
            case '-':
                $hasNext = $this->textPos + 1 < $this->textLen;
                if ($hasNext && Char::isDigit($this->text[$this->textPos + 1])) {
                    $this->nextChar();
                    $t = $this->parseFromDigit();
                    if (self::isNumeric($t)) {
                        break;
                    }
                } elseif ($hasNext && $this->text[$tokenPos + 1] == 'I') {
                    $this->nextChar();
                    $this->parseIdentifier();
                    $currentIdentifier = substr($this->text, $tokenPos + 1, $this->textPos - $tokenPos - 1);

                    if (self::isInfinityLiteralDouble($currentIdentifier)) {
                        $t = ExpressionTokenId::DOUBLE_LITERAL();
                        break;
                    } elseif (self::isInfinityLiteralSingle($currentIdentifier)) {
                        $t = ExpressionTokenId::SINGLE_LITERAL();
                        break;
                    }

                    // If it looked like '-INF' but wasn't we'll rewind and fall through to a simple '-' token.
                }
                $this->setTextPos($tokenPos);
                $this->nextChar();
                $t = ExpressionTokenId::MINUS();
                break;
            case '=':
                $this->nextChar();
                $t = ExpressionTokenId::EQUAL();
                break;
            case '/':
                $this->nextChar();
                $t = ExpressionTokenId::SLASH();
                break;
            case '?':
                $this->nextChar();
                $t = ExpressionTokenId::QUESTION();
                break;
            case '.':
                $this->nextChar();
                $t = ExpressionTokenId::DOT();
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
                $t = ExpressionTokenId::STRING_LITERAL();
                break;
            case '*':
                $this->nextChar();
                $t = ExpressionTokenId::STAR();
                break;
            default:
                if (Char::isLetter($this->ch) || $this->ch == '_') {
                    $this->parseIdentifier();
                    $t = ExpressionTokenId::IDENTIFIER();
                    break;
                }

                if (Char::isDigit($this->ch)) {
                    $t = $this->parseFromDigit();
                    break;
                }

                if ($this->textPos == $this->textLen) {
                    $t = ExpressionTokenId::END();
                    break;
                }

                $this->parseError(
                    Messages::expressionLexerInvalidCharacter(
                        $this->ch,
                        $this->textPos
                    )
                );
        }

        $this->token->setId($t);
        $this->token->Text     = substr($this->text, $tokenPos, $this->textPos - $tokenPos);
        $this->token->Position = $tokenPos;

        // Handle type-prefixed literals such as binary, datetime or guid.
        $this->handleTypePrefixedLiterals();

        // Handle keywords.
        if ($this->token->getId() == ExpressionTokenId::IDENTIFIER()) {
            if (self::isInfinityOrNaNDouble($this->token->Text)) {
                $this->token->setId(ExpressionTokenId::DOUBLE_LITERAL());
            } elseif (self::isInfinityOrNanSingle($this->token->Text)) {
                $this->token->setId(ExpressionTokenId::SINGLE_LITERAL());
            } elseif ($this->token->Text == ODataConstants::KEYWORD_TRUE
                || $this->token->Text == ODataConstants::KEYWORD_FALSE
            ) {
                $this->token->setId(ExpressionTokenId::BOOLEAN_LITERAL());
            } elseif ($this->token->Text == ODataConstants::KEYWORD_NULL) {
                $this->token->setId(ExpressionTokenId::NULL_LITERAL());
            }
        }
    }

    /**
     * Advance to next character.
     */
    private function nextChar(): void
    {
        if ($this->textPos < $this->textLen) {
            ++$this->textPos;
        }

        $nextChar = $this->textPos < $this->textLen ? $this->text[$this->textPos] : '\0';
        assert(2 >= strlen($nextChar));
        $this->ch = $nextChar;
    }

    /**
     * Parses a token that starts with a digit.
     *
     * @throws ODataException
     * @return ExpressionTokenId The kind of token recognized
     */
    private function parseFromDigit(): ExpressionTokenId
    {
        $startChar = $this->ch;
        $this->nextChar();
        if ($startChar == '0' && $this->ch == 'x' || $this->ch == 'X') {
            $result = ExpressionTokenId::BINARY_LITERAL();
            do {
                $this->nextChar();
            } while (ctype_xdigit($this->ch));
        } else {
            $result = ExpressionTokenId::INTEGER_LITERAL();
            while (Char::isDigit($this->ch)) {
                $this->nextChar();
            }

            if ($this->ch == '.') {
                $result = ExpressionTokenId::DOUBLE_LITERAL();
                $this->nextChar();
                $this->validateDigit();

                do {
                    $this->nextChar();
                } while (Char::isDigit($this->ch));
            }

            if ($this->ch == 'E' || $this->ch == 'e') {
                $result = ExpressionTokenId::DOUBLE_LITERAL();
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
                $result = ExpressionTokenId::DECIMAL_LITERAL();
                $this->nextChar();
            } elseif ($this->ch == 'd' || $this->ch == 'D') {
                $result = ExpressionTokenId::DOUBLE_LITERAL();
                $this->nextChar();
            } elseif ($this->ch == 'L' || $this->ch == 'l') {
                $result = ExpressionTokenId::INT64_LITERAL();
                $this->nextChar();
            } elseif ($this->ch == 'f' || $this->ch == 'F') {
                $result = ExpressionTokenId::SINGLE_LITERAL();
                $this->nextChar();
            }
        }

        return $result;
    }

    /**
     * Validate current character is a digit.
     * @throws ODataException
     */
    private function validateDigit(): void
    {
        if (!Char::isDigit($this->ch)) {
            $this->parseError(Messages::expressionLexerDigitExpected($this->textPos));
        }
    }

    /**
     * Throws parser error.
     *
     * @param string $message The error message
     *
     * @throws ODataException
     */
    private function parseError(string $message): void
    {
        throw ODataException::createSyntaxError($message);
    }

    /**
     * Whether the specified token identifier is a numeric literal.
     *
     * @param ExpressionTokenId $id Token identifier to check
     *
     * @return bool true if it's a numeric literal; false otherwise
     */
    public static function isNumeric(ExpressionTokenId $id): bool
    {
        return
            $id == ExpressionTokenId::INTEGER_LITERAL()
            || $id == ExpressionTokenId::DECIMAL_LITERAL()
            || $id == ExpressionTokenId::DOUBLE_LITERAL()
            || $id == ExpressionTokenId::INT64_LITERAL()
            || $id == ExpressionTokenId::SINGLE_LITERAL();
    }

    /**
     * Parses an identifier by advancing the current character.
     */
    private function parseIdentifier(): void
    {
        do {
            $this->nextChar();
        } while (Char::isLetterOrDigit($this->ch) || $this->ch == '_');
    }

    /**
     * Check if the parameter ($text) is INF.
     *
     * @param string $text Text to look in
     *
     * @return bool true if match found, false otherwise
     */
    private static function isInfinityLiteralDouble(string $text): bool
    {
        return strcmp($text, ODataConstants::XML_INFINITY_LITERAL) == 0;
    }

    /**
     * Checks whether parameter ($text) EQUALS to 'INFf' or 'INFF' at position.
     *
     * @param string $text Text to look in
     *
     * @return bool true if the substring is equal using an ordinal comparison; false otherwise
     */
    private static function isInfinityLiteralSingle(string $text): bool
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
    private function handleTypePrefixedLiterals(): void
    {
        $id = $this->token->getId();
        if ($id != ExpressionTokenId::IDENTIFIER()) {
            return;
        }

        $quoteFollows = $this->ch == '\'';
        if (!$quoteFollows) {
            return;
        }

        $tokenText = $this->token->Text;

        if (strcasecmp('datetime', $tokenText) == 0) {
            $id = ExpressionTokenId::DATETIME_LITERAL();
        } elseif (strcasecmp('guid', $tokenText) == 0) {
            $id = ExpressionTokenId::GUID_LITERAL();
        } elseif (strcasecmp('binary', $tokenText) == 0
            || strcasecmp('X', $tokenText) == 0
            || strcasecmp('x', $tokenText) == 0
        ) {
            $id = ExpressionTokenId::BINARY_LITERAL();
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
        $this->token->setId($id);
        $this->token->Text
            = substr($this->text, $tokenPos, $this->textPos - $tokenPos);
    }

    /**
     * Check if the parameter ($tokenText) is INF or NaN.
     *
     * @param string $tokenText Text to look in
     *
     * @return bool true if match found, false otherwise
     */
    private static function isInfinityOrNaNDouble(string $tokenText): bool
    {
        if (strlen($tokenText) == 3) {
            if ($tokenText[0] == 'I') {
                return self::isInfinityLiteralDouble($tokenText);
            } elseif ($tokenText[0] == 'N') {
                return strncmp($tokenText, ODataConstants::XML_NAN_LITERAL, 3) == 0;
            }
        }

        return false;
    }

    /**
     * Checks if the parameter ($tokenText) is INFf/INFF or NaNf/NaNF.
     *
     * @param string $tokenText Input token
     *
     * @return bool true if match found, false otherwise
     */
    private static function isInfinityOrNanSingle(string $tokenText): bool
    {
        if (strlen($tokenText) == 4) {
            if ($tokenText[0] == 'I') {
                return self::isInfinityLiteralSingle($tokenText);
            } elseif ($tokenText[0] == 'N') {
                return ($tokenText[3] == self::SINGLE_SUFFIX_LOWER
                        || $tokenText[3] == self::SINGLE_SUFFIX_UPPER)
                    && strncmp($tokenText, ODataConstants::XML_NAN_LITERAL, 3) == 0;
            }
        }

        return false;
    }

    /**
     * To get the expression token being processed.
     *
     * @return ExpressionToken
     */
    public function getCurrentToken(): ExpressionToken
    {
        return $this->token;
    }

    /**
     * To set the token being processed.
     *
     * @param ExpressionToken $token The expression token to set as current
     */
    public function setCurrentToken(ExpressionToken $token): void
    {
        $this->token = $token;
    }

    /**
     * To get the text being parsed.
     *
     * @return string
     */
    public function getExpressionText(): string
    {
        return $this->text;
    }

    /**
     * Position of the current token in the text being parsed.
     *
     * @return int
     */
    public function getPosition(): int
    {
        return $this->token->Position;
    }

    /**
     * Returns the next token without advancing the lexer to next token.
     *
     * @throws ODataException
     * @return ExpressionToken
     */
    public function peekNextToken(): ExpressionToken
    {
        $savedTextPos = $this->textPos;
        assert(2 >= strlen($this->ch));
        $savedChar  = $this->ch;
        $savedToken = clone $this->token;
        $this->nextToken();
        $result        = clone $this->token;
        $this->textPos = $savedTextPos;
        $this->ch      = $savedChar;
        $this->token->setId($savedToken->getId());
        $this->token->Position = $savedToken->Position;
        $this->token->Text     = $savedToken->Text;

        return $result;
    }

    /**
     * Starting from an identifier, reads alternate sequence of dots and identifiers
     * and returns the text for it.
     *
     * @throws ODataException
     * @return string         The dotted identifier starting at the current identifier
     */
    public function readDottedIdentifier(): string
    {
        $this->validateToken(ExpressionTokenId::IDENTIFIER());
        $identifier = $this->token->Text;
        $this->nextToken();
        while ($this->token->getId() == ExpressionTokenId::DOT()) {
            $this->nextToken();
            $this->validateToken(ExpressionTokenId::IDENTIFIER());
            $identifier = $identifier . '.' . $this->token->Text;
            $this->nextToken();
        }

        return $identifier;
    }

    /**
     * Validates the current token is of the specified kind.
     *
     * @param ExpressionTokenId $tokenId Expected token kind
     *
     * @throws ODataException if current token is not of the
     *                        specified kind
     */
    public function validateToken(ExpressionTokenId $tokenId): void
    {
        if ($this->token->getId() != $tokenId) {
            $this->parseError(Messages::expressionLexerSyntaxError($this->textPos));
        }
    }
}
