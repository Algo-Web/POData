<?php

namespace POData\Common\Messages;

trait expressionLexer
{
    /**
     * Format message for unterminated string literal error.
     *
     * @param int    $pos  Position of unterminated string literal in the text
     * @param string $text The text with unterminated string literal
     *
     * @return string The formatted message
     */
    public static function expressionLexerUnterminatedStringLiteral($pos, $text)
    {
        return 'Unterminated string literal at position ' . $pos . ' in ' . $text;
    }

    /**
     * Format message for digit expected error.
     *
     * @param int $pos Position at which digit is expected
     *
     * @return string The formatted message
     */
    public static function expressionLexerDigitExpected($pos)
    {
        return 'Digit expected at position ' . $pos;
    }

    /**
     * Format message for syntax error.
     *
     * @param int $pos Position at which syntax error found
     *
     * @return string The formatted message
     */
    public static function expressionLexerSyntaxError($pos)
    {
        return 'Syntax Error at position ' . $pos;
    }

    /**
     * Format message for invalid character error.
     *
     * @param string $ch  The invalid character found
     * @param int    $pos Position at which invalid character found
     *
     * @return string The formatted message
     */
    public static function expressionLexerInvalidCharacter($ch, $pos)
    {
        return "Invalid character '$ch' at position $pos";
    }

    /**
     * Format message for not applicable function error.
     *
     * @param string $functionName The name of the function called
     * @param string $protoTypes   Prototype of the functions considered
     * @param int    $position     Position at which function-call found
     *
     * @return string The formatted message
     */
    public static function expressionLexerNoApplicableFunctionsFound(
        $functionName,
        $protoTypes,
        $position
    ) {
        return "No applicable function found for '$functionName' at position $position with the specified arguments. The functions considered are: $protoTypes";
    }

    /**
     * Format message for property not found error.
     *
     * @param string $property The name of the property
     * @param string $type     The parent type in which property searched for
     * @param int    $position Position at which property mentioned
     *
     * @return string The formatted message
     */
    public static function expressionLexerNoPropertyInType($property, $type, $position)
    {
        return "No property '$property' exists in type '$type' at position $position";
    }
}
