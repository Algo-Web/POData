<?php

namespace POData\Common\Messages;

trait expressionParser
{
    /**
     * Format message for an operator's incompatible operands types.
     *
     * @param string $operator The operator
     * @param string $str      The operand list separated by comma
     * @param string $pos      Position at which operator with incompatible operands found
     *
     * @return string The formatted message
     */
    public static function expressionParserInCompatibleTypes($operator, $str, $pos)
    {
        return "Operator '$operator' incompatible with operand types $str at position $pos";
    }

    /**
     * Format message for an unsupported null operation.
     *
     * @param string $operator The operator
     * @param int    $pos      Position at which operator with null operands found
     *
     * @return string The formatted message
     */
    public static function expressionParserOperatorNotSupportNull($operator, $pos)
    {
        return "The operator '$operator' at position $pos is not supported for the 'null' literal; only equality checks are supported";
    }

    /**
     * Format message for an unsupported guid operation.
     *
     * @param string $operator The operator
     * @param int    $pos      Position at which operator with guid operands found
     *
     * @return string The formatted message
     */
    public static function expressionParserOperatorNotSupportGuid($operator, $pos)
    {
        return "The operator '$operator' at position $pos is not supported for the Edm.Guid ; only equality checks are supported";
    }

    /**
     * Format message for an unsupported binary operation.
     *
     * @param string $operator The operator
     * @param int    $pos      Position at which operator with binary operands found
     *
     * @return string The formatted message
     */
    public static function expressionParserOperatorNotSupportBinary($operator, $pos)
    {
        return "The operator '$operator' at position $pos is not supported for the Edm.Binary ; only equality checks are supported";
    }

    /**
     * Format message for an unrecognized literal.
     *
     * @param string $type    The expected literal type
     * @param string $literal The malformed literal
     * @param int    $pos     Position at which literal found
     *
     * @return string The formatted message
     */
    public static function expressionParserUnrecognizedLiteral($type, $literal, $pos)
    {
        return "Unrecognized '$type' literal '$literal' in position '$pos'.";
    }

    /**
     * Format message for an unknown function-call.
     *
     * @param string $str The unknown function name
     * @param int    $pos Position at which unknown function-call found
     *
     * @return string The formatted message
     */
    public static function expressionParserUnknownFunction($str, $pos)
    {
        return "Unknown function '$str' at position $pos";
    }

    /**
     * Format message for non-boolean filter expression.
     *
     * @return string The formatted message
     */
    public static function expressionParser2BooleanRequired()
    {
        return 'Expression of type \'System.Boolean\' expected at position 0';
    }

    /**
     * Format message for unexpected expression.
     *
     * @param string $expressionClassName Name  of the unexpected expression
     *
     * @return string The formatted message
     */
    public static function expressionParser2UnexpectedExpression($expressionClassName)
    {
        return "Unexpected expression of type \'$expressionClassName\' found";
    }

    /**
     * Format a message to show error when expression contains sub-property access of non-primitive property.
     *
     * @return string The message
     */
    public static function expressionParser2NonPrimitivePropertyNotAllowed()
    {
        return 'This data service does not support non-primitive types in the expression';
    }

    /**
     * Format a message to show error when resourceset reference is
     * used in $filter query option.
     *
     * @param string $property       The resourceset property used in query
     * @param string $parentProperty The parent resource of property
     * @param int    $pos            Postion at which resource set has been used
     *
     * @return string The formatted message
     */
    public static function expressionParserEntityCollectionNotAllowedInFilter($property, $parentProperty, $pos)
    {
        return "The '$property' is an entity collection property of '$parentProperty' (position: $pos), which cannot be used in \$filter query option";
    }
}
