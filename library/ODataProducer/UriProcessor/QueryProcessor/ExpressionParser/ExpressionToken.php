<?php
/** 
 * A type to represent a parsed token.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\ODataConstants;
/**
 * A type to represent a parsed token.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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