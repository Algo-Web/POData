<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 13/05/20
 * Time: 6:36 PM.
 */
namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionLexer;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser;

class DummyExpressionParser extends ExpressionParser
{
    /**
     * @param ExpressionLexer $lexer
     */
    public function setLexer(ExpressionLexer $lexer)
    {
        $this->lexer = $lexer;
    }
}
