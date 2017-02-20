<?php

namespace POData\Common\Messages;

trait configuration
{
    /**
     * Error message to show when both page size and
     * result collection size are specified.
     *
     * @return string The message
     */
    public static function configurationMaxResultAndPageSizeMutuallyExclusive()
    {
        return 'Specification of \'entity set page size\' is mutually exclusive with the specification of \'maximum result per collection\' in configuration';
    }

    /**
     * Format a message to show error when configuration expects a
     * name as resource set name but it is not.
     *
     * @param string $name The unresolved name
     *
     * @return string The formatted message
     */
    public static function configurationResourceSetNameNotFound($name)
    {
        return "The given name '$name' was not found in the entity sets";
    }

    /**
     * Format a message to show error when a function argument expected to
     * EntitySetRights enum value but it is not.
     *
     * @param string $argument     The argument name
     * @param string $functionName The function name
     *
     * @return string The formatted message
     */
    public static function configurationRightsAreNotInRange($argument, $functionName)
    {
        return "The argument '$argument' of '$functionName' should be EntitySetRights enum value";
    }

    /**
     * A message to show error when service developer disabled count request and
     * client requested for count.
     *
     * @return string The message
     */
    public static function configurationCountNotAccepted()
    {
        return 'The ability of the data service to return row count information is disabled. To enable this functionality, set the ServiceConfiguration.AcceptCountRequests property to true.';
    }

    /**
     * Message to show error when query processor found $select clause but which is
     * disabled by the service developer.
     *
     * @return string The message
     */
    public static function configurationProjectionsNotAccepted()
    {
        return 'The ability to use the $select query option to define a projection in a data service query is disabled. To enable this functionality, call ServiceConfiguration::setAcceptProjectionRequests method with argument as true.';
    }
}
