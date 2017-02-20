<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Common\ODataConstants;
use POData\Common\ODataException;

/**
 * Class ExpressionToken.
 */
class ExpressionToken
{
    /**
     * @var ExpressionTokenId
     */
    public $Id;

    /**
     * @var string
     */
    public $Text;

    /**
     * @var int
     */
    public $Position;

    /**
     * Checks whether this token is a comparison operator.
     *
     * @return bool True if this token represent a comparison operator
     *              False otherwise
     */
    public function isComparisonOperator()
    {
        return
            $this->Id == ExpressionTokenId::IDENTIFIER &&
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
    public function isEqualityOperator()
    {
        return
            $this->Id == ExpressionTokenId::IDENTIFIER &&
                (strcmp($this->Text, ODataConstants::KEYWORD_EQUAL) == 0 ||
                    strcmp($this->Text, ODataConstants::KEYWORD_NOT_EQUAL) == 0);
    }

    /**
     * Checks whether this token is a valid token for a key value.
     *
     * @return bool True if this token represent valid key value
     *              False otherwise
     */
    public function isKeyValueToken()
    {
        return
            $this->Id == ExpressionTokenId::BINARY_LITERAL ||
            $this->Id == ExpressionTokenId::BOOLEAN_LITERAL ||
            $this->Id == ExpressionTokenId::DATETIME_LITERAL ||
            $this->Id == ExpressionTokenId::GUID_LITERAL ||
            $this->Id == ExpressionTokenId::STRING_LITERAL ||
            ExpressionLexer::isNumeric($this->Id);
    }

    /**
     * Gets the current identifier text.
     *
     * @return string
     */
    public function getIdentifier()
    {
        if ($this->Id != ExpressionTokenId::IDENTIFIER) {
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
    public function identifierIs($id)
    {
        return $this->Id == ExpressionTokenId::IDENTIFIER
            && strcmp($this->Text, $id) == 0;
    }
}
