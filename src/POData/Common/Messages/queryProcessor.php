<?php

namespace POData\Common\Messages;

trait queryProcessor
{
    /**
     * Message to show error when query prcocessor found
     * invalid value for $format option.
     *
     * @return string The message
     */
    public static function queryProcessorInvalidValueForFormat()
    {
        return 'Invalid $format query option - the only acceptable values are "json" and "atom"';
    }

    /**
     * Message to show error when query processor found odata query option
     * in the request uri which is not applicable for the
     * resource targeted by the resource path.
     *
     * @return string The message
     */
    public static function queryProcessorNoQueryOptionsApplicable()
    {
        return 'Query options $select, $expand, $filter, $orderby, $inlinecount, $skip, $skiptoken and $top are not supported by this request method or cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found $filter option in the
     * request uri but is not applicable for the resource targeted by the
     * resource path.
     *
     * @return string The message
     */
    public static function queryProcessorQueryFilterOptionNotApplicable()
    {
        return 'Query option $filter cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found any $orderby,
     * $inlinecount, $skip or $top options in the request uri but is not
     * applicable for the resource targeted by the resource path.
     *
     * @return string The message
     */
    public static function queryProcessorQuerySetOptionsNotApplicable()
    {
        return 'Query options $orderby, $inlinecount, $skip and $top cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found skiptoken option in the
     * request uri but is not applicable for the resource targeted by the
     * resource path.
     *
     * @return string The message
     */
    public static function queryProcessorSkipTokenNotAllowed()
    {
        return 'Query option $skiptoken cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found $expand option in the
     * request uri but is not applicable for the resource targeted by the
     * resource path.
     *
     * @return string The message
     */
    public static function queryProcessorQueryExpandOptionNotApplicable()
    {
        return 'Query option $expand cannot be applied to the requested resource.';
    }

    /**
     * Message to show error when query processor found usage of $inline count
     * option for a resource path ending with $count.
     *
     * @return string The message
     */
    public static function queryProcessorInlineCountWithValueCount()
    {
        return '$inlinecount cannot be applied to the resource segment $count';
    }

    /**
     * Message to show error when value of $inlinecount option found invalid.
     *
     * @return string The message
     */
    public static function queryProcessorInvalidInlineCountOptionError()
    {
        return 'Unknown $inlinecount option, only "allpages" and "none" are supported';
    }

    /**
     * Format a message to show error when query processor found invalid
     * value for a query option.
     *
     * @param string $argName  The name of the argument
     * @param string $argValue The value of the argument
     *
     * @return string The formatted message
     */
    public static function queryProcessorIncorrectArgumentFormat($argName, $argValue)
    {
        return "Incorrect format for $argName argument '$argValue'";
    }

    /**
     * Format a message to show error when query processor found $skiptoken
     * in the request uri targetting to a resource for which paging is not
     * enabled.
     *
     * @param string $resourceSetName The name of the resource set
     *
     * @return string The formatted message
     */
    public static function queryProcessorSkipTokenCannotBeAppliedForNonPagedResourceSet($resourceSetName)
    {
        return "\$skiptoken cannot be applied to the resource set '$resourceSetName', since paging is not enabled for this resource set";
    }

    /**
     * Format a message to show error when query processor found $select
     * or $expand which cannot be applied to resource targeted by the
     * request uri.
     *
     * @param string $queryItem Query item
     *
     * @return string The formatted message
     */
    public static function queryProcessorSelectOrExpandOptionNotApplicable($queryItem)
    {
        return "Query option $queryItem cannot be applied to the requested resource";
    }
}
