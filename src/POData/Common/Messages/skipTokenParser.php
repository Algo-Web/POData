<?php

namespace POData\Common\Messages;

trait skipTokenParser
{
    /**
     * Format a message to show error when skiptoken parser fails
     * to parse due to syntax error.
     *
     * @param string $skipToken Skip token
     *
     * @return string The formatted message
     */
    public static function skipTokenParserSyntaxError($skipToken)
    {
        return "Bad Request - Error in the syntax of skiptoken '$skipToken'";
    }

    /**
     * Message to show error when orderByInfo argument to SkipTokenParser is
     * non null and not an instance of OrderByInfo.
     *
     * @return string The message
     */
    public static function skipTokenParserUnexpectedTypeOfOrderByInfoArg()
    {
        return 'The argument orderByInfo should be either null ot instance of OrderByInfo class';
    }

    /**
     * Format a message to show error when number of keys in the
     * skiptoken does not matches with the number of keys required for ordering.
     *
     * @param int    $skipTokenValuesCount Number of keys in the skiptoken
     * @param string $skipToken            The skiptoken as string
     * @param int    $expectedCount        Expected number of skiptoken keys
     *
     * @return string The formatted message
     */
    public static function skipTokenParserSkipTokenNotMatchingOrdering($skipTokenValuesCount, $skipToken, $expectedCount)
    {
        return "The number of keys '$skipTokenValuesCount' in skip token with value '$skipToken' did not match the number of ordering constraints '$expectedCount' for the resource type.";
    }

    /**
     * Format a message to show error when skiptoken parser
     * found null value for key.
     *
     * @param string $skipToken The skiptoken as string
     *
     * @return string The formatted message
     */
    public static function skipTokenParserNullNotAllowedForKeys($skipToken)
    {
        return "The skiptoken value $skipToken contain null value for key";
    }

    /**
     * Format a message to show error when skiptoken parser found values in
     * skiptoken which is not compatible with the
     * type of corresponding orderby constraint.
     *
     * @param string $skipToken                   Skip token
     * @param string $expectedTypeName            Expected type name
     * @param int    $position                    Position
     * @param string $typeProvidedInSkipTokenName The type provided in
     *                                            skip token name
     *
     * @return string The formatted message
     */
    public static function skipTokenParserInCompatibleTypeAtPosition($skipToken, $expectedTypeName, $position, $typeProvidedInSkipTokenName)
    {
        return "The skiptoken value '$skipToken' contain a value of type '$typeProvidedInSkipTokenName' at position $position which is not compatible with the type '$expectedTypeName' of corresponding orderby constraint.";
    }
}
