<?php
/** 
 * A type to represent a parsed token.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\ODataConstants;
/**
 * A type to represent a parsed token.
 *
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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
     * @return boolean True if this token represent a comparison operator
     *                 False otherwise.
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
     * @return boolean True if this token represent a equality operator
     *                 False otherwise.
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
     * @return boolean True if this token represent valid key value
     *                 False otherwise.
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
     * Gets the current identifier text
     * 
     * @return string
     */
    public function getIdentifier()
    {
        if ($this->Id != ExpressionTokenId::IDENTIFIER) {
            ODataException::createSyntaxError(
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
     * @return true if this is an identifier with the specified text
     */
    public function identifierIs($id)
    {
        return $this->Id == ExpressionTokenId::IDENTIFIER 
            && strcmp($this->Text, $id) == 0;
    }
}
?>