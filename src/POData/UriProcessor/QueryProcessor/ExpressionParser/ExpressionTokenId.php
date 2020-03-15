<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use MyCLabs\Enum\Enum;

/**
 * Class ExpressionTokenId.
 * @method static IDENTIFIER()
 * @method static COMMA()
 * @method static DOT()
 * @method static INTEGER_LITERAL()
 * @method static OPENPARAM()
 * @method static CLOSEPARAM()
 * @method static DOUBLE_LITERAL()
 * @method static SINGLE_LITERAL()
 * @method static MINUS()
 * @method static EQUAL()
 * @method static QUESTION()
 * @method static STRING_LITERAL()
 * @method static STAR()
 * @method static END()
 * @method static BOOLEAN_LITERAL()
 * @method static NULL_LITERAL()
 * @method static SLASH()
 * @method static DECIMAL_LITERAL()
 * @method static INT64_LITERAL()
 * @method static DATETIME_LITERAL()
 * @method static GUID_LITERAL()
 * @method static BINARY_LITERAL()
 */
class ExpressionTokenId extends Enum
{
    //Unknown.
    protected const UNKNOWN = 1;

    //End of text.
    protected const END = 2;

    //'=' - equality character.
    protected const EQUAL = 3;

    //Identifier.
    protected const IDENTIFIER = 4;

    //NullLiteral.
    protected const NULL_LITERAL = 5;

    //BooleanLiteral.
    protected const BOOLEAN_LITERAL = 6;

    //StringLiteral.
    protected const STRING_LITERAL = 7;

    //IntegerLiteral. (int32)
    protected const INTEGER_LITERAL = 8;

    //Int64 literal.
    protected const INT64_LITERAL = 9;

    //Single literal. (float)
    protected const SINGLE_LITERAL = 10;

    //DateTime literal.
    protected const DATETIME_LITERAL = 11;

    //Decimal literal.
    protected const DECIMAL_LITERAL = 12;

    //Double literal.
    protected const DOUBLE_LITERAL = 13;

    //GUID literal.
    protected const GUID_LITERAL = 14;

    //Binary literal.
    protected const BINARY_LITERAL = 15;

    //Exclamation.
    protected const EXCLAMATION = 16;

    //OpenParen.
    protected const OPENPARAM = 17;

    //CloseParen.
    protected const CLOSEPARAM = 18;

    //Comma.
    protected const COMMA = 19;

    //Minus.
    protected const MINUS = 20;

    //Slash.
    protected const SLASH = 21;

    //Question.
    protected const QUESTION = 22;

    //Dot.
    protected const DOT = 23;

    //Star.
    protected const STAR = 24;
}
