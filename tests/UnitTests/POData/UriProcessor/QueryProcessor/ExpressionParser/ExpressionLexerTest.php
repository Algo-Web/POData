<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Common\ODataException;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionLexer;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionTokenId;
use UnitTests\POData\TestCase;

class ExpressionLexerTest extends TestCase
{
    protected function setUp()
    {
    }

    public function testStringLiteral()
    {
        $expression = "StringIdentifier eq 'mystring'";
        $lexer = new ExpressionLexer($expression);
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 0);
        $this->AssertEquals($token->Text, 'StringIdentifier');
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 17);
        $this->AssertEquals($token->Text, 'eq');
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::STRING_LITERAL);
        $this->AssertEquals($token->Position, 20);
        $this->AssertEquals($token->Text, '\'mystring\'');

        $expression = "StringIdentifier eq 'mystring";
        $lexer = new ExpressionLexer($expression);
        $lexer->nextToken();
        try {
            $lexer->nextToken();
            $this->fail('An expected ODataException has not been raised');
        } catch (ODataException $ex) {
            //TODO: some assertion
        }
    }

    public function testTypePreFixedLiteral()
    {
        //datetime, binary, guid, X, x followed by open-close quotes, only with open quote
        $expression = "BinaryIdentifier1 eq X'AF0' and DateTimeIdentifier gt datetime'2010-02-12 T24:58:58Z' or BinaryIdentifier2 ne x'FF' and GuidIdentifier eq guid'' or BinaryIdentifier3 eq binary'0AFC'";
        $lexer = new ExpressionLexer($expression);

        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 0);
        $this->AssertEquals($token->Text, 'BinaryIdentifier1');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 18);
        $this->AssertEquals($token->Text, 'eq');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::BINARY_LITERAL);
        $this->AssertEquals($token->Position, 21);
        $this->AssertEquals($token->Text, 'X\'AF0\'');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 28);
        $this->AssertEquals($token->Text, 'and');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 32);
        $this->AssertEquals($token->Text, 'DateTimeIdentifier');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 51);
        $this->AssertEquals($token->Text, 'gt');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DATETIME_LITERAL);
        $this->AssertEquals($token->Position, 54);
        $this->AssertEquals($token->Text, 'datetime\'2010-02-12 T24:58:58Z\'');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 86);
        $this->AssertEquals($token->Text, 'or');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 89);
        $this->AssertEquals($token->Text, 'BinaryIdentifier2');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 107);
        $this->AssertEquals($token->Text, 'ne');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::BINARY_LITERAL);
        $this->AssertEquals($token->Position, 110);
        $this->AssertEquals($token->Text, 'x\'FF\'');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 116);
        $this->AssertEquals($token->Text, 'and');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 120);
        $this->AssertEquals($token->Text, 'GuidIdentifier');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 135);
        $this->AssertEquals($token->Text, 'eq');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::GUID_LITERAL);
        $this->AssertEquals($token->Position, 138);
        $this->AssertEquals($token->Text, 'guid\'\'');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 145);
        $this->AssertEquals($token->Text, 'or');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 148);
        $this->AssertEquals($token->Text, 'BinaryIdentifier3');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 166);
        $this->AssertEquals($token->Text, 'eq');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::BINARY_LITERAL);
        $this->AssertEquals($token->Position, 169);
        $this->AssertEquals($token->Text, 'binary\'0AFC\'');

        //-----------------------------------------------------------------
        $expression = 'NonBinaryIdentifier1 eq binaryABC';
        $lexer = new ExpressionLexer($expression);

        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 0);
        $this->AssertEquals($token->Text, 'NonBinaryIdentifier1');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 21);
        $this->AssertEquals($token->Text, 'eq');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 24);
        $this->AssertEquals($token->Text, 'binaryABC');

        //-----------------------------------------------------------------
        $expression = "DateTimeIdentifier eq datetime'";
        $lexer = new ExpressionLexer($expression);
        $lexer->nextToken();
        try {
            $lexer->nextToken();
            $this->fail('An expected ODataException has not been raised');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Unterminated string literal at position 31 in', $ex->getMessage());
        }
    }

    public function testNumericLiteral()
    {
        //Double, Single, Integer, Decimal, F, f, L, l, M, m, D, d, Ee (+/-)
        //0x, 0X, 0123
        $expression = 'DoubleIdentifier1   ne 123.34  and '.
                    'DoubleIdentifier2     eq 124E3     and '.
                    'DoubleIdentifier3     eq 125.6E3   and '.
                    'DoubleIdentifier4     eq 125.6E+3  and '.
                    'DoubleIdentifier5     eq 125.6E-3  and '.
                    'DoubleIdentifier6     ne 126D      or  '.
                    'DoubleIdentifier7     ne 127d      and '.
                    'SingleIdentifier1     eq 154F      and '.
                    'SingleIdentifier2     eq 155f      and '.
                    'SingleIdentifier3     eq 156.45F   and '.
                    'SingleIdentifier4     eq 157.45f   and '.
                    'SingleIdentifier5     eq 158E2F    and '.
                    'SingleIdentifier6     eq 159E3f    and '.
                    'SingleIdentifier7     eq 160E+2F   and '.
                    'SingleIdentifier8     eq 161E-3f   and '.
                    'IntegralIdentifier1   eq 170 	    and '.
                    'Integral64Identifier1 eq 171L 	    and '.
                    'DecimalIdentifier1    ne 180M      and '.
                    'DecimalIdentifier2    eq 181.2m    and '.
                    'DecimalIdentifier3    eq 181.2E4m  and '.
                    'ErrNumeric1		   ne 123.A2';

        $lexer = new ExpressionLexer($expression);
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Text, 'DoubleIdentifier1');
        $this->AssertEquals($token->Position, 0);

        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Text, '123.34');
        $this->AssertEquals($token->Position, 23);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Text, '124E3');
        $this->AssertEquals($token->Position, 60);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Text, '125.6E3');
        $this->AssertEquals($token->Position, 99);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Text, '125.6E+3');
        $this->AssertEquals($token->Position, 138);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Text, '125.6E-3');
        $this->AssertEquals($token->Position, 177);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Text, '126D');
        $this->AssertEquals($token->Position, 216);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Text, '127d');
        $this->AssertEquals($token->Position, 255);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Text, '154F');
        $this->AssertEquals($token->Position, 294);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Text, '155f');
        $this->AssertEquals($token->Position, 333);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Text, '156.45F');
        $this->AssertEquals($token->Position, 372);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Text, '157.45f');
        $this->AssertEquals($token->Position, 411);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Text, '158E2F');
        $this->AssertEquals($token->Position, 450);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Text, '159E3f');
        $this->AssertEquals($token->Position, 489);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Text, '160E+2F');
        $this->AssertEquals($token->Position, 528);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Text, '161E-3f');
        $this->AssertEquals($token->Position, 567);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::INTEGER_LITERAL);
        $this->AssertEquals($token->Text, '170');
        $this->AssertEquals($token->Position, 606);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::INT64_LITERAL);
        $this->AssertEquals($token->Text, '171L');
        $this->AssertEquals($token->Position, 644);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DECIMAL_LITERAL);
        $this->AssertEquals($token->Text, '180M');
        $this->AssertEquals($token->Position, 683);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DECIMAL_LITERAL);
        $this->AssertEquals($token->Text, '181.2m');
        $this->AssertEquals($token->Position, 722);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DECIMAL_LITERAL);
        $this->AssertEquals($token->Text, '181.2E4m');
        $this->AssertEquals($token->Position, 761);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();

        try {
            $lexer->nextToken();
            $this->fail('An expected ODataException has not been raised');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Digit expected at position', $ex->getMessage());
        }

        $expression = 'ErrNumeric2	 ne 124.3EA2';
        $lexer = new ExpressionLexer($expression);
        $lexer->nextToken();
        try {
            $lexer->nextToken();
            $this->fail('An expected ODataException has not been raised');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Digit expected at position', $ex->getMessage());
        }

        $expression = 'ErrNumeric3	ne 126.3e';
        $lexer = new ExpressionLexer($expression);
        $lexer->nextToken();

        try {
            $lexer->nextToken();
            $this->fail('An expected ODataException has not been raised');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Digit expected at position', $ex->getMessage());
        }

        $expression = 'ErrNumeric4	ne 127.3e++5';
        $lexer = new ExpressionLexer($expression);
        $lexer->nextToken();
        try {
            $lexer->nextToken();
            $this->fail('An expected ODataException has not been raised');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Digit expected at position', $ex->getMessage());
        }
    }

    public function testEndToken()
    {
        $expression = 'IntIdentifier eq 123';
        $lexer = new ExpressionLexer($expression);

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::END);

        //Test boundry
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::END);
    }

    public function testReservedCharToken()
    {
        //(, ), /, ', *
        $expression = "substring(CustomerName, 0, 5) eq 'ABCDE' and Address/LineNumber eq 1";
        $lexer = new ExpressionLexer($expression);

        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 0);
        $this->AssertEquals($token->Text, 'substring');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::OPENPARAM);
        $this->AssertEquals($token->Position, 9);
        $this->AssertEquals($token->Text, '(');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 10);
        $this->AssertEquals($token->Text, 'CustomerName');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::COMMA);
        $this->AssertEquals($token->Position, 22);
        $this->AssertEquals($token->Text, ',');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::INTEGER_LITERAL);
        $this->AssertEquals($token->Position, 24);
        $this->AssertEquals($token->Text, '0');

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::CLOSEPARAM);
        $this->AssertEquals($token->Position, 28);
        $this->AssertEquals($token->Text, ')');

        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::STRING_LITERAL);
        $this->AssertEquals($token->Position, 33);
        $this->AssertEquals($token->Text, '\'ABCDE\'');

        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 45);
        $this->AssertEquals($token->Text, 'Address');

        $lexer->nextToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SLASH);
        $this->AssertEquals($token->Position, 52);
        $this->AssertEquals($token->Text, '/');

        $lexer->nextToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 53);
        $this->AssertEquals($token->Text, 'LineNumber');
    }

    public function testInvalidCharacer()
    {
        $expression = 'IntIdent@ifier eq 123';
        $lexer = new ExpressionLexer($expression);
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 0);
        $this->AssertEquals($token->Text, 'IntIdent');

        try {
            $lexer->nextToken();
            $this->fail('An expected ODataException has not been raised');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith("Invalid character '@' at position 8", $ex->getMessage());
        }
    }

    public function testValidateToken()
    {
        $expression = 'IntIdentifier eq 123';
        $lexer = new ExpressionLexer($expression);
        $lexer->validateToken(ExpressionTokenId::IDENTIFIER);
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();

        try {
            $lexer->validateToken(ExpressionTokenId::INTEGER_LITERAL);
            $this->fail('An expected ODataException has not been raised');
        } catch (ODataException $ex) {
            $this->assertStringStartsWith('Syntax Error at position 16', $ex->getMessage());
        }
    }

    public function testInfinityAndNanLiteral()
    {
        //Double Infinity and Not-a-Number
        //| INF |
        //| NaN |

        //Single Infinity and Not-a-Number
        //|INFF/INFf
        //|NaNF/NaNf

        $expression = 'INFIdentifierDouble eq INF or NANIdentifierDouble eq NaN and Identifier1 eq inf or Identifier2 eq nan';
        $lexer = new ExpressionLexer($expression);

        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 0);
        $this->AssertEquals($token->Text, 'INFIdentifierDouble');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 20);
        $this->AssertEquals($token->Text, 'eq');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Position, 23);
        $this->AssertEquals($token->Text, 'INF');

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Position, 53);
        $this->AssertEquals($token->Text, 'NaN');

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 76);
        $this->AssertEquals($token->Text, 'inf');

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 98);
        $this->AssertEquals($token->Text, 'nan');

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::END);

        $expression = 'INFIdentifierSingle1 eq INFF or INFIdentifierSingle2 eq INFf and NaNIdentifierSingle1 eq NaNF or NaNIdentifierSingle2 eq NaNf';
        $lexer = new ExpressionLexer($expression);

        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 0);
        $this->AssertEquals($token->Text, 'INFIdentifierSingle1');

        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Position, 24);
        $this->AssertEquals($token->Text, 'INFF');

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Position, 56);
        $this->AssertEquals($token->Text, 'INFf');

        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 65);
        $this->AssertEquals($token->Text, 'NaNIdentifierSingle1');

        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Position, 89);
        $this->AssertEquals($token->Text, 'NaNF');

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Position, 121);
        $this->AssertEquals($token->Text, 'NaNf');
    }

    public function testNegationLiteral()
    {
        //-123, 123.5, INFF and INFf (negation is not applicable for NaN)
        $expression = 'IntIdentifier eq -123 and Int64Identifier eq -124L';
        $lexer = new ExpressionLexer($expression);

        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::INTEGER_LITERAL);
        $this->AssertEquals($token->Position, 17);
        $this->AssertEquals($token->Text, '-123');

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::INT64_LITERAL);
        $this->AssertEquals($token->Position, 45);
        $this->AssertEquals($token->Text, '-124L');

        $expression = 'INFIdentifier1 eq -INF and INFIdentifier2 eq -INFF';
        $lexer = new ExpressionLexer($expression);

        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::DOUBLE_LITERAL);
        $this->AssertEquals($token->Position, 18);
        $this->AssertEquals($token->Text, '-INF');

        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::SINGLE_LITERAL);
        $this->AssertEquals($token->Position, 45);
        $this->AssertEquals($token->Text, '-INFF');

        $expression = 'OrderRate1 eq -OrderRate2';
        $lexer = new ExpressionLexer($expression);
        $lexer->nextToken();
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::MINUS);
        $this->AssertEquals($token->Position, 14);
        $this->AssertEquals($token->Text, '-');
        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Position, 15);
        $this->AssertEquals($token->Text, 'OrderRate2');
    }

    public function testPeekNextToken()
    {
        //Peek for next token and then call nexttoken to see same token peeked is getting
        $expression = 'IntIdentifier eq 123';
        $lexer = new ExpressionLexer($expression);
        $token1 = $lexer->peekNextToken();
        $lexer->nextToken();
        $token2 = $lexer->getCurrentToken();
        $this->AssertEquals($token1->Id, $token2->Id);
        $this->AssertEquals($token1->Text, $token2->Text);
        $this->AssertEquals($token1->Position, $token2->Position);
    }

    public function testWhiteSpace()
    {
        $expression = '     IntIdentifier     eq     123    ';
        $lexer = new ExpressionLexer($expression);
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Text, 'IntIdentifier');
        $this->AssertEquals($token->Position, 5);

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::IDENTIFIER);
        $this->AssertEquals($token->Text, 'eq');
        $this->AssertEquals($token->Position, 23);

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::INTEGER_LITERAL);
        $this->AssertEquals($token->Text, '123');
        $this->AssertEquals($token->Position, 30);

        $lexer->nextToken();
        $token = $lexer->getCurrentToken();
        $this->AssertEquals($token->Id, ExpressionTokenId::END);
        $this->AssertEquals($token->Position, 37);
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
