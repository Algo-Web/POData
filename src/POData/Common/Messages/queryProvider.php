<?php

namespace POData\Common\Messages;

use POData\Providers\Query\QueryType;

trait queryProvider
{
    /**
     * @param string $methodName method name
     *
     * @return string The message
     */
    public static function queryProviderReturnsNonQueryResult($methodName)
    {
        return "The implementation of the method $methodName must return a QueryResult instance.";
    }

    /**
     * @param string    $methodName method name
     * @param QueryType $queryType
     *
     * @return string The message
     */
    public static function queryProviderResultCountMissing($methodName, QueryType $queryType)
    {
        return "The implementation of the method $methodName must return a QueryResult instance with a count for queries of type $queryType.";
    }

    /**
     * @param string    $methodName method name
     * @param QueryType $queryType
     *
     * @return string The message
     */
    public static function queryProviderResultsMissing($methodName, QueryType $queryType)
    {
        return "The implementation of the method $methodName must return a QueryResult instance with an array of results for queries of type $queryType.";
    }
}
