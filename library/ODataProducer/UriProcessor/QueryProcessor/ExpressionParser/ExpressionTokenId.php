<?php
/**
 * Enumeration values for expression token kinds.
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
/**
 * Expression token enum.
 *
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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