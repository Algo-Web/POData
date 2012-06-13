<?php
/**
 * Enumeration values for expression token kinds.
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
/**
 * Expression token enum.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ExpressionTokenId
{
    //Unknown.
    const UNKNOWN = 1;

    //End of text.
    const END = 2;

    //'=' - equality character.
    const EQUAL = 3;

    //Identifier.
    const IDENTIFIER = 4;

    //NullLiteral.
    const NULL_LITERAL = 5;

    //BooleanLiteral.
    const BOOLEAN_LITERAL = 6;

    //StringLiteral.
    const STRING_LITERAL = 7;

    //IntegerLiteral. (int32)
    const INTEGER_LITERAL = 8;

    //Int64 literal.
    const INT64_LITERAL = 9;

    //Single literal. (float)
    const SINGLE_LITERAL = 10;

    //DateTime literal.
    const DATETIME_LITERAL = 11;

    //Decimal literal.
    const DECIMAL_LITERAL = 12;

    //Double literal.
    const DOUBLE_LITERAL = 13;

    //GUID literal.
    const GUID_LITERAL = 14;

    //Binary literal.
    const BINARY_LITERAL = 15;

    //Exclamation.
    const EXCLAMATION = 16;

    //OpenParen.
    const OPENPARAM = 17;

    //CloseParen.
    const CLOSEPARAM = 18;

    //Comma.
    const COMMA = 19;

    //Minus.
    const MINUS = 20;

    //Slash.
    const SLASH = 21;

    //Question.
    const QUESTION = 22;

    //Dot.
    const DOT = 23;

    //Star.
    const STAR = 24;
}
?>