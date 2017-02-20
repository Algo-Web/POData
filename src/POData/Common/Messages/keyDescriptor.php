<?php

namespace POData\Common\Messages;

trait keyDescriptor
{
    /**
     * Format a message to show error when actual number of key values given
     * in the key predicate is not matching with the expected number of key values.
     *
     * @param string $segment       The segment with key predicate in question
     * @param int    $expectedCount The expected number of key values
     * @param int    $actualCount   The actual number of key values
     *
     * @return string The formatted message
     */
    public static function keyDescriptorKeyCountNotMatching($segment, $expectedCount, $actualCount)
    {
        return "The predicate in the segment '$segment' expect $expectedCount keys but $actualCount provided";
    }

    /**
     * Format a message to show error when a required key is
     * missing from key predicate of a segment.
     *
     * @param string $segment      The segment with key predicate in question
     * @param string $expectedKeys The keys expected by the predicate
     *
     * @return string The formatted message
     */
    public static function keyDescriptorMissingKeys($segment, $expectedKeys)
    {
        return "Missing keys in key predicate for the segment '$segment'. The key predicate expect the keys '$expectedKeys'";
    }

    /**
     * Format a message to show error when type of a key given in the
     * predicate with named key values does not compatible with the expected type.
     *
     * @param string $segment      The segment with key predicate in question
     * @param string $keyProperty  Name of the key in question
     * @param string $expectedType Expected type of the key
     * @param string $actualType   Actual type of the key
     *
     * @return string The formatted message
     */
    public static function keyDescriptorInCompatibleKeyType($segment, $keyProperty, $expectedType, $actualType)
    {
        return "Syntax error in the segment '$segment'. The value of key property '$keyProperty' should be of type " . $expectedType . ', given ' . $actualType;
    }

    /**
     * Format a message to show error when type of a key given in the predicate
     * with positional key values does not compatible with the expected type.
     *
     * @param string $segment      The segment with key predicate in question
     * @param string $keyProperty  The Key property
     * @param int    $position     The position of key
     * @param string $expectedType Expected type of the key
     * @param string $actualType   Actual type of the key
     *
     * @return string The formatted message
     */
    public static function keyDescriptorInCompatibleKeyTypeAtPosition($segment, $keyProperty, $position, $expectedType, $actualType)
    {
        return "Syntax error in the segment '$segment'. The value of key property '$keyProperty' at position $position should be of type " . $expectedType . ', given ' . $actualType;
    }

    /**
     * Format a message to show error when trying to access
     * KeyDescriptor::_validatedNamedValues before
     * invoking KeyDescriptor::validate function.
     *
     * @return string The message
     */
    public static function keyDescriptorValidateNotCalled()
    {
        return 'Invoking KeyDescriptor::getValidatedNamedValues requires KeyDescriptor::validate to be called before';
    }
}
