<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use PhpParser\Node\Stmt\Expression;
use POData\Common\ODataConstants;
use POData\Common\ODataException;

/**
 * Class ExpressionToken.
 */
class ExpressionToken
{
    /**
     * @var string
     */
    public $Text;
    /**
     * @var int
     */
    public $Position;
    /**
     * @var ExpressionTokenId
     */
    protected $Id;

    /**
     * Checks whether this token is a comparison operator.
     *
     * @return bool True if this token represent a comparison operator
     *              False otherwise
     */
    public function isComparisonOperator(): bool
    {
        return
            $this->Id == ExpressionTokenId::IDENTIFIER() &&
            is_string($this->Text) &&
            (strcmp($this->Text, ODataConstants::KEYWORD_EQUAL) == 0 ||
                strcmp($this->Text, ODataConstants::KEYWORD_NOT_EQUAL) == 0 ||
                strcmp($this->Text, ODataConstants::KEYWORD_LESSTHAN) == 0 ||
                strcmp($this->Text, ODataConstants::KEYWORD_GREATERTHAN) == 0 ||
                strcmp($this->Text, ODataConstants::KEYWORD_LESSTHAN_OR_EQUAL) == 0 ||
                strcmp($this->Text, ODataConstants::KEYWORD_GREATERTHAN_OR_EQUAL) == 0);
    }

    /**
     * Checks whether this token is an equality operator.
     *
     * @return bool True if this token represent a equality operator
     *              False otherwise
     */
    public function isEqualityOperator(): bool
    {
        return
            $this->Id == ExpressionTokenId::IDENTIFIER() &&
            is_string($this->Text) &&
            (strcmp($this->Text, ODataConstants::KEYWORD_EQUAL) === 0 ||
                strcmp($this->Text, ODataConstants::KEYWORD_NOT_EQUAL) === 0);
    }

    /**
     * Checks whether this token is a valid token for a key value.
     *
     * @return bool True if this token represent valid key value
     *              False otherwise
     */
    public function isKeyValueToken(): bool
    {
        return
            $this->Id == ExpressionTokenId::BINARY_LITERAL() ||
            $this->Id == ExpressionTokenId::BOOLEAN_LITERAL() ||
            $this->Id == ExpressionTokenId::DATETIME_LITERAL() ||
            $this->Id == ExpressionTokenId::GUID_LITERAL() ||
            $this->Id == ExpressionTokenId::STRING_LITERAL() ||
            ExpressionLexer::isNumeric($this->Id);
    }

    /**
     * Gets the current identifier text.
     *
     * @throws ODataException
     * @return string
     */
    public function getIdentifier(): string
    {
        if ($this->Id != ExpressionTokenId::IDENTIFIER()) {
            throw ODataException::createSyntaxError(
                'Identifier expected at position ' . $this->Position
            );
        }

        return $this->Text;
    }

    /**
     * Checks that this token has the specified identifier.
     *
     * @param ExpressionTokenId $id Identifier to check
     *
     * @return bool true if this is an identifier with the specified text
     */
    public function identifierIs($id): bool
    {
        return $this->Id == ExpressionTokenId::IDENTIFIER()
            && strcmp($this->Text, $id) == 0;
    }

    /**
     * @return ExpressionTokenId
     */
    public function getId(): ExpressionTokenId
    {
        return $this->Id;
    }

    /**
     * @param ExpressionTokenId $Id
     */
    public function setId(ExpressionTokenId $Id): void
    {
        $this->Id = $Id;
    }
}
